<?php
declare(strict_types=1);

/**
 * The two questions an error page leaves an administrator with, answered
 * under Admin → System: is the website running the code we think it is, and
 * what went wrong behind this reference?
 *
 * On shared hosting neither is in view otherwise. A fix merged on GitHub
 * reaches the site only when the host's git deploy runs, and until it does
 * the old error goes on appearing, indistinguishable from a fix that did not
 * work. And the server's own error log, where the reference was written, is
 * not something the office can open.
 */

require_once __DIR__ . '/db.php';

/**
 * The commit the website was deployed from, read from the .git folder the
 * host's git deploy keeps in public_html (.htaccess keeps it off the web).
 * Null when this copy was not deployed with git, or the folder cannot be read.
 *
 * @return array{commit:string, branch:?string, updated:?int, url:?string}|null
 */
function site_version(): ?array
{
    $git  = dirname(__DIR__, 2) . '/.git';
    $head = @file_get_contents($git . '/HEAD');
    if ($head === false) {
        return null;
    }
    $head    = trim($head);
    $commit  = $head;
    $branch  = null;
    $changed = $git . '/HEAD';
    if (str_starts_with($head, 'ref: ')) {
        $ref     = substr($head, 5);
        $branch  = preg_replace('#^refs/heads/#', '', $ref);
        $changed = $git . '/' . $ref;
        $commit  = trim((string) @file_get_contents($changed));
        if ($commit === '') {
            // A fresh clone keeps its branches in packed-refs instead.
            $changed = $git . '/packed-refs';
            foreach (@file($changed) ?: [] as $line) {
                $parts = explode(' ', trim($line));
                if (count($parts) === 2 && $parts[1] === $ref) {
                    $commit = $parts[0];
                    break;
                }
            }
        }
    }
    if (!preg_match('/^[0-9a-f]{40}$/', $commit)) {
        return null;
    }

    // A link to the commit on GitHub, built from the owner and name alone: the
    // remote URL can carry a deploy token, which must never reach a page.
    $url    = null;
    $config = (string) @file_get_contents($git . '/config');
    if (preg_match('#github\.com[:/]([\w.-]+)/([\w.-]+?)(?:\.git)?\s*$#m', $config, $m)) {
        $url = 'https://github.com/' . $m[1] . '/' . $m[2] . '/commit/' . $commit;
    }

    $updated = @filemtime($changed);
    return [
        'commit'  => $commit,
        'branch'  => $branch,
        'updated' => $updated === false ? null : $updated,
        'url'     => $url,
    ];
}

/**
 * Lines of the private error log, oldest first: the previous file, then the
 * current one. Null when there is no private log (portal_error_log_file()).
 *
 * @return list<string>|null
 */
function error_log_lines(): ?array
{
    $file = portal_error_log_file();
    if ($file === null) {
        return null;
    }
    $lines = [];
    foreach ([$file . '.1', $file] as $f) {
        foreach (@file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $lines[] = $line;
        }
    }
    return $lines;
}

/**
 * Every logged line carrying a reference from an error page, newest first.
 * A reference is six hexadecimal characters; anything else finds nothing.
 *
 * @return list<string>
 */
function find_error_reference(string $ref): array
{
    $ref = strtoupper(trim($ref));
    if (!preg_match('/^[0-9A-F]{6}$/', $ref)) {
        return [];
    }
    $needle = '[' . $ref . ']';
    $hits   = array_filter(error_log_lines() ?? [], fn(string $l): bool => str_contains($l, $needle));
    return array_reverse(array_values($hits));
}

/**
 * The newest few logged errors, newest first.
 *
 * @return list<string>
 */
function recent_errors(int $count = 10): array
{
    return array_reverse(array_slice(error_log_lines() ?? [], -$count));
}
