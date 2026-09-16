<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/uploads.php';
require_once __DIR__ . '/../inc/translate.php';

$admin = require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Checked before the CSRF token, because when PHP discards an oversized
    // request body there is no token left to check — the form comes through
    // completely empty and an attachment that was merely too big would be
    // reported as an expired session.
    if (post_exceeded_limit()) {
        flash('error', t('err_file_too_large', format_bytes(upload_limit_bytes())));
        header('Location: ' . portal_url('/admin/notices.php'));
        exit;
    }

    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_notice') {
        $id       = (int) ($_POST['notice_id'] ?? 0);
        $existing = $id ? one('SELECT * FROM notices WHERE id = ?', [$id]) : null;

        $titleEn = trim((string) ($_POST['title_en'] ?? ''));
        $titleNe = trim((string) ($_POST['title_ne'] ?? ''));
        $bodyEn  = trim((string) ($_POST['body_en'] ?? ''));
        $bodyNe  = trim((string) ($_POST['body_ne'] ?? ''));
        $cat     = in_array($_POST['category'] ?? '', ['tu', 'exam', 'scholarship', 'ugc', 'campus'], true) ? $_POST['category'] : 'tu';
        $year    = (int) ($_POST['year_level'] ?? 0);
        $url     = trim((string) ($_POST['source_url'] ?? ''));
        $drop    = isset($_POST['remove_file']);

        $file      = null;
        $fileError = null;
        if (upload_present($_FILES['file'] ?? null)) {
            $stored = store_upload($_FILES['file'], 'notices');
            if ($stored['ok']) {
                $file = $stored;
            } else {
                $fileError = (string) $stored['error'];
            }
        }

        if ($titleEn === '') {
            flash('error', t('err_notice_title'));
        } elseif ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url))) {
            flash('error', t('err_bad_url'));
        } elseif ($fileError !== null) {
            // Nothing is saved when the attachment failed. Publishing the
            // notice anyway would put it on the board with its content
            // missing, and "The notice has been published" would bury the
            // reason — which is exactly how a PDF goes quietly astray.
            flash('error', upload_error_message($fileError));
        } else {
            $tracked = notices_track_auto_translation();

            // Nepali the admin did not write themselves — left blank, or a
            // machine translation still sitting untouched in the box — is
            // (re)generated from the English, so correcting the English
            // corrects both languages. Anything typed by a person is kept.
            $titleAuto = 0;
            $keptTitle = $tracked && $existing && !empty($existing['title_ne_auto'])
                      && $titleNe === trim((string) $existing['title_ne']);
            if ($titleNe === '' || $keptTitle) {
                $made = translate_to_nepali($titleEn);
                if ($made !== null) {
                    $titleNe   = $made['text'];
                    $titleAuto = 1;
                } elseif ($keptTitle) {
                    $titleAuto = 1;
                }
            }

            $bodyAuto = 0;
            $keptBody = $tracked && $existing && !empty($existing['body_ne_auto'])
                     && $bodyNe === trim((string) $existing['body_ne']);
            if ($bodyEn !== '' && ($bodyNe === '' || $keptBody)) {
                $made = translate_to_nepali($bodyEn);
                if ($made !== null) {
                    $bodyNe   = $made['text'];
                    $bodyAuto = 1;
                } elseif ($keptBody) {
                    $bodyAuto = 1;
                }
            }

            $data = [
                'category'   => $cat,
                'title_en'   => $titleEn,
                'title_ne'   => $titleNe ?: null,
                'body_en'    => $bodyEn ?: null,
                'body_ne'    => $bodyNe ?: null,
                'source_url' => $url ?: null,
                'year_level' => $year >= 1 && $year <= 4 ? $year : null,
                'is_pinned'  => isset($_POST['is_pinned']) ? 1 : 0,
            ];
            if ($tracked) {
                $data['title_ne_auto'] = $titleAuto;
                $data['body_ne_auto']  = $bodyAuto;
            }
            if ($file) {
                $data['file_path'] = $file['path'];
                $data['file_name'] = $file['name'];
            } elseif ($drop) {
                $data['file_path'] = null;
                $data['file_name'] = null;
            }

            // The replaced file is of no further use, and uploads/ is not
            // somewhere a campus wants to accumulate forgotten copies.
            if (($file || $drop) && $existing && $existing['file_path']) {
                delete_upload($existing['file_path']);
            }

            // Every column name here is one of our own literals, which is
            // what makes interpolating them safe; the values are all bound.
            if ($id) {
                $set = implode(', ', array_map(static fn(string $c): string => $c . ' = ?', array_keys($data)));
                q("UPDATE notices SET {$set} WHERE id = ?", [...array_values($data), $id]);
            } else {
                $data['created_by'] = $admin['id'];
                $cols = implode(', ', array_keys($data));
                $marks = implode(', ', array_fill(0, count($data), '?'));
                q("INSERT INTO notices ({$cols}) VALUES ({$marks})", array_values($data));
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
        <?php // Easy to miss, and it decides whether the public ever sees the notice. ?>
        <span class="hint"><?= te('applies_to_hint') ?></span>
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
        <span class="hint"><?= te('nepali_auto_hint') ?></span>
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
        <span class="hint"><?= te('nepali_auto_hint') ?></span>
      </div>
    </div>

    <div class="p-field">
      <label for="source_url"><?= te('source_url') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="url" id="source_url" name="source_url" value="<?= e($editing['source_url'] ?? '') ?>" placeholder="https://tuexam.edu.np/…">
    </div>

    <div class="p-field">
      <label for="file"><?= te('attachment') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.gif,.txt,.csv,.zip">
      <?php // The real ceiling is usually the host's, not ours, and a scanned
            // notice can sail past it. Saying so here is what keeps an
            // oversized PDF from turning into a mystery. ?>
      <span class="hint"><?= te('max_upload_hint', format_bytes(upload_limit_bytes())) ?></span>
      <?php if (!empty($editing['file_path'])): ?>
        <p class="hint" style="margin-top:8px;">
          <?= te('current_attachment') ?>:
          <a href="<?= e(portal_url('/download.php?notice=' . (int) $editing['id'] . '&view=1')) ?>"
             target="_blank" rel="noopener"><?= e($editing['file_name'] ?: basename((string) $editing['file_path'])) ?></a>
        </p>
        <label style="display:flex;align-items:center;gap:9px;font-weight:500;margin-top:8px;">
          <input type="checkbox" name="remove_file" value="1" style="width:auto;">
          <?= te('remove_attachment') ?>
        </label>
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
              <th><?= te('applies_to') ?></th><th><?= te('seen_by') ?></th>
              <th><?= te('attachment') ?></th>
              <th><?= te('registered_on') ?></th><th><?= te('actions') ?></th></tr>
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
              <td class="nowrap">
                <?php // Where a notice actually shows up is not obvious from
                      // the fields that decide it, so spell it out. ?>
                <?php if (!$n['is_published']): ?>
                  <span class="p-tag bad"><?= te('seen_nobody') ?></span>
                <?php elseif ($n['year_level']): ?>
                  <span class="p-tag pending"><?= te('seen_portal_only') ?></span>
                <?php else: ?>
                  <span class="p-tag ok"><?= te('seen_public') ?></span>
                <?php endif; ?>
              </td>
              <td class="nowrap">
                <?php if ($n['file_path']): ?>
                  <a href="<?= e(portal_url('/download.php?notice=' . (int) $n['id'] . '&view=1')) ?>"
                     target="_blank" rel="noopener"><?= te('open_attachment') ?> ↗</a>
                <?php else: ?>
                  <span class="hint">—</span>
                <?php endif; ?>
              </td>
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
