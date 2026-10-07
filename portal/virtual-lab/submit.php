<?php
/* Kamala Virtual Physics Lab: a student submits the record of one experiment; GET returns its status. */
define('VPL', 1);
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/exps.php';
vpl_headers();
header('Content-Type: application/json; charset=utf-8');
function vpl_out($code, $data) { http_response_code($code); echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); exit; }

// Not require_login(): a script asking for JSON cannot follow a redirect to the sign-in page.
$u = current_user();
if (!$u || !empty($u['must_change_password'])) vpl_out(401, ['ok' => false, 'error' => 'please sign in to the portal again']);
$role = vlab_role($u);
if ($role === null) vpl_out(403, ['ok' => false, 'error' => 'this account cannot use the virtual lab']);
if (!vlab_records_ready()) vpl_out(503, ['ok' => false, 'error' => 'records cannot be saved yet: ask the administrator to run System → Run database updates']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $exp = (string)($_GET['exp'] ?? '');
    if (!vpl_valid_exp($exp)) vpl_out(400, ['ok' => false, 'error' => 'unknown experiment']);
    $r = one('SELECT UNIX_TIMESTAMP(submitted_at) AS at, marked_at, mark_total, remark FROM vlab_records WHERE user_id = ? AND exp_no = ?', [(int)$u['id'], vpl_exp_no($exp)]);
    if (!$r) vpl_out(200, ['ok' => true]);
    vpl_out(200, ['ok' => true, 'submitted_at' => (int)$r['at'], 'checked' => $r['marked_at'] !== null,
                  'total' => $r['mark_total'] === null ? null : (float)$r['mark_total'], 'remark' => (string)($r['remark'] ?? '')]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') vpl_out(405, ['ok' => false, 'error' => 'method not allowed']);
if ($role !== 'student') vpl_out(403, ['ok' => false, 'error' => 'only students submit records']);

$max = VPL_MAX_RECORD_KB * 1024;
$raw = file_get_contents('php://input', false, null, 0, $max + 1);
if ($raw === false || strlen($raw) > $max) vpl_out(413, ['ok' => false, 'error' => 'the record is too large']);
$in = json_decode($raw, true);
if (!is_array($in)) vpl_out(400, ['ok' => false, 'error' => 'bad request']);
$sent = $in['csrf'] ?? '';
if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) vpl_out(403, ['ok' => false, 'error' => 'your session has expired; reload the page and submit again']);
$exp = (string)($in['exp'] ?? '');
if (!vpl_valid_exp($exp)) vpl_out(400, ['ok' => false, 'error' => 'unknown experiment']);

$str = function ($v, $n) { $v = is_scalar($v) ? (string)$v : ''; $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v); return mb_substr(trim($v), 0, $n); };
$tables = [];
foreach (array_slice(is_array($in['tables'] ?? null) ? $in['tables'] : [], 0, 12) as $t) {
    if (!is_array($t)) continue;
    $tables[] = ['title' => $str($t['title'] ?? '', 200), 'tsv' => $str($t['tsv'] ?? '', 60000)];
}
$name = $str($in['name'] ?? '', 80);
if ($name === '') $name = mb_substr(vpl_name($u), 0, 80);

// One row per student and experiment. Submitting again replaces the record and
// clears its marks, keeping the earlier total so the lecturer can see it was
// marked before. previous_total is assigned first: MySQL applies these in order,
// and it must read the marks before they are cleared.
q('INSERT INTO vlab_records (user_id, exp_no, title, name, roll, exp_date, result, note, body, tables_json)
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
   ON DUPLICATE KEY UPDATE
     previous_total = IF(marked_at IS NULL, previous_total, mark_total),
     title = VALUES(title), name = VALUES(name), roll = VALUES(roll), exp_date = VALUES(exp_date),
     result = VALUES(result), note = VALUES(note), body = VALUES(body), tables_json = VALUES(tables_json),
     versions = LEAST(versions + 1, 65535), submitted_at = NOW(),
     mark_record = NULL, mark_experiment = NULL, mark_error = NULL, mark_viva = NULL, mark_total = NULL,
     remark = NULL, marked_by = NULL, marked_at = NULL',
  [(int)$u['id'], vpl_exp_no($exp), $VPL_EXPS[$exp], $name, $str($in['roll'] ?? '', 30), $str($in['date'] ?? '', 20),
   $str($in['result'] ?? '', 20000), $str($in['note'] ?? '', 20000), $str($in['text'] ?? '', 150000),
   json_encode($tables, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)]);

$at = (int)scalar('SELECT UNIX_TIMESTAMP(submitted_at) FROM vlab_records WHERE user_id = ? AND exp_no = ?', [(int)$u['id'], vpl_exp_no($exp)]);
vpl_out(200, ['ok' => true, 'submitted_at' => $at, 'checked' => false]);
