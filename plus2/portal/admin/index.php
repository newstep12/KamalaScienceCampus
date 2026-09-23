<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/signatures.php';
require_once __DIR__ . '/../inc/idcard.php';

require_role(ROLE_ADMIN);

$pending  = (int) scalar('SELECT COUNT(*) FROM users WHERE status = \'pending\'');
$teachers = (int) scalar('SELECT COUNT(*) FROM users WHERE role = \'teacher\' AND status = \'active\'');

// Active students by class and group — the two numbers the office is asked
// for most. A student who has not chosen a group yet counts under "—".
$byGroup = [];
foreach (all(
    'SELECT class_level, study_group, COUNT(*) AS n FROM users
      WHERE role = \'student\' AND status = \'active\'
      GROUP BY class_level, study_group'
) as $r) {
    $byGroup[(int) $r['class_level']][(string) ($r['study_group'] ?? '')] = (int) $r['n'];
}
$byClass = [];
foreach (class_levels() as $c) {
    $byClass[$c] = array_sum($byGroup[$c] ?? []);
}

// Students with something still missing from their card, so the office can
// chase them before a print run rather than after.
// Counted by the same rule the card page uses, id_card_missing(), rather than
// a second list of columns that could drift from it — the names in particular
// can only be judged there, by script.
$incomplete = 0;
foreach (all('SELECT * FROM users WHERE role = \'student\' AND status = \'active\'') as $s) {
    if (id_card_missing($s)) {
        $incomplete++;
    }
}

$needsDbUpdate = !signature_tables_ready();

$recent = all(
    'SELECT l.*, u.full_name AS actor FROM activity_log l
       LEFT JOIN users u ON u.id = l.actor_id
      ORDER BY l.created_at DESC LIMIT 10'
);

layout_head(['title' => t('admin_home'), 'active' => 'home', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('admin_home') ?></h1>
  <p><?= te('campus_name') ?> · <?= te('portal') ?></p>
</div>

<?php if ($needsDbUpdate): ?>
  <div class="p-flash p-flash-error" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">
    <span><?= te('db_update_needed') ?></span>
    <a class="p-btn p-btn-primary p-btn-sm" href="<?= e(portal_url('/admin/system.php')) ?>"><?= te('db_update_run') ?> →</a>
  </div>
<?php endif; ?>

<?php if ($pending > 0): ?>
  <div class="p-flash p-flash-info" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">
    <span><?= e(t('pending_count', localize_digits((string) $pending))) ?></span>
    <a class="p-btn p-btn-gold p-btn-sm" href="<?= e(portal_url('/admin/approvals.php')) ?>"><?= te('pending_approvals') ?> →</a>
  </div>
<?php endif; ?>

<div class="p-grid p-grid-3" style="margin-bottom:30px;">
  <dl class="p-stat"><dt><?= te('pending_approvals') ?></dt><dd><?= e(localize_digits((string) $pending)) ?></dd></dl>
  <?php foreach ($byClass as $c => $n): ?>
    <dl class="p-stat">
      <dt><?= e(t('students_in_class', class_label($c))) ?></dt>
      <dd><a href="<?= e(portal_url('/admin/users.php?role=student&class=' . $c)) ?>" style="color:inherit;text-decoration:none;"><?= e(localize_digits((string) $n)) ?></a></dd>
    </dl>
  <?php endforeach; ?>
  <dl class="p-stat"><dt><?= te('total_teachers') ?></dt><dd><?= e(localize_digits((string) $teachers)) ?></dd></dl>
  <dl class="p-stat"><dt><?= te('cards_incomplete') ?></dt><dd><?= e(localize_digits((string) $incomplete)) ?></dd></dl>
</div>

<section class="p-card">
  <h2><?= te('students_by_group') ?></h2>
  <div class="p-table-wrap">
    <table class="p-table" style="border:0;">
      <thead>
        <tr>
          <th><?= te('class') ?></th>
          <?php foreach (study_groups() as $g): ?><th style="text-align:end;"><?= e(study_group_label($g)) ?></th><?php endforeach; ?>
          <th style="text-align:end;"><?= te('group_none') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (class_levels() as $c): ?>
          <tr>
            <th scope="row"><?= e(class_label($c)) ?></th>
            <?php foreach (study_groups() as $g): ?>
              <td style="text-align:end;font-weight:600;">
                <a href="<?= e(portal_url('/admin/users.php?role=student&class=' . $c . '&group=' . $g)) ?>"><?= e(localize_digits((string) ($byGroup[$c][$g] ?? 0))) ?></a>
              </td>
            <?php endforeach; ?>
            <td style="text-align:end;"><?= e(localize_digits((string) ($byGroup[$c][''] ?? 0))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="p-card">
  <h2><?= te('recent_activity') ?></h2>
  <?php if (!$recent): ?>
    <p style="color:var(--ink-soft);margin:0;"><?= te('none_yet') ?></p>
  <?php else: ?>
    <ul class="p-list">
      <?php foreach ($recent as $r): ?>
        <li class="p-item" style="padding:11px 0;">
          <div class="p-item-body">
            <h4 style="font-size:.9rem;"><?= e($r['action']) ?> — <?= e((string) $r['subject']) ?></h4>
            <div class="p-item-meta">
              <?= e(format_date($r['created_at'], true)) ?>
              <?php if ($r['actor']): ?> · <?= e($r['actor']) ?><?php endif; ?>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php layout_foot(); ?>
