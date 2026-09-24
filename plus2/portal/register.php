<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/mail.php';
require_once __DIR__ . '/inc/uploads.php';
require_once __DIR__ . '/inc/photos.php';
require_once __DIR__ . '/inc/idcard.php';

if ($u = current_user()) {
    header('Location: ' . home_for($u));
    exit;
}

$errors = [];
$done   = false;
$in = [
    'full_name'     => '', 'full_name_ne'   => '', 'email'   => '',
    'class_level'   => '', 'section'        => '', 'roll_no' => '', 'study_group' => '',
    'guardian_name' => '', 'guardian_phone' => '', 'phone'   => '',
    'date_of_birth' => '', 'address'        => '',
];

/**
 * A date of birth that could belong to a class 11 or 12 student: a real
 * calendar date, in the past, and not so far back — or so recent — that it is
 * plainly a typo — a BS year typed into this AD date field lands decades in
 * the future, and is refused.
 */
function birth_date_is_plausible(string $date): bool
{
    $age = (new DateTime('today'))->diff(new DateTime($date))->y;
    return $date <= date('Y-m-d') && $age >= 12 && $age <= 40;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_exceeded_limit()) {
    // A photograph too big for the server: PHP drops the whole form, token
    // and all, so say what happened rather than "your session expired".
    $errors['photo'] = t('err_file_too_large', format_bytes(upload_limit_bytes()));
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && recent_registrations(client_ip()) >= MAX_REGISTRATIONS_PER_HOUR) {
    // The form is public and every submission can store two pictures, so one
    // address gets a handful an hour — a whole class registering from the
    // school's own connection fits; a script filling the disk does not.
    verify_csrf();
    $errors['email'] = t('err_too_many_registrations');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Trim to the column widths in sql/schema.sql: MySQL runs in strict mode,
    // so an over-long value would throw and show the visitor a bare error page.
    $widths = ['full_name' => 120, 'full_name_ne' => 120, 'section' => 10, 'roll_no' => 20,
               'guardian_name' => 120, 'guardian_phone' => 30,
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
    $class       = (int) $in['class_level'];
    // Numbers typed on a Nepali keyboard arrive as ०-९; stored as ASCII so
    // they compare and sort, and the card puts them back as it prints.
    $in['roll_no']        = ascii_digits($in['roll_no']);
    $in['guardian_phone'] = ascii_digits($in['guardian_phone']);
    $in['phone']          = ascii_digits($in['phone']);
    $in['section']        = mb_strtoupper($in['section']);
    $dob         = parse_date($in['date_of_birth']);
    $photoPath   = null;
    $photoSource = null;

    if (mb_strlen($in['full_name']) < 3)                      { $errors['full_name'] = t('err_name_short'); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) { $errors['email'] = t('err_email_bad'); }
    if (!in_array($class, class_levels(), true))              { $errors['class_level'] = t('err_class_bad'); }
    if (study_group($in['study_group']) === null)             { $errors['study_group'] = t('err_group_bad'); }
    if (mb_strlen($in['guardian_name']) < 3)                  { $errors['guardian_name'] = t('err_guardian_name'); }
    if (!preg_match('/[0-9]{7,}/', preg_replace('/\D+/', '', $in['guardian_phone']) ?? '')) {
        $errors['guardian_phone'] = t('err_guardian_phone');
    }
    if ($dob === null)                                        { $errors['date_of_birth'] = t('err_dob'); }
    elseif (!birth_date_is_plausible($dob))                   { $errors['date_of_birth'] = t('err_dob_range'); }
    if (mb_strlen($in['address']) < 3)                        { $errors['address'] = t('err_address'); }
    if (!password_is_strong($password))                       { $errors['password'] = t('err_pw_weak'); }
    if ($password !== $confirm)                               { $errors['password_confirm'] = t('err_pw_match'); }

    if (!$errors && !$isBot && one('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors['email'] = t('err_email_taken');
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
                                class_level, section, roll_no, study_group, guardian_name, guardian_phone,
                                phone, date_of_birth, address, avatar_path, avatar_source_path)
             VALUES (?, ?, ?, ?, \'student\', \'pending\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $in['full_name'],
                $in['full_name_ne'] !== '' ? $in['full_name_ne'] : null,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $class,
                $in['section'] !== '' ? $in['section'] : null,
                $in['roll_no'] !== '' ? $in['roll_no'] : null,
                study_group($in['study_group']),
                $in['guardian_name'],
                $in['guardian_phone'],
                $in['phone'] !== '' ? $in['phone'] : null,
                $dob,
                $in['address'],
                $photoPath,
                $photoSource,
            ]
        );
        log_activity(null, 'register', $email, 'Class ' . $class);
        // Counted only once something was stored: a student correcting a typo
        // has not used up anybody's allowance, and a refused form stores no
        // files.
        record_registration(client_ip());

        // Best-effort: a failed notification must not fail the registration.
        notify_admins_of_registration([
            'full_name'  => $in['full_name'],
            'email'      => $email,
            'class_level' => $class,
            'roll_no'     => $in['roll_no'],
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
        <label for="full_name"><?= te('full_name_en') ?></label>
        <input type="text" id="full_name" name="full_name" value="<?= e($in['full_name']) ?>" autocomplete="name" required>
        <?php if (isset($errors['full_name'])): ?><span class="err"><?= e($errors['full_name']) ?></span><?php endif; ?>
      </div>

      <div class="p-field">
        <label for="full_name_ne"><?= te('full_name_ne') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="text" id="full_name_ne" name="full_name_ne" value="<?= e($in['full_name_ne']) ?>" lang="ne"
               data-suggest="<?= e(portal_url('/name-ne.php')) ?>">
        <span class="hint"><?= te('full_name_ne_hint') ?></span>
      </div>

      <div class="p-field <?= isset($errors['email']) ? 'error' : '' ?>">
        <label for="email"><?= te('email') ?></label>
        <input type="email" id="email" name="email" value="<?= e($in['email']) ?>" autocomplete="email" required>
        <?php if (isset($errors['email'])): ?><span class="err"><?= e($errors['email']) ?></span><?php endif; ?>
      </div>

      <div class="p-field-row">
        <div class="p-field <?= isset($errors['class_level']) ? 'error' : '' ?>">
          <label for="class_level"><?= te('class') ?></label>
          <select id="class_level" name="class_level" required>
            <option value=""><?= te('choose_class') ?></option>
            <?php foreach (class_levels() as $c): ?>
              <option value="<?= $c ?>" <?= (int) $in['class_level'] === $c ? 'selected' : '' ?>>
                <?= e(class_label($c)) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['class_level'])): ?><span class="err"><?= e($errors['class_level']) ?></span><?php endif; ?>
        </div>

        <div class="p-field">
          <label for="section"><?= te('section') ?> <span class="hint"><?= te('optional') ?></span></label>
          <input type="text" id="section" name="section" value="<?= e($in['section']) ?>" maxlength="10"
                 placeholder="<?= te('section_placeholder') ?>">
        </div>
      </div>

      <div class="p-field-row">
        <div class="p-field <?= isset($errors['study_group']) ? 'error' : '' ?>">
          <label for="study_group"><?= te('study_group') ?></label>
          <select id="study_group" name="study_group" required>
            <option value=""><?= te('choose_group') ?></option>
            <?php foreach (study_groups() as $g): ?>
              <option value="<?= e($g) ?>" <?= $in['study_group'] === $g ? 'selected' : '' ?>><?= e(study_group_label($g)) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['study_group'])): ?><span class="err"><?= e($errors['study_group']) ?></span><?php endif; ?>
        </div>

        <div class="p-field">
          <label for="roll_no"><?= te('roll_no') ?> <span class="hint"><?= te('if_known') ?></span></label>
          <input type="text" id="roll_no" name="roll_no" value="<?= e($in['roll_no']) ?>" maxlength="20" inputmode="numeric">
        </div>
      </div>

      <div class="p-field" style="max-width:50%;">
        <label for="phone"><?= te('phone') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="tel" id="phone" name="phone" value="<?= e($in['phone']) ?>" autocomplete="tel">
      </div>

      <div class="p-field-row">
        <div class="p-field <?= isset($errors['guardian_name']) ? 'error' : '' ?>">
          <label for="guardian_name"><?= te('guardian_name') ?></label>
          <input type="text" id="guardian_name" name="guardian_name" value="<?= e($in['guardian_name']) ?>" required>
          <?php if (isset($errors['guardian_name'])): ?><span class="err"><?= e($errors['guardian_name']) ?></span><?php endif; ?>
        </div>

        <div class="p-field <?= isset($errors['guardian_phone']) ? 'error' : '' ?>">
          <label for="guardian_phone"><?= te('guardian_phone') ?></label>
          <input type="tel" id="guardian_phone" name="guardian_phone" value="<?= e($in['guardian_phone']) ?>" required>
          <?php if (isset($errors['guardian_phone'])): ?><span class="err"><?= e($errors['guardian_phone']) ?></span><?php endif; ?>
        </div>
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
<script src="<?= e(portal_url('/../assets/js/name-ne.js?v=' . asset_version('/../assets/js/name-ne.js'))) ?>"></script>
<?php layout_foot(); ?>
