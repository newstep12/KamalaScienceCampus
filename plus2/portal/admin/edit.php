<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/idcard.php';

/**
 * The school office's own edit of one person's details.
 *
 * A student cannot change their name or date of birth once approved — those
 * are printed on a card that carries the Principal's and the Coordinator's
 * signatures, and changing them should be the office's decision, not the
 * holder's. So this is where they are corrected: a typo at registration, a
 * name spelt differently on the school's register, a wrong date of birth.
 * Everything else a student can still keep up to date themselves.
 *
 * Every save is written to the activity log with the fields that changed.
 */

$admin = require_role(ROLE_ADMIN);
$id    = (int) ($_GET['user'] ?? 0);
$u     = $id > 0 ? one('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]) : null;
if (!$u) {
    flash('error', t('notfound_body'));
    header('Location: ' . portal_url('/admin/users.php'));
    exit;
}
$student = $u['role'] === ROLE_STUDENT;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $field = fn(string $k, int $max): string
        => mb_substr(trim(is_string($_POST[$k] ?? null) ? $_POST[$k] : ''), 0, $max);

    $new = [
        'full_name'     => $field('full_name', 120),
        'full_name_ne'  => $field('full_name_ne', 120) ?: null,
        'date_of_birth' => parse_date($field('date_of_birth', 10)),
        'phone'         => ascii_digits($field('phone', 30)) ?: null,
        'address'       => $field('address', 190) ?: null,
        'blood_group'   => blood_group($field('blood_group', 8)),
    ];
    if (mb_strlen($new['full_name']) < 3) {
        $errors['full_name'] = t('err_name_short');
    }
    if ($student) {
        $class = (int) $field('class_level', 2);
        $new += [
            'class_level'    => in_array($class, class_levels(), true) ? $class : null,
            'section'        => mb_strtoupper($field('section', 10)) ?: null,
            'roll_no'        => ascii_digits($field('roll_no', 20)) ?: null,
            'study_group'    => study_group($field('study_group', 20)),
            'guardian_name'  => $field('guardian_name', 120) ?: null,
            'guardian_phone' => ascii_digits($field('guardian_phone', 30)) ?: null,
        ];
    } else {
        $new += [
            'designation' => in_array($_POST['designation'] ?? '', designations(), true) ? $_POST['designation'] : null,
            'national_id' => id_number($field('national_id', 30), 30) ?: null,
            'pan_no'      => id_number($field('pan_no', 20), 20) ?: null,
        ];
    }

    if (!$errors) {
        $changed = [];
        foreach ($new as $col => $val) {
            if ((string) ($u[$col] ?? '') !== (string) ($val ?? '')) {
                $changed[] = $col;
            }
        }
        if ($changed) {
            // Column names come from the fixed list above, never from input.
            $set = implode(', ', array_map(fn($c) => $c . ' = ?', array_keys($new)));
            q('UPDATE users SET ' . $set . ' WHERE id = ?', [...array_values($new), $id]);
            log_activity((int) $admin['id'], 'edit_user', $u['email'], mb_substr(implode(', ', $changed), 0, 255));
            flash('ok', t('user_updated'));
        } else {
            flash('info', t('edit_no_change'));
        }
        header('Location: ' . portal_url('/admin/edit.php?user=' . $id));
        exit;
    }
    $u = array_merge($u, $new);
}

