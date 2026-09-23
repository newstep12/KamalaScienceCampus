<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/lang.php';
require_once __DIR__ . '/inc/uploads.php';
require_once __DIR__ . '/inc/idcard.php';
require_once __DIR__ . '/inc/signatures.php';

/**
 * Photographs and signatures are stored outside the web's reach (uploads/
 * denies direct access) and streamed through here, so only permitted users
 * can see them.
 */

$user = require_login();

/**
 * Photographs and signatures go into an <img>, so they are sent inline
 * rather than as a download — with the type re-sniffed from the file and
 * checked against the image allowlist, so only a real image is ever served
 * inline whatever the stored name says. Who may ask for one is decided by
 * the caller, below.
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
    // and the office's to see, nobody else's.
    if (!can_view_holder_signature($user, $holderSigId)) {
        http_response_code(404);
        exit('Not found.');
    }
    $person = one('SELECT signature_path FROM users WHERE id = ? LIMIT 1', [$holderSigId]);
    inline_image_or_404($person['signature_path'] ?? null);
}

if (isset($_GET['signature'])) {
    // The Principal's signature is handed out only as far as the office has
    // released it: to administrators alone while the scope is 'admin', to
    // anybody signed in once it is 'everyone', and to nobody while it is
    // locked. A 404 rather than a 403, so the reply says nothing about which
    // signatures the school holds.
    $sig = signature_row((int) $_GET['signature']);
    if (!signature_released_to($sig, $user)) {
        http_response_code(404);
        exit('Not found.');
    }
    inline_image_or_404($sig['file_path']);
}

http_response_code(404);
exit('Not found.');
