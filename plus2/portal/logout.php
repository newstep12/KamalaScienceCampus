<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

// Only with this session's own token, from the portal's own Sign out link.
// Without it — an <img> on some other page pointing here — nothing happens.
start_session();
$t = $_GET['t'] ?? '';
if (is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t)) {
    logout();
    header('Location: ' . portal_url('/index.php'));
    exit;
}
header('Location: ' . portal_url(current_user() ? '/student/portfolio.php' : '/index.php'));
exit;
