<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config-path.php';

/** Remove one stored upload, wherever it is kept. */
function delete_upload(?string $relPath): void
{
    if (!$relPath) {
        return;
    }
    foreach (uploads_roots() as $root) {
        $full = realpath($root . '/' . $relPath);
        if ($full && str_starts_with($full, $root . DIRECTORY_SEPARATOR) && is_file($full)) {
            @unlink($full);
        }
    }
}

/**
 * Which of the users table's file-bearing columns this install actually has.
 *
 * Only avatar_path is in every install: the working copy and the holder's
 * signature arrived as database updates, and an install that has not run them
 * does without. Read once per request, the way notices_track_auto_translation()
 * reads the notice flags, because the answer cannot change under us.
 */
function user_file_columns(): array
{
    static $columns = null;
    if ($columns === null) {
        $wanted = ['avatar_path', 'avatar_source_path', 'signature_path'];
        // Not caught and degraded to a shorter list. Guessing here is the
        // silent orphaning this whole pair exists to refuse — the signature
        // left behind, nothing logged, the delete reporting success — and it
        // would arrive through the code written to prevent it. A delete that
        // cannot establish what it owns fails instead, having destroyed
        // nothing, because user_file_paths() runs before the row is removed.
        $columns = array_values(array_intersect(
            $wanted,
            array_column(all('SHOW COLUMNS FROM users'), 'Field')
        ));
    }
    return $columns;
}

/**
 * Every upload a user row owns, as paths — read before the row is deleted.
 *
 * Three columns name a file, and a delete that listed them by hand kept only
 * the ones whoever wrote it remembered: the signature arrived after the
 * delete was written, so deleting an account left a scan of a real person's
 * signature in the uploads directory for ever, with no row naming it and
 * nothing to find it by. The list belongs in one place, beside the code that
 * removes what is on it.
 *
 * A row the caller selected columns from rather than SELECT *ing is refused
 * outright. Read with ?? it would be indistinguishable from an account that
 * simply has no files, and the difference is every one of them orphaned —
 * which is the failure this exists to prevent, arriving silently through the
 * thing written to prevent it. A column this install has not got is not that
 * case, and is not an error.
 */
function user_file_paths(array $u): array
{
    $paths = [];
    foreach (user_file_columns() as $column) {
        if (!array_key_exists($column, $u)) {
            throw new InvalidArgumentException(
                'user_file_paths() needs a whole users row; ' . $column . ' was not selected'
            );
        }
        if (!empty($u[$column])) {
            $paths[] = (string) $u[$column];
        }
    }
    return $paths;
}

/** Remove several uploads — what user_file_paths() gathered, once the row has gone. */
function delete_uploads(array $paths): void
{
    foreach ($paths as $path) {
        delete_upload($path);
    }
}

/* -------------------------------------------------------------- pictures -- */

/**
 * Photographs and signature images. A much narrower allowlist than the
 * teaching materials above: these are shown inline by download.php, so
 * anything that a browser could treat as a document must stay out.
 */
const ALLOWED_IMAGES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

const MAX_IMAGE_UPLOAD = 5 * 1024 * 1024;

/** The smallest photograph worth printing on a card. */
const MIN_PHOTO_SIDE = 200;

/**
 * Everything an uploaded image has to satisfy before anything is done with
 * it, in one place: store_image() and store_card_photo() both come through
 * here, so the rules cannot drift apart.
 *
 * $minSide rejects an image too small to print sharply on a card — a 25 mm
 * photo at 300 dpi needs roughly 300 px across.
 *
 * @return array{ok:bool, error?:string, mime?:string, width?:int, height?:int}
 */
function validate_image_upload(array $file, int $minSide = MIN_PHOTO_SIDE): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'upload'];
    }
    if ($file['size'] > MAX_IMAGE_UPLOAD || $file['size'] <= 0) {
        return ['ok' => false, 'error' => 'upload'];
    }

    // Trust the sniffed type, never the browser's or the extension.
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(ALLOWED_IMAGES[$mime])) {
        return ['ok' => false, 'error' => 'type'];
    }

    // getimagesize also fails on a file that merely sniffs as an image, so a
    // crafted payload cannot reach the uploads directory with a .jpg name.
    $size = @getimagesize($file['tmp_name']);
    if (!$size || $size[0] < 1 || $size[1] < 1) {
        return ['ok' => false, 'error' => 'type'];
    }
    if ($size[0] < $minSide || $size[1] < $minSide) {
        return ['ok' => false, 'error' => 'small'];
    }

    return ['ok' => true, 'mime' => $mime, 'width' => $size[0], 'height' => $size[1]];
}

