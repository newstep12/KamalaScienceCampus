<?php
require_once __DIR__ . '/inc/layout.php';
layout_head(['title' => t('forbidden_title')]);
?>
<div class="p-auth">
  <div class="p-card" style="text-align:center;">
    <h1><?= te('forbidden_title') ?></h1>
    <p class="p-auth-intro"><?= te('forbidden_body') ?></p>
    <?php if ($u = current_user()): ?>
      <a class="p-btn p-btn-primary" href="<?= e(home_for($u)) ?>"><?= te('go_back') ?></a>
    <?php else: ?>
      <a class="p-btn p-btn-primary" href="<?= e(portal_url('/index.php')) ?>"><?= te('sign_in') ?></a>
    <?php endif; ?>
  </div>
</div>
<?php layout_foot(); ?>
