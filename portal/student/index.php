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
      ORDER BY c.code',
    [$user['id']]
);

$materialTotal = array_sum(array_column($courses, 'material_count'));

$notices = all(
    'SELECT * FROM notices
      WHERE is_published = 1 AND (year_level IS NULL OR year_level = ?)
      ORDER BY is_pinned DESC, published_at DESC
      LIMIT 4',
    [$year ?: null]
);

layout_head(['title' => t('nav_overview'), 'active' => 'home']);
?>
<div class="p-page-head">
  <h1><?= e(t('hello_name', display_name($user))) ?></h1>
  <?php if ($year): ?>
    <p><?= e(t('your_year', localize_digits((string) $year))) ?></p>
  <?php endif; ?>
</div>

<div class="p-grid p-grid-3" style="margin-bottom:30px;">
  <dl class="p-stat">
    <dt><?= te('enrolled_courses') ?></dt>
    <dd><?= e(localize_digits((string) count($courses))) ?></dd>
  </dl>
  <dl class="p-stat">
    <dt><?= te('materials_count') ?></dt>
    <dd><?= e(localize_digits((string) $materialTotal)) ?></dd>
  </dl>
  <dl class="p-stat">
    <dt><?= te('year_of_study') ?></dt>
    <dd><?= e($year ? year_label($year) : '—') ?></dd>
  </dl>
</div>

<section style="margin-bottom:34px;">
  <div class="p-section-head">
    <h2><?= te('enrolled_courses') ?></h2>
    <?php if ($courses): ?>
      <a href="<?= e(portal_url('/student/courses.php')) ?>"><?= te('view_all') ?> →</a>
    <?php endif; ?>
  </div>

  <?php if (!$courses): ?>
    <div class="p-empty"><p><?= te('no_courses_yet') ?></p></div>
  <?php else: ?>
    <div class="p-grid p-grid-2">
      <?php foreach (array_slice($courses, 0, 4) as $c): ?>
        <a class="p-course" href="<?= e(portal_url('/student/course.php?id=' . (int) $c['id'])) ?>">
          <div class="p-course-code"><?= e($c['code']) ?></div>
          <h3><?= e(bilingual($c, 'title')) ?></h3>
          <div class="p-course-meta">
            <span><?= e(year_label((int) $c['year_level'])) ?></span>
            <span><?= e(localize_digits((string) $c['material_count'])) ?> · <?= te('materials') ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section>
  <div class="p-section-head">
    <h2><?= te('latest_notices') ?></h2>
    <a href="<?= e(portal_url('/student/notices.php')) ?>"><?= te('view_all') ?> →</a>
  </div>

  <?php if (!$notices): ?>
    <div class="p-empty"><p><?= te('no_notices_yet') ?></p></div>
  <?php else: ?>
    <?php foreach ($notices as $n): ?>
      <article class="p-notice<?= $n['is_pinned'] ? ' pinned' : '' ?>">
        <div class="p-notice-head">
          <span class="p-tag <?= e($n['category']) ?>"><?= te('cat_' . $n['category']) ?></span>
          <?php if ($n['is_pinned']): ?><span class="p-tag pin"><?= te('pinned') ?></span><?php endif; ?>
          <span class="p-item-meta"><?= e(format_date($n['published_at'])) ?></span>
        </div>
        <h3><?= e(bilingual($n, 'title')) ?></h3>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php layout_foot(); ?>
