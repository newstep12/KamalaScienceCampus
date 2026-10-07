<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/virtual-lab.php';

$admin = require_role(ROLE_ADMIN);

/*
 * The Virtual Physics Lab from the administrator's side: the way into the lab
 * and its records, and the one decision only an administrator makes — which
 * lecturers it is open to. Students need no setting: their year of study
 * decides (VLAB_YEARS).
 */

$lecturers = all(
    'SELECT id, full_name, full_name_ne, email, designation FROM users
      WHERE role = \'lecturer\' AND status = \'active\' ORDER BY full_name'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    // Only ids of active lecturers are kept, whatever the form sent.
    $valid  = array_map('intval', array_column($lecturers, 'id'));
    $picked = array_intersect(array_map('intval', (array) ($_POST['lecturers'] ?? [])), $valid);
    vlab_save_lecturer_ids($picked);
    $names = array_map(
        fn (array $l): string => display_name($l),
        array_values(array_filter($lecturers, fn (array $l): bool => in_array((int) $l['id'], $picked, true)))
    );
    log_activity((int) $admin['id'], 'vlab_lecturers', implode(', ', $names) ?: '—');
    flash('ok', t('vlab_saved', $names ? join_list($names) : '—'));
    header('Location: ' . portal_url('/admin/virtual-lab.php'));
    exit;
}

$assigned = vlab_lecturer_ids();
$years    = implode(',', array_map('intval', VLAB_YEARS));
$students = (int) scalar(
    "SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active' AND year_level IN ($years)"
);
$ready   = vlab_records_ready();
$records = $ready ? (int) scalar('SELECT COUNT(*) FROM vlab_records') : 0;
$waiting = $ready ? (int) scalar('SELECT COUNT(*) FROM vlab_records WHERE marked_at IS NULL') : 0;

layout_head(['title' => t('vlab_title'), 'active' => 'vlab', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('vlab_title') ?></h1>
  <p><?= te('vlab_intro') ?></p>
</div>

<?php if (!$ready): ?>
  <div class="p-flash p-flash-error" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">
    <span><?= te('vlab_records_missing') ?></span>
    <a class="p-btn p-btn-primary p-btn-sm" href="<?= e(portal_url('/admin/system.php')) ?>"><?= te('db_update_run') ?> →</a>
  </div>
<?php endif; ?>

<div class="p-form-actions" style="margin:0 0 24px;display:flex;flex-wrap:wrap;gap:10px;">
  <a class="p-btn p-btn-primary" href="<?= e(portal_url('/virtual-lab/')) ?>"><?= te('vlab_open') ?> →</a>
  <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/virtual-lab/lecturer.php')) ?>"><?= te('vlab_submissions') ?></a>
</div>

<div class="p-grid p-grid-3" style="margin-bottom:30px;">
  <dl class="p-stat"><dt><?= te('vlab_stat_students') ?></dt><dd><?= e(localize_digits((string) $students)) ?></dd></dl>
  <dl class="p-stat"><dt><?= te('vlab_stat_records') ?></dt><dd><?= e(localize_digits((string) $records)) ?></dd></dl>
  <dl class="p-stat"><dt><?= te('vlab_stat_waiting') ?></dt><dd><?= e(localize_digits((string) $waiting)) ?></dd></dl>
</div>

<div class="p-grid p-grid-2" style="align-items:start;">
  <section class="p-card">
    <h2><?= te('vlab_who') ?></h2>
    <ul style="margin:0;padding-inline-start:1.2em;line-height:1.6;">
      <li><?= te('vlab_who_students') ?></li>
      <li><?= te('vlab_who_admins') ?></li>
      <li><?= te('vlab_who_lecturers') ?></li>
    </ul>
  </section>

  <section class="p-card">
    <h2><?= te('vlab_lecturers') ?></h2>
    <?php if (!$lecturers): ?>
      <p style="color:var(--ink-soft);margin:0;"><?= te('vlab_no_lecturers') ?></p>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <?php foreach ($lecturers as $l): ?>
          <div class="p-field" style="margin-bottom:8px;">
            <label style="display:flex;align-items:center;gap:9px;font-weight:500;">
              <input type="checkbox" name="lecturers[]" value="<?= (int) $l['id'] ?>" style="width:auto;"
                     <?= in_array((int) $l['id'], $assigned, true) ? 'checked' : '' ?>>
              <span>
                <?= e(display_name($l)) ?>
                <span style="color:var(--ink-soft);font-size:.88rem;">
                  · <?= e(designation_label($l['designation'] ?? null) ?: t('role_lecturer')) ?> · <?= e($l['email']) ?>
                </span>
              </span>
            </label>
          </div>
        <?php endforeach; ?>
        <div class="p-form-actions">
          <button class="p-btn p-btn-primary" type="submit"><?= te('vlab_save') ?></button>
        </div>
      </form>
    <?php endif; ?>
  </section>
</div>
<?php layout_foot(); ?>
