<?php
/* Kamala Virtual Physics Lab: the assigned lecturers and administrators read and mark submitted records. */
define('VPL', 1);
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/exps.php';

$me = vpl_require_user();
$home = home_for($me);
if (vlab_role($me) !== 'lecturer') vpl_message('Lecturers only', '<p>This page is for the lecturers of the B.Sc. physics practicals.</p>', 403, $home);
if (!vlab_records_ready()) vpl_message('Not set up yet', '<p>The table that keeps the submitted records could not be created. An administrator can create it under System → Run database updates.</p>', 503, $home);

/*
 * Every student who can use the lab, and anyone else who has submitted a
 * record (a student since promoted to fourth year, say), with what each has
 * submitted. Sorted by year, then name.
 */
function vpl_students() {
    $list = [];
    $in = implode(',', array_map('intval', VLAB_YEARS));
    foreach (all("SELECT id, full_name, full_name_ne, symbol_no, year_level FROM users
                   WHERE role = 'student' AND status = 'active' AND year_level IN ($in)") as $u)
        $list[(int)$u['id']] = ['id' => (int)$u['id'], 'name' => vpl_name($u), 'symbol' => (string)$u['symbol_no'], 'year' => (int)$u['year_level'], 'roll' => '', 'subs' => []];
    foreach (all('SELECT r.user_id, r.exp_no, r.name, r.roll, r.versions, r.mark_total, r.remark, r.marked_at,
                         UNIX_TIMESTAMP(r.submitted_at) AS at, u.full_name, u.full_name_ne, u.symbol_no, u.year_level
                    FROM vlab_records r JOIN users u ON u.id = r.user_id
                   ORDER BY r.submitted_at') as $r) {
        $id = (int)$r['user_id'];
        if (!isset($list[$id])) $list[$id] = ['id' => $id, 'name' => vpl_name($r), 'symbol' => (string)$r['symbol_no'], 'year' => (int)$r['year_level'], 'roll' => '', 'subs' => []];
        if ($r['roll'] !== null && $r['roll'] !== '') $list[$id]['roll'] = $r['roll'];   // as written on the latest record
        $list[$id]['subs']['exp' . (int)$r['exp_no']] = ['at' => (int)$r['at'], 'versions' => (int)$r['versions'],
            'marked' => $r['marked_at'] !== null, 'total' => $r['mark_total'], 'remark' => (string)$r['remark']];
    }
    usort($list, function ($x, $y) { return ($x['year'] <=> $y['year']) ?: strnatcasecmp($x['name'], $y['name']); });
    return $list;
}
function vpl_num($v) { return $v === null ? '' : rtrim(rtrim(number_format((float)$v, 1, '.', ''), '0'), '.'); }

/* --- save marks --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $s = (int)($_POST['s'] ?? 0); $x = (string)($_POST['e'] ?? '');
    if ($s <= 0 || !vpl_valid_exp($x)) vpl_message('Not saved', '<p>The form was not valid. Go back, reload the page and try again.</p>', 400, $home);
    if (!one('SELECT id FROM vlab_records WHERE user_id = ? AND exp_no = ?', [$s, vpl_exp_no($x)])) vpl_message('Not found', '<p>That record no longer exists.</p>', 404, $home);
    $m = [];
    foreach (VPL_MARKS as $k => [, $max]) {
        $v = trim((string)($_POST[$k] ?? ''));
        $m[$k] = $v === '' || !is_numeric($v) ? null : max(0, min($max, round((float)$v, 1)));
    }
    $parts = array_filter($m, function ($v) { return $v !== null; });
    $total = $parts ? round(array_sum($parts), 1) : null;
    q('UPDATE vlab_records SET mark_record = ?, mark_experiment = ?, mark_error = ?, mark_viva = ?, mark_total = ?,
              remark = ?, marked_by = ?, marked_at = NOW()
        WHERE user_id = ? AND exp_no = ?',
      [$m['record'], $m['experiment'], $m['error'], $m['viva'], $total,
       mb_substr(trim((string)($_POST['remark'] ?? '')), 0, 500) ?: null, (int)$me['id'], $s, vpl_exp_no($x)]);
    header('Location: lecturer.php?s=' . $s . '&e=' . rawurlencode($x) . '&saved=1'); exit;
}

/* --- CSV of submissions and marks --- */
if (isset($_GET['csv'])) {
    vpl_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="virtual-lab-records-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    $cell = function ($v) { $v = (string)$v; return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v; }; // no spreadsheet formulas
    fputcsv($o, ['Year', 'Roll no.', 'Name', 'Symbol no.', 'Experiment', 'Title', 'Submitted', 'Versions', 'Record /20', 'Experiment /50', 'Error analysis /10', 'Viva /20', 'Total /100', 'Remark']);
    $rows = all('SELECT r.*, UNIX_TIMESTAMP(r.submitted_at) AS at, u.full_name, u.full_name_ne, u.symbol_no, u.year_level
                   FROM vlab_records r JOIN users u ON u.id = r.user_id
                  ORDER BY u.year_level, u.full_name, r.exp_no');
    foreach ($rows as $r)
        fputcsv($o, array_map($cell, [$r['year_level'], $r['roll'], $r['name'], $r['symbol_no'], $r['exp_no'], $VPL_EXPS['exp' . (int)$r['exp_no']] ?? $r['title'],
            date('Y-m-d H:i', (int)$r['at']), $r['versions'], vpl_num($r['mark_record']), vpl_num($r['mark_experiment']), vpl_num($r['mark_error']),
            vpl_num($r['mark_viva']), vpl_num($r['mark_total']), $r['remark']]));
    exit;
}

$right = '<a class="btn" href="./">Open the lab</a> <a class="btn" href="' . e($home) . '">← Portal</a>';

/* --- one record --- */
if (isset($_GET['s'], $_GET['e'])) {
    $s = (int)$_GET['s']; $x = (string)$_GET['e'];
    if ($s <= 0 || !vpl_valid_exp($x)) vpl_message('Not found', '<p>No such record.</p>', 404, $home);
    $r = one('SELECT r.*, UNIX_TIMESTAMP(r.submitted_at) AS at, UNIX_TIMESTAMP(r.marked_at) AS marked_ts,
                     u.full_name, u.full_name_ne, u.symbol_no, u.year_level, m.full_name AS marker
                FROM vlab_records r JOIN users u ON u.id = r.user_id LEFT JOIN users m ON m.id = r.marked_by
               WHERE r.user_id = ? AND r.exp_no = ?', [$s, vpl_exp_no($x)]);
    if (!$r) vpl_message('Not found', '<p>No such record.</p>', 404, $home);
    $account = vpl_name($r);
    vpl_page_head('Record ' . $r['roll'], $right);
    echo '<p class="noprint"><a href="lecturer.php">← All submissions</a></p>';
    if (isset($_GET['saved'])) echo '<p class="ok noprint"><b>Marks saved.</b></p>';
    echo '<h1>Experiment ' . (int)$r['exp_no'] . ': ' . e($VPL_EXPS[$x]) . '</h1>';
    echo '<p><b>' . e($r['name']) . '</b> · Roll no. ' . e($r['roll']) . ' · ' . e(vpl_label(['role' => ROLE_STUDENT, 'year_level' => $r['year_level']])) .
        ($account !== $r['name'] ? ' <span class="muted small">(portal account: ' . e($account) . ')</span>' : '') .
        ($r['symbol_no'] ? ' <span class="muted small">· TU symbol no. ' . e($r['symbol_no']) . '</span>' : '') .
        '<br><span class="muted small">Experiment date ' . e(preg_match('/^(\d{4})-(\d\d)-(\d\d)$/', (string)$r['exp_date'], $dm) ? "$dm[3]/$dm[2]/$dm[1]" : $r['exp_date']) . ' · submitted ' . e(date('d M Y, H:i', (int)$r['at'])) . ((int)$r['versions'] > 1 ? ' · version ' . (int)$r['versions'] : '') . '</span></p>';
    echo '<form method="post" class="card noprint">' . csrf_field() . '<input type="hidden" name="s" value="' . $s . '"><input type="hidden" name="e" value="' . e($x) . '">';
    echo '<h2 style="margin-top:0;font-size:18px">Marks (PHY202 scheme)</h2><div class="marks">';
    foreach (VPL_MARKS as $k => [$l, $mx])
        echo '<label>' . e($l) . ' /' . $mx . '<input type="number" name="' . $k . '" min="0" max="' . $mx . '" step="0.5" value="' . e(vpl_num($r['mark_' . $k])) . '"></label>';
    echo '<label style="flex:1 1 260px">Remark for the student<input name="remark" maxlength="500" value="' . e($r['remark']) . '"></label><button class="btn pri" type="submit">Save marks</button></div>';
    if ($r['marked_at'] !== null) echo '<p class="small muted">Last marked ' . e(date('d M Y, H:i', (int)$r['marked_ts'])) . ($r['marker'] ? ' by ' . e($r['marker']) : '') . ' · total ' . e(vpl_num($r['mark_total']) ?: '—') . '/100</p>';
    elseif ($r['previous_total'] !== null) echo '<p class="small muted">The student resubmitted after an earlier marking (' . e(vpl_num($r['previous_total'])) . '/100).</p>';
    echo '</form>';
    if ((string)$r['result'] !== '') echo '<div class="card"><h2 style="margin-top:0;font-size:18px">Calculation and result (from the bench)</h2><pre>' . e($r['result']) . '</pre></div>';
    if ((string)$r['note'] !== '') echo '<div class="card"><h2 style="margin-top:0;font-size:18px">Student’s error analysis and conclusion</h2><pre>' . e($r['note']) . '</pre></div>';
    foreach (json_decode((string)$r['tables_json'], true) ?: [] as $t) {
        if (!is_array($t)) continue;
        $rows = array_map(function ($l) { return explode("\t", $l); }, explode("\n", (string)($t['tsv'] ?? '')));
        $head = array_shift($rows);
        echo '<div class="card"><h2 style="margin-top:0;font-size:16px">' . e($t['title'] ?? '') . '</h2><div class="scroll"><table><tr>';
        foreach ($head as $c) echo '<th>' . esub($c) . '</th>';
        echo '</tr>';
        foreach ($rows as $row) { echo '<tr>'; foreach ($row as $c) echo '<td>' . esub($c) . '</td>'; echo '</tr>'; }
        echo '</table></div></div>';
    }
    echo '<details class="card"><summary><b>Full record as submitted</b></summary><pre>' . e($r['body']) . '</pre></details>';
    echo '<p class="noprint"><button class="btn" onclick="print()">Print</button></p>';
    vpl_page_foot(); exit;
}

/* --- overview --- */
$list = vpl_students();
vpl_page_head('Student submissions', $right);
echo '<h1>Student submissions</h1><p class="muted">Records submitted from the virtual laboratory by B.Sc. students. Click a mark to open the record. <span class="ok">■</span> marked, <span style="color:var(--accent)">■</span> waiting to be marked, · not submitted.</p>';
echo '<p class="noprint"><a class="btn" href="lecturer.php?csv=1">Download all marks (CSV)</a></p>';
if (!$list) echo '<div class="card"><p>There are no B.Sc. 2nd or 3rd year students in the portal yet, and no records have been submitted. Students submit a record from the <b>Record file</b> tab of each experiment.</p></div>';
else {
    echo '<div class="card scroll"><table><tr><th>Year</th><th class="l">Roll no.</th><th class="l">Name</th>';
    foreach ($VPL_EXPS as $id => $t) echo '<th title="' . e($t) . '">' . vpl_exp_no($id) . '</th>';
    echo '<th>Done</th></tr>';
    foreach ($list as $s) {
        echo '<tr><td>' . ($s['year'] ?: '—') . '</td><td class="l">' . e($s['roll']) . '</td><td class="l">' . e($s['name']) . ($s['symbol'] !== '' ? ' <span class="muted small">' . e($s['symbol']) . '</span>' : '') . '</td>';
        foreach ($VPL_EXPS as $id => $t) {
            if (empty($s['subs'][$id])) { echo '<td class="muted">·</td>'; continue; }
            $x = $s['subs'][$id];
            $label = $x['marked'] && $x['total'] !== null ? e(vpl_num($x['total'])) : '✓';
            echo '<td><a href="lecturer.php?s=' . $s['id'] . '&amp;e=' . e($id) . '" title="' . e($t . ' · ' . date('d M Y', $x['at'])) . '" style="font-weight:600;text-decoration:none;color:' . ($x['marked'] ? 'var(--ok)' : 'var(--accent)') . '">' . $label . '</a></td>';
        }
        echo '<td>' . count($s['subs']) . '</td></tr>';
    }
    echo '</table></div>';
}
vpl_page_foot();
