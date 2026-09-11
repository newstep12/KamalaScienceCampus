<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/lang.php';

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
    $n = one('SELECT file_path, file_name FROM notices WHERE id = ? AND is_published = 1 LIMIT 1', [$noticeId]);
    stream_or_404($n['file_path'] ?? null, $n['file_name'] ?? null, $root);
}

http_response_code(404);
exit('Not found.');