/**
 * Validate and store one uploaded image under uploads/<subdir>/, as it is.
 *
 * @return array{ok:bool, error?:string, path?:string, width?:int, height?:int}
 */
function store_image(array $file, string $subdir, int $minSide = MIN_PHOTO_SIDE): array
{
    $check = validate_image_upload($file, $minSide);
    if (!$check['ok']) {
        return $check;
    }
    $mime = $check['mime'];
    $size = [$check['width'], $check['height']];

    $dir = upload_dir($subdir);
    if ($dir === null) {
        return ['ok' => false, 'error' => 'upload'];
    }

    $stored = bin2hex(random_bytes(16)) . '.' . ALLOWED_IMAGES[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) {
        return ['ok' => false, 'error' => 'upload'];
    }
    @chmod($dir . '/' . $stored, 0644);

    return ['ok' => true, 'path' => $subdir . '/' . $stored, 'width' => $size[0], 'height' => $size[1]];
}

/** True when the form actually carried a file, rather than an empty input. */
function upload_present(?array $file): bool
{
    return $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/** The error string key store_image() returned, as a translated message. */
function image_error_message(string $error): string
{
    return $error === 'small' ? t('err_photo_small') : t('err_photo');
}

/**
 * Where uploads are kept: photographs, their working copies, and signatures.
 *
 * Outside the website on the live server — kssd-plus2-uploads/ beside the
 * settings file, in the directory above public_html (portal_outside_dir()).
 * They used to be kept in plus2/portal/uploads/, and the deploy, which
 * rewrites public_html from the repository, deleted them every time: after an
 * update every card came back without its photograph and with a broken image
 * where each signature had been, although the database still named them all.
 * Nothing the repository does not hold survives a deploy inside public_html.
 *
 * Every folder that exists is read, the outside one first; new files are
 * written to the first that can be written to. Files still in the old place
 * are moved out, all of them, the first time this runs with the outside
 * folder in place (uploads_move_out()) — not one by one as they happen to be
 * asked for, since a file nobody opens before the next deploy would go with it.
 *
 * The outside folder is found even on a request whose document root is not
 * public_html (an alias, a preview address), as long as it exists; it is only
 * ever created from the site itself. Where there is no directory above the
 * website to use (a test copy under htdocs), uploads stay in
 * plus2/portal/uploads/ as before, which .htaccess denies to the web.
 *
 * @return list<string> absolute paths, the outside folder first when there is one
 */
function uploads_roots(): array
{
    static $roots = null;
    if ($roots !== null) {
        return $roots;
    }
    $inside  = realpath(__DIR__ . '/../uploads') ?: __DIR__ . '/../uploads';
    $outside = uploads_outside_path();
    $roots   = [];
    // @: a host whose open_basedir stops at public_html warns on a path
    // above it; that is an answer of "not here", not an error.
    if ($outside !== null && (@is_dir($outside) || (portal_outside_dir() !== null && @mkdir($outside, 0755, true)))) {
        $roots[] = realpath($outside) ?: $outside;
    }
    $roots[] = $inside;
    $roots = array_values(array_unique($roots));
    if (count($roots) > 1 && @is_writable($roots[0])) {
        uploads_move_out($roots[1], $roots[0]);
    }
    return $roots;
}

/** Where the outside folder is, or would be: beside public_html. Null when there is no such place. */
function uploads_outside_path(): ?string
{
    $above = portal_outside_dir() ?? dirname(__DIR__, 4);
    $path  = $above . '/kssd-plus2-uploads';
    // Without a document root that is the site, only an outside folder that
    // already exists is used — never one invented beside some other folder.
    return (portal_outside_dir() !== null || @is_dir($path)) ? $path : null;
}

/** Where new uploads are written: the first folder that can be written to. */
function uploads_root(): string
{
    foreach (uploads_roots() as $root) {
        if (@is_writable($root)) {
            return $root;
        }
    }
    return uploads_roots()[count(uploads_roots()) - 1];
}

/**
 * True when uploads are going into the website folder although there is a
 * folder above it they belong in — the outside folder could not be created
 * or written to — so they will be lost at the next deploy. Admin → System
 * says so.
 */
function uploads_at_risk(): bool
{
    $inside = realpath(__DIR__ . '/../uploads') ?: __DIR__ . '/../uploads';
    return portal_outside_dir() !== null && uploads_root() === $inside;
}

/**
 * A folder under the uploads root, created if need be, or null if it cannot
 * be. The one place new files' folders come from.
 */
function upload_dir(string $subdir): ?string
{
    $dir = uploads_root() . '/' . $subdir;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return null;
    }
    return $dir;
}