layout_head(['title' => t('edit_person_title', $u['full_name']), 'active' => 'users', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= e(t('edit_person_title', display_name($u))) ?></h1>
  <p><?= te('edit_person_intro') ?></p>
</div>

<?php if ($errors): ?>
  <div class="p-flash p-flash-error" role="alert"><?= e(reset($errors)) ?></div>
<?php endif; ?>

<form class="p-card" method="post" novalidate style="max-width:880px;">
  <?= csrf_field() ?>
  <p class="hint" style="margin-top:0;"><?= e($u['email']) ?> · <?= e(role_label($u)) ?> · <?= e(id_card_number($u)) ?></p>

  <div class="p-field-row">
    <div class="p-field <?= isset($errors['full_name']) ? 'error' : '' ?>">
      <label for="full_name"><?= te('full_name_en') ?></label>
      <input type="text" id="full_name" name="full_name" maxlength="120" value="<?= e($u['full_name']) ?>" required>
    </div>
    <div class="p-field">
      <label for="full_name_ne"><?= te('full_name_ne') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="text" id="full_name_ne" name="full_name_ne" maxlength="120" lang="ne" value="<?= e($u['full_name_ne'] ?? '') ?>">
    </div>
  </div>

  <div class="p-field-row">
    <div class="p-field">
      <label for="date_of_birth"><?= te('date_of_birth') ?></label>
      <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($u['date_of_birth'] ?? '') ?>">
    </div>
    <div class="p-field">
      <label for="blood_group"><?= te('blood_group') ?></label>
      <select id="blood_group" name="blood_group">
        <option value=""><?= te('blood_group_none') ?></option>
        <?php foreach (blood_groups() as $g): ?>
          <option value="<?= e($g) ?>" <?= ($u['blood_group'] ?? '') === $g ? 'selected' : '' ?>><?= e($g) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <?php if ($student): ?>
    <div class="p-field-row">
      <div class="p-field">
        <label for="class_level"><?= te('class') ?></label>
        <select id="class_level" name="class_level">
          <option value="">—</option>
          <?php foreach (class_levels() as $c): ?>
            <option value="<?= $c ?>" <?= (int) ($u['class_level'] ?? 0) === $c ? 'selected' : '' ?>><?= e(class_label($c)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="p-field">
        <label for="study_group"><?= te('study_group') ?></label>
        <select id="study_group" name="study_group">
          <option value="">—</option>
          <?php foreach (study_groups() as $g): ?>
            <option value="<?= e($g) ?>" <?= ($u['study_group'] ?? '') === $g ? 'selected' : '' ?>><?= e(study_group_label($g)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="section"><?= te('section') ?></label>
        <input type="text" id="section" name="section" maxlength="10" value="<?= e($u['section'] ?? '') ?>">
      </div>
      <div class="p-field">
        <label for="roll_no"><?= te('roll_no') ?></label>
        <input type="text" id="roll_no" name="roll_no" maxlength="20" inputmode="numeric" value="<?= e($u['roll_no'] ?? '') ?>">
      </div>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="guardian_name"><?= te('guardian_name') ?></label>
        <input type="text" id="guardian_name" name="guardian_name" maxlength="120" value="<?= e($u['guardian_name'] ?? '') ?>">
      </div>
      <div class="p-field">
        <label for="guardian_phone"><?= te('guardian_phone') ?></label>
        <input type="tel" id="guardian_phone" name="guardian_phone" maxlength="30" value="<?= e($u['guardian_phone'] ?? '') ?>">
      </div>
    </div>
  <?php else: ?>
    <div class="p-field" style="max-width:420px;">
      <label for="designation"><?= te('designation') ?></label>
      <select id="designation" name="designation">
        <option value="">—</option>
        <?php foreach (designations() as $d): ?>
          <option value="<?= e($d) ?>" <?= ($u['designation'] ?? '') === $d ? 'selected' : '' ?>><?= e(designation_label($d)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="national_id"><?= te('national_id') ?></label>
        <input type="text" id="national_id" name="national_id" inputmode="tel" value="<?= e($u['national_id'] ?? '') ?>">
      </div>
      <div class="p-field">
        <label for="pan_no"><?= te('pan_no') ?></label>
        <input type="text" id="pan_no" name="pan_no" inputmode="numeric" value="<?= e($u['pan_no'] ?? '') ?>">
      </div>
    </div>
  <?php endif; ?>

  <div class="p-field-row">
    <div class="p-field">
      <label for="phone"><?= te('phone') ?></label>
      <input type="tel" id="phone" name="phone" maxlength="30" value="<?= e($u['phone'] ?? '') ?>">
    </div>
    <div class="p-field">
      <label for="address"><?= te('address') ?></label>
      <input type="text" id="address" name="address" maxlength="190" value="<?= e($u['address'] ?? '') ?>">
    </div>
  </div>

  <div class="p-form-actions">
    <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
    <?php if ($u['status'] === 'active'): ?>
      <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/id-card.php?user=' . $id)) ?>"><?= te('print_id_card') ?></a>
    <?php endif; ?>
    <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/admin/users.php')) ?>"><?= te('manage_people') ?></a>
  </div>
</form>
<?php layout_foot(); ?>
