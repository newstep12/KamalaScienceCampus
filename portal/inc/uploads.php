<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config-path.php';

/** Extensions teachers may upload, mapped from the real MIME type we detect. */
const ALLOWED_UPLOADS = [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'text/plain' => 'txt',
    'text/csv' => 'csv',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'application/zip' => 'zip',
];

/**
 * Validate and store one uploaded file under uploads/<subdir>/.
 *
 * On failure 'error' is 'size' (bigger than the server allows), 'type' (not
 * an accepted format) or 'upload' (anything else).
 *
 * @return array{ok:bool, error?:string, path?:string, name?:string, size?:int}
 */
function store_upload(array $file, string $subdir): array
{
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_OK) {
        // PHP's own size refusals are reported as such rather than as a
        // generic failure: "the file is too large" tells the person what to
        // do next, "the upload failed" does not.
        $tooBig = $err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE;
        return ['ok' => false, 'error' => $tooBig ? 'size' : 'upload'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'upload'];
    }
    if ($file['size'] <= 0) {
        return ['ok' => false, 'error' => 'upload'];
    }
    if ($file['size'] > upload_limit_bytes()) {
        return ['ok' => false, 'error' => 'size'];
    }

    // Trust the sniffed type, never the browser-supplied one or the extension.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string) $finfo->file($file['tmp_name']);
    if (!isset(ALLOWED_UPLOADS[$mime])) {
        return ['ok' => false, 'error' => 'type'];
    }
    $ext = ALLOWED_UPLOADS[$mime];

    $dir = upload_dir($subdir);
    if ($dir === null) {
        return ['ok' => false, 'error' => 'upload'];
    }

    // Random stored name: the original never reaches the filesystem.
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) {
        return ['ok' => false, 'error' => 'upload'];
    }
    @chmod($dir . '/' . $stored, 0644);

    $original = (string) ($file['name'] ?? $stored);
    $original = preg_replace('/[^\p{L}\p{N}. \-_]+/u', '_', $original) ?? $stored;

    return [
        'ok'   => true,
        'path' => $subdir . '/' . $stored,
        'name' => mb_substr($original, 0, 190),
        'size' => (int) $file['size'],
    ];
}

