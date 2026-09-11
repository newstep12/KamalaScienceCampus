<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

$admin = require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_course') {
        $id     = (int) ($_POST['course_id'] ?? 0);
        $code   = trim((string) ($_POST['code'] ?? ''));
        $titleEn= trim((string) ($_POST['title_en'] ?? ''));
        $year   = (int) ($_POST['year_level'] ?? 0);
        $lect   = (int) ($_POST['lecturer_id'] ?? 0) ?: null;
        $credit = trim((string) ($_POST['credit_hours'] ?? ''));

        if ($code === '' || $titleEn === '' || $year < 1 || $year > 4) {
            flash('error', t('err_name_short'));
        } else {
            $params = [
                $code, $titleEn,
                trim((string) ($_POST['title_ne'] ?? '')) ?: null,
                trim((string) ($_POST['description_en'] ?? '')) ?: null,
                trim((string) ($_POST['description_ne'] ?? '')) ?: null,
                $year, $credit !== '' ? (float) $credit : null, $lect,
            ];
            try {
                if ($id) {
                    $params[] = $id;
                    q('UPDATE courses SET code = ?, title_en = ?, title_ne = ?, description_en = ?,
                              description_ne = ?, year_level = ?, credit_hours = ?, lecturer_id = ?
                        WHERE id = ?', $params);
                } else {
                    q('INSERT INTO courses (code, title_en, title_ne, description_en, description_ne,
                              year_level, credit_hours, lecturer_id)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $params);
                }
                log_activity((int) $admin['id'], $id ? 'update_course' : 'create_course', $code);
                flash('ok', t('course_saved'));
            } catch (PDOException $e) {
                // Duplicate course code is the only realistic failure here.
                flash('error', t('err_code_taken'));
            }
        }
    } elseif ($action === 'toggle_course') {
        q('UPDATE courses SET is_active = 1 - is_active WHERE id = ?', [(int) ($_POST['course_id'] ?? 0)]);
        flash('ok', t('course_saved'));
    } elseif ($action === 'enrol') {
        $cid = (int) ($_POST['course_id'] ?? 0);
        $uid = (int) ($_POST['student_id'] ?? 0);
        if ($cid && $uid) {
            q('INSERT IGNORE INTO enrolments (user_id, course_id) VALUES (?, ?)', [$uid, $cid]);
            flash('ok', t('enrol_added'));
        }
    } elseif ($action === 'enrol_year') {
        // Bulk: enrol every active student of a year into the course.
        $cid  = (int) ($_POST['course_id'] ?? 0);
        $year = (int) ($_POST['year_level'] ?? 0);
        if ($cid && $year >= 1 && $year <= 4) {
            q('INSERT IGNORE INTO enrolments (user_id, course_id)
               SELECT id, ? FROM users WHERE role = \'student\' AND status = \'active\' AND year_level = ?',
              [$cid, $year]);
            flash('ok', t('enrol_added'));
        }
    } elseif ($action === 'unenrol') {
        q('DELETE FROM enrolments WHERE user_id = ? AND course_id = ?',
          [(int) ($_POST['student_id'] ?? 0), (int) ($_POST['course_id'] ?? 0)]);
        flash('ok', t('enrol_removed'));
    }

    $back = (int) ($_POST['course_id'] ?? 0) && $action !== 'save_course'
        ? '/admin/courses.php?edit=' . (int) $_POST['course_id']
        : '/admin/courses.php';
    header('Location: ' . portal_url($back));
    exit;
}

