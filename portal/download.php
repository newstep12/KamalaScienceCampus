<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/lang.php';
require_once __DIR__ . '/inc/uploads.php';
require_once __DIR__ . '/inc/idcard.php';
require_once __DIR__ . '/inc/signatures.php';

/**
 * Files are stored outside the document root's reach (uploads/ denies direct
 * access) and streamed through here, so only permitted users can read them.
 */

$user = require_login();

/**
 * ?view=1 asks for the file to be shown in the browser rather than saved.
 * serve_upload() grants that only for the handful of types a browser renders
 * safely, sniffed from the file itself, so this flag cannot turn an arbitrary
 * upload into something the browser will execute.
 */
$view = isset($_GET['view']);

/**
 * Photographs and signatures go into an <img>, so they are sent inline
 * rather than as a download — with the type re-sniffed from the file and
 * checked against the image allowlist, so only a real image is ever served
 * inline whatever the stored name says. Who may ask for one is decided by
 * the caller, above.
 */
function inline_image_or_404(?string $relPath): void
{
    $full = resolve_upload($relPath);
    if ($full === null) {
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
    inline_image_or_404($person['avatar_path'] ?? null);
}

if ($holderSigId = (int) ($_GET['holder_signature'] ?? 0)) {
    // The holder's own signature, printed on the back of their card. Theirs
    // and the office's to see, nobody else's — a lecturer who may see a
    // student's photograph may not see their signature. A 404 rather than a
    // 403, as above, so the reply says nothing about whose account exists.
    if (!can_view_holder_signature($user, $holderSigId)) {
        http_response_code(404);
        exit('Not found.');
    }
    $person = one('SELECT signature_path FROM users WHERE id = ? LIMIT 1', [$holderSigId]);
    inline_image_or_404($person['signature_path'] ?? null);
}

if (isset($_GET['signature'])) {
    // A signature is handed out only as far as the office has released it:
    // to administrators alone while the scope is 'admin', to anybody signed
    // in once it is 'everyone', and to nobody at all while it is locked.
    // A 404 rather than a 403, so the reply says nothing about which
    // signatures the campus holds.
    $sig = signature_row((int) $_GET['signature']);
    if (!signature_released_to($sig, $user)) {
        http_response_code(404);
        exit('Not found.');
    }
    inline_image_or_404($sig['file_path']);
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
    serve_upload($m['file_path'] ?? null, $m['file_name'] ?? null, $view);
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
    serve_upload($n['file_path'] ?? null, $n['file_name'] ?? null, $view);
}

http_response_code(404);
exit('Not found.');
