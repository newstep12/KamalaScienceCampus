<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/uploads.php';
require_once __DIR__ . '/../inc/translate.php';

$user = require_login();
$year = (int) ($user['year_level'] ?? 0);
$cat  = (string) ($_GET['cat'] ?? '');
$cats = ['tu', 'exam', 'scholarship', 'ugc', 'campus'];

$sql    = 'SELECT n.*, u.full_name AS author FROM notices n
             LEFT JOIN users u ON u.id = n.created_by
            WHERE n.is_published = 1';
$params = [];

// Students only see notices for their year or for everyone; staff see all.
if ($user['role'] === ROLE_STUDENT) {
    $sql .= ' AND (n.year_level IS NULL OR n.year_level = ?)';
    $params[] = $year ?: null;
}
if (in_array($cat, $cats, true)) {
    $sql .= ' AND n.category = ?';
    $params[] = $cat;
}
$sql .= ' ORDER BY n.is_pinned DESC, n.published_at DESC LIMIT 100';

$notices = all($sql, $params);

layout_head(['title' => t('notices_title'), 'active' => 'notices']);
?>
<div class="p-page-head">
  <h1><?= te('notices_title') ?></h1>
  <p><?= te('notices_intro') ?></p>
</div>

<nav class="p-filters" aria-label="<?= te('category') ?>">
  <a href="?" <?= $cat === '' ? 'aria-current="true"' : '' ?>><?= te('all_categories') ?></a>
  <?php foreach ($cats as $c): ?>
    <a href="?cat=<?= e($c) ?>" <?= $cat === $c ? 'aria-current="true"' : '' ?>><?= te('cat_' . $c) ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$notices): ?>
  <div class="p-empty"><p><?= te('no_notices_yet') ?></p></div>
<?php else: ?>
  <?php foreach ($notices as $n): ?>
    <article class="p-notice<?= $n['is_pinned'] ? ' pinned' : '' ?>">
      <div class="p-notice-head">
        <span class="p-tag <?= e($n['category']) ?>"><?= te('cat_' . $n['category']) ?></span>
        <?php if ($n['is_pinned']): ?><span class="p-tag pin"><?= te('pinned') ?></span><?php endif; ?>
        <?php if ($n['year_level']): ?>
          <span class="p-tag year"><?= e(year_label((int) $n['year_level'])) ?></span>
        <?php endif; ?>
        <span class="p-item-meta"><?= e(format_date($n['published_at'])) ?></span>
      </div>

      <h3><?= e(bilingual($n, 'title')) ?></h3>
      <?php if ($body = bilingual($n, 'body')): ?>
        <div class="p-notice-body"><?= e($body) ?></div>
      <?php endif; ?>

      <?php
      $kind = $n['file_path'] ? upload_kind($n['file_path']) : '';
      $file = portal_url('/download.php?notice=' . (int) $n['id']);
      ?>
      <?php if ($n['source_url'] || $n['file_path']): ?>
        <div class="p-form-actions">
          <?php if ($n['file_path']): ?>
            <?php // An attached notice is meant to be read, so opening it comes
                  // first and ?view= asks download.php to send it inline. ?>
            <?php if ($kind !== 'file'): ?>
              <a class="p-btn p-btn-primary p-btn-sm" href="<?= e($file) ?>&amp;view=1"
                 target="_blank" rel="noopener"><?= te('open_attachment') ?> ↗</a>
            <?php endif; ?>
            <a class="p-btn p-btn-ghost p-btn-sm" href="<?= e($file) ?>"><?= te('attachment') ?> ⤓</a>
          <?php endif; ?>
          <?php if ($n['source_url']): ?>
            <a class="p-btn p-btn-ghost p-btn-sm" href="<?= e($n['source_url']) ?>"
               target="_blank" rel="noopener noreferrer nofollow"><?= te('read_source') ?> ↗</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($kind === 'pdf' || $kind === 'image'): ?>
        <details class="p-preview" data-preview-src="<?= e($file) ?>&amp;view=1">
          <summary><?= te('read_it_here') ?></summary>
          <div class="p-preview-frame">
            <?php if ($kind === 'image'): ?>
              <img src="<?= e($file) ?>&amp;view=1" alt="<?= e(bilingual($n, 'title')) ?>" loading="lazy">
            <?php else: ?>
              <iframe title="<?= e(bilingual($n, 'title')) ?>" loading="lazy"></iframe>
              <noscript><p><a href="<?= e($file) ?>&amp;view=1" target="_blank"
                 rel="noopener"><?= te('open_attachment') ?> ↗</a></p></noscript>
            <?php endif; ?>
          </div>
        </details>
      <?php endif; ?>

      <?php if (notice_is_machine_translated($n)): ?>
        <p class="p-auto-note"><?= te('auto_translated_note') ?></p>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
<?php endif; ?>
<?php layout_foot(); ?>
