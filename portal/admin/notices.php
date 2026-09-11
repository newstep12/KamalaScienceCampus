<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/uploads.php';

$admin = require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_notice') {
        $id      = (int) ($_POST['notice_id'] ?? 0);
        $titleEn = trim((string) ($_POST['title_en'] ?? ''));
        $cat     = in_array($_POST['category'] ?? '', ['tu', 'exam', 'scholarship', 'ugc', 'campus'], true) ? $_POST['category'] : 'tu';
        $year    = (int) ($_POST['year_level'] ?? 0);
        $url     = trim((string) ($_POST['source_url'] ?? ''));

        if ($titleEn === '') {
            flash('error', t('err_name_short'));
        } elseif ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url))) {
            flash('error', t('err_upload'));
        } else {
            $file = null;
            if (!empty($_FILES['file']['name'])) {
                $res = store_upload($_FILES['file'], 'notices');
                if (!$res['ok']) {
                    flash('error', $res['error'] === 'type' ? t('err_file_type') : t('err_upload'));
                } else {
                    $file = $res;
                }
            }

            $fields = [
                $cat, $titleEn,
                trim((string) ($_POST['title_ne'] ?? '')) ?: null,
                trim((string) ($_POST['body_en'] ?? '')) ?: null,
                trim((string) ($_POST['body_ne'] ?? '')) ?: null,
                $url ?: null,
                $year >= 1 && $year <= 4 ? $year : null,
                isset($_POST['is_pinned']) ? 1 : 0,
            ];

            if ($id) {
                $sql = 'UPDATE notices SET category=?, title_en=?, title_ne=?, body_en=?, body_ne=?,
                               source_url=?, year_level=?, is_pinned=?';
                if ($file) {
                    $sql .= ', file_path=?, file_name=?';
                    $fields[] = $file['path'];
                    $fields[] = $file['name'];
                }
                $sql .= ' WHERE id=?';
                $fields[] = $id;
                q($sql, $fields);
            } else {
                $fields[] = $file['path'] ?? null;
                $fields[] = $file['name'] ?? null;
                $fields[] = $admin['id'];
                q('INSERT INTO notices (category, title_en, title_ne, body_en, body_ne, source_url,
                          year_level, is_pinned, file_path, file_name, created_by)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?)', $fields);
            }
            log_activity((int) $admin['id'], $id ? 'update_notice' : 'publish_notice', $titleEn);
            flash('ok', t('notice_saved'));
        }
    } elseif ($action === 'delete_notice') {
        $id = (int) ($_POST['notice_id'] ?? 0);
        $n  = one('SELECT file_path FROM notices WHERE id = ?', [$id]);
        if ($n) {
            delete_upload($n['file_path']);
            q('DELETE FROM notices WHERE id = ?', [$id]);
            flash('ok', t('material_deleted'));
        }
    } elseif ($action === 'toggle_publish') {
        q('UPDATE notices SET is_published = 1 - is_published WHERE id = ?', [(int) ($_POST['notice_id'] ?? 0)]);
        flash('ok', t('notice_saved'));
    }
    header('Location: ' . portal_url('/admin/notices.php'));
    exit;
}

$editId  = (int) ($_GET['edit'] ?? 0);
$editing = $editId ? one('SELECT * FROM notices WHERE id = ?', [$editId]) : null;
$notices = all('SELECT * FROM notices ORDER BY is_pinned DESC, published_at DESC LIMIT 200');

layout_head(['title' => t('manage_notices'), 'active' => 'notices', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('manage_notices') ?></h1>
  <p><?= te('notices_intro') ?></p>
</div>

<section class="p-card">
  <h2><?= $editing ? te('edit') : te('add_notice') ?></h2>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_notice">
    <input type="hidden" name="notice_id" value="<?= (int) ($editing['id'] ?? 0) ?>">

    <div class="p-field-row">
      <div class="p-field">
        <label for="category"><?= te('category') ?></label>
        <select id="category" name="category">
          <?php foreach (['tu', 'exam', 'scholarship', 'ugc', 'campus'] as $c): ?>
            <option value="<?= $c ?>" <?= ($editing['category'] ?? '') === $c ? 'selected' : '' ?>><?= te('cat_' . $c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="p-field">
        <label for="year_level"><?= te('applies_to') ?></label>
        <select id="year_level" name="year_level">
          <option value="0"><?= te('all_years') ?></option>
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
        <label for="body_en"><?= te('body_english') ?></label>
        <textarea id="body_en" name="body_en"><?= e($editing['body_en'] ?? '') ?></textarea>
      </div>
      <div class="p-field">
        <label for="body_ne"><?= te('body_nepali') ?></label>
        <textarea id="body_ne" name="body_ne" lang="ne"><?= e($editing['body_ne'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="p-field">
      <label for="source_url"><?= te('source_url') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="url" id="source_url" name="source_url" value="<?= e($editing['source_url'] ?? '') ?>" placeholder="https://tuexam.edu.np/…">
    </div>

    <div class="p-field">
      <label for="file"><?= te('attachment') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="file" id="file" name="file">
      <?php if (!empty($editing['file_name'])): ?>
        <span class="hint"><?= e($editing['file_name']) ?></span>
      <?php endif; ?>
    </div>

    <div class="p-field">
      <label style="display:flex;align-items:center;gap:9px;font-weight:500;">
        <input type="checkbox" name="is_pinned" value="1" style="width:auto;" <?= !empty($editing['is_pinned']) ? 'checked' : '' ?>>
        <?= te('pin_notice') ?>
      </label>
    </div>

    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('publish') ?></button>
      <?php if ($editing): ?>
        <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/admin/notices.php')) ?>"><?= te('cancel') ?></a>
      <?php endif; ?>
    </div>
  </form>
</section>

<section>
  <div class="p-section-head"><h2><?= te('notices_title') ?></h2></div>
  <?php if (!$notices): ?>
    <div class="p-empty"><p><?= te('no_notices_yet') ?></p></div>
  <?php else: ?>
    <div class="p-table-wrap">
      <table class="p-table">
        <thead>
          <tr><th><?= te('material_title') ?></th><th><?= te('category') ?></th>
              <th><?= te('applies_to') ?></th><th><?= te('registered_on') ?></th><th><?= te('actions') ?></th></tr>
        </thead>
        <tbody>
          <?php foreach ($notices as $n): ?>
            <tr<?= $n['is_published'] ? '' : ' style="opacity:.55"' ?>>
              <td>
                <strong><?= e($n['title_en']) ?></strong>
                <?php if ($n['is_pinned']): ?> <span class="p-tag pin"><?= te('pinned') ?></span><?php endif; ?>
              </td>
              <td class="nowrap"><span class="p-tag <?= e($n['category']) ?>"><?= te('cat_' . $n['category']) ?></span></td>
              <td class="nowrap"><?= e($n['year_level'] ? year_label((int) $n['year_level']) : t('all_years')) ?></td>
              <td class="nowrap"><?= e(format_date($n['published_at'])) ?></td>
              <td class="nowrap">
                <a class="p-btn p-btn-ghost p-btn-sm" href="?edit=<?= (int) $n['id'] ?>"><?= te('edit') ?></a>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle_publish">
                  <input type="hidden" name="notice_id" value="<?= (int) $n['id'] ?>">
                  <button class="p-btn p-btn-ghost p-btn-sm" type="submit"><?= $n['is_published'] ? te('suspend') : te('publish') ?></button>
                </form>
                <form method="post" data-confirm="<?= te('confirm_delete') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_notice">
                  <input type="hidden" name="notice_id" value="<?= (int) $n['id'] ?>">
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
