<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

require_role(ROLE_ADMIN);

$pending   = (int) scalar('SELECT COUNT(*) FROM users WHERE status = \'pending\'');
$students  = (int) scalar('SELECT COUNT(*) FROM users WHERE role = \'student\'  AND status = \'active\'');
$lecturers = (int) scalar('SELECT COUNT(*) FROM users WHERE role = \'lecturer\' AND status = \'active\'');
$courses   = (int) scalar('SELECT COUNT(*) FROM courses WHERE is_active = 1');

$byYear = all(
    'SELECT year_level, COUNT(*) AS n FROM users
      WHERE role = \'student\' AND status = \'active\' AND year_level IS NOT NULL
      GROUP BY year_level ORDER BY year_level'
);

$recent = all(
    'SELECT l.*, u.full_name AS actor FROM activity_log l
       LEFT JOIN users u ON u.id = l.actor_id
      ORDER BY l.created_at DESC LIMIT 10'
);

layout_head(['title' => t('admin_home'), 'active' => 'home', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('admin_home') ?></h1>
  <p><?= te('campus_name') ?></p>
</div>

<?php if ($pending > 0): ?>
  <div class="p-flash p-flash-info" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">
    <span><?= e(t('pending_count', localize_digits((string) $pending))) ?></span>
    <a class="p-btn p-btn-gold p-btn-sm" href="<?= e(portal_url('/admin/approvals.php')) ?>"><?= te('pending_approvals') ?> →</a>
  </div>
<?php endif; ?>

<div class="p-grid p-grid-3" style="margin-bottom:30px;">
  <dl class="p-stat"><dt><?= te('pending_approvals') ?></dt><dd><?= e(localize_digits((string) $pending)) ?></dd></dl>
  <dl class="p-stat"><dt><?= te('total_students') ?></dt><dd><?= e(localize_digits((string) $students)) ?></dd></dl>
  <dl class="p-stat"><dt><?= te('total_lecturers') ?></dt><dd><?= e(localize_digits((string) $lecturers)) ?></dd></dl>
  <dl class="p-stat"><dt><?= te('total_courses') ?></dt><dd><?= e(localize_digits((string) $courses)) ?></dd></dl>
</div>

<div class="p-grid p-grid-2" style="align-items:start;">
  <section class="p-card">
    <h2><?= te('total_students') ?> · <?= te('year_of_study') ?></h2>
    <?php if (!$byYear): ?>
      <p style="color:var(--ink-soft);margin:0;"><?= te('none_yet') ?></p>
    <?php else: ?>
      <table class="p-table" style="border:0;">
        <tbody>
          <?php foreach ($byYear as $r): ?>
            <tr>
              <th scope="row"><?= e(year_label((int) $r['year_level'])) ?></th>
              <td style="text-align:end;font-weight:600;"><?= e(localize_digits((string) $r['n'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <section class="p-card">
    <h2><?= te('actions') ?></h2>
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
</div>
<?php layout_foot(); ?>
