<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/layout.php';

if ($u = current_user()) {
    header('Location: ' . home_for($u));
    exit;
}

$errors = [];
$done   = false;
$in = [
    'full_name'    => '', 'full_name_ne' => '', 'email' => '',
    'year_level'   => '', 'symbol_no'    => '', 'phone' => '',
];

function password_is_strong(string $pw): bool
{
    return mb_strlen($pw) >= 8 && preg_match('/\p{L}/u', $pw) && preg_match('/\d/', $pw);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach (array_keys($in) as $k) {
        $in[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['password_confirm'] ?? '');
    $email    = strtolower($in['email']);
    $year     = (int) $in['year_level'];

    if (mb_strlen($in['full_name']) < 3)                      { $errors['full_name'] = t('err_name_short'); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))           { $errors['email'] = t('err_email_bad'); }
    if ($year < 1 || $year > 4)                               { $errors['year_level'] = t('err_year_bad'); }
    if ($in['symbol_no'] === '')                              { $errors['symbol_no'] = t('err_symbol'); }
    if (!password_is_strong($password))                       { $errors['password'] = t('err_pw_weak'); }
    if ($password !== $confirm)                               { $errors['password_confirm'] = t('err_pw_match'); }

    if (!$errors && one('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors['email'] = t('err_email_taken');
    }

    if (!$errors) {
        q(
            'INSERT INTO users (full_name, full_name_ne, email, password_hash, role, status,
                                year_level, symbol_no, phone)
             VALUES (?, ?, ?, ?, \'student\', \'pending\', ?, ?, ?)',
            [
                $in['full_name'],
                $in['full_name_ne'] !== '' ? $in['full_name_ne'] : null,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $year,
                $in['symbol_no'],
                $in['phone'] !== '' ? $in['phone'] : null,
            ]
        );
        log_activity(null, 'register', $email, 'Year ' . $year);
        $done = true;
    }
}

layout_head(['title' => t('reg_title'), 'nav' => []]);
?>
<div class="p-auth">
  <div class="p-card">
  <?php if ($done): ?>
    <h1><?= te('reg_done_title') ?></h1>
    <p class="p-auth-intro"><?= e(t('reg_done_body', $in['full_name'])) ?></p>
    <a class="p-btn p-btn-primary p-btn-block" href="<?= e(portal_url('/index.php')) ?>"><?= te('sign_in') ?></a>
  <?php else: ?>
    <h1><?= te('reg_title') ?></h1>
    <p class="p-auth-intro"><?= te('reg_intro') ?></p>

    <?php if ($errors): ?>
      <div class="p-flash p-flash-error" role="alert"><?= e(reset($errors)) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>

      <div class="p-field <?= isset($errors['full_name']) ? 'error' : '' ?>">
        <label for="full_name"><?= te('full_name') ?></label>
        <input type="text" id="full_name" name="full_name" value="<?= e($in['full_name']) ?>" autocomplete="name" required>
        <?php if (isset($errors['full_name'])): ?><span class="err"><?= e($errors['full_name']) ?></span><?php endif; ?>
      </div>

      <div class="p-field">
        <label for="full_name_ne"><?= te('full_name_ne') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="text" id="full_name_ne" name="full_name_ne" value="<?= e($in['full_name_ne']) ?>" lang="ne">
      </div>

      <div class="p-field <?= isset($errors['email']) ? 'error' : '' ?>">
        <label for="email"><?= te('email') ?></label>
        <input type="email" id="email" name="email" value="<?= e($in['email']) ?>" autocomplete="email" required>
        <?php if (isset($errors['email'])): ?><span class="err"><?= e($errors['email']) ?></span><?php endif; ?>
      </div>

      <div class="p-field-row">
        <div class="p-field <?= isset($errors['year_level']) ? 'error' : '' ?>">
          <label for="year_level"><?= te('year_of_study') ?></label>
          <select id="year_level" name="year_level" required>
            <option value=""><?= te('choose_year') ?></option>
            <?php foreach ([1, 2, 3, 4] as $y): ?>
              <option value="<?= $y ?>" <?= (int) $in['year_level'] === $y ? 'selected' : '' ?>>
                <?= e(year_label($y)) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['year_level'])): ?><span class="err"><?= e($errors['year_level']) ?></span><?php endif; ?>
        </div>

        <div class="p-field">
          <label for="phone"><?= te('phone') ?> <span class="hint"><?= te('optional') ?></span></label>
          <input type="tel" id="phone" name="phone" value="<?= e($in['phone']) ?>" autocomplete="tel">
        </div>
      </div>

      <div class="p-field <?= isset($errors['symbol_no']) ? 'error' : '' ?>">
        <label for="symbol_no"><?= te('symbol_no') ?></label>
        <input type="text" id="symbol_no" name="symbol_no" value="<?= e($in['symbol_no']) ?>" required>
        <?php if (isset($errors['symbol_no'])): ?><span class="err"><?= e($errors['symbol_no']) ?></span><?php endif; ?>
      </div>

      <div class="p-field <?= isset($errors['password']) ? 'error' : '' ?>">
        <label for="password"><?= te('password') ?> <span class="hint"><?= te('password_rule') ?></span></label>
        <div class="p-pw-wrap">
          <input type="password" id="password" name="password" autocomplete="new-password" required>
          <button class="p-pw-toggle" type="button" data-toggle-password="password"
                  title="<?= te('show_password') ?>" aria-label="<?= te('show_password') ?>">👁</button>
        </div>
        <?php if (isset($errors['password'])): ?><span class="err"><?= e($errors['password']) ?></span><?php endif; ?>
      </div>

      <div class="p-field <?= isset($errors['password_confirm']) ? 'error' : '' ?>">
        <label for="password_confirm"><?= te('confirm_password') ?></label>
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
        <?php if (isset($errors['password_confirm'])): ?><span class="err"><?= e($errors['password_confirm']) ?></span><?php endif; ?>
      </div>

      <div class="p-form-actions">
        <button class="p-btn p-btn-primary p-btn-block" type="submit"><?= te('reg_submit') ?></button>
      </div>
    </form>

    <p class="p-auth-alt">
      <?= te('have_account') ?>
      <a href="<?= e(portal_url('/index.php')) ?>"><?= te('sign_in') ?></a>
    </p>
  <?php endif; ?>
  </div>
</div>
<?php layout_foot(); ?>