/**
 * Move every file from the old uploads folder to the new one, keeping the
 * same relative paths. A file already present at the destination is left
 * where it is (it is then served from the new place, and the old copy is
 * harmless). .htaccess and .gitkeep belong to the repository and stay.
 */
function uploads_move_out(string $from, string $to): void
{
    if (!is_dir($from)) {
        return;
    }
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $file) {
            if (!$file->isFile() || in_array($file->getFilename(), ['.htaccess', '.gitkeep'], true)) {
                continue;
            }
            $rel    = substr($file->getPathname(), strlen($from) + 1);
            $target = $to . '/' . $rel;
            $dir    = dirname($target);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                continue;
            }
            if (!file_exists($target)) {
                @rename($file->getPathname(), $target);
            }
        }
    } catch (Throwable $e) {
        // A folder that cannot be read is left for the next request to try.
        error_log('+2 portal: moving uploads out of the website folder: ' . $e->getMessage());
    }
}

/**
 * The absolute path of a stored upload, or null when it is missing or the
 * relative path tries to climb out of the uploads tree. realpath resolves
 * any ../ first, so the prefix check below cannot be talked around.
 *
 * Looked up once per request: the card, the page header and the portfolio
 * all ask about the same few files.
 */
function resolve_upload(?string $relPath): ?string
{
    static $seen = [];
    if (!$relPath) {
        return null;
    }
    if (array_key_exists($relPath, $seen)) {
        return $seen[$relPath];
    }
    foreach (uploads_roots() as $root) {
        $full = realpath($root . '/' . $relPath);
        if ($full !== false && is_file($full) && str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
            return $seen[$relPath] = $full;
        }
    }
    return $seen[$relPath] = null;
}

/** A php.ini size such as "8M" or "512K" as a byte count. */
function ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $unit = strtolower(substr($value, -1));
    $n    = (int) $value;
    return match ($unit) {
        'g'     => $n * 1024 * 1024 * 1024,
        'm'     => $n * 1024 * 1024,
        'k'     => $n * 1024,
        default => (int) $value,
    };
}

/**
 * The real ceiling on an upload: the smallest of our own limit and the two
 * PHP settings. Shared hosting often caps upload_max_filesize at 2M, well
 * under the 20M config default — a scanned notice easily exceeds it, and the
 * upload then fails before any of our code runs. Showing this figure on the
 * form is what stops that failure from being a mystery.
 */
function upload_limit_bytes(): int
{
    // This portal takes only photographs and signatures, each capped at
    // MAX_IMAGE_UPLOAD; a form carries at most two of them.
    $limits = [2 * MAX_IMAGE_UPLOAD];
    foreach (['upload_max_filesize', 'post_max_size'] as $key) {
        $bytes = ini_bytes((string) ini_get($key));
        if ($bytes > 0) {
            $limits[] = $bytes;
        }
    }
    return min($limits);
}

/**
 * True when the browser sent a body that PHP discarded for exceeding
 * post_max_size. Both $_POST and $_FILES come back empty in that case, so a
 * form looks as though it was never filled in — including its CSRF token.
 */
function post_exceeded_limit(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && empty($_POST)
        && empty($_FILES)
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}
