<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/mail.php';
require_once __DIR__ . '/inc/uploads.php';
require_once __DIR__ . '/inc/photos.php';

if ($u = current_user()) {
    header('Location: ' . home_for($u));
    exit;
}

$errors = [];
$done   = false;
$in = [
    'full_name'     => '', 'full_name_ne' => '', 'email' => '',
    'year_level'    => '', 'symbol_no'    => '', 'phone' => '',
    'date_of_birth' => '', 'address'      => '',
];

/**
 * A date of birth that could belong to a campus student: a real calendar
 * date, in the past, and not so far back that it is plainly a typo.
 */
function birth_date_is_plausible(string $date): bool
{
    $age = (new DateTime('today'))->diff(new DateTime($date))->y;
    return $date <= date('Y-m-d') && $age >= 10 && $age <= 90;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Trim to the column widths in sql/schema.sql: MySQL runs in strict mode,
    // so an over-long value would throw and show the visitor a bare error page.
    $widths = ['full_name' => 120, 'full_name_ne' => 120, 'symbol_no' => 40,
               'phone' => 30, 'address' => 190, 'date_of_birth' => 10];
    foreach (array_keys($in) as $k) {
        $v = $_POST[$k] ?? '';
        $in[$k] = is_string($v) ? trim($v) : '';
        if (isset($widths[$k])) {
            $in[$k] = mb_substr($in[$k], 0, $widths[$k]);
        }
    }
    // A field people never see but form-filling bots complete. Such a
    // submission gets the normal success page and nothing is stored.
    $isBot = ($_POST['website'] ?? '') !== '';
    $password    = (string) ($_POST['password'] ?? '');
    $confirm     = (string) ($_POST['password_confirm'] ?? '');
    $email       = strtolower($in['email']);
    $year        = (int) $in['year_level'];
    $dob         = parse_date($in['date_of_birth']);
    $photoPath   = null;
    $photoSource = null;

    if (mb_strlen($in['full_name']) < 3)                      { $errors['full_name'] = t('err_name_short'); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) { $errors['email'] = t('err_email_bad'); }
    if ($year < 1 || $year > 4)                               { $errors['year_level'] = t('err_year_bad'); }
    if ($in['symbol_no'] === '')                              { $errors['symbol_no'] = t('err_symbol'); }
    if ($dob === null)                                        { $errors['date_of_birth'] = t('err_dob'); }
    elseif (!birth_date_is_plausible($dob))                   { $errors['date_of_birth'] = t('err_dob_range'); }
    if (mb_strlen($in['address']) < 3)                        { $errors['address'] = t('err_address'); }
    if (!password_is_strong($password))                       { $errors['password'] = t('err_pw_weak'); }
    if ($password !== $confirm)                               { $errors['password_confirm'] = t('err_pw_match'); }

    if (!$errors && !$isBot && one('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors['email'] = t('err_email_taken');
    }

    // Two accounts on one TU symbol number are two people claiming to be the
    // same student, and the office finds out at examination time.
    //
    // A check rather than a UNIQUE key: registrations predating this may
    // already collide, and MySQL refuses to add a unique index to a column
    // that does. sql/schema.sql carries a plain index instead, which applies
    // to colliding data and makes this a seek rather than a table scan.
    //
    // Rejected rows are excluded on purpose. A student whose registration was
    // turned down for a typo has to be able to register again with the same
    // symbol number — it is, after all, still their symbol number — and the
    // rejected row is kept rather than deleted.
    if (!$errors && !$isBot && $in['symbol_no'] !== ''
        && one('SELECT id FROM users WHERE symbol_no = ? AND status <> \'rejected\' LIMIT 1', [$in['symbol_no']])) {
        $errors['symbol_no'] = t('err_symbol_taken');
    }

    // The photograph is optional — a student can add one later from their
    // portfolio — but a file that was chosen and cannot be stored is an error
    // rather than something to drop silently. Stored only once the rest of
    // the form is good, so a rejected registration leaves no orphan file.
    if (!$errors && !$isBot && upload_present($_FILES['photo'] ?? null)) {
        $stored = store_card_photo($_FILES['photo'], 'photos');
        if ($stored['ok']) {
            $photoPath = $stored['path'];
            // The picture the frame was cut from, kept with the account so the
            // student can move the frame on it from their portfolio later.
            // Dropped here, it would sit in the uploads directory with nothing
            // naming it, and the portfolio would tell them their photograph
            // was uploaded before the portal kept one.
            $photoSource = $stored['source'];
        } else {
            $errors['photo'] = image_error_message($stored['error']);
        }
    }

    if (!$errors && $isBot) {
        $done = true;
    } elseif (!$errors) {
        q(
            'INSERT INTO users (full_name, full_name_ne, email, password_hash, role, status,
                                year_level, symbol_no, phone, date_of_birth, address, avatar_path,
                                avatar_source_path)
             VALUES (?, ?, ?, ?, \'student\', \'pending\', ?, ?, ?, ?, ?, ?, ?)',
            [
                $in['full_name'],
                $in['full_name_ne'] !== '' ? $in['full_name_ne'] : null,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $year,
                $in['symbol_no'],
                $in['phone'] !== '' ? $in['phone'] : null,
                $dob,
                $in['address'],
                $photoPath,
                $photoSource,
            ]
        );
        log_activity(null, 'register', $email, 'Year ' . $year);

        // Best-effort: a failed notification must not fail the registration.
        notify_admins_of_registration([
            'full_name'  => $in['full_name'],
            'email'      => $email,
            'year_level' => $year,
            'symbol_no'  => $in['symbol_no'],
        ]);

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

    <form method="post" enctype="multipart/form-data" novalidate>
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

      <div class="p-field-row">
        <div class="p-field <?= isset($errors['date_of_birth']) ? 'error' : '' ?>">
          <label for="date_of_birth"><?= te('date_of_birth') ?></label>
          <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($in['date_of_birth']) ?>"
                 max="<?= e(date('Y-m-d')) ?>" required>
          <?php if (isset($errors['date_of_birth'])): ?><span class="err"><?= e($errors['date_of_birth']) ?></span><?php endif; ?>
        </div>

        <div class="p-field <?= isset($errors['address']) ? 'error' : '' ?>">
          <label for="address"><?= te('address') ?></label>
          <input type="text" id="address" name="address" value="<?= e($in['address']) ?>"
                 autocomplete="street-address" placeholder="<?= te('address_placeholder') ?>" required>
          <?php if (isset($errors['address'])): ?><span class="err"><?= e($errors['address']) ?></span><?php endif; ?>
        </div>
      </div>

      <div class="p-field <?= isset($errors['photo']) ? 'error' : '' ?>">
        <label for="photo"><?= te('photo') ?> <span class="hint"><?= te('photo_hint') ?></span></label>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
        <span class="hint"><?= te('photo_idcard_note') ?></span>
        <?php if (isset($errors['photo'])): ?><span class="err"><?= e($errors['photo']) ?></span><?php endif; ?>
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

      <div style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
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
