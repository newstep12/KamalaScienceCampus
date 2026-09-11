<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (($_POST['form'] ?? '') === 'profile') {
        q(
            'UPDATE users SET full_name = ?, full_name_ne = ?, phone = ?, address = ?,
                              date_of_birth = ?, bio = ?
              WHERE id = ?',
            [
                trim((string) $_POST['full_name']) ?: $user['full_name'],
                trim((string) $_POST['full_name_ne']) ?: null,
                trim((string) $_POST['phone']) ?: null,
                trim((string) $_POST['address']) ?: null,
                trim((string) $_POST['date_of_birth']) ?: null,
                trim((string) $_POST['bio']) ?: null,
                $user['id'],
            ]
        );
        flash('ok', t('profile_saved'));
        header('Location: ' . portal_url('/student/portfolio.php'));
        exit;
    }

    if (($_POST['form'] ?? '') === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if (!password_verify($current, $user['password_hash'])) {
            flash('error', t('err_current_pw'));
        } elseif (mb_strlen($new) < 8 || !preg_match('/\p{L}/u', $new) || !preg_match('/\d/', $new)) {
            flash('error', t('err_pw_weak'));
        } elseif ($new !== $confirm) {
            flash('error', t('err_pw_match'));
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?',
              [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            log_activity((int) $user['id'], 'password_change', $user['email']);
            flash('ok', t('password_changed'));
        }
        header('Location: ' . portal_url('/student/portfolio.php'));
        exit;
    }
}

layout_head(['title' => t('portfolio_title'), 'active' => 'portfolio']);
?>
<div class="p-page-head">
  <h1><?= te('portfolio_title') ?></h1>
  <p><?= te('portfolio_intro') ?></p>
</div>

<div class="p-grid p-grid-2" style="align-items:start;">
  <div>
    <form class="p-card" method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="profile">
      <h2><?= te('personal_details') ?></h2>

      <div class="p-field">
        <label for="full_name"><?= te('full_name') ?></label>
        <input type="text" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required>
      </div>

      <div class="p-field">
        <label for="full_name_ne"><?= te('full_name_ne') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="text" id="full_name_ne" name="full_name_ne" value="<?= e($user['full_name_ne']) ?>" lang="ne">
      </div>

      <div class="p-field">
        <label for="email"><?= te('email') ?></label>
        <input type="email" id="email" value="<?= e($user['email']) ?>" disabled>
      </div>

      <div class="p-field-row">
        <div class="p-field">
          <label for="phone"><?= te('phone') ?></label>
          <input type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>">
        </div>
        <div class="p-field">
          <label for="date_of_birth"><?= te('date_of_birth') ?></label>
          <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($user['date_of_birth']) ?>">
        </div>
      </div>

      <div class="p-field">
        <label for="address"><?= te('address') ?></label>
        <input type="text" id="address" name="address" value="<?= e($user['address']) ?>">
      </div>

      <div class="p-field">
        <label for="bio"><?= te('about_me') ?> <span class="hint"><?= te('about_me_hint') ?></span></label>
        <textarea id="bio" name="bio"><?= e($user['bio']) ?></textarea>
      </div>

      <div class="p-form-actions">
        <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
      </div>
    </form>

    <form class="p-card" method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="password">
      <h2><?= te('change_password') ?></h2>

      <div class="p-field">
        <label for="current_password"><?= te('current_password') ?></label>
        <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
      </div>
      <div class="p-field">
        <label for="new_password"><?= te('new_password') ?> <span class="hint"><?= te('password_rule') ?></span></label>
        <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
      </div>
      <div class="p-field">
        <label for="password_confirm"><?= te('confirm_password') ?></label>
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
      </div>

      <div class="p-form-actions">
        <button class="p-btn p-btn-ghost" type="submit"><?= te('change_password') ?></button>
      </div>
    </form>
  </div>

  <aside class="p-card">
    <h2><?= te('academic_details') ?></h2>
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:22px;">
      <span class="p-avatar" style="width:54px;height:54px;font-size:1.1rem;" aria-hidden="true"><?= e(initials($user['full_name'])) ?></span>
      <div>
        <div style="font-weight:600;color:var(--navy);"><?= e(display_name($user)) ?></div>
        <div style="font-size:.86rem;color:var(--ink-soft);"><?= e(role_label($user)) ?></div>
      </div>
    </div>

    <table class="p-table" style="border:0;">
      <tbody>
        <?php if ($user['role'] === ROLE_STUDENT): ?>
        <tr><th scope="row"><?= te('year_of_study') ?></th>
            <td><?= e($user['year_level'] ? year_label((int) $user['year_level']) : '—') ?></td></tr>
        <tr><th scope="row"><?= te('symbol_no') ?></th><td><?= e($user['symbol_no'] ?: '—') ?></td></tr>
        <?php endif; ?>
        <tr><th scope="row"><?= te('status_active') ?></th>
            <td><span class="p-tag ok"><?= te('status_' . $user['status']) ?></span></td></tr>
        <tr><th scope="row"><?= te('member_since') ?></th><td><?= e(format_date($user['created_at'])) ?></td></tr>
        <tr><th scope="row"><?= te('last_signed_in') ?></th><td><?= e(format_date($user['last_login_at'], true)) ?></td></tr>
      </tbody>
    </table>
  </aside>
</div>
<?php layout_foot(); ?>
