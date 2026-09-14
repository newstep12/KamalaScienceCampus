<?php
declare(strict_types=1);

/**
 * Database access. One shared PDO connection, exceptions on, emulation off so
 * that prepared statements are genuinely prepared server-side.
 */

// Don't advertise the exact PHP version in every response.
header_remove('X-Powered-By');

/**
 * Thrown by db() when it cannot connect. A public page that must survive a
 * database outage catches it; anywhere else it reaches the handler below.
 */
final class DatabaseUnavailable extends RuntimeException
{
}

// Log the real error; never show it to a visitor.
set_exception_handler(function (Throwable $e) {
    if ($e instanceof DatabaseUnavailable) {
        // db() has already logged the underlying error.
        http_response_code(503);
        exit('The portal is temporarily unavailable. Please try again shortly.');
    }
    error_log('Portal error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    exit('Something went wrong. Please try again.');
});

function config(): array
{
    static $config = null;
    if ($config === null) {
        $path = __DIR__ . '/config.php';
        if (!is_file($path)) {
            // Not set up yet: send the visitor to the installer rather than
            // emitting a half-rendered page.
            if (!headers_sent()) {
                header('Location: /portal/install.php');
            }
            exit;
        }
        $config = require $path;
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config()['db'];
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $c['host'], $c['name'], $c['charset'] ?? 'utf8mb4');
        try {
            $pdo = new PDO($dsn, $c['user'], $c['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            // TEMPORARY (2026-09-14): shows only the MySQL error number and when
            // the config files last changed. Reverted as soon as it has been read.
            if (($_GET['dbdiag'] ?? '') === 'f204752dd915861b') {
                $mtime = fn(string $f): string
                    => is_file($f) ? gmdate('Y-m-d H:i', (int) filemtime($f)) . ' UTC' : 'missing';
                http_response_code(503);
                header('Content-Type: text/plain; charset=utf-8');
                exit(implode("\n", [
                    preg_match('/\] \[(\d+)\]/', $e->getMessage(), $m) ? 'db error ' . $m[1] : 'db error: no number',
                    'config.php modified: ' . $mtime(__DIR__ . '/config.php'),
                    'config.sample.php modified: ' . $mtime(__DIR__ . '/config.sample.php'),
                ]));
            }
            throw new DatabaseUnavailable('Database connection failed', 0, $e);
        }
    }
    return $pdo;
}

/** Run a query with bound parameters and return the statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** First row, or null. */
function one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** All rows. */
function all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** Single scalar value from the first column, or null. */
function scalar(string $sql, array $params = [])
{
    $value = q($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}

function log_activity(?int $actorId, string $action, ?string $subject = null, ?string $detail = null): void
{
    q(
        'INSERT INTO activity_log (actor_id, action, subject, detail) VALUES (?, ?, ?, ?)',
        [$actorId, $action, $subject, $detail]
    );
}
