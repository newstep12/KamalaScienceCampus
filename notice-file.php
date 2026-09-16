<?php
declare(strict_types=1);
/**
 * Public notice attachments.
 *
 * A notice published on the public board is meant to be read by anyone, and
 * for most notices the PDF *is* the notice. portal/download.php cannot serve
 * these: it calls require_login() first, so a visitor who is not a student
 * gets the sign-in page instead of the file, and uploads/.htaccess denies the
 * file itself. This endpoint fills that gap — the same containment and
 * type-sniffing rules, but for notices that are already public.
 *
 * Only attachments of published notices that apply to every year are served,
 * exactly the set notices.php lists. A notice aimed at one year stays inside
 * the portal, and so does its attachment.
 */
require_once __DIR__ . '/portal/inc/db.php';
require_once __DIR__ . '/portal/inc/uploads.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit('Not found.');
}

try {
    $notice = one(
        'SELECT file_path, file_name FROM notices
          WHERE id = ? AND is_published = 1 AND year_level IS NULL
          LIMIT 1',
        [$id]
    );
} catch (Throwable $e) {
    error_log('Public notice attachment unavailable: ' . $e->getMessage());
    http_response_code(503);
    exit('Temporarily unavailable. Please try again shortly.');
}

if (!$notice || !$notice['file_path']) {
    http_response_code(404);
    exit('Not found.');
}

// Displayed in the browser by default — a notice should open, not land in the
// Downloads folder. ?download=1 is the "save a copy" button on the page.
$inline = !isset($_GET['download']);

// The URL is keyed on the notice, not on the stored file, so replacing an
// attachment reuses it. A few minutes of shared caching keeps a busy notice
// off the database without leaving a replacement stale for long.
serve_upload($notice['file_path'], $notice['file_name'], $inline, 'public, max-age=300');
