<?php
declare(strict_types=1);

/**
 * A name written in Devanagari, for the forms that ask for both — register.php
 * and the portfolio fill the Nepali-name box from the English one as it is
 * typed (plus2/assets/js/name-ne.js), so the holder sees the spelling the card
 * would print and can put it right before it is saved.
 *
 * Open without signing in, because the registration form needs it before
 * there is an account. It reads nothing and stores nothing: the answer is a
 * function of the name sent, nepali_name() in inc/devanagari.php.
 */
require_once __DIR__ . '/inc/devanagari.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$name = $_GET['name'] ?? '';
$name = is_string($name) ? mb_substr(trim($name), 0, 120) : '';
echo json_encode(['ne' => $name === '' ? null : nepali_name($name)], JSON_UNESCAPED_UNICODE);
