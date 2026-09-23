<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Key/value settings, so an admin can configure the portal from the UI rather
 * than by editing config.php on the server. Loaded once per request.
 */
function settings(bool $reload = false): array
{
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = [];
        try {
            foreach (all('SELECT k, v FROM settings') as $row) {
                $cache[$row['k']] = $row['v'];
            }
        } catch (PDOException $e) {
            // Table not created yet — fall back to defaults rather than failing.
            // A DatabaseUnavailable is not a PDOException, so an outage still
            // gets the 503 page.
            $cache = [];
        }
    }
    return $cache;
}

function setting(string $key, ?string $default = null): ?string
{
    $v = settings()[$key] ?? null;
    return ($v === null || $v === '') ? $default : $v;
}

function set_setting(string $key, ?string $value): void
{
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
    // The copy loaded at the start of the request is now out of date, and code
    // that saves a setting and then acts on it — saving a translation provider
    // and immediately translating with it — would otherwise use the old value.
    settings(true);
}

function setting_bool(string $key, bool $default = false): bool
{
    $v = setting($key);
    return $v === null ? $default : ($v === '1' || $v === 'true');
}
