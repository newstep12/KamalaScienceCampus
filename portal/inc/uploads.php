<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';

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

    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
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
    $root = realpath(__DIR__ . '/../uploads');
    $full = realpath($root . '/' . $relPath);
    if ($full && str_starts_with($full, $root . DIRECTORY_SEPARATOR) && is_file($full)) {
        @unlink($full);
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

    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
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

function uploads_root(): string
{
    $root = realpath(__DIR__ . '/../uploads');
    return $root === false ? __DIR__ . '/../uploads' : $root;
}

/**
 * The absolute path of a stored upload, or null when it is missing or the
 * relative path tries to climb out of the uploads tree. realpath resolves
 * any ../ first, so the prefix check below cannot be talked around.
 */
function resolve_upload(?string $relPath): ?string
{
    if (!$relPath) {
        return null;
    }
    $root = uploads_root();
    $full = realpath($root . '/' . $relPath);
    if ($full === false || !is_file($full) || !str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
        return null;
    }
    return $full;
}

/**
 * The filename parameters for a Content-Disposition header.
 *
 * Two of them, per RFC 6266: a plain ASCII `filename` that any client
 * understands, and `filename*` carrying the real UTF-8 name. Without the
 * second, a notice saved as "सूचना.pdf" reaches the visitor as a row of
 * underscores — which on a bilingual campus site is most of the point of
 * keeping the original name at all.
 */
function disposition_filename(string $name): string
{
    $name  = str_replace(['"', '\\', "\r", "\n"], '', $name);
    $ascii = preg_replace('/[^\x20-\x7e]+/', '_', $name) ?: 'file';
    $ascii = preg_replace('/[^\w. \-]+/', '_', $ascii) ?: 'file';

    $header = 'filename="' . $ascii . '"';
    if ($name !== $ascii && $name !== '') {
        $header .= "; filename*=UTF-8''" . rawurlencode($name);
    }
    return $header;
}

/**
 * Send a stored upload and stop. $inline asks for it to be displayed rather
 * than downloaded, which is honoured only when the type sniffed from the file
 * itself is one a browser renders safely — the stored name never decides.
 */
function serve_upload(?string $relPath, ?string $downloadName, bool $inline = false, string $cacheControl = 'private, max-age=0, must-revalidate'): void
{
    $full = resolve_upload($relPath);
    if ($full === null) {
        http_response_code(404);
        exit('Not found.');
    }

    $mime   = (string) (new finfo(FILEINFO_MIME_TYPE))->file($full);
    $inline = $inline && isset(INLINE_TYPES[$mime]);
    $name   = $downloadName ?: basename($full);

    header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
    header('Content-Length: ' . filesize($full));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; ' . disposition_filename($name));
    // Without nosniff a browser may second-guess the type we just declared.
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: ' . $cacheControl);
    readfile($full);
    exit;
}

/* ----------------------------------------------------------- size limits -- */

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
