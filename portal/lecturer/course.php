<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/uploads.php';

$user     = require_role(ROLE_LECTURER, ROLE_ADMIN);
$courseId = (int) ($_GET['id'] ?? 0);

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

    if ($action === 'add_material') {
        $kind  = in_array($_POST['kind'] ?? '', ['file', 'link', 'note'], true) ? $_POST['kind'] : 'file';
        $title = trim((string) ($_POST['title'] ?? ''));
        $desc  = trim((string) ($_POST['description'] ?? '')) ?: null;

        if ($title === '') {
            flash('error', t('err_name_short'));
        } elseif ($kind === 'file') {
            $res = store_upload($_FILES['file'] ?? [], 'materials/' . $courseId);
            if (!$res['ok']) {
                flash('error', $res['error'] === 'type' ? t('err_file_type') : t('err_upload'));
            } else {
                q('INSERT INTO materials (course_id, kind, title, description, file_path, file_name, file_size, uploaded_by)
                   VALUES (?, \'file\', ?, ?, ?, ?, ?, ?)',
                  [$courseId, $title, $desc, $res['path'], $res['name'], $res['size'], $user['id']]);
                flash('ok', t('material_added'));
            }
        } elseif ($kind === 'link') {
            $url = trim((string) ($_POST['link_url'] ?? ''));
            // Only http(s) — blocks javascript: and data: URLs.
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                flash('error', t('err_upload'));
            } else {
                q('INSERT INTO materials (course_id, kind, title, description, link_url, uploaded_by)
                   VALUES (?, \'link\', ?, ?, ?, ?)',
                  [$courseId, $title, $desc, $url, $user['id']]);
                flash('ok', t('material_added'));
            }
        } else {
            q('INSERT INTO materials (course_id, kind, title, description, body, uploaded_by)
               VALUES (?, \'note\', ?, ?, ?, ?)',
              [$courseId, $title, $desc, trim((string) ($_POST['body'] ?? '')), $user['id']]);
            flash('ok', t('material_added'));
        }
    } elseif ($action === 'delete_material') {
        $mid = (int) ($_POST['material_id'] ?? 0);
        $m = one('SELECT * FROM materials WHERE id = ? AND course_id = ?', [$mid, $courseId]);
        if ($m) {
            delete_upload($m['file_path']);
            q('DELETE FROM materials WHERE id = ?', [$mid]);
            flash('ok', t('material_deleted'));
        }
    }
    header('Location: ' . portal_url('/lecturer/course.php?id=' . $courseId));
    exit;
}

$materials = all('SELECT * FROM materials WHERE course_id = ? ORDER BY created_at DESC', [$courseId]);
$students  = all(
    'SELECT u.id, u.full_name, u.full_name_ne, u.symbol_no, u.email, u.year_level
       FROM enrolments e JOIN users u ON u.id = e.user_id
      WHERE e.course_id = ? AND u.status = \'active\'
      ORDER BY u.full_name',
    [$courseId]
);

layout_head(['title' => bilingual($course, 'title'), 'active' => 'home', 'wide' => true]);
?>
<a class="p-back" href="<?= e(portal_url('/lecturer/index.php')) ?>">← <?= te('nav_dashboard') ?></a>

<div class="p-page-head">
  <div class="p-course-code"><?= e($course['code']) ?></div>
  <h1><?= e(bilingual($course, 'title')) ?></h1>
  <p><?= e(year_label((int) $course['year_level'])) ?></p>
  <div class="p-form-actions" style="margin-top:16px;">
    <a class="p-btn p-btn-primary" href="<?= e(portal_url('/lecturer/assessments.php?course=' . $courseId)) ?>"><?= te('assessments') ?></a>
    <a class="p-btn p-btn-gold" href="<?= e(portal_url('/lecturer/attendance.php?course=' . $courseId)) ?>"><?= te('take_attendance') ?></a>
  </div>
</div>

<section class="p-card">
  <h2><?= te('upload_material') ?></h2>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_material">

    <div class="p-field-row">
      <div class="p-field">
        <label for="kind"><?= te('material_kind') ?></label>
        <select id="kind" name="kind">
          <option value="file"><?= te('kind_file') ?></option>
          <option value="link"><?= te('kind_link') ?></option>
          <option value="note"><?= te('kind_note') ?></option>
        </select>
      </div>
      <div class="p-field">
        <label for="title"><?= te('material_title') ?></label>
        <input type="text" id="title" name="title" required>
      </div>
    </div>

    <div class="p-field">
      <label for="description"><?= te('material_desc') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="text" id="description" name="description">
    </div>

    <div class="p-field" data-kind="file">
      <label for="file"><?= te('choose_file') ?>
        <span class="hint"><?= e(t('allowed_types', format_bytes((int) (config()['max_upload'] ?? 20971520)))) ?></span>
      </label>
      <input type="file" id="file" name="file">
    </div>

    <div class="p-field" data-kind="link" hidden>
      <label for="link_url"><?= te('link_url') ?></label>
      <input type="url" id="link_url" name="link_url" placeholder="https://">
    </div>

    <div class="p-field" data-kind="note" hidden>
      <label for="body"><?= te('note_body') ?></label>
      <textarea id="body" name="body"></textarea>
    </div>

    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('add') ?></button>
    </div>
  </form>
</section>

<section class="p-card">
  <h2><?= te('materials') ?></h2>
  <?php if (!$materials): ?>
    <p style="color:var(--ink-soft);margin:0;"><?= te('no_materials') ?></p>
  <?php else: ?>
    <ul class="p-list">
      <?php foreach ($materials as $m):
          $icon = ['file' => '⤓', 'link' => '↗', 'note' => '✎'][$m['kind']] ?? '•'; ?>
        <li class="p-item">
          <span class="p-icon <?= e($m['kind']) ?>" aria-hidden="true"><?= $icon ?></span>
          <div class="p-item-body">
            <h4><?= e($m['title']) ?></h4>
            <?php if ($m['description']): ?><p><?= e($m['description']) ?></p><?php endif; ?>
            <div class="p-item-meta">
              <?= e(t('posted_on', format_date($m['created_at']))) ?>
              <?php if ($m['file_size']): ?> · <?= e(format_bytes((int) $m['file_size'])) ?><?php endif; ?>
            </div>
          </div>
          <div class="p-item-actions">
            <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_material">
              <input type="hidden" name="material_id" value="<?= (int) $m['id'] ?>">
              <button class="p-btn p-btn-danger p-btn-sm" type="submit"><?= te('delete') ?></button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<section class="p-card">
  <h2><?= te('enrol_students') ?> (<?= e(localize_digits((string) count($students))) ?>)</h2>
  <?php if (!$students): ?>
    <p style="color:var(--ink-soft);margin:0;"><?= te('none_yet') ?></p>
  <?php else: ?>
    <div class="p-table-wrap">
      <table class="p-table">
        <thead>
          <tr>
            <th><?= te('full_name') ?></th>
            <th><?= te('symbol_no') ?></th>
            <th><?= te('email') ?></th>
            <th><?= te('year_of_study') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): ?>
            <tr>
              <td><?= e(display_name($s)) ?></td>
              <td class="nowrap"><?= e($s['symbol_no'] ?: '—') ?></td>
              <td><?= e($s['email']) ?></td>
              <td class="nowrap"><?= e(year_label((int) $s['year_level'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<script>
(function () {
  var kind = document.getElementById('kind');
  if (!kind) return;
  function sync() {
    document.querySelectorAll('[data-kind]').forEach(function (el) {
      el.hidden = el.getAttribute('data-kind') !== kind.value;
    });
  }
  kind.addEventListener('change', sync);
  sync();
})();
</script>
<?php layout_foot(); ?>
