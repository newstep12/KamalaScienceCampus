<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/uploads.php';
require_once __DIR__ . '/../inc/signatures.php';
require_once __DIR__ . '/../inc/photos.php';

/**
 * The signature library — administrators only, and the only page in the portal
 * from which a signature can be uploaded, released or applied.
 *
 * Holding a signature and using one are two separate acts here, which is the
 * point of the page: a scan can be uploaded and kept locked, and it prints on
 * nothing until an administrator both releases it and points a use at it.
 */

$admin = require_role(ROLE_ADMIN);

// The tables arrive with a database update, which an administrator runs after
// a deploy. Until then, say so and point at the button rather than failing.
if (!signature_tables_ready()) {
    flash('error', t('db_update_needed'));
    header('Location: ' . portal_url('/admin/system.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // A signature file is a few hundred kilobytes at most, so a POST that PHP
    // dropped for exceeding post_max_size is worth naming: $_POST comes back
    // empty, which would otherwise look like an expired session.
    if (post_exceeded_limit()) {
        flash('error', t('err_file_too_large', format_bytes(upload_limit_bytes())));
        header('Location: ' . portal_url('/admin/signatures.php'));
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['signature_id'] ?? 0);

    if ($action === 'upload') {
        $label = mb_substr(trim((string) ($_POST['label'] ?? '')), 0, 120);
        $owner = mb_substr(trim((string) ($_POST['owner_name'] ?? '')), 0, 120);
        $title = in_array($_POST['owner_title'] ?? '', designations(), true)
            ? (string) $_POST['owner_title'] : 'principal';

        if ($label === '' || mb_strlen($owner) < 3) {
            flash('error', t('err_signature_details'));
        } else {
            // 80 px on the short side: a signature strip is wide and shallow,
            // so the 200 px a passport photo needs would turn away a perfectly
            // good scan. Wide enough to print at the 22 mm it occupies is what
            // matters, and the hint on the form asks for 600 px across. A scan
            // photographed rather than scanned is turned the right way up as
            // it is stored; a PNG is never touched, so a transparent
            // background stays transparent.
            $stored = store_signature_image($_FILES['signature'] ?? [], 'signature');
            if (!$stored['ok']) {
                flash('error', $stored['error'] === 'small' ? t('err_signature_small') : image_error_message($stored['error']));
            } else {
                q(
                    'INSERT INTO signatures (label, owner_name, owner_name_ne, owner_title, file_path,
                                             release_scope, note, uploaded_by)
                     VALUES (?, ?, ?, ?, ?, \'locked\', ?, ?)',
                    [
                        $label,
                        $owner,
                        mb_substr(trim((string) ($_POST['owner_name_ne'] ?? '')), 0, 120) ?: null,
                        $title,
                        $stored['path'],
                        mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 255) ?: null,
                        (int) $admin['id'],
                    ]
                );
                log_activity((int) $admin['id'], 'signature_upload', $label, $owner);
                // Uploaded locked, always. Releasing it is a second, deliberate
                // click, and the flash says so rather than leaving the admin to
                // wonder why no card changed.
                flash('ok', t('signature_uploaded'));
            }
        }
    } elseif ($action === 'release') {
        release_signature($id, (string) ($_POST['release_scope'] ?? 'locked'), (int) $admin['id']);
        flash('ok', t('signature_scope_saved'));
    } elseif ($action === 'assign') {
        foreach (SIGNATURE_USES as $use) {
            if (array_key_exists('use_' . $use, $_POST)) {
                assign_signature($use, (int) $_POST['use_' . $use], (int) $admin['id']);
            }
        }
        flash('ok', t('signature_applied_saved'));
    } elseif ($action === 'edit') {
        $label = mb_substr(trim((string) ($_POST['label'] ?? '')), 0, 120);
        $owner = mb_substr(trim((string) ($_POST['owner_name'] ?? '')), 0, 120);
        $title = in_array($_POST['owner_title'] ?? '', designations(), true)
            ? (string) $_POST['owner_title'] : 'principal';
        if ($label === '' || mb_strlen($owner) < 3) {
            flash('error', t('err_signature_details'));
        } else {
            q(
                'UPDATE signatures SET label = ?, owner_name = ?, owner_name_ne = ?, owner_title = ?, note = ?
                  WHERE id = ?',
                [
                    $label,
                    $owner,
                    mb_substr(trim((string) ($_POST['owner_name_ne'] ?? '')), 0, 120) ?: null,
                    $title,
                    mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 255) ?: null,
                    $id,
                ]
            );
            log_activity((int) $admin['id'], 'signature_edit', $label);
            flash('ok', t('signature_edited'));
        }
    } elseif ($action === 'delete') {
        delete_signature($id, (int) $admin['id']);
        flash('ok', t('signature_deleted'));
    }

    header('Location: ' . portal_url('/admin/signatures.php'));
    exit;
}

