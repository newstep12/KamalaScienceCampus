<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/mail.php';
require_once __DIR__ . '/../inc/idcard.php';

$admin = require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id     = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // An admin must never be able to lock themselves out.
    // Nor reset their own password here: that would sign them out with the
    // temporary one still unread. Their own is changed from My portfolio.
    if ($id === (int) $admin['id'] && in_array($action, ['suspend', 'set_role', 'reset_password'], true)) {
        flash('error', t('forbidden_body'));
        header('Location: ' . portal_url('/admin/users.php'));
        exit;
    }

    if ($action === 'create_staff') {
        $name  = trim((string) ($_POST['full_name'] ?? ''));
        $nameNe= trim((string) ($_POST['full_name_ne'] ?? '')) ?: null;
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? '')) ?: null;
        $role  = in_array($_POST['role'] ?? '', ['teacher', 'admin'], true) ? $_POST['role'] : 'teacher';
        $desig = in_array($_POST['designation'] ?? '', designations(), true) ? $_POST['designation'] : null;
        // An administrator may set the password themselves — one they can say
        // over the phone, which a generated one is not. Left empty, one is
        // generated as before. Either way it is temporary: must_change_password
        // holds the account on the change-password page until its owner picks
        // their own, so an administrator never keeps a working password.
        // Trimmed, like every other field on this form. A password with a
        // space on the end is a password nobody can retype, and the copy
        // button beside it trims — so an untrimmed one would hash to
        // something the copied value does not match.
        $chosen = trim((string) ($_POST['password'] ?? ''));

        if (mb_strlen($name) < 3) {
            flash('error', t('err_name_short'));
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', t('err_email_bad'));
        } elseif ($chosen !== '' && !password_is_strong($chosen)) {
            flash('error', t('err_pw_weak'));
        } elseif (one('SELECT id FROM users WHERE email = ?', [$email])) {
            flash('error', t('err_email_taken'));
        } else {
            $temp = $chosen !== '' ? $chosen : temporary_password();
            q('INSERT INTO users (full_name, full_name_ne, email, password_hash, role, status,
                      phone, designation, approved_at, approved_by, must_change_password)
               VALUES (?, ?, ?, ?, ?, \'active\', ?, ?, NOW(), ?, 1)',
              [$name, $nameNe, $email, password_hash($temp, PASSWORD_DEFAULT), $role, $phone, $desig, $admin['id']]);

            log_activity((int) $admin['id'], 'create_staff', $email, $role);
            $sent = send_notification(
                $email,
                'Your Shree Kamala Secondary School +2 portal account',
                "Dear {$name},\n\nAn account has been created for you on the Shree Kamala Secondary School +2 Science portal.\n\n"
                . "Sign in at: " . portal_absolute_url('/index.php') . "\n"
                . "Email:    {$email}\n"
                . "Password: {$temp}\n\n"
                . "You will be asked to choose your own password the first time you sign in.\n\n"
                . "Shree Kamala Secondary School\n"
            );
            // The password goes into its own panel rather than into the
            // sentence, so it can be copied whole.
            stash_credentials($name, $email, $temp, $sent, mail_enabled());
            flash('ok', t('staff_created', $name));
        }
        header('Location: ' . portal_url('/admin/users.php'));
        exit;
    }

    if ($action === 'promote_class_11') {
        // The start of a new session: every active class 11 student becomes a
        // class 12 student. Roll numbers are cleared with it, because the
        // school numbers each class afresh and last year's roll number
        // printed on this year's card would be wrong. Class 12 leavers should
        // be suspended first, so they are not mixed in with the new class 12.
        $moved = q(
            'UPDATE users SET class_level = 12, roll_no = NULL
              WHERE role = \'student\' AND status = \'active\' AND class_level = 11'
        )->rowCount();
        log_activity((int) $admin['id'], 'promote_class_11', (string) $moved);
        flash('ok', t('promoted_ok', localize_digits((string) $moved)));
        header('Location: ' . portal_url('/admin/users.php?role=student&class=12'));
        exit;
    }

    $target = one('SELECT * FROM users WHERE id = ?', [$id]);
    if ($target) {
        if ($action === 'suspend') {
            q('UPDATE users SET status = \'suspended\' WHERE id = ?', [$id]);
            log_activity((int) $admin['id'], 'suspend_user', $target['email']);
        } elseif ($action === 'reactivate') {
            q('UPDATE users SET status = \'active\', approved_at = NOW(), approved_by = ? WHERE id = ?', [$admin['id'], $id]);
            assign_student_no($id);       // a no-op for staff and for anyone who has one
            log_activity((int) $admin['id'], 'reactivate_user', $target['email']);
        } elseif ($action === 'set_role') {
            $role = in_array($_POST['role'] ?? '', ['student', 'teacher', 'admin'], true) ? $_POST['role'] : null;
            if ($role) {
                q('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
                if ($target['status'] === 'active') {
                    assign_student_no($id);   // only does anything when the new role is student
                }
                log_activity((int) $admin['id'], 'set_role', $target['email'], $role);
            }
        } elseif ($action === 'reset_password') {
            $temp = temporary_password();
            q('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?',
              [password_hash($temp, PASSWORD_DEFAULT), $id]);
            log_activity((int) $admin['id'], 'reset_password', $target['email']);
            $sent = send_notification(
                (string) $target['email'],
                'Your Shree Kamala Secondary School +2 portal password has been reset',
                "Dear {$target['full_name']},\n\nYour portal password has been reset by the school office.\n\n"
                . "Sign in at: " . portal_absolute_url('/index.php') . "\n"
                . "Email:    {$target['email']}\n"
                . "Password: {$temp}\n\n"
                . "You will be asked to choose your own password the next time you sign in.\n\n"
                . "Shree Kamala Secondary School\n"
            );
            stash_credentials((string) $target['full_name'], (string) $target['email'], $temp, $sent, mail_enabled());
            flash('ok', t('password_reset_to', $target['full_name']));
            // Its own message, and the panel below it. The generic "Saved."
            // that every other action here falls through to would be a third
            // success banner above the one thing worth reading.
            header('Location: ' . portal_url('/admin/users.php'));
            exit;
        } elseif ($action === 'set_designation') {
            $desig = in_array($_POST['designation'] ?? '', designations(), true) ? $_POST['designation'] : null;
            q('UPDATE users SET designation = ? WHERE id = ?', [$desig, $id]);
            log_activity((int) $admin['id'], 'set_designation', $target['email'], (string) $desig);
        } elseif ($action === 'set_class') {
            $class = (int) ($_POST['class_level'] ?? 0);
            q('UPDATE users SET class_level = ? WHERE id = ?',
              [in_array($class, class_levels(), true) ? $class : null, $id]);
            log_activity((int) $admin['id'], 'set_class', $target['email'], (string) $class);
        } elseif ($action === 'set_group') {
            $group = study_group(is_string($_POST['study_group'] ?? null) ? $_POST['study_group'] : null);
            q('UPDATE users SET study_group = ? WHERE id = ?', [$group, $id]);
            log_activity((int) $admin['id'], 'set_group', $target['email'], (string) $group);
        } elseif ($action === 'set_roll') {
            $roll = mb_substr(ascii_digits(trim((string) ($_POST['roll_no'] ?? ''))), 0, 20);
            q('UPDATE users SET roll_no = ? WHERE id = ?', [$roll !== '' ? $roll : null, $id]);
            log_activity((int) $admin['id'], 'set_roll', $target['email'], $roll);
        }
        flash('ok', t('user_updated'));
    }
    header('Location: ' . portal_url('/admin/users.php?' . http_build_query(array_filter([
        'role' => $_GET['role'] ?? null, 'class' => $_GET['class'] ?? null,
        'group' => $_GET['group'] ?? null, 'q' => $_GET['q'] ?? null,
    ]))));
    exit;
}

