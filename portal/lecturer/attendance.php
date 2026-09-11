<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

$user     = require_role(ROLE_LECTURER, ROLE_ADMIN);
$courseId = (int) ($_GET['course'] ?? 0);

$course = one(
    'SELECT * FROM courses WHERE id = ? AND (lecturer_id = ? OR ? = \'admin\') LIMIT 1',
    [$courseId, $user['id'], $user['role']]
);
if (!$course) {
    http_response_code(404);
    layout_head(['title' => t('notfound_title'), 'active' => 'home']);
    echo '<div class="p-empty"><p>' . te('notfound_body') . '</p></div>';
    layout_foot();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_session') {
        $date  = parse_date((string) ($_POST['held_on'] ?? ''));
        $topic = mb_substr(trim((string) ($_POST['topic'] ?? '')), 0, 190) ?: null;
        if ($date === null) {
            flash('error', t('err_date_bad'));
        } else {
            // One session per course per day; re-saving the same date reopens it.
            q('INSERT INTO attendance_sessions (course_id, held_on, topic, created_by)
               VALUES (?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE topic = VALUES(topic)',
              [$courseId, $date, $topic, $user['id']]);
            $sid = (int) scalar('SELECT id FROM attendance_sessions WHERE course_id = ? AND held_on = ?',
                                [$courseId, $date]);
            header('Location: ' . portal_url('/lecturer/attendance.php?course=' . $courseId . '&session=' . $sid));
            exit;
        }
    } elseif ($action === 'save_attendance') {
        $sid = (int) ($_POST['session_id'] ?? 0);
        $session = one('SELECT * FROM attendance_sessions WHERE id = ? AND course_id = ?', [$sid, $courseId]);
        if ($session) {
            foreach ((array) ($_POST['status'] ?? []) as $uid => $status) {
                $uid = (int) $uid;
                if (!in_array($status, ['present', 'absent', 'late', 'excused'], true)) {
                    continue;
                }
                if (!scalar('SELECT 1 FROM enrolments WHERE course_id = ? AND user_id = ?', [$courseId, $uid])) {
                    continue;
                }
                q('INSERT INTO attendance (session_id, user_id, status) VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE status = VALUES(status)',
                  [$sid, $uid, $status]);
            }
            flash('ok', t('attendance_saved'));
        }
        header('Location: ' . portal_url('/lecturer/attendance.php?course=' . $courseId . '&session=' . $sid));
        exit;
    } elseif ($action === 'delete_session') {
        q('DELETE FROM attendance_sessions WHERE id = ? AND course_id = ?',
          [(int) ($_POST['session_id'] ?? 0), $courseId]);
        flash('ok', t('session_deleted'));
    }
    header('Location: ' . portal_url('/lecturer/attendance.php?course=' . $courseId));
    exit;
}

