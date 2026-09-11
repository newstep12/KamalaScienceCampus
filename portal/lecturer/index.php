<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

$user = require_role(ROLE_LECTURER, ROLE_ADMIN);

$courses = all(
    'SELECT c.*,
            (SELECT COUNT(*) FROM materials m  WHERE m.course_id  = c.id) AS material_count,
            (SELECT COUNT(*) FROM enrolments e WHERE e.course_id = c.id) AS student_count
       FROM courses c
      WHERE (c.lecturer_id = ? OR ? = \'admin\') AND c.is_active = 1
      ORDER BY c.year_level, c.code',
    [$user['id'], $user['role']]
);

layout_head(['title' => t('lecturer_home'), 'active' => 'home']);
?>
<div class="p-page-head">
  <h1><?= te('lecturer_home') ?></h1>
  <p><?= e(t('hello_name', display_name($user))) ?></p>
</div>

<div class="p-grid p-grid-3" style="margin-bottom:30px;">
  <dl class="p-stat">
    <dt><?= te('total_courses') ?></dt>
    <dd><?= e(localize_digits((string) count($courses))) ?></dd>
  </dl>
  <dl class="p-stat">
    <dt><?= te('my_students') ?></dt>
    <dd><?= e(localize_digits((string) array_sum(array_column($courses, 'student_count')))) ?></dd>
  </dl>
  <dl class="p-stat">
    <dt><?= te('materials_count') ?></dt>
    <dd><?= e(localize_digits((string) array_sum(array_column($courses, 'material_count')))) ?></dd>
  </dl>
</div>

<?php if (!$courses): ?>
  <div class="p-empty"><p><?= te('none_yet') ?></p></div>
<?php else: ?>
  <div class="p-grid p-grid-2">
    <?php foreach ($courses as $c): ?>
      <a class="p-course" href="<?= e(portal_url('/lecturer/course.php?id=' . (int) $c['id'])) ?>">
        <div class="p-course-code"><?= e($c['code']) ?></div>
        <h3><?= e(bilingual($c, 'title')) ?></h3>
        <div class="p-course-meta">
          <span><?= e(year_label((int) $c['year_level'])) ?></span>
          <span><?= e(localize_digits((string) $c['student_count'])) ?> · <?= te('my_students') ?></span>
          <span><?= e(localize_digits((string) $c['material_count'])) ?> · <?= te('materials') ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php layout_foot(); ?>
