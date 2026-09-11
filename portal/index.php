<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/layout.php';

if ($u = current_user()) {
    header('Location: ' . home_for($u));
    exit;
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email  = trim((string) ($_POST['email'] ?? ''));
    $result = attempt_login($email, (string) ($_POST['password'] ?? ''));

    if ($result['ok']) {
        flash('ok', t('welcome_back', display_name($result['user'])));
        $next = (string) ($_GET['next'] ?? '');
        // Only ever redirect within this site.
        $safe = (str_starts_with($next, '/') && !str_starts_with($next, '//'))
            ? $next : home_for($result['user']);
        header('Location: ' . $safe);
        exit;
    }

    $error = match ($result['error']) {
        'too_many'  => t('err_too_many'),
        'pending'   => t('err_pending'),
        'rejected'  => t('err_rejected'),
        'suspended' => t('err_suspended'),
        default     => t('err_bad_creds'),
    };
}

layout_head(['title' => t('sign_in'), 'nav' => []]);
?>
<div class="p-auth">
  <div class="p-card">
    <h1><?= te('login_title') ?></h1>
    <p class="p-auth-intro"><?= te('login_intro') ?></p>

    <?php if ($error): ?>
      <div class="p-flash p-flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="p-field">
        <label for="email"><?= te('email') ?></label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>"
               autocomplete="username" required autofocus>
      </div>

      <div class="p-field">
        <label for="password"><?= te('password') ?></label>
        <div class="p-pw-wrap">
          <input type="password" id="password" name="password" autocomplete="current-password" required>
          <button class="p-pw-toggle" type="button" data-toggle-password="password"
                  title="<?= te('show_password') ?>" aria-label="<?= te('show_password') ?>">👁</button>
        </div>
      </div>

      <div class="p-form-actions">
        <button class="p-btn p-btn-primary p-btn-block" type="submit"><?= te('sign_in') ?></button>
      </div>
    </form>

    <p class="p-auth-alt">
      <?= te('no_account') ?>
      <a href="<?= e(portal_url('/register.php')) ?>"><?= te('register_here') ?></a>
    </p>
  </div>
</div>
<?php layout_foot(); ?>
