<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/uploads.php';
require_once __DIR__ . '/../inc/photos.php';
require_once __DIR__ . '/../inc/idcard.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Checked before the CSRF token, because when PHP discards an oversized
    // request body there is no token left to check — the form comes through
    // completely empty and would be reported as an expired session. This page
    // can carry two pictures at once, a photograph and a signature, so it
    // reaches post_max_size on hosts where either alone would not.
    if (post_exceeded_limit()) {
        flash('error', t('err_file_too_large', format_bytes(upload_limit_bytes())));
        header('Location: ' . portal_url('/student/portfolio.php'));
        exit;
    }

    verify_csrf();

    if (($_POST['form'] ?? '') === 'profile') {
        // Trimmed to the column widths and the date checked, so bad input is
        // dropped instead of making MySQL (strict mode) throw a 500.
        $field = fn(string $k, int $max): string
            => mb_substr(trim(is_string($_POST[$k] ?? null) ? $_POST[$k] : ''), 0, $max);
        q(
            'UPDATE users SET full_name = ?, full_name_ne = ?, phone = ?, address = ?,
                              date_of_birth = ?, blood_group = ?, national_id = ?,
                              pan_no = ?, bio = ?
              WHERE id = ?',
            [
                $field('full_name', 120) ?: $user['full_name'],
                $field('full_name_ne', 120) ?: null,
                $field('phone', 30) ?: null,
                $field('address', 190) ?: null,
                parse_date($field('date_of_birth', 10)),
                // One of the eight or nothing at all. Anything else is stored
                // as nothing, which leaves the line on the card blank rather
                // than printing a group nobody typed.
                blood_group(is_string($_POST['blood_group'] ?? null) ? $_POST['blood_group'] : null),
                // Digits and the separators written between them, and nothing
                // else: these are printed on an identity document, where a
                // stray keystroke nobody re-read would stay for the life of
                // the card.
                id_number($field('national_id', 30), 30) ?: null,
                id_number($field('pan_no', 20), 20) ?: null,
                $field('bio', 5000) ?: null,
                $user['id'],
            ]
        );

        /**
         * The two pictures a profile can carry, each judged on its own.
         *
         * One that fails says so and leaves the other alone. Ending the
         * request on the first failure — which is what a photograph too small
         * to print used to do — threw away a signature uploaded in the same
         * submission, and left the person to find that file again with
         * nothing on screen to say why it had not been kept.
         */
        $failed = false;
        $placed = false;

        // A new photograph replaces the old one, and the old file is removed
        // rather than left in the uploads directory for ever.
        if (upload_present($_FILES['photo'] ?? null)) {
            // Cropped to the card's frame and turned the right way up as it
            // is stored, so what is kept is what the card prints.
            $stored = store_card_photo($_FILES['photo'], 'photos');
            if ($stored['ok']) {
                // The row is pointed at the new files first: delete first and
                // a failed update leaves the account naming a photograph that
                // is no longer there.
                //
                // The placement goes back to the default with the photograph,
                // because it described where the frame sat on the last one. A
                // setting carried over from a picture nobody is looking at any
                // more is a worse starting point than the rule.
                //
                // Written out rather than left null, because null means "the
                // default" and the default is a figure that can change. It has
                // already changed once: a photograph cut at the old bias would
                // have had the slider showing today's, reporting a crop the
                // card was not printing. What is stored now is the placement
                // the photograph on the card was actually cut at.
                q(
                    'UPDATE users SET avatar_path = ?, avatar_source_path = ?, avatar_focus = ?
                      WHERE id = ?',
                    [$stored['path'], $stored['source'], card_photo_focus(null), $user['id']]
                );
                delete_upload($user['avatar_path']);
                delete_upload($user['avatar_source_path'] ?? null);
                $placed = true;          // the new photograph settles the placement
            } else {
                flash('error', image_error_message($stored['error']));
                $failed = true;
            }
        }

        // The holder's own signature for the back of their card. Not cropped
        // like the photograph: a signature is wide and shallow, and the card
        // fits it to the line with object-fit instead.
        if (upload_present($_FILES['signature'] ?? null)) {
            // Turned the right way up, and the ink lifted off the paper:
            // black on a transparent ground, trimmed to the writing. What
            // people have to hand is a photograph of a page, and a page is
            // what would otherwise print on the card.
            $stored = store_signature_image($_FILES['signature'], 'holder-signature', true);
            if ($stored['ok']) {
                q('UPDATE users SET signature_path = ? WHERE id = ?', [$stored['path'], $user['id']]);
                delete_upload($user['signature_path'] ?? null);
                if (!$stored['inked']) {
                    // The form promised the paper would come off. When it
                    // could not — GD missing on the host, a picture too large
                    // to decode, or one with no ink and paper to tell apart —
                    // saying nothing would leave somebody looking at a
                    // photograph of a page on their card and no reason for it.
                    flash('info', t('holder_signature_kept'));
                }
            } else {
                flash('error', $stored['error'] === 'small'
                    ? t('err_signature_small')
                    : image_error_message($stored['error']));
                $failed = true;
            }
        }

        /**
         * Moving the frame on a photograph already uploaded.
         *
         * Only when the placement actually changed, there is a working copy
         * with something to move, and no new photograph came with this
         * submission — a new one has just been cut at the default, and cutting
         * it again to a figure the person chose for the picture before it
         * would undo that silently.
         *
         * A re-cut that fails changes nothing: the row still names the
         * photograph on the card, which is on disk, and the setting is left
         * where it was so the form does not claim a placement the card is not
         * printing.
         */
        $focus = array_key_exists('photo_focus', $_POST)
            ? card_photo_focus((int) $_POST['photo_focus'])
            : null;

        $moved = $focus !== null && $focus !== card_photo_focus(
            isset($user['avatar_focus']) ? (int) $user['avatar_focus'] : null
        );

        if (!$placed && $moved && photo_can_be_placed($user['avatar_source_path'] ?? null)) {
            $recut = recrop_from_source($user['avatar_source_path'] ?? null, $focus);
            if ($recut !== null) {
                q(
                    'UPDATE users SET avatar_path = ?, avatar_focus = ? WHERE id = ?',
                    [$recut, $focus, $user['id']]
                );
                delete_upload($user['avatar_path']);
            } else {
                flash('error', t('err_photo_place'));
                $failed = true;
            }
        }

        if (!$failed) {
            flash('ok', t('profile_saved'));
        }
        header('Location: ' . portal_url('/student/portfolio.php'));
        exit;
    }

    if (($_POST['form'] ?? '') === 'remove_photo') {
        delete_upload($user['avatar_path']);
        delete_upload($user['avatar_source_path'] ?? null);
        q('UPDATE users SET avatar_path = NULL, avatar_source_path = NULL, avatar_focus = NULL
            WHERE id = ?', [$user['id']]);
        flash('ok', t('photo_removed'));
        header('Location: ' . portal_url('/student/portfolio.php'));
        exit;
    }

    if (($_POST['form'] ?? '') === 'remove_signature') {
        delete_upload($user['signature_path'] ?? null);
        q('UPDATE users SET signature_path = NULL WHERE id = ?', [$user['id']]);
        flash('ok', t('signature_removed'));
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
    <form class="p-card" method="post" enctype="multipart/form-data" novalidate>
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

      <div class="p-field-row">
        <div class="p-field">
          <label for="address"><?= te('address') ?></label>
          <input type="text" id="address" name="address" value="<?= e($user['address']) ?>"
                 placeholder="<?= te('address_placeholder') ?>">
        </div>
        <div class="p-field">
          <label for="blood_group"><?= te('blood_group') ?> <span class="hint"><?= te('optional') ?></span></label>
          <select id="blood_group" name="blood_group">
            <option value=""><?= te('blood_group_none') ?></option>
            <?php foreach (blood_groups() as $group): ?>
              <option value="<?= e($group) ?>" <?= ($user['blood_group'] ?? '') === $group ? 'selected' : '' ?>>
                <?= e($group) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="hint"><?= te('blood_group_hint') ?></span>
        </div>
      </div>

      <div class="p-field-row">
        <div class="p-field">
          <label for="national_id"><?= te('national_id') ?> <span class="hint"><?= te('optional') ?></span></label>
          <?php /* tel, not numeric: a numeric keypad on a phone has no hyphen
                   and no way to reach one, and this is a number written with
                   three of them. */ ?>
          <input type="text" id="national_id" name="national_id" inputmode="tel"
                 value="<?= e($user['national_id'] ?? '') ?>" placeholder="000-000-000-0">
          <span class="hint"><?= te('national_id_hint') ?></span>
        </div>
        <div class="p-field">
          <label for="pan_no"><?= te('pan_no') ?> <span class="hint"><?= te('optional') ?></span></label>
          <input type="text" id="pan_no" name="pan_no" inputmode="numeric"
                 value="<?= e($user['pan_no'] ?? '') ?>" placeholder="000000000">
          <span class="hint"><?= te('pan_no_hint') ?></span>
        </div>
      </div>

      <div class="p-field">
        <label for="photo"><?= te('photo') ?> <span class="hint"><?= te('photo_hint') ?></span></label>
        <?php if ($src = photo_src($user)): ?>
          <?php /* The frame is the card's own, so this is the crop that prints
                   rather than a round thumbnail that hides it. */ ?>
          <div class="p-photo-preview">
            <div class="p-photo-frame"><img src="<?= e($src) ?>" alt="<?= te('photo') ?>"></div>
            <p class="hint"><?= te('photo_card_preview') ?></p>
          </div>
        <?php endif; ?>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
        <span class="hint"><?= te('photo_idcard_note') ?></span>
      </div>

      <?php
      /**
       * Where the round frame sits on the photograph.
       *
       * Offered only when it can do something. A photograph no taller than
       * the frame — one already cut square for a website or a round avatar,
       * which is what people reach for first, or a landscape shot — has no
       * excess for the frame to take off one end or the other, so the slider
       * would move nothing however far it was dragged. Those get the reason
       * instead, because it is also the one thing that makes a head sit hard
       * against the ring: a photograph cropped to the top of somebody's hair
       * has no room above it to show, and no frame can invent any.
       */
      $source   = resolve_upload($user['avatar_source_path'] ?? null);
      $canPlace = $user['avatar_path'] && $source !== null
          && photo_can_be_placed($user['avatar_source_path']);
      ?>
      <?php if ($user['avatar_path']): ?>
        <div class="p-field p-place">
          <label for="photo_focus"><?= te('photo_place') ?></label>
          <?php if ($canPlace): ?>
            <input type="range" id="photo_focus" name="photo_focus" min="0" max="100" step="5"
                   value="<?= (int) card_photo_focus(isset($user['avatar_focus']) ? (int) $user['avatar_focus'] : null) ?>">
            <div class="p-place-ends">
              <span><?= te('photo_place_top') ?></span>
              <span><?= te('photo_place_bottom') ?></span>
            </div>
            <span class="hint"><?= te('photo_place_hint') ?></span>
          <?php elseif ($source === null): ?>
            <?php /* Either the photograph predates the working copy, or the
                     copy has gone from disk. Both mean the same thing to the
                     person reading it: there is nothing to move it on, and
                     uploading it again is what fixes that. Saying it is
                     square instead would be telling them something untrue
                     about their own picture. */ ?>
            <span class="hint"><?= te('photo_place_no_source') ?></span>
          <?php else: ?>
            <span class="hint"><?= te('photo_place_no_room') ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="p-field">
        <label for="signature"><?= te('holder_signature') ?> <span class="hint"><?= te('optional') ?></span></label>
        <?php if ($sigSrc = holder_signature_src($user)): ?>
          <div class="p-sig-preview">
            <div class="p-sig-frame"><img src="<?= e($sigSrc) ?>" alt="<?= te('holder_signature') ?>"></div>
            <p class="hint"><?= te('holder_signature_preview') ?></p>
          </div>
        <?php endif; ?>
        <input type="file" id="signature" name="signature" accept="image/jpeg,image/png,image/webp">
        <span class="hint"><?= te('holder_signature_hint') ?></span>
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
      <?php if ($src = photo_src($user)): ?>
        <img class="p-avatar" style="width:54px;height:54px;" src="<?= e($src) ?>" alt="" width="54" height="54">
      <?php else: ?>
        <span class="p-avatar" style="width:54px;height:54px;font-size:1.1rem;" aria-hidden="true"><?= e(initials($user['full_name'])) ?></span>
      <?php endif; ?>
      <div>
        <div style="font-weight:600;color:var(--navy);"><?= e(display_name($user)) ?></div>
        <div style="font-size:.86rem;color:var(--ink-soft);"><?= e(role_label($user)) ?></div>
        <?php if ($user['avatar_path']): ?>
          <form method="post" style="margin-top:4px;" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="remove_photo">
            <button class="p-btn-link" type="submit"><?= te('remove_photo') ?></button>
          </form>
        <?php endif; ?>
        <?php /* Beside the photograph's, because both remove a file this
                 person uploaded — and because a form cannot be nested inside
                 the one that uploads it. */ ?>
        <?php if (!empty($user['signature_path'])): ?>
          <form method="post" style="margin-top:4px;" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="remove_signature">
            <button class="p-btn-link" type="submit"><?= te('remove_signature') ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <table class="p-table p-table-compact" style="border:0;">
      <tbody>
        <?php if ($user['role'] === ROLE_STUDENT): ?>
        <tr><th scope="row"><?= te('year_of_study') ?></th>
            <td><?= e($user['year_level'] ? year_label((int) $user['year_level']) : '—') ?></td></tr>
        <tr><th scope="row"><?= te('symbol_no') ?></th><td><?= e($user['symbol_no'] ?: '—') ?></td></tr>
        <?php else: ?>
        <tr><th scope="row"><?= te('designation') ?></th>
            <td><?= e(designation_label($user['designation'] ?? null) ?: t('role_' . $user['role'])) ?>
              <div class="hint" style="font-size:.8rem;"><?= te('designation_set_by_office') ?></div>
            </td></tr>
        <?php endif; ?>
        <tr><th scope="row"><?= te('id_card_no') ?></th><td><?= e(id_card_number($user)) ?></td></tr>
        <tr><th scope="row"><?= te('status_active') ?></th>
            <td><span class="p-tag ok"><?= te('status_' . $user['status']) ?></span></td></tr>
        <tr><th scope="row"><?= te('member_since') ?></th><td><?= e(format_date($user['created_at'])) ?></td></tr>
        <tr><th scope="row"><?= te('last_signed_in') ?></th><td><?= e(format_date($user['last_login_at'], true)) ?></td></tr>
      </tbody>
    </table>

    <div class="p-idcard-cta">
      <h3><?= te('id_card_title') ?></h3>
      <p><?= te('id_card_cta') ?></p>
      <?php if ($missing = id_card_missing($user)): ?>
        <p class="p-idcard-missing"><?= e(t('id_card_missing', join_list($missing))) ?></p>
      <?php endif; ?>
      <a class="p-btn p-btn-primary" href="<?= e(portal_url('/id-card.php')) ?>"><?= te('id_card_open') ?></a>
    </div>
  </aside>
</div>
<?php layout_foot(); ?>