$role   = (string) ($_GET['role'] ?? '');
$class  = (int) ($_GET['class'] ?? 0);
$group  = study_group(is_string($_GET['group'] ?? null) ? $_GET['group'] : null);
$search = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT * FROM users WHERE 1 = 1';
$par = [];
if (in_array($role, ['student', 'teacher', 'admin'], true)) { $sql .= ' AND role = ?';        $par[] = $role; }
if (in_array($class, class_levels(), true))                 { $sql .= ' AND class_level = ?'; $par[] = $class; }
if ($group !== null)                                        { $sql .= ' AND study_group = ?'; $par[] = $group; }
if ($search !== '') {
    $sql .= ' AND (full_name LIKE ? OR full_name_ne LIKE ? OR email LIKE ? OR roll_no LIKE ? OR guardian_name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($par, $like, $like, $like, $like, $like);
}
// Students by class, section and roll number — the order the office reads a
// class register in — then staff by name.
$sql .= ' ORDER BY role, class_level, section, CAST(roll_no AS UNSIGNED), roll_no, full_name LIMIT 600';
$users = all($sql, $par);

layout_head(['title' => t('manage_people'), 'active' => 'users', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('manage_people') ?></h1>
</div>

<?php /* Shown once, straight after an account is made or a password reset.
         The value stands on its own line, in a monospace face that tells I
         from l and 0 from O, with a button that copies it — because the one
         way this goes wrong is a character lost between here and the person
         who has to type it. */ ?>
<?php if ($creds = take_credentials()): ?>
  <section class="p-creds" role="status">
    <h2><?= te('creds_title') ?></h2>
    <p><?= e(t('creds_intro', $creds['name'])) ?></p>
    <dl>
      <div>
        <dt><?= te('email') ?></dt>
        <dd><code id="creds-email"><?= e($creds['email']) ?></code></dd>
      </div>
      <div>
        <dt><?= te('password') ?></dt>
        <dd>
          <code id="creds-pw"><?= e($creds['password']) ?></code>
          <button class="p-btn p-btn-ghost p-btn-sm" type="button"
                  data-copy="creds-pw" data-copied="<?= te('copied') ?>"><?= te('copy') ?></button>
        </dd>
      </div>
    </dl>
    <p class="hint">
      <?php if ($creds['emailed']): ?>
        <?= te('creds_emailed') ?>
      <?php elseif (empty($creds['mail_on'])): ?>
        <?= te('creds_not_emailed') ?>
      <?php else: ?>
        <?php /* Notifications are on and it still did not go: an invalid
                 from-address, or the host refusing the send. Saying "they are
                 off" would send the admin to check a setting that is right. */ ?>
        <?= te('creds_send_failed') ?>
      <?php endif; ?>
    </p>
  </section>
<?php endif; ?>

<section class="p-card" style="margin-bottom:24px;">
  <h2><?= te('add_staff') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('add_staff_intro') ?></p>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create_staff">
    <div class="p-field-row">
      <div class="p-field">
        <label for="s_name"><?= te('full_name_en') ?></label>
        <input type="text" id="s_name" name="full_name" required>
      </div>
      <div class="p-field">
        <label for="s_name_ne"><?= te('full_name_ne') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="text" id="s_name_ne" name="full_name_ne" lang="ne">
      </div>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="s_email"><?= te('email') ?></label>
        <input type="email" id="s_email" name="email" required autocomplete="off">
      </div>
      <div class="p-field">
        <label for="s_phone"><?= te('phone') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="tel" id="s_phone" name="phone">
      </div>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="s_role"><?= te('role') ?></label>
        <select id="s_role" name="role">
          <option value="teacher"><?= te('role_teacher') ?></option>
          <option value="admin"><?= te('role_admin') ?></option>
        </select>
      </div>
      <div class="p-field">
        <label for="s_desig"><?= te('designation') ?> <span class="hint"><?= te('designation_hint') ?></span></label>
        <select id="s_desig" name="designation">
          <option value="">—</option>
          <?php foreach (designations() as $d): ?>
            <option value="<?= e($d) ?>"><?= e(designation_label($d)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="p-field" style="max-width:420px;">
      <label for="s_pw"><?= te('first_password') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="text" id="s_pw" name="password" autocomplete="off" spellcheck="false"
             placeholder="<?= te('first_password_placeholder') ?>">
      <span class="hint"><?= te('first_password_hint') ?></span>
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('create_account') ?></button>
    </div>
  </form>
</section>

<section class="p-card" style="margin-bottom:24px;">
  <h2><?= te('promote_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('promote_intro') ?></p>
  <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="promote_class_11">
    <button class="p-btn p-btn-gold" type="submit"><?= te('promote_run') ?></button>
  </form>
</section>

<form method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin-bottom:20px;">
  <div class="p-field" style="margin:0;min-width:180px;">
    <label for="q"><?= te('search') ?></label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>">
  </div>
  <div class="p-field" style="margin:0;min-width:150px;">
    <label for="role"><?= te('role') ?></label>
    <select id="role" name="role">
      <option value=""><?= te('all_categories') ?></option>
      <?php foreach (['student', 'teacher', 'admin'] as $r): ?>
        <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= te('role_' . $r) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="p-field" style="margin:0;min-width:140px;">
    <label for="class"><?= te('class') ?></label>
    <select id="class" name="class">
      <option value="0"><?= te('all_classes') ?></option>
      <?php foreach (class_levels() as $c): ?>
        <option value="<?= $c ?>" <?= $class === $c ? 'selected' : '' ?>><?= e(class_label($c)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="p-field" style="margin:0;min-width:170px;">
    <label for="group"><?= te('study_group') ?></label>
    <select id="group" name="group">
      <option value=""><?= te('all_groups') ?></option>
      <?php foreach (study_groups() as $g): ?>
        <option value="<?= e($g) ?>" <?= $group === $g ? 'selected' : '' ?>><?= e(study_group_label($g)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="p-btn p-btn-ghost" type="submit"><?= te('search') ?></button>
</form>

<?php if (!$users): ?>
  <div class="p-empty"><p><?= te('none_yet') ?></p></div>
<?php else: ?>
  <div class="p-table-wrap">
    <table class="p-table">
      <thead>
        <tr>
          <th><?= te('full_name') ?></th>
          <th><?= te('email') ?></th>
          <th><?= te('role') ?></th>
          <th><?= te('year_or_title') ?></th>
          <th><?= te('status_active') ?></th>
          <th><?= te('actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u):
            $tagClass = ['active' => 'ok', 'pending' => 'pending', 'rejected' => 'bad', 'suspended' => 'bad'][$u['status']] ?? ''; ?>
          <tr>
            <td>
              <strong><?= e($u['full_name']) ?></strong>
              <?php if ($u['role'] === 'student' && $u['guardian_name']): ?>
                <div style="font-size:.82rem;color:var(--ink-soft);"><?= e(t('guardian_short', $u['guardian_name'])) ?><?= $u['guardian_phone'] ? ' · ' . e($u['guardian_phone']) : '' ?></div>
              <?php endif; ?>
            </td>
            <td><?= e($u['email']) ?></td>
            <td>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="action" value="set_role">
                <select name="role" onchange="this.form.submit()" style="min-width:118px;" aria-label="<?= te('role') ?>" <?= (int) $u['id'] === (int) $admin['id'] ? 'disabled' : '' ?>>
                  <?php foreach (['student', 'teacher', 'admin'] as $r): ?>
                    <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= te('role_' . $r) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td>
              <?php if ($u['role'] === 'student'): ?>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <input type="hidden" name="action" value="set_class">
                  <select name="class_level" onchange="this.form.submit()" aria-label="<?= te('class') ?>">
                    <option value="0">—</option>
                    <?php foreach (class_levels() as $c): ?>
                      <option value="<?= $c ?>" <?= (int) $u['class_level'] === $c ? 'selected' : '' ?>><?= e(class_label($c)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
                <form method="post" style="margin-top:6px;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <input type="hidden" name="action" value="set_group">
                  <select name="study_group" onchange="this.form.submit()" aria-label="<?= te('study_group') ?>">
                    <option value="">—</option>
                    <?php foreach (study_groups() as $g): ?>
                      <option value="<?= e($g) ?>" <?= ($u['study_group'] ?? '') === $g ? 'selected' : '' ?>><?= e(study_group_label($g)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
                <form method="post" style="display:flex;gap:6px;margin-top:6px;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <input type="hidden" name="action" value="set_roll">
                  <input type="text" name="roll_no" value="<?= e($u['roll_no'] ?? '') ?>" maxlength="20"
                         inputmode="numeric" placeholder="<?= te('roll_no') ?>" aria-label="<?= te('roll_no') ?>" style="width:90px;">
                  <button class="p-btn p-btn-ghost p-btn-sm" type="submit"><?= te('save') ?></button>
                </form>
              <?php else: ?>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <input type="hidden" name="action" value="set_designation">
                  <select name="designation" onchange="this.form.submit()">
                    <option value="">—</option>
                    <?php foreach (designations() as $d): ?>
                      <option value="<?= e($d) ?>" <?= ($u['designation'] ?? '') === $d ? 'selected' : '' ?>>
                        <?= e(designation_label($d)) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              <?php endif; ?>
            </td>
            <td><span class="p-tag <?= e($tagClass) ?>"><?= te('status_' . $u['status']) ?></span></td>
            <td class="nowrap">
              <a class="p-btn p-btn-ghost p-btn-sm" href="<?= e(portal_url('/admin/edit.php?user=' . (int) $u['id'])) ?>"><?= te('edit') ?></a>
              <?php if ($u['status'] === 'active'): ?>
                <a class="p-btn p-btn-ghost p-btn-sm" href="<?= e(portal_url('/id-card.php?user=' . (int) $u['id'])) ?>">
                  <?= te('print_id_card') ?>
                </a>
              <?php endif; ?>
              <?php if ((int) $u['id'] !== (int) $admin['id']): ?>
                <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <button class="p-btn p-btn-ghost p-btn-sm" name="action" value="reset_password"><?= te('reset_password') ?></button>
                </form>
              <?php endif; ?>
              <?php if ($u['status'] === 'active' && (int) $u['id'] !== (int) $admin['id']): ?>
                <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <button class="p-btn p-btn-danger p-btn-sm" name="action" value="suspend"><?= te('suspend') ?></button>
                </form>
              <?php elseif (in_array($u['status'], ['suspended', 'rejected'], true)): ?>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <button class="p-btn p-btn-ghost p-btn-sm" name="action" value="reactivate"><?= te('reactivate') ?></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php layout_foot(); ?>
