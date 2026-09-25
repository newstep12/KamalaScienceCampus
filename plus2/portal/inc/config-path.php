<?php
declare(strict_types=1);

/**
 * Where the +2 portal's settings file — the database name and password — is
 * kept.
 *
 * Outside the website first. The site is deployed from git straight into
 * public_html, and a deploy was seen to rewrite that whole directory: the
 * settings file, deliberately never in the repository, went with it, and the
 * portal answered every visitor with "being set up". The directory above
 * public_html (on Hostinger, domains/<domain>/) is the account's own and no
 * deploy reaches it, and it is not served, so a file there cannot be fetched
 * from the web either.
 *
 * That location is used only when the repository really is the web root —
 * the live site. Tested from a folder under XAMPP's htdocs, the directory
 * above the repository is htdocs itself, which is served, so there the file
 * stays inside inc/ (which .htaccess denies) exactly as before.
 *
 * inc/config.php is still read second, so an install made before this change
 * keeps working until the installer is run again.
 */
function portal_config_paths(): array
{
    $paths   = [];
    $outside = portal_outside_dir();
    if ($outside !== null) {
        $paths['outside'] = $outside . '/kssd-plus2-config.php';
    }
    $paths['inside'] = __DIR__ . '/config.php';
    return $paths;
}

/**
 * The directory above the website — on the live server the account's own
 * domains/<domain>/, which no deploy reaches and the web cannot read — or null
 * when the repository is not the web root (a test copy under XAMPP's htdocs,
 * where the directory above is htdocs and is served).
 *
 * What lives there: this portal's settings file, and everything people upload
 * (see uploads_root()). Both are deliberately never in the repository, and a
 * deploy rewrites public_html from the repository — so anything kept inside it
 * that the repository does not hold is lost on the next deploy.
 */
function portal_outside_dir(): ?string
{
    $site    = dirname(__DIR__, 3);                     // public_html on the live server
    $docroot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    return ($docroot !== false && $docroot === realpath($site)) ? dirname($site) : null;
}

/** The settings file in use, or null when the portal has not been set up. */
function portal_config_file(): ?string
{
    foreach (portal_config_paths() as $path) {
        // @: a host whose open_basedir stops at public_html warns on a path
        // above it; that is an answer of "no", not an error for the visitor.
        if (@is_file($path)) {
            return $path;
        }
    }
    return null;
}