$library = signatures();
$applied = [];
foreach (SIGNATURE_USES as $use) {
    $applied[$use] = signature_for_use($use);
}

layout_head(['title' => t('signatures_title'), 'active' => 'signatures', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('signatures_title') ?></h1>
  <p><?= te('signatures_intro') ?></p>
</div>

<section class="p-card">
  <h2><?= te('signatures_applied_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('signatures_applied_intro') ?></p>

  <?php if (!$library): ?>
    <div class="p-empty" style="margin-top:16px;"><p><?= te('signatures_none') ?></p></div>
  <?php else: ?>
    <form method="post" style="margin-top:18px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="assign">
      <div class="p-field-row">
        <?php foreach (SIGNATURE_USES as $use): ?>
          <div class="p-field">
            <label for="use_<?= e($use) ?>"><?= te('sig_use_' . $use) ?></label>
            <select id="use_<?= e($use) ?>" name="use_<?= e($use) ?>">
              <option value="0"><?= te('sig_use_none') ?></option>
              <?php foreach ($library as $s): ?>
                <option value="<?= (int) $s['id'] ?>"
                        <?= (int) ($applied[$use]['id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                  <?= e($s['label']) ?> — <?= e($s['owner_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="hint"><?= te('sig_use_' . $use . '_hint') ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="p-form-actions">
        <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
        <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/id-card.php')) ?>"><?= te('id_card_preview') ?></a>
      </div>
    </form>
  <?php endif; ?>
</section>

<section class="p-card">
  <h2><?= te('signatures_library_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('signatures_library_intro') ?></p>

  <?php if (!$library): ?>
    <div class="p-empty" style="margin-top:16px;"><p><?= te('signatures_none') ?></p></div>
  <?php else: ?>
    <div class="p-sig-list">
      <?php foreach ($library as $s):
          $uses  = signature_uses((int) $s['id']);
          $scope = signature_scope($s['release_scope']); ?>
        <article class="p-sig">
          <div class="p-sig-image">
            <img src="<?= e(signature_url($s)) ?>" alt="<?= e($s['label']) ?>">
          </div>

          <div class="p-sig-meta">
            <h3><?= e($s['label']) ?></h3>
            <p class="p-sig-owner">
              <?= e($s['owner_name']) ?>
              <?php if ($s['owner_name_ne']): ?><span lang="ne">· <?= e($s['owner_name_ne']) ?></span><?php endif; ?>
              <br><span><?= e(designation_label($s['owner_title'])) ?></span>
            </p>
            <?php if ($s['note']): ?><p class="p-sig-note"><?= e($s['note']) ?></p><?php endif; ?>

            <p class="p-sig-trail">
              <?= te('signature_uploaded_by', $s['uploader'] ?: t('unknown'), format_date($s['created_at'], true)) ?>
              <?php if ($s['released_at']): ?>
                <br><?= te('signature_released_by', $s['releaser'] ?: t('unknown'), format_date($s['released_at'], true)) ?>
              <?php endif; ?>
            </p>

            <p class="p-sig-tags">
              <span class="p-tag <?= $scope === 'locked' ? 'bad' : ($scope === 'everyone' ? 'pending' : 'ok') ?>">
                <?= te('sig_scope_' . $scope) ?>
              </span>
              <?php foreach ($uses as $use): ?>
                <span class="p-tag"><?= te('sig_applied_' . $use) ?></span>
              <?php endforeach; ?>
              <?php if (!$uses): ?>
                <span class="p-tag"><?= te('sig_applied_nowhere') ?></span>
              <?php endif; ?>
            </p>
          </div>

          <div class="p-sig-controls">
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="release">
              <input type="hidden" name="signature_id" value="<?= (int) $s['id'] ?>">
              <div class="p-field">
                <label for="scope-<?= (int) $s['id'] ?>"><?= te('sig_scope') ?></label>
                <select id="scope-<?= (int) $s['id'] ?>" name="release_scope" onchange="this.form.submit()">
                  <?php foreach (signature_scopes() as $sc): ?>
                    <option value="<?= e($sc) ?>" <?= $scope === $sc ? 'selected' : '' ?>><?= te('sig_scope_' . $sc) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <noscript><button class="p-btn p-btn-ghost p-btn-sm" type="submit"><?= te('save') ?></button></noscript>
            </form>
            <p class="hint"><?= te('sig_scope_' . $scope . '_hint') ?></p>

            <details class="p-sig-edit">
              <summary><?= te('signature_edit') ?></summary>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="signature_id" value="<?= (int) $s['id'] ?>">
                <div class="p-field">
                  <label for="label-<?= (int) $s['id'] ?>"><?= te('signature_label') ?></label>
                  <input type="text" id="label-<?= (int) $s['id'] ?>" name="label" value="<?= e($s['label']) ?>" required>
                </div>
                <div class="p-field">
                  <label for="owner-<?= (int) $s['id'] ?>"><?= te('signature_owner') ?></label>
                  <input type="text" id="owner-<?= (int) $s['id'] ?>" name="owner_name" value="<?= e($s['owner_name']) ?>" required>
                </div>
                <div class="p-field">
                  <label for="owner-ne-<?= (int) $s['id'] ?>"><?= te('signature_owner_ne') ?></label>
                  <input type="text" id="owner-ne-<?= (int) $s['id'] ?>" name="owner_name_ne" lang="ne"
                         value="<?= e($s['owner_name_ne']) ?>">
                </div>
                <div class="p-field">
                  <label for="title-<?= (int) $s['id'] ?>"><?= te('signature_owner_title') ?></label>
                  <select id="title-<?= (int) $s['id'] ?>" name="owner_title">
                    <?php foreach (designations() as $d): ?>
                      <option value="<?= e($d) ?>" <?= $s['owner_title'] === $d ? 'selected' : '' ?>>
                        <?= e(designation_label($d)) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="p-field">
                  <label for="note-<?= (int) $s['id'] ?>"><?= te('signature_note') ?></label>
                  <input type="text" id="note-<?= (int) $s['id'] ?>" name="note" value="<?= e($s['note']) ?>">
                </div>
                <div class="p-form-actions">
                  <button class="p-btn p-btn-primary p-btn-sm" type="submit"><?= te('save') ?></button>
                </div>
              </form>
            </details>

            <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="signature_id" value="<?= (int) $s['id'] ?>">
              <button class="p-btn p-btn-danger p-btn-sm" type="submit"><?= te('signature_delete') ?></button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="p-card">
  <h2><?= te('signature_add_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('signature_add_intro') ?></p>

  <form method="post" enctype="multipart/form-data" style="margin-top:18px;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload">

    <div class="p-field">
      <label for="signature"><?= te('signature_file') ?> <span class="hint"><?= te('signature_file_hint') ?></span></label>
      <input type="file" id="signature" name="signature" accept="image/png,image/jpeg,image/webp" required>
      <span class="hint"><?= te('signature_scan_hint') ?></span>
    </div>

    <div class="p-field-row">
      <div class="p-field">
        <label for="new-label"><?= te('signature_label') ?> <span class="hint"><?= te('signature_label_hint') ?></span></label>
        <input type="text" id="new-label" name="label" maxlength="120" required
               placeholder="<?= te('signature_label_placeholder') ?>">
      </div>
      <div class="p-field">
        <label for="new-title"><?= te('signature_owner_title') ?></label>
        <select id="new-title" name="owner_title">
          <?php foreach (designations() as $d): ?>
            <option value="<?= e($d) ?>" <?= $d === 'principal' ? 'selected' : '' ?>><?= e(designation_label($d)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="p-field-row">
      <div class="p-field">
        <label for="new-owner"><?= te('signature_owner') ?></label>
        <input type="text" id="new-owner" name="owner_name" maxlength="120" required
               value="<?= e(setting('id_card_chief_name')) ?>">
      </div>
      <div class="p-field">
        <label for="new-owner-ne"><?= te('signature_owner_ne') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="text" id="new-owner-ne" name="owner_name_ne" lang="ne" maxlength="120"
               value="<?= e(setting('id_card_chief_name_ne')) ?>">
      </div>
    </div>

    <div class="p-field">
      <label for="new-note"><?= te('signature_note') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="text" id="new-note" name="note" maxlength="255" placeholder="<?= te('signature_note_placeholder') ?>">
    </div>

    <p class="p-flash p-flash-info"><?= te('signature_locked_notice') ?></p>

    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('signature_upload') ?></button>
    </div>
  </form>
</section>

<section class="p-card">
  <h2><?= te('signature_trail_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('signature_trail_intro') ?></p>
  <?php $trail = signature_activity(30); ?>
  <?php if (!$trail): ?>
    <div class="p-empty" style="margin-top:16px;"><p><?= te('none_yet') ?></p></div>
  <?php else: ?>
    <div class="p-table-wrap" style="margin-top:16px;">
      <table class="p-table">
        <thead>
          <tr>
            <th><?= te('date') ?></th>
            <th><?= te('actions') ?></th>
            <th><?= te('full_name') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trail as $row): ?>
            <tr>
              <td class="nowrap"><?= e(format_date($row['created_at'], true)) ?></td>
              <td>
                <?= e(signature_action_label($row['action'])) ?>
                <?php if ($row['subject']): ?>
                  <span style="color:var(--ink-soft);">— <?= e($row['subject']) ?></span>
                <?php endif; ?>
                <?php if ($row['detail']): ?>
                  <span style="color:var(--ink-soft);">(<?= e($row['detail']) ?>)</span>
                <?php endif; ?>
              </td>
              <td><?= e($row['actor'] ?: t('unknown')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php layout_foot(); ?>