$sessions = all(
    'SELECT s.*,
            (SELECT COUNT(*) FROM attendance a WHERE a.session_id = s.id AND a.status IN (\'present\',\'late\')) AS present_count,
            (SELECT COUNT(*) FROM attendance a WHERE a.session_id = s.id) AS marked_count
       FROM attendance_sessions s WHERE s.course_id = ? ORDER BY s.held_on DESC',
    [$courseId]
);

$sessionId = (int) ($_GET['session'] ?? 0);
$active = $sessionId ? one('SELECT * FROM attendance_sessions WHERE id = ? AND course_id = ?', [$sessionId, $courseId]) : null;

$roll = $active ? all(
    'SELECT u.id, u.full_name, u.full_name_ne, u.symbol_no, a.status
       FROM enrolments e
       JOIN users u ON u.id = e.user_id AND u.status = \'active\'
       LEFT JOIN attendance a ON a.user_id = u.id AND a.session_id = ?
      WHERE e.course_id = ?
      ORDER BY u.full_name',
    [$sessionId, $courseId]
) : [];

layout_head(['title' => t('take_attendance'), 'active' => 'home', 'wide' => true]);
?>
<a class="p-back" href="<?= e(portal_url('/lecturer/course.php?id=' . $courseId)) ?>">← <?= e(bilingual($course, 'title')) ?></a>

<div class="p-page-head">
  <div class="p-course-code"><?= e($course['code']) ?></div>
  <h1><?= te('take_attendance') ?></h1>
</div>

<?php if ($active): ?>
<section class="p-card">
  <h2><?= e(format_date($active['held_on'])) ?><?= $active['topic'] ? ' — ' . e($active['topic']) : '' ?></h2>
  <?php if (!$roll): ?>
    <p style="color:var(--ink-soft);"><?= te('none_yet') ?></p>
  <?php else: ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_attendance">
    <input type="hidden" name="session_id" value="<?= (int) $active['id'] ?>">
    <p>
      <button class="p-btn p-btn-ghost p-btn-sm" type="button" id="all-present"><?= te('mark_all_present') ?></button>
    </p>
    <div class="p-table-wrap">
      <table class="p-table">
        <thead>
          <tr><th><?= te('full_name') ?></th><th><?= te('symbol_no') ?></th><th><?= te('attendance_percent') ?></th></tr>
        </thead>
        <tbody>
          <?php foreach ($roll as $s): $cur = $s['status'] ?: 'present'; ?>
            <tr>
              <td><?= e(is_nepali() && $s['full_name_ne'] ? $s['full_name_ne'] : $s['full_name']) ?></td>
              <td class="nowrap"><?= e($s['symbol_no'] ?: '—') ?></td>
              <td>
                <div style="display:flex;gap:14px;flex-wrap:wrap;">
                  <?php foreach (['present', 'absent', 'late', 'excused'] as $st): ?>
                    <label style="display:flex;align-items:center;gap:5px;font-weight:400;font-size:.88rem;">
                      <input type="radio" name="status[<?= (int) $s['id'] ?>]" value="<?= $st ?>"
                             style="width:auto;" <?= $cur === $st ? 'checked' : '' ?>>
                      <?= te($st) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
      <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/lecturer/attendance.php?course=' . $courseId)) ?>"><?= te('cancel') ?></a>
    </div>
  </form>
  <script>
  document.getElementById('all-present').addEventListener('click', function () {
    document.querySelectorAll('input[type=radio][value=present]').forEach(function (r) { r.checked = true; });
  });
  </script>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="p-card">
  <h2><?= te('take_attendance') ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_session">
    <div class="p-field-row">
      <div class="p-field">
        <label for="held_on"><?= te('attendance_date') ?></label>
        <input type="date" id="held_on" name="held_on" value="<?= e(date('Y-m-d')) ?>" required>
      </div>
      <div class="p-field">
        <label for="topic"><?= te('topic') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="text" id="topic" name="topic">
      </div>
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('take_attendance') ?></button>
    </div>
  </form>
</section>

<section class="p-card">
  <h2><?= te('sessions') ?></h2>
  <?php if (!$sessions): ?>
    <p style="color:var(--ink-soft);margin:0;"><?= te('no_attendance_yet') ?></p>
  <?php else: ?>
    <div class="p-table-wrap">
      <table class="p-table">
        <thead><tr><th><?= te('attendance_date') ?></th><th><?= te('topic') ?></th><th><?= te('classes_attended') ?></th><th><?= te('actions') ?></th></tr></thead>
        <tbody>
          <?php foreach ($sessions as $s): ?>
            <tr>
              <td class="nowrap"><strong><?= e(format_date($s['held_on'])) ?></strong></td>
              <td><?= e($s['topic'] ?: '—') ?></td>
              <td class="nowrap">
                <?= e(localize_digits((string) $s['present_count'])) ?> / <?= e(localize_digits((string) $s['marked_count'])) ?>
              </td>
              <td class="nowrap">
                <a class="p-btn p-btn-ghost p-btn-sm" href="?course=<?= $courseId ?>&session=<?= (int) $s['id'] ?>"><?= te('edit') ?></a>
                <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_session">
                  <input type="hidden" name="session_id" value="<?= (int) $s['id'] ?>">
                  <button class="p-btn p-btn-danger p-btn-sm" type="submit"><?= te('delete') ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php layout_foot(); ?>
