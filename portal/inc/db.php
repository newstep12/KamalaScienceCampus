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
            // This used to redirect to portal/install.php. The installer has
            // since been deleted and .htaccess 404s that path, so the redirect
            // presented a missing configuration as a missing page — every
            // portal address, sign-in included, answering "Not Found" with
            // nothing to say why. Name the real fault instead: config.php is
            // git-ignored, so it is absent from any fresh checkout of this
            // repository and has to be restored on the server.
            error_log('Portal configuration missing: ' . $path);
            if (!headers_sent()) {
                http_response_code(503);
            }
            exit('The portal is not set up on this server yet. Please contact the campus administrator.');
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
