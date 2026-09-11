<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

$user = require_role(ROLE_STUDENT);

// Only published assessments, and only for courses this student is enrolled in.
$rows = all(
    'SELECT c.id AS course_id, c.code, c.title_en, c.title_ne,
            a.id AS assessment_id, a.title AS assessment, a.kind, a.max_marks, a.assessed_on,
            m.marks_obtained, m.is_absent, m.remarks
       FROM enrolments e
       JOIN courses c     ON c.id = e.course_id
       JOIN assessments a ON a.course_id = c.id AND a.is_published = 1
       LEFT JOIN marks m  ON m.assessment_id = a.id AND m.user_id = e.user_id
      WHERE e.user_id = ?
      ORDER BY c.year_level, c.code, a.assessed_on DESC, a.id DESC',
    [$user['id']]
);

// Group by course, and total only the assessments actually marked.
$courses = [];
foreach ($rows as $r) {
    $cid = (int) $r['course_id'];
    if (!isset($courses[$cid])) {
        $courses[$cid] = ['course' => $r, 'items' => [], 'got' => 0.0, 'out_of' => 0.0];
    }
    $courses[$cid]['items'][] = $r;
    if ($r['marks_obtained'] !== null && !$r['is_absent']) {
        $courses[$cid]['got']    += (float) $r['marks_obtained'];
        $courses[$cid]['out_of'] += (float) $r['max_marks'];
    } elseif ($r['is_absent']) {
        $courses[$cid]['out_of'] += (float) $r['max_marks'];
    }
}

function num(string|float|null $v): string
{
    if ($v === null) {
        return '—';
    }
    return localize_digits(rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'));
}

layout_head(['title' => t('results_title'), 'active' => 'results']);
?>
<div class="p-page-head">
  <h1><?= te('results_title') ?></h1>
  <p><?= te('results_intro') ?></p>
</div>

<?php if (!$courses): ?>
  <div class="p-empty"><p><?= te('no_results_yet') ?></p></div>
<?php else: ?>
  <?php foreach ($courses as $c):
      $pct = $c['out_of'] > 0 ? ($c['got'] / $c['out_of']) * 100 : null; ?>
    <section class="p-card">
      <div class="p-section-head" style="margin-bottom:10px;">
        <div>
          <div class="p-course-code"><?= e($c['course']['code']) ?></div>
          <h2 style="margin:0;"><?= e(bilingual($c['course'], 'title')) ?></h2>
        </div>
        <?php if ($pct !== null): ?>
          <div style="text-align:end;">
            <div style="font-size:.76rem;letter-spacing:.09em;text-transform:uppercase;color:var(--ink-soft);"><?= te('overall') ?></div>
            <div style="font-family:var(--serif);font-size:1.5rem;color:var(--navy);">
              <?= e(num($c['got'])) ?> / <?= e(num($c['out_of'])) ?>
              <span style="font-size:.9rem;color:var(--teal-600);">(<?= e(localize_digits(number_format($pct, 1))) ?>%)</span>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="p-table-wrap">
        <table class="p-table">
          <thead>
            <tr>
              <th><?= te('assessment') ?></th><th><?= te('assessment_kind') ?></th>
              <th><?= te('assessed_on') ?></th><th><?= te('marks') ?></th>
              <th><?= te('percentage') ?></th><th><?= te('remarks') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($c['items'] as $i):
                $ipct = ($i['marks_obtained'] !== null && !$i['is_absent'] && (float) $i['max_marks'] > 0)
                    ? ((float) $i['marks_obtained'] / (float) $i['max_marks']) * 100 : null; ?>
              <tr>
                <td><strong><?= e($i['assessment']) ?></strong></td>
                <td class="nowrap"><?= te('kind_' . $i['kind']) ?></td>
                <td class="nowrap"><?= e(format_date($i['assessed_on'])) ?></td>
                <td class="nowrap">
                  <?php if ($i['is_absent']): ?>
                    <span class="p-tag bad"><?= te('absent') ?></span>
                  <?php elseif ($i['marks_obtained'] === null): ?>
                    <span style="color:var(--ink-soft);"><?= te('not_marked') ?></span>
                  <?php else: ?>
                    <?= e(num($i['marks_obtained'])) ?> / <?= e(num($i['max_marks'])) ?>
                  <?php endif; ?>
                </td>
                <td class="nowrap"><?= $ipct !== null ? e(localize_digits(number_format($ipct, 1))) . '%' : '—' ?></td>
                <td><?= e($i['remarks'] ?: '—') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
<?php layout_foot(); ?>