$editId   = (int) ($_GET['edit'] ?? 0);
$editing  = $editId ? one('SELECT * FROM courses WHERE id = ?', [$editId]) : null;
$lecturers= all('SELECT id, full_name FROM users WHERE role IN (\'lecturer\',\'admin\') AND status = \'active\' ORDER BY full_name');
$courses  = all(
    'SELECT c.*, u.full_name AS lecturer_name,
            (SELECT COUNT(*) FROM enrolments e WHERE e.course_id = c.id) AS student_count
       FROM courses c LEFT JOIN users u ON u.id = c.lecturer_id
      ORDER BY c.year_level, c.code'
);
$enrolled = $editing
    ? all('SELECT u.id, u.full_name, u.symbol_no, u.year_level FROM enrolments e
             JOIN users u ON u.id = e.user_id WHERE e.course_id = ? ORDER BY u.full_name', [$editId])
    : [];
$candidates = $editing
    ? all('SELECT id, full_name, symbol_no FROM users
            WHERE role = \'student\' AND status = \'active\'
              AND id NOT IN (SELECT user_id FROM enrolments WHERE course_id = ?)
            ORDER BY full_name LIMIT 300', [$editId])
    : [];

layout_head(['title' => t('manage_courses'), 'active' => 'courses', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('manage_courses') ?></h1>
</div>

<section class="p-card">
  <h2><?= $editing ? te('edit') : te('add_course') ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_course">
    <input type="hidden" name="course_id" value="<?= (int) ($editing['id'] ?? 0) ?>">

    <div class="p-field-row">
      <div class="p-field">
        <label for="code"><?= te('course_code') ?></label>
        <input type="text" id="code" name="code" value="<?= e($editing['code'] ?? '') ?>" required>
      </div>
      <div class="p-field">
        <label for="year_level"><?= te('year_of_study') ?></label>
        <select id="year_level" name="year_level" required>
          <?php foreach ([1, 2, 3, 4] as $y): ?>
            <option value="<?= $y ?>" <?= (int) ($editing['year_level'] ?? 0) === $y ? 'selected' : '' ?>><?= e(year_label($y)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="p-field-row">
      <div class="p-field">
        <label for="title_en"><?= te('title_english') ?></label>
        <input type="text" id="title_en" name="title_en" value="<?= e($editing['title_en'] ?? '') ?>" required>
      </div>
      <div class="p-field">
        <label for="title_ne"><?= te('title_nepali') ?></label>
        <input type="text" id="title_ne" name="title_ne" value="<?= e($editing['title_ne'] ?? '') ?>" lang="ne">
      </div>
    </div>

    <div class="p-field-row">
      <div class="p-field">
        <label for="description_en"><?= te('body_english') ?></label>
        <textarea id="description_en" name="description_en" style="min-height:80px;"><?= e($editing['description_en'] ?? '') ?></textarea>
      </div>
      <div class="p-field">
        <label for="description_ne"><?= te('body_nepali') ?></label>
        <textarea id="description_ne" name="description_ne" style="min-height:80px;" lang="ne"><?= e($editing['description_ne'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="p-field-row">
      <div class="p-field">
        <label for="credit_hours"><?= te('credit_hours') ?></label>
        <input type="number" step="0.5" min="0" id="credit_hours" name="credit_hours" value="<?= e($editing['credit_hours'] ?? '') ?>">
      </div>
      <div class="p-field">
        <label for="lecturer_id"><?= te('taught_by') ?></label>
        <select id="lecturer_id" name="lecturer_id">
          <option value="0"><?= te('not_assigned') ?></option>
          <?php foreach ($lecturers as $l): ?>
            <option value="<?= (int) $l['id'] ?>" <?= (int) ($editing['lecturer_id'] ?? 0) === (int) $l['id'] ? 'selected' : '' ?>>
              <?= e($l['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
      <?php if ($editing): ?>
        <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/admin/courses.php')) ?>"><?= te('cancel') ?></a>
      <?php endif; ?>
    </div>
  </form>
</section>

<?php if ($editing): ?>
<section class="p-card">
  <h2><?= te('enrol_students') ?> (<?= e(localize_digits((string) count($enrolled))) ?>)</h2>

  <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin-bottom:20px;">
    <form method="post" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="enrol">
      <input type="hidden" name="course_id" value="<?= (int) $editing['id'] ?>">
      <div class="p-field" style="margin:0;min-width:220px;">
        <label for="student_id"><?= te('enrol_add') ?></label>
        <select id="student_id" name="student_id" required>
          <?php foreach ($candidates as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= e($c['full_name']) ?><?= $c['symbol_no'] ? ' — ' . e($c['symbol_no']) : '' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="p-btn p-btn-ghost" type="submit"><?= te('add') ?></button>
    </form>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="enrol_year">
      <input type="hidden" name="course_id" value="<?= (int) $editing['id'] ?>">
      <input type="hidden" name="year_level" value="<?= (int) $editing['year_level'] ?>">
      <button class="p-btn p-btn-gold" type="submit">
        <?= te('enrol_add') ?> — <?= e(year_label((int) $editing['year_level'])) ?>
      </button>
    </form>
  </div>

  <?php if ($enrolled): ?>
    <div class="p-table-wrap">
      <table class="p-table">
        <thead><tr><th><?= te('full_name') ?></th><th><?= te('symbol_no') ?></th><th><?= te('actions') ?></th></tr></thead>
        <tbody>
          <?php foreach ($enrolled as $s): ?>
            <tr>
              <td><?= e($s['full_name']) ?></td>
              <td><?= e($s['symbol_no'] ?: '—') ?></td>
              <td>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="unenrol">
                  <input type="hidden" name="course_id" value="<?= (int) $editing['id'] ?>">
                  <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
                  <button class="p-btn p-btn-danger p-btn-sm" type="submit"><?= te('delete') ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p style="color:var(--ink-soft);margin:0;"><?= te('none_yet') ?></p>
  <?php endif; ?>
</section>
<?php endif; ?>

<section>
  <div class="p-section-head"><h2><?= te('total_courses') ?></h2></div>
  <?php if (!$courses): ?>
    <div class="p-empty"><p><?= te('none_yet') ?></p></div>
  <?php else: ?>
    <div class="p-table-wrap">
      <table class="p-table">
        <thead>
          <tr>
            <th><?= te('course_code') ?></th><th><?= te('material_title') ?></th>
            <th><?= te('year_of_study') ?></th><th><?= te('taught_by') ?></th>
            <th><?= te('total_students') ?></th><th><?= te('actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $c): ?>
            <tr<?= $c['is_active'] ? '' : ' style="opacity:.55"' ?>>
              <td class="nowrap"><strong><?= e($c['code']) ?></strong></td>
              <td><?= e(bilingual($c, 'title')) ?></td>
              <td class="nowrap"><?= e(year_label((int) $c['year_level'])) ?></td>
              <td><?= e($c['lecturer_name'] ?: t('not_assigned')) ?></td>
              <td><?= e(localize_digits((string) $c['student_count'])) ?></td>
              <td class="nowrap">
                <a class="p-btn p-btn-ghost p-btn-sm" href="?edit=<?= (int) $c['id'] ?>"><?= te('edit') ?></a>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle_course">
                  <input type="hidden" name="course_id" value="<?= (int) $c['id'] ?>">
                  <button class="p-btn p-btn-ghost p-btn-sm" type="submit">
                    <?= $c['is_active'] ? te('suspend') : te('reactivate') ?>
                  </button>
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
