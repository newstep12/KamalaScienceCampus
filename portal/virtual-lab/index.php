<?php
/* Kamala Virtual Physics Lab: entry point. Lets in B.Sc. 2nd and 3rd year students, administrators and the assigned lecturers. */
define('VPL', 1);
require __DIR__ . '/inc/lib.php';

$u = vpl_require_user();
$role = vlab_role($u);

/* --- allowed: serve the laboratory with the user's details --- */
// The roll number is left for the student to write on the record: the portal
// keeps a TU symbol number, which is not the class roll number the lab asks for.
$user = ['id' => (string)$u['id'], 'name' => vpl_name($u), 'roll' => '', 'role' => $role, 'label' => vpl_label($u)];
$cfg = ['portal' => home_for($u), 'csrf' => csrf_token(), 'submit' => 'submit.php', 'logout' => portal_url('/logout.php')];
if ($role === 'lecturer') $cfg['lecturer'] = 'lecturer.php';
$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
$VPL_INJECT = '<script>window.VPL_USER=' . json_encode($user, $flags) . ';window.VPL_CFG=' . json_encode($cfg, $flags) . ';</script>';

vpl_headers();
header('Content-Type: text/html; charset=utf-8');
// The lab is one 640 KB page, under 200 KB compressed, and a class opening it
// together shares the campus's one connection. So it is sent compressed even
// where the server would not compress PHP's output itself; ob_gzhandler sends
// it plain to a browser that does not ask for gzip.
if (!ini_get('zlib.output_compression') && function_exists('ob_gzhandler')) ob_start('ob_gzhandler');
define('VPL_LAB', 1);
require __DIR__ . '/inc/lab.php';
