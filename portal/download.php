<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/lang.php';
require_once __DIR__ . '/inc/uploads.php';
require_once __DIR__ . '/inc/idcard.php';

/**
 * Files are stored outside the document root's reach (uploads/ denies direct
 * access) and streamed through here, so only permitted users can read them.
 */

$user = require_login();
$root = realpath(__DIR__ . '/uploads');

function stream_or_404(?string $relPath, ?string $downloadName, string $root): void
{
    if (!$relPath) {
        http_response_code(404);
        exit('Not found.');
    }
    $full = realpath($root . '/' . $relPath);
    // realpath + prefix check keeps a crafted ../ path inside the uploads tree.
    if ($full === false || !is_file($full) || !str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        exit('Not found.');
    }

    $name = $downloadName ?: basename($full);
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($full));
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^\w. \-]+/u', '_', $name) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($full);
    exit;
}

/**
 * Photographs and the signature go into an <img>, so they are sent inline
 * rather than as a download — with the type re-sniffed from the file and
 * checked against the image allowlist, so only a real image is ever served
 * inline whatever the stored name says.
 */
function inline_image_or_404(?string $relPath, string $root): void
{
    if (!$relPath) {
        http_response_code(404);
        exit('Not found.');
    }
    $full = realpath($root . '/' . $relPath);
    if ($full === false || !is_file($full) || !str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        exit('Not found.');
    }
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($full);
    if (!isset(ALLOWED_IMAGES[$mime])) {
        http_response_code(404);
        exit('Not found.');
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($full));
    header('Content-Disposition: inline');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($full);
    exit;
}

if ($photoId = (int) ($_GET['photo'] ?? 0)) {
    // A 404 rather than a 403 for a photo the viewer may not see: the reply
    // is then the same whether or not that account exists.
    if (!can_view_photo($user, $photoId)) {
        http_response_code(404);
        exit('Not found.');
    }
    $person = one('SELECT avatar_path FROM users WHERE id = ? LIMIT 1', [$photoId]);
    inline_image_or_404($person['avatar_path'] ?? null, $root);
}

if (isset($_GET['signature'])) {
    // The Campus Chief's signature is printed on every card, so anyone who
    // can sign in and print their own card can load it.
    inline_image_or_404(setting('id_card_signature_path'), $root);
}

if ($id = (int) ($_GET['id'] ?? 0)) {
    $m = one(
        'SELECT m.file_path, m.file_name FROM materials m
          WHERE m.id = ? AND m.kind = \'file\'
            AND (? IN (\'admin\')
                 OR EXISTS (SELECT 1 FROM enrolments e WHERE e.course_id = m.course_id AND e.user_id = ?)
                 OR EXISTS (SELECT 1 FROM courses c WHERE c.id = m.course_id AND c.lecturer_id = ?))
          LIMIT 1',
        [$id, $user['role'], $user['id'], $user['id']]
    );
    stream_or_404($m['file_path'] ?? null, $m['file_name'] ?? null, $root);
}

if ($noticeId = (int) ($_GET['notice'] ?? 0)) {
    // Students get only attachments of notices meant for their year or for
    // everyone — the same rule the notice list applies. Staff get all.
    $n = one(
        'SELECT file_path, file_name FROM notices
          WHERE id = ? AND is_published = 1
            AND (year_level IS NULL OR ? <> \'student\' OR year_level = ?)
          LIMIT 1',
        [$noticeId, $user['role'], $user['year_level']]
    );
    stream_or_404($n['file_path'] ?? null, $n['file_name'] ?? null, $root);
}

http_response_code(404);
exit('Not found.');
