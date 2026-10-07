<?php
/*
 * Kamala Virtual Physics Lab, inside the B.Sc. portal: what index.php,
 * submit.php and lecturer.php share.
 *
 * The lab arrived as a stand-alone package with its own settings file, its
 * own reading of the PHP session and a data/ folder of records. Here all three
 * belong to the portal instead: the sign-in is the portal's, who may enter is
 * vlab_role() in portal/inc/virtual-lab.php, and records are rows of
 * vlab_records. A data/ folder inside public_html would be in the way of the
 * next deploy, which is how the +2 portal lost its uploads.
 */
if (!defined('VPL')) { http_response_code(403); exit; }

require_once __DIR__ . '/../../inc/layout.php';
require_once __DIR__ . '/../../inc/virtual-lab.php';

// Times are read from the database as UNIX_TIMESTAMP(), so this shows them in
// Nepal time whatever zone the server keeps.
date_default_timezone_set('Asia/Kathmandu');

/* Largest record a student may submit, in kilobytes. */
const VPL_MAX_RECORD_KB = 400;

/* The marking scheme: field => [label, out of]. */
const VPL_MARKS = [
    'record'     => ['Record file', 20],
    'experiment' => ['Experiment', 50],
    'error'      => ['Error analysis', 10],
    'viva'       => ['Viva', 20],
];

function esub($s) { return preg_replace('/\\b([A-Za-z]{1,3})_([A-Za-z0-9]{1,6})\\b/', '$1<sub>$2</sub>', e((string)$s)); }
function vpl_valid_exp($x) { return is_string($x) && preg_match('/^exp([1-9]|1[0-9]|2[0-5])$/', $x); }
function vpl_exp_no($x) { return (int)substr($x, 3); }

/* The name the lab writes on a record: the one in Latin script, the lab being in English. */
function vpl_name(array $u) { $n = name_by_script($u); return $n['latin'] ?? $n['deva'] ?? (string)$u['full_name']; }

/* What the lab's left column says under the name. */
function vpl_label(array $u) {
    if ($u['role'] === ROLE_ADMIN) return 'Administrator';
    if ($u['role'] === ROLE_LECTURER) return 'Lecturer';
    $y = (int)($u['year_level'] ?? 0);
    return 'B.Sc. ' . ([1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'][$y] ?? (string)$y) . ' year';
}

/*
 * The visitor, if this account may use the lab. Sends somebody who is not
 * signed in to the portal's sign-in (which brings them back here), holds a
 * temporary password on the change-password page as every portal page does,
 * and tells anyone else plainly why the lab is not theirs.
 */
function vpl_require_user() {
    $u = require_login();
    if (vlab_role($u) === null) vpl_denied($u);
    return $u;
}

function vpl_denied(array $u) {
    if ($u['role'] === ROLE_STUDENT) {
        $why = $u['year_level'] ? t('vlab_denied_student', program_year_label((int)$u['year_level'])) : t('vlab_denied_student_noyear');
    } elseif ($u['role'] === ROLE_LECTURER) {
        $why = t('vlab_denied_lecturer');
    } else {
        $why = t('forbidden_body');
    }
    http_response_code(403);
    layout_head(['title' => t('vlab_title')]);
    echo '<div class="p-auth"><div class="p-card" style="text-align:center;"><h1>', te('vlab_title'), '</h1>',
         '<p class="p-auth-intro">', e($why), '</p>',
         '<a class="p-btn p-btn-primary" href="', e(home_for($u)), '">', te('go_back'), '</a></div></div>';
    layout_foot();
    exit;
}

function vpl_headers() {
    header('Cache-Control: private, no-store');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

/* a small page in the lab's style, for messages and the lecturer pages */
function vpl_page_head($title, $right = '') {
    vpl_headers();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>' . e($title) . ' · Kamala Virtual Physics Lab</title>';
    echo '<link rel="stylesheet" href="assets/fonts/fonts.css"><style>
:root{--bg:#edf1f1;--panel:#fff;--panel2:#f5f8f8;--ink:#15212a;--muted:#5b6b75;--line:#d2dadd;--accent:#1a5a80;--accent-soft:#dceaf2;--ok:#1d7a4a;--warn:#a34d12;color-scheme:light}
@media (prefers-color-scheme:dark){:root{--bg:#0f1519;--panel:#162026;--panel2:#1b272e;--ink:#e3eaed;--muted:#94a4ad;--line:#2a3841;--accent:#72b6df;--accent-soft:#1d3646;--ok:#62c48f;--warn:#ec9259;color-scheme:dark}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.55 "IBM Plex Sans",system-ui,sans-serif}
.top{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;background:var(--panel);border-bottom:1px solid var(--line);flex-wrap:wrap}
.top b{font:700 17px "IBM Plex Sans Condensed","Arial Narrow",sans-serif}.top small{display:block;color:var(--muted);font-size:12px}
.wrap{max-width:1180px;margin:0 auto;padding:22px 16px 60px}h1,h2{font-family:"IBM Plex Sans Condensed","Arial Narrow",sans-serif;line-height:1.2}
a{color:var(--accent)}.card{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin:14px 0}
.btn{display:inline-block;border:1px solid var(--line);background:var(--panel2);color:var(--ink);border-radius:8px;padding:8px 14px;font-family:inherit;font-weight:600;font-size:14px;text-decoration:none;cursor:pointer}
.btn.pri{background:var(--accent);border-color:var(--accent);color:#fff}
input,select,textarea{font:inherit;color:var(--ink);background:var(--panel);border:1px solid var(--line);border-radius:7px;padding:7px 9px}
label{display:grid;gap:4px;font-size:13px;font-weight:600;color:var(--muted)}
.muted{color:var(--muted)}.small{font-size:13px}.ok{color:var(--ok)}.warn{color:var(--warn)}
.scroll{overflow-x:auto}table{border-collapse:collapse;font-size:13px}th,td{border:1px solid var(--line);padding:5px 8px;text-align:center;white-space:nowrap}
th{background:var(--panel2);font-weight:600}td.l,th.l{text-align:left}
pre{white-space:pre-wrap;font:12.5px/1.5 "IBM Plex Mono",ui-monospace,monospace;background:var(--panel2);border-radius:8px;padding:12px;max-height:520px;overflow:auto}
.grid{display:grid;gap:12px}@media(min-width:760px){.g2{grid-template-columns:1fr 1fr}}
.marks{display:flex;flex-wrap:wrap;gap:10px;align-items:end}.marks label{flex:0 0 120px}.marks input{width:100%}
@media print{.top,.noprint{display:none!important}body{background:#fff}.card{border:none;padding:0}}
</style></head><body><header class="top"><div><b>Virtual Physics Lab</b><small>B.Sc. Physics practicals (PHY202) · Kamala Science Campus, Sindhuli</small></div><div>' . $right . '</div></header><div class="wrap">';
}
function vpl_page_foot() { echo '</div></body></html>'; }
function vpl_message($title, $html, $code = 200, $back = '') {
    http_response_code($code);
    vpl_page_head($title, '<a class="btn" href="' . e($back !== '' ? $back : portal_url('/index.php')) . '">← B.Sc. portal</a>');
    echo '<div class="card" style="max-width:640px;margin:40px auto"><h1>' . e($title) . '</h1>' . $html . '</div>';
    vpl_page_foot();
    exit;
}
