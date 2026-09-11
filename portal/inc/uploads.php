<?php
declare(strict_types=1);

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
