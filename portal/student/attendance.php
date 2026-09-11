<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

$user = require_role(ROLE_STUDENT);

// 'late' counts as attended; 'excused' is removed from the denominator so an
// approved absence does not count against the student.
$courses = all(
    'SELECT c.id, c.code, c.title_en, c.title_ne,
            (SELECT COUNT(*) FROM attendance_sessions s WHERE s.course_id = c.id) AS held,
            (SELECT COUNT(*) FROM attendance a
               JOIN attendance_sessions s ON s.id = a.session_id
              WHERE s.course_id = c.id AND a.user_id = e.user_id
                AND a.status IN (\'present\', \'late\')) AS attended,
            (SELECT COUNT(*) FROM attendance a
               JOIN attendance_sessions s ON s.id = a.session_id
              WHERE s.course_id = c.id AND a.user_id = e.user_id
                AND a.status = \'excused\') AS excused
       FROM enrolments e
       JOIN courses c ON c.id = e.course_id AND c.is_active = 1
      WHERE e.user_id = ?
      ORDER BY c.year_level, c.code',
    [$user['id']]
);

$recent = all(
    'SELECT s.held_on, s.topic, c.code, c.title_en, c.title_ne, a.status
       FROM attendance a
       JOIN attendance_sessions s ON s.id = a.session_id
       JOIN courses c ON c.id = s.course_id
      WHERE a.user_id = ?
      ORDER BY s.held_on DESC LIMIT 25',
    [$user['id']]
);

layout_head(['title' => t('attendance_title'), 'active' => 'attendance']);
?>
<div class="p-page-head">
  <h1><?= te('attendance_title') ?></h1>
  <p><?= te('attendance_intro') ?></p>
</div>

<?php
$anyHeld = array_sum(array_map(fn($c) => (int) $c['held'], $courses));
if (!$courses || !$anyHeld): ?>
  <div class="p-empty"><p><?= te('no_attendance_yet') ?></p></div>
<?php else: ?>
  <div class="p-grid p-grid-2" style="margin-bottom:24px;">
    <?php foreach ($courses as $c):
        $denominator = max(0, (int) $c['held'] - (int) $c['excused']);
        $pct = $denominator > 0 ? ((int) $c['attended'] / $denominator) * 100 : null;
        $tone = $pct === null ? '' : ($pct >= 75 ? 'ok' : ($pct >= 60 ? 'pending' : 'bad')); ?>
      <div class="p-card" style="margin:0;">
        <div class="p-course-code"><?= e($c['code']) ?></div>
        <h3 style="margin-bottom:.5em;"><?= e(bilingual($c, 'title')) ?></h3>
        <?php if ($pct === null): ?>
          <p style="color:var(--ink-soft);margin:0;font-size:.92rem;"><?= te('no_attendance_yet') ?></p>
        <?php else: ?>
          <div style="display:flex;align-items:baseline;gap:10px;margin-bottom:10px;">
            <span style="font-family:var(--serif);font-size:1.9rem;color:var(--navy);">
              <?= e(localize_digits(number_format($pct, 0))) ?>%
            </span>
            <span class="p-tag <?= e($tone) ?>"><?= te('attendance_percent') ?></span>
          </div>
          <div style="height:8px;background:var(--bg-tint);border-radius:999px;overflow:hidden;">
            <div style="height:100%;width:<?= (float) min(100, $pct) ?>%;background:var(--teal);"></div>
          </div>
          <div class="p-course-meta">
            <span><?= te('classes_held') ?>: <?= e(localize_digits((string) $c['held'])) ?></span>
            <span><?= te('classes_attended') ?>: <?= e(localize_digits((string) $c['attended'])) ?></span>
            <?php if ((int) $c['excused']): ?>
              <span><?= te('excused') ?>: <?= e(localize_digits((string) $c['excused'])) ?></span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($recent): ?>
  <section class="p-card">
    <h2><?= te('sessions') ?></h2>
    <div class="p-table-wrap">
      <table class="p-table">
        <thead><tr><th><?= te('attendance_date') ?></th><th><?= te('course_code') ?></th><th><?= te('topic') ?></th><th><?= te('attendance_percent') ?></th></tr></thead>
        <tbody>
          <?php foreach ($recent as $r):
              $tone = ['present' => 'ok', 'late' => 'pending', 'excused' => '', 'absent' => 'bad'][$r['status']] ?? ''; ?>
            <tr>
              <td class="nowrap"><?= e(format_date($r['held_on'])) ?></td>
              <td class="nowrap"><?= e($r['code']) ?></td>
              <td><?= e($r['topic'] ?: '—') ?></td>
              <td><span class="p-tag <?= e($tone) ?>"><?= te($r['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>
<?php endif; ?>
<?php layout_foot(); ?>
