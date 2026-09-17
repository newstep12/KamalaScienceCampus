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

/**
 * The page a visitor gets when something has gone wrong.
 *
 * Deliberately self-contained — no stylesheet, no translation, nothing from
 * the rest of the portal. It has to render when the thing that broke is the
 * database, or the language files, or whatever was half-way through writing
 * the real page.
 */
function portal_error_page(int $code, string $message, ?string $ref = null, string $hint = ''): void
{
    // Not `never`: that return type is PHP 8.1, this is the file every single
    // page requires, and nothing else in the portal needs 8.1. A parse error
    // here on an 8.0 host would take the whole site down — somewhere this
    // handler could never report it.

    // Whatever the half-written page already produced goes in the bin, or the
    // error document is appended to it and the browser gets two of everything.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        // A failure part-way through serving a file leaves these behind: a
        // stale Content-Length makes the browser wait for megabytes that never
        // arrive, and a stale Content-Disposition saves this page into the
        // Downloads folder under that file's name.
        header_remove('Content-Length');
        header_remove('Content-Disposition');
    }
    $esc = fn(string $t): string => htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">',
         '<meta name="viewport" content="width=device-width, initial-scale=1">',
         '<title>', $esc($message), '</title><style>',
         // Every selector is scoped. Appended to a page that was half-written
         // when it threw, a bare body{} or p{} would re-lay-out and recolour
         // everything already on screen.
         '.ksc-err{max-width:34rem;margin:9vh auto;padding:30px 32px;background:#fff;',
         'border:1px solid #e2e8ef;border-radius:14px;color:#16212e;',
         'font:16px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;',
         'box-shadow:0 1px 2px rgba(11,37,69,.06),0 10px 30px rgba(11,37,69,.07)}',
         '.ksc-err h1{margin:0 0 .5em;font-size:1.3rem;color:#0b2545;font-weight:700}',
         '.ksc-err p{margin:0 0 1em;color:#4d5b6b}',
         '.ksc-err code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:1.05rem;',
         'font-weight:700;letter-spacing:.06em;background:#eef4f8;border:1px solid #e2e8ef;',
         'border-radius:7px;padding:5px 11px;color:#16212e}',
         '.ksc-err .r{margin-top:1.4em;padding-top:1.1em;border-top:1px solid #e2e8ef;font-size:.9rem}',
         '</style></head><body><div class="ksc-err">',
         '<h1>', $esc($message), '</h1>';
    if ($hint !== '') {
        echo '<p>', $esc($hint), '</p>';
    }
    echo '<p>Please try again. If it keeps happening, tell the campus office.</p>';
    if ($ref !== null) {
        echo '<p class="r">Quote this reference: <code>', $esc($ref), '</code><br>',
             'It is written to the server\'s error log beside what went wrong.</p>';
    }
    echo '</div></body></html>';
    exit;
}

/**
 * A reference for one failure: short enough to read down a phone, long enough
 * not to collide within a day's log.
 *
 * random_bytes() can throw where the system CSPRNG is out of reach, which on
 * locked-down shared hosting is a real possibility — and a throw raised inside
 * an exception handler is fatal and unrecoverable, so it would turn every
 * error into a blank white page. This only has to be unique enough to find a
 * log line by; it is not a secret.
 */
function portal_error_reference(): string
{
    try {
        return strtoupper(bin2hex(random_bytes(3)));
    } catch (Throwable $e) {
        return strtoupper(substr(md5(uniqid('', true)), 0, 6));
    }
}

// Log the real error; never show it to a visitor.
set_exception_handler(function (Throwable $e) {
    // A short reference, printed on the page and written into the log line.
    // Without one, "Something went wrong" is the whole of what anybody can
    // report, and the line in the host's error log that would explain it
    // cannot be found again among a day's worth of others. A database outage
    // is the likeliest failure on shared hosting, so it gets one too.
    $ref = portal_error_reference();

    if ($e instanceof DatabaseUnavailable) {
        error_log('Portal error [' . $ref . ']: the database is unreachable');
        portal_error_page(503, 'The portal is temporarily unavailable.', $ref);
    }
    error_log(sprintf(
        'Portal error [%s]: %s: %s @ %s:%d',
        $ref, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()
    ));

    // A missing table or column means one thing on this portal: a deploy has
    // landed and the database update has not been run yet. That is one click
    // for an administrator, so it is worth naming rather than making somebody
    // read a log to find out.
    $schema = $e instanceof PDOException
        && preg_match('/Base table or view not found|Unknown column|no such (table|column)|doesn\'t exist/i', $e->getMessage()) === 1;

    portal_error_page(
        500,
        'Something went wrong.',
        $ref,
        // Hardcoded in both languages rather than translated: this page has to
        // render when the language files are themselves what broke, so it
        // cannot call t() — and a Nepali reader should not meet a wall of
        // English on the one page that tells them what to do.
        $schema
            ? 'The database is missing something this page needs, which usually means the '
              . 'portal has been updated and the database update has not been run yet. An '
              . 'administrator can do it under System → Run database updates. '
              . '(डाटाबेस अद्यावधिक गर्न बाँकी हुन सक्छ — प्रशासकले सिस्टम → डाटाबेस अद्यावधिक चलाउनुहोस्।)'
            : ''
    );
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
            $ref = portal_error_reference();
            error_log('Portal error [' . $ref . ']: configuration missing at ' . $path);
            portal_error_page(
                503,
                'The portal is not set up on this server yet.',
                $ref,
                'portal/inc/config.php is missing. It holds the database password, is deliberately '
                . 'not kept in the repository, and has to be restored on the server.'
            );
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
