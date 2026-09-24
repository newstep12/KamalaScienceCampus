<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/layout.php';

$user = require_login();

// Reached directly by someone who has already chosen a password: nothing to do.
if (empty($user['must_change_password'])) {
    header('Location: ' . home_for($user));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $new     = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if (!password_is_strong($new)) {
        $error = t('err_pw_weak');
    } elseif ($new !== $confirm) {
        $error = t('err_pw_match');
    } elseif (password_verify($new, $user['password_hash'])) {
        $error = t('err_pw_same');
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        q('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [$hash, $user['id']]);
        keep_session_after_password_change($hash);
        log_activity((int) $user['id'], 'first_password_set', $user['email']);
        flash('ok', t('password_changed'));
        header('Location: ' . home_for($user));
        exit;
    }
}

layout_head(['title' => t('set_password_title'), 'nav' => []]);
?>
<div class="p-auth">
  <div class="p-card">
    <h1><?= te('set_password_title') ?></h1>
    <p class="p-auth-intro"><?= te('set_password_intro') ?></p>

    <?php if ($error): ?>
      <div class="p-flash p-flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="p-field">
        <label for="new_password"><?= te('new_password') ?> <span class="hint"><?= te('password_rule') ?></span></label>
        <div class="p-pw-wrap">
          <input type="password" id="new_password" name="new_password" autocomplete="new-password" required autofocus>
          <button class="p-pw-toggle" type="button" data-toggle-password="new_password"
                  title="<?= te('show_password') ?>" aria-label="<?= te('show_password') ?>">👁</button>
        </div>
      </div>
      <div class="p-field">
        <label for="password_confirm"><?= te('confirm_password') ?></label>
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
      </div>
      <div class="p-form-actions">
        <button class="p-btn p-btn-primary p-btn-block" type="submit"><?= te('save') ?></button>
      </div>
    </form>

    <p class="p-auth-alt">
      <a href="<?= e(logout_url()) ?>"><?= te('sign_out') ?></a>
    </p>
  </div>
</div>
<?php layout_foot(); ?>
