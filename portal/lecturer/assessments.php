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

    if ($action === 'save_assessment') {
        $title = mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 190);
        $max   = (float) ($_POST['max_marks'] ?? 0);
        $kind  = in_array($_POST['kind'] ?? '', ['internal','assignment','practical','terminal','other'], true)
            ? $_POST['kind'] : 'internal';
        $date  = parse_date((string) ($_POST['assessed_on'] ?? ''));

        // max_marks is DECIMAL(6,2): anything from 10000 up would throw.
        if ($title === '' || $max <= 0 || $max >= 10000) {
            flash('error', t('err_name_short'));
        } else {
            q('INSERT INTO assessments (course_id, title, kind, max_marks, assessed_on, created_by)
               VALUES (?, ?, ?, ?, ?, ?)',
              [$courseId, $title, $kind, $max, $date, $user['id']]);
            flash('ok', t('assessment_saved'));
        }
    } elseif ($action === 'delete_assessment') {
        q('DELETE FROM assessments WHERE id = ? AND course_id = ?',
          [(int) ($_POST['assessment_id'] ?? 0), $courseId]);
        flash('ok', t('assessment_deleted'));
    } elseif ($action === 'toggle_publish') {
        q('UPDATE assessments SET is_published = 1 - is_published WHERE id = ? AND course_id = ?',
          [(int) ($_POST['assessment_id'] ?? 0), $courseId]);
        flash('ok', t('assessment_saved'));
    } elseif ($action === 'save_marks') {
        $aid = (int) ($_POST['assessment_id'] ?? 0);
        // Confirm the assessment belongs to this course before writing marks.
        $assessment = one('SELECT * FROM assessments WHERE id = ? AND course_id = ?', [$aid, $courseId]);
        if ($assessment) {
            $marks   = (array) ($_POST['mark'] ?? []);
            $absent  = (array) ($_POST['absent'] ?? []);
            $remarks = (array) ($_POST['remark'] ?? []);

            foreach ($marks as $uid => $value) {
                $uid = (int) $uid;
                // Only students actually enrolled in this course.
                $ok = scalar('SELECT 1 FROM enrolments WHERE course_id = ? AND user_id = ?', [$courseId, $uid]);
                if (!$ok) {
                    continue;
                }
                $isAbsent = isset($absent[$uid]) ? 1 : 0;
                $value    = trim((string) $value);
                $score    = ($isAbsent || $value === '') ? null
                    : max(0, min((float) $assessment['max_marks'], (float) $value));

                q('INSERT INTO marks (assessment_id, user_id, marks_obtained, is_absent, remarks, recorded_by)
                   VALUES (?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained),
                                           is_absent      = VALUES(is_absent),
                                           remarks        = VALUES(remarks),
                                           recorded_by    = VALUES(recorded_by)',
                  [$aid, $uid, $score, $isAbsent,
                   mb_substr(trim((string) ($remarks[$uid] ?? '')), 0, 255) ?: null, $user['id']]);
            }
            flash('ok', t('marks_saved'));
        }
    }
    header('Location: ' . portal_url('/lecturer/assessments.php?course=' . $courseId
        . (isset($_POST['assessment_id']) && $_POST['action'] === 'save_marks' ? '&enter=' . (int) $_POST['assessment_id'] : '')));
    exit;
}

$assessments = all(
    'SELECT a.*, (SELECT COUNT(*) FROM marks m WHERE m.assessment_id = a.id) AS marked
       FROM assessments a WHERE a.course_id = ? ORDER BY a.assessed_on DESC, a.id DESC',
    [$courseId]
);

$enterId = (int) ($_GET['enter'] ?? 0);
$entering = $enterId ? one('SELECT * FROM assessments WHERE id = ? AND course_id = ?', [$enterId, $courseId]) : null;

$students = $entering ? all(
    'SELECT u.id, u.full_name, u.full_name_ne, u.symbol_no,
            m.marks_obtained, m.is_absent, m.remarks
       FROM enrolments e
       JOIN users u ON u.id = e.user_id AND u.status = \'active\'
       LEFT JOIN marks m ON m.user_id = u.id AND m.assessment_id = ?
      WHERE e.course_id = ?
      ORDER BY u.full_name',
    [$enterId, $courseId]
) : [];

layout_head(['title' => t('assessments'), 'active' => 'home', 'wide' => true]);
?>
<a class="p-back" href="<?= e(portal_url('/lecturer/course.php?id=' . $courseId)) ?>">← <?= e(bilingual($course, 'title')) ?></a>

<div class="p-page-head">
  <div class="p-course-code"><?= e($course['code']) ?></div>
  <h1><?= te('assessments') ?></h1>
</div>

