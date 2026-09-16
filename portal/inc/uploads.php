<?php
declare(strict_types=1);

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
 * @return array{ok:bool, error?:string, path?:string, name?:string, size?:int}
 */
function store_upload(array $file, string $subdir): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'upload'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'upload'];
    }
    $max = config()['max_upload'] ?? (20 * 1024 * 1024);
    if ($file['size'] > $max || $file['size'] <= 0) {
        return ['ok' => false, 'error' => 'upload'];
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

/**
 * Validate and store one uploaded image under uploads/<subdir>/.
 *
 * $minSide rejects an image too small to print sharply on a card — a 25 mm
 * photo at 300 dpi needs roughly 300 px across.
 *
 * @return array{ok:bool, error?:string, path?:string, width?:int, height?:int}
 */
function store_image(array $file, string $subdir, int $minSide = 200): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'upload'];
    }
    if ($file['size'] > MAX_IMAGE_UPLOAD || $file['size'] <= 0) {
        return ['ok' => false, 'error' => 'upload'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string) $finfo->file($file['tmp_name']);
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
