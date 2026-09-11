<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

$user     = require_role(ROLE_STUDENT, ROLE_LECTURER);
$courseId = (int) ($_GET['id'] ?? 0);

// A student may only open a course they are actually enrolled in; a lecturer
// may open a course they teach.
$course = one(
    'SELECT c.*, u.full_name AS lecturer_name, u.full_name_ne AS lecturer_name_ne
       FROM courses c
       LEFT JOIN users u ON u.id = c.lecturer_id
      WHERE c.id = ?
        AND (EXISTS (SELECT 1 FROM enrolments e WHERE e.course_id = c.id AND e.user_id = ?)
             OR c.lecturer_id = ?)
      LIMIT 1',
    [$courseId, $user['id'], $user['id']]
);

if (!$course) {
    http_response_code(404);
    layout_head(['title' => t('notfound_title'), 'active' => 'courses']);
    echo '<div class="p-empty"><p>' . te('notfound_body') . '</p></div>';
    layout_foot();
    exit;
}

// --- student's own private notes -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_note') {
        $title = trim((string) ($_POST['note_title'] ?? ''));
        $body  = trim((string) ($_POST['note_body'] ?? ''));
        if ($title !== '') {
            q('INSERT INTO student_notes (user_id, course_id, title, body) VALUES (?, ?, ?, ?)',
              [$user['id'], $courseId, $title, $body]);
            flash('ok', t('note_saved'));
        }
    } elseif ($action === 'delete_note') {
        // Scoped by user_id so a crafted id cannot delete another student's note.
        q('DELETE FROM student_notes WHERE id = ? AND user_id = ?',
          [(int) ($_POST['note_id'] ?? 0), $user['id']]);
        flash('ok', t('note_deleted'));
    }
    header('Location: ' . portal_url('/student/course.php?id=' . $courseId));
    exit;
}

$materials = all(
    'SELECT m.*, u.full_name AS author, u.full_name_ne AS author_ne
       FROM materials m
       LEFT JOIN users u ON u.id = m.uploaded_by
      WHERE m.course_id = ?
      ORDER BY m.created_at DESC',
    [$courseId]
);

$myNotes = all(
    'SELECT * FROM student_notes WHERE user_id = ? AND course_id = ? ORDER BY updated_at DESC',
    [$user['id'], $courseId]
);

$lecturer = is_nepali() && $course['lecturer_name_ne'] ? $course['lecturer_name_ne'] : $course['lecturer_name'];

layout_head(['title' => bilingual($course, 'title'), 'active' => 'courses']);
?>
<a class="p-back" href="<?= e(portal_url('/student/courses.php')) ?>">← <?= te('courses_title') ?></a>

<div class="p-page-head">
  <div class="p-course-code"><?= e($course['code']) ?></div>
  <h1><?= e(bilingual($course, 'title')) ?></h1>
  <?php if ($desc = bilingual($course, 'description')): ?><p><?= e($desc) ?></p><?php endif; ?>
  <div class="p-course-meta" style="border:0;padding-top:10px;">
    <span><?= e(year_label((int) $course['year_level'])) ?></span>
    <?php if ($course['credit_hours']): ?>
      <span><?= e(localize_digits(rtrim(rtrim((string) $course['credit_hours'], '0'), '.'))) ?> <?= te('credit_hours') ?></span>
    <?php endif; ?>
    <span><?= te('taught_by') ?>: <?= e($lecturer ?: t('not_assigned')) ?></span>
  </div>
</div>

<section class="p-card">
  <h2><?= te('materials') ?></h2>
  <?php if (!$materials): ?>
    <p style="color:var(--ink-soft);margin:0;"><?= te('no_materials') ?></p>
  <?php else: ?>
    <ul class="p-list">
      <?php foreach ($materials as $m):
          $author = is_nepali() && $m['author_ne'] ? $m['author_ne'] : $m['author'];
          $icon = ['file' => '⤓', 'link' => '↗', 'note' => '✎'][$m['kind']] ?? '•';
      ?>
        <li class="p-item">
          <span class="p-icon <?= e($m['kind']) ?>" aria-hidden="true"><?= $icon ?></span>
          <div class="p-item-body">
            <h4><?= e($m['title']) ?></h4>
            <?php if ($m['description']): ?><p><?= e($m['description']) ?></p><?php endif; ?>
            <?php if ($m['kind'] === 'note' && $m['body']): ?>
              <div class="p-item-note"><?= e($m['body']) ?></div>
            <?php endif; ?>
            <div class="p-item-meta">
              <?= e(t('posted_on', format_date($m['created_at']))) ?>
              <?php if ($author): ?> · <?= e(t('uploaded_by', $author)) ?><?php endif; ?>
              <?php if ($m['kind'] === 'file' && $m['file_size']): ?> · <?= e(format_bytes((int) $m['file_size'])) ?><?php endif; ?>
            </div>
          </div>
          <div class="p-item-actions">
            <?php if ($m['kind'] === 'file' && $m['file_path']): ?>
              <a class="p-btn p-btn-ghost p-btn-sm"
                 href="<?= e(portal_url('/download.php?id=' . (int) $m['id'])) ?>"><?= te('download') ?></a>
            <?php elseif ($m['kind'] === 'link' && $m['link_url']): ?>
              <a class="p-btn p-btn-ghost p-btn-sm" href="<?= e($m['link_url']) ?>"
                 target="_blank" rel="noopener noreferrer nofollow"><?= te('open_link') ?></a>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<section class="p-card">
  <h2><?= te('my_notes') ?></h2>
  <p style="color:var(--ink-soft);font-size:.92rem;"><?= te('my_notes_intro') ?></p>

  <?php if ($myNotes): ?>
    <ul class="p-list" style="margin-bottom:22px;">
      <?php foreach ($myNotes as $n): ?>
        <li class="p-item">
          <span class="p-icon note" aria-hidden="true">✎</span>
          <div class="p-item-body">
            <h4><?= e($n['title']) ?></h4>
            <?php if ($n['body']): ?><div class="p-item-note"><?= e($n['body']) ?></div><?php endif; ?>
            <div class="p-item-meta"><?= e(format_date($n['updated_at'], true)) ?></div>
          </div>
          <div class="p-item-actions">
            <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_note">
              <input type="hidden" name="note_id" value="<?= (int) $n['id'] ?>">
              <button class="p-btn p-btn-danger p-btn-sm" type="submit"><?= te('delete') ?></button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_note">
    <div class="p-field">
      <label for="note_title"><?= te('note_title') ?></label>
      <input type="text" id="note_title" name="note_title" required>
    </div>
    <div class="p-field">
      <label for="note_body"><?= te('note_body') ?></label>
      <textarea id="note_body" name="note_body"></textarea>
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-gold" type="submit"><?= te('add_note') ?></button>
    </div>
  </form>
</section>
<?php layout_foot(); ?>
