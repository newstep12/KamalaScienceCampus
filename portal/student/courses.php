<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

$user = require_role(ROLE_STUDENT, ROLE_LECTURER);
$year = (int) ($user['year_level'] ?? 0);

$courses = all(
    'SELECT c.*, u.full_name AS lecturer_name, u.full_name_ne AS lecturer_name_ne,
            (SELECT COUNT(*) FROM materials m WHERE m.course_id = c.id) AS material_count
       FROM enrolments e
       JOIN courses c ON c.id = e.course_id
       LEFT JOIN users u ON u.id = c.lecturer_id
      WHERE e.user_id = ? AND c.is_active = 1
      ORDER BY c.year_level, c.code',
    [$user['id']]
);

layout_head(['title' => t('courses_title'), 'active' => 'courses']);
?>
<div class="p-page-head">
  <h1><?= te('courses_title') ?></h1>
  <?php if ($year): ?>
    <p><?= e(t('courses_intro', localize_digits((string) $year))) ?></p>
  <?php endif; ?>
</div>

<?php if (!$courses): ?>
  <div class="p-empty"><p><?= te('no_courses_yet') ?></p></div>
<?php else: ?>
  <div class="p-grid p-grid-2">
    <?php foreach ($courses as $c):
        $lecturer = is_nepali() && $c['lecturer_name_ne'] ? $c['lecturer_name_ne'] : $c['lecturer_name'];
    ?>
      <a class="p-course" href="<?= e(portal_url('/student/course.php?id=' . (int) $c['id'])) ?>">
        <div class="p-course-code"><?= e($c['code']) ?></div>
        <h3><?= e(bilingual($c, 'title')) ?></h3>
        <?php if ($desc = bilingual($c, 'description')): ?>
          <p><?= e(mb_strimwidth($desc, 0, 150, '…')) ?></p>
        <?php endif; ?>
        <div class="p-course-meta">
          <span><?= e(year_label((int) $c['year_level'])) ?></span>
          <?php if ($c['credit_hours']): ?>
            <span><?= e(localize_digits(rtrim(rtrim((string) $c['credit_hours'], '0'), '.'))) ?> <?= te('credit_hours') ?></span>
          <?php endif; ?>
          <span><?= te('taught_by') ?>: <?= e($lecturer ?: t('not_assigned')) ?></span>
          <span><?= e(localize_digits((string) $c['material_count'])) ?> · <?= te('materials') ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php layout_foot(); ?>