function delete_upload(?string $relPath): void
{
    if (!$relPath) {
        return;
    }
    $seen = &upload_lookups();
    unset($seen[$relPath]);
    // Wherever it is kept — see uploads_roots().
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
 * The error string key store_upload() returned, as a translated message.
 * The size case names the actual limit: "too large" without a figure leaves
 * whoever is uploading guessing at how much smaller the file has to be.
 */
function upload_error_message(string $error): string
{
    return match ($error) {
        'size'  => t('err_file_too_large', format_bytes(upload_limit_bytes())),
        'type'  => t('err_file_type'),
        default => t('err_upload'),
    };
}

/* ---------------------------------------------------------------- serving -- */

/**
 * Types a browser can render in place. A notice is meant to be read, not
 * collected, so these are sent with Content-Disposition: inline and the real
 * type — with nosniff alongside, the browser shows the PDF in its own viewer
 * instead of dropping a file in the Downloads folder.
 *
 * Deliberately narrower than ALLOWED_UPLOADS: nothing here can carry script.
 * SVG and HTML are absent for that reason and must stay absent.
 */
const INLINE_TYPES = [
    'application/pdf' => 'pdf',
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/gif'       => 'gif',
    'image/webp'      => 'webp',
];

/**
 * Whether a stored upload is something the page can show in place.
 *
 * Reads the stored extension rather than the original filename: store_upload()
 * derives it from the type sniffed out of the file, so it describes what the
 * file really is and not what it was called when it arrived.
 *
 * @return 'pdf'|'image'|'file'
 */
function upload_kind(?string $relPath): string
{
    $ext = strtolower(pathinfo((string) $relPath, PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        return 'pdf';
    }
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? 'image' : 'file';
}

/**
 * Where uploads are kept.
 *
 * Outside the website on the live server — ksc-portal-uploads/ beside the
 * settings file, in the directory above public_html (portal_outside_dir()).
 * They used to be kept in portal/uploads/, inside public_html, where a
 * deploy — which rewrites public_html from the repository — deletes them: the
 * +2 portal lost every photograph and signature that way, although its
 * database still named them all. Nothing the repository does not hold is safe
 * inside public_html.
 *
 * Every folder that exists is read, the outside one first; new files are
 * written to the first that can be written to. Files still in the old place
 * are all moved out the first time this runs with the outside folder in place
 * (uploads_move_out()) — called from config(), so on the very first request
 * after an update, whatever the page.
 *
 * The outside folder is found even on a request whose document root is not
 * public_html (an alias, a preview address), as long as it exists; it is only
 * ever created from the site itself. Where there is no directory above the
 * website to use (a test copy under htdocs), uploads stay in
 * portal/uploads/ as before, which .htaccess denies to the web.
 *
 * @return list<string> absolute paths, the outside folder first when there is one
 */
function uploads_roots(): array
{
    static $roots = null;
    if ($roots !== null) {
        return $roots;
    }
    $inside  = uploads_inside_path();
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
        uploads_move_out($inside, $roots[0]);
    }
    return $roots;
}

/** The old folder, inside the website. */
function uploads_inside_path(): string
{
    static $inside = null;
    return $inside ??= (realpath(__DIR__ . '/../uploads') ?: __DIR__ . '/../uploads');
}

/** Where the outside folder is, or would be: beside public_html. Null when there is no such place. */
function uploads_outside_path(): ?string
{
    static $path = false;
    if ($path !== false) {
        return $path;
    }
    $candidate = (portal_outside_dir() ?? dirname(__DIR__, 3)) . '/ksc-portal-uploads';
    // Without a document root that is the site, only an outside folder that
    // already exists is used — never one invented beside some other folder.
    return $path = (portal_outside_dir() !== null || @is_dir($candidate)) ? $candidate : null;
}

/** Where new uploads are written: the first folder that can be written to. */
function uploads_root(): string
{
    $roots = uploads_roots();
    foreach ($roots as $root) {
        if (@is_writable($root)) {
            return $root;
        }
    }
    return $roots[count($roots) - 1];
}

/**
 * True when uploads are at risk from the next deploy: new ones are going into
 * the website folder although there is a folder above it they belong in, or
 * files are left there that could not be moved out. Admin → System says so.
 */
function uploads_at_risk(): bool
{
    if (portal_outside_dir() === null) {
        return false;
    }
    return uploads_root() === uploads_inside_path() || uploads_inside_files() > 0;
}

/** How many uploaded files are still in the old folder inside the website. */
function uploads_inside_files(): int
{
    static $count = null;
    if ($count !== null) {
        return $count;
    }
    $count = 0;
    $inside = uploads_inside_path();
    if (!is_dir($inside) || in_array($inside, array_slice(uploads_roots(), 0, 1), true)) {
        return $count;
    }
    try {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($inside, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && !in_array($file->getFilename(), ['.htaccess', '.gitkeep'], true)) {
                $count++;
            }
        }
    } catch (Throwable $e) {
        // Unreadable: counted as nothing rather than failing the page.
    }
    return $count;
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
 * Move every file from the old uploads folder to the new one, keeping the same
 * relative paths, then remove the folders it leaves empty — so once the move
 * is done, the check on each later request finds nothing but the
 * repository's own .htaccess and .gitkeep and costs next to nothing.
 *
 * A file already present at the destination is left where it is (it is then
 * served from the new place). A file changed in the last minute is left for a
 * later request: it may still be being written by a request that chose this
 * folder before the move. A file that cannot be moved is logged, and
 * uploads_at_risk() then reports it.
 */
function uploads_move_out(string $from, string $to): void
{
    if (!is_dir($from)) {
        return;
    }
    $keep = ['.htaccess', '.gitkeep'];
    // The quick answer, on every request after the move: nothing but the
    // repository's own files at the top of the folder.
    $top = @scandir($from) ?: [];
    if (!array_diff($top, array_merge(['.', '..'], $keep))) {
        return;
    }
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($path);                           // only succeeds when empty
                continue;
            }
            if (in_array($file->getFilename(), $keep, true) || $file->getMTime() > time() - 60) {
                continue;
            }
            $target = $to . substr($path, strlen($from));
            $dir    = dirname($target);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                error_log('Campus portal: could not create ' . $dir . ' to move an upload out of the website folder');
                continue;
            }
            if (!file_exists($target) && !@rename($path, $target)) {
                error_log('Campus portal: could not move ' . $path . ' out of the website folder');
            }
        }
    } catch (Throwable $e) {
        error_log('Campus portal: moving uploads out of the website folder: ' . $e->getMessage());
    }
}

/**
 * What resolve_upload() has already answered in this request. Shared with
 * delete_upload(), which must forget a file it deletes.
 */
function &upload_lookups(): array
{
    static $seen = [];
    return $seen;
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
    if (!$relPath) {
        return null;
    }
    $seen = &upload_lookups();
    if (isset($seen[$relPath])) {
        return $seen[$relPath];
    }
    foreach (uploads_roots() as $root) {
        $full = realpath($root . '/' . $relPath);
        if ($full !== false && is_file($full) && str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
            return $seen[$relPath] = $full;
        }
    }
    // Not remembered: a file that is not there yet may be written later in
    // this same request.
    return null;
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
    $limits = [(int) (config()['max_upload'] ?? 20 * 1024 * 1024)];
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