<?php if ($entering): ?>
<section class="p-card">
  <h2><?= te('enter_marks') ?> — <?= e($entering['title']) ?></h2>
  <p style="color:var(--ink-soft);font-size:.92rem;">
    <?= te('max_marks') ?>: <?= e(localize_digits(rtrim(rtrim((string) $entering['max_marks'], '0'), '.'))) ?>
  </p>

  <?php if (!$students): ?>
    <p style="color:var(--ink-soft);"><?= te('none_yet') ?></p>
  <?php else: ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_marks">
    <input type="hidden" name="assessment_id" value="<?= (int) $entering['id'] ?>">
    <div class="p-table-wrap">
      <table class="p-table">
        <thead>
          <tr>
            <th><?= te('full_name') ?></th>
            <th><?= te('symbol_no') ?></th>
            <th style="width:120px;"><?= te('marks') ?></th>
            <th style="width:90px;"><?= te('absent') ?></th>
            <th><?= te('remarks') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): ?>
            <tr>
              <td><?= e(is_nepali() && $s['full_name_ne'] ? $s['full_name_ne'] : $s['full_name']) ?></td>
              <td class="nowrap"><?= e($s['symbol_no'] ?: '—') ?></td>
              <td>
                <input type="number" step="0.25" min="0" max="<?= e((string) $entering['max_marks']) ?>"
                       name="mark[<?= (int) $s['id'] ?>]"
                       value="<?= $s['marks_obtained'] !== null ? e(rtrim(rtrim((string) $s['marks_obtained'], '0'), '.')) : '' ?>"
                       style="padding:7px 9px;">
              </td>
              <td style="text-align:center;">
                <input type="checkbox" name="absent[<?= (int) $s['id'] ?>]" value="1"
                       style="width:auto;" <?= $s['is_absent'] ? 'checked' : '' ?>>
              </td>
              <td><input type="text" name="remark[<?= (int) $s['id'] ?>]" value="<?= e($s['remarks']) ?>" style="padding:7px 9px;"></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
      <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/lecturer/assessments.php?course=' . $courseId)) ?>"><?= te('cancel') ?></a>
    </div>
  </form>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="p-card">
  <h2><?= te('add_assessment') ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_assessment">
    <div class="p-field-row">
      <div class="p-field">
        <label for="title"><?= te('assessment_title') ?></label>
        <input type="text" id="title" name="title" required>
      </div>
      <div class="p-field">
        <label for="kind"><?= te('assessment_kind') ?></label>
        <select id="kind" name="kind">
          <?php foreach (['internal','assignment','practical','terminal','other'] as $k): ?>
            <option value="<?= $k ?>"><?= te('kind_' . $k) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="max_marks"><?= te('max_marks') ?></label>
        <input type="number" step="0.5" min="1" id="max_marks" name="max_marks" value="100" required>
      </div>
      <div class="p-field">
        <label for="assessed_on"><?= te('assessed_on') ?></label>
        <input type="date" id="assessed_on" name="assessed_on" value="<?= e(date('Y-m-d')) ?>">
      </div>
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('add') ?></button>
    </div>
  </form>
</section>

<section class="p-card">
  <h2><?= te('assessments') ?></h2>
  <?php if (!$assessments): ?>
    <p style="color:var(--ink-soft);margin:0;"><?= te('none_yet') ?></p>
  <?php else: ?>
    <div class="p-table-wrap">
      <table class="p-table">
        <thead>
          <tr>
            <th><?= te('assessment_title') ?></th><th><?= te('assessment_kind') ?></th>
            <th><?= te('max_marks') ?></th><th><?= te('assessed_on') ?></th>
            <th><?= te('marks') ?></th><th><?= te('published') ?></th><th><?= te('actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assessments as $a): ?>
            <tr>
              <td><strong><?= e($a['title']) ?></strong></td>
              <td class="nowrap"><?= te('kind_' . $a['kind']) ?></td>
              <td><?= e(localize_digits(rtrim(rtrim((string) $a['max_marks'], '0'), '.'))) ?></td>
              <td class="nowrap"><?= e(format_date($a['assessed_on'])) ?></td>
              <td><?= e(localize_digits((string) $a['marked'])) ?></td>
              <td>
                <span class="p-tag <?= $a['is_published'] ? 'ok' : 'pending' ?>">
                  <?= $a['is_published'] ? te('published') : te('not_published') ?>
                </span>
              </td>
              <td class="nowrap">
                <a class="p-btn p-btn-ghost p-btn-sm" href="?course=<?= $courseId ?>&enter=<?= (int) $a['id'] ?>"><?= te('enter_marks') ?></a>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle_publish">
                  <input type="hidden" name="assessment_id" value="<?= (int) $a['id'] ?>">
                  <button class="p-btn p-btn-ghost p-btn-sm" type="submit">
                    <?= $a['is_published'] ? te('unpublish_results') : te('publish_results') ?>
                  </button>
                </form>
                <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_assessment">
                  <input type="hidden" name="assessment_id" value="<?= (int) $a['id'] ?>">
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
