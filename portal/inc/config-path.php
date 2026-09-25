<?php
declare(strict_types=1);

/**
 * Where the campus portal keeps what the repository must never hold: its
 * settings file (the database name and password) and everything people
 * upload.
 *
 * The site is deployed from git straight into public_html, and a deploy was
 * seen to rewrite what it deploys wholesale: the +2 portal lost its settings
 * file that way, and then every photograph and signature its people had
 * uploaded. This portal kept both in the same kind of place — inc/config.php
 * and uploads/ — so both are now kept in the directory above public_html
 * (on Hostinger, domains/<domain>/), which no deploy reaches and the web
 * cannot read.
 *
 * That location is used only when the repository really is the web root — the
 * live site. Tested from a folder under XAMPP's htdocs, the directory above is
 * htdocs itself, which is served, so there everything stays where it was.
 */

/**
 * The directory above the website, or null when the repository is not the web
 * root.
 */
function portal_outside_dir(): ?string
{
    static $dir = false;
    if ($dir === false) {
        $site    = dirname(__DIR__, 2);                 // public_html on the live server
        $docroot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $dir     = ($docroot !== false && $docroot === realpath($site)) ? dirname($site) : null;
    }
    return $dir;
}

/**
 * Where the settings file is looked for, the outside one first. The outside
 * copy is looked for even on a request whose document root is not the site
 * (an alias, a preview address) — beside the site's own folder, where it was
 * put — so that such a request still finds the settings once a deploy has
 * taken inc/config.php.
 */
function portal_config_paths(): array
{
    $paths   = [];
    $outside = (portal_outside_dir() ?? dirname(__DIR__, 3)) . '/ksc-portal-config.php';
    if (portal_outside_dir() !== null || @is_file($outside)) {
        $paths['outside'] = $outside;
    }
    $paths['inside'] = __DIR__ . '/config.php';
    return $paths;
}

/**
 * The settings file in use, or null when there is none.
 *
 * There is no installer for this portal: inc/config.php was put on the server
 * by hand, and it is still where the settings are edited. What changes is that
 * a copy is kept outside the website — made the first time the file is read on
 * the live site, and made again whenever inc/config.php is newer than it — and
 * the copy is what is used. So a deploy that takes inc/config.php no longer
 * takes the portal down, and an edit to inc/config.php still takes effect.
 *
 * The copy is written whole to a private temporary file first and then renamed
 * into place, so no request ever reads half a file, and nobody but the web
 * server's own account can read the password in it.
 */
function portal_config_file(): ?string
{
    $paths   = portal_config_paths();
    $inside  = $paths['inside'];
    $outside = $paths['outside'] ?? null;
    // @: a host whose open_basedir stops at public_html warns on a path above
    // it; that is an answer of "no", not an error for the visitor.
    $haveOut = $outside !== null && @is_file($outside) && @filesize($outside) > 0;
    $haveIn  = is_file($inside);

    if ($haveIn && $outside !== null && portal_outside_dir() !== null
        && (!$haveOut || @filemtime($inside) > @filemtime($outside))) {
        if (portal_config_copy($inside, $outside)) {
            return $outside;
        }
    }
    if ($haveOut) {
        return $outside;
    }
    return $haveIn ? $inside : null;
}

/** Copy the settings file outside, privately and in one step. */
function portal_config_copy(string $from, string $to): bool
{
    $data = @file_get_contents($from);
    if ($data === false || $data === '') {
        return false;
    }
    $tmp = $to . '.' . bin2hex(random_bytes(6)) . '.tmp';
    $old = umask(0077);                                 // created readable by this account only
    $ok  = @file_put_contents($tmp, $data, LOCK_EX) === strlen($data);
    umask($old);
    if ($ok) {
        @chmod($tmp, 0600);
        $ok = @rename($tmp, $to);
    }
    if (!$ok) {
        @unlink($tmp);
        error_log('Campus portal: could not keep a copy of the settings file at ' . $to);
    }
    return $ok;
}
