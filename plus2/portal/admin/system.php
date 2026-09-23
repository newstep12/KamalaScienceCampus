<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/mail.php';
require_once __DIR__ . '/../inc/uploads.php';
require_once __DIR__ . '/../inc/idcard-view.php';
require_once __DIR__ . '/../inc/signatures.php';
require_once __DIR__ . '/../inc/photos.php';

$admin = require_role(ROLE_ADMIN);

/**
 * Applies sql/schema.sql. Every statement is CREATE TABLE IF NOT EXISTS, so
 * running it is idempotent — it adds tables introduced since the last run and
 * never alters or drops an existing one.
 *
 * Comment lines are stripped BEFORE splitting on semicolons: a '--' comment and
 * the CREATE TABLE beneath it share a chunk, so filtering chunks that merely
 * start with '--' would discard the statement as well.
 */
function apply_schema(): array
{
    $sql = file_get_contents(__DIR__ . '/../../sql/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('sql/schema.sql is missing.');
    }
    $before = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (preg_split('/;\s*[\r\n]/', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            db()->exec($stmt);
        }
    }

    // Column changes that CREATE TABLE IF NOT EXISTS cannot deliver to an
    // existing table. Each must be safe to run repeatedly.
    foreach (column_migrations() as $label => $ddl) {
        try {
            db()->exec($ddl);
        } catch (Throwable $e) {
            error_log('Migration "' . $label . '" skipped: ' . $e->getMessage());
        }
    }

    $after = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    return ['added' => array_values(array_diff($after, $before)), 'tables' => $after];
}

/**
 * Re-runnable ALTERs. MODIFY COLUMN restates the column definition, so applying
 * it twice is a no-op rather than an error.
 */
function column_migrations(): array
{
    // Columns added after the first version. Each is safe to run again: a
    // column that already exists raises "Duplicate column", which the caller
    // catches and logs.
    return [
        // Biology or Computer Science, printed on the card.
        'study group' => 'ALTER TABLE users ADD COLUMN study_group VARCHAR(20) NULL AFTER roll_no',
    ];
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'migrate') {
            $result = apply_schema();
            log_activity((int) $admin['id'], 'apply_schema', implode(', ', $result['added']) ?: 'no change');
            flash('ok', t('db_updated'));
        } elseif ($action === 'save_mail') {
            $from = trim((string) ($_POST['mail_from'] ?? ''));
            if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
                flash('error', t('err_email_bad'));
            } else {
                $name = str_replace(["\r", "\n"], ' ', trim((string) ($_POST['mail_from_name'] ?? '')));
                set_setting('mail_enabled',   isset($_POST['mail_enabled']) ? '1' : '0');
                set_setting('mail_from',      $from ?: null);
                set_setting('mail_from_name', $name ?: null);
                flash('ok', t('mail_saved'));
            }
        } elseif ($action === 'test_mail') {
            $to = trim((string) ($_POST['test_to'] ?? '')) ?: (string) $admin['email'];
            $sent = send_notification(
                $to,
                'Test email from the Shree Kamala Secondary School +2 portal',
                "This is a test message.\n\nIf you are reading it, notifications are working.\n"
            );
            flash($sent ? 'ok' : 'error', $sent ? t('mail_test_sent', $to) : t('mail_test_failed'));
        } elseif ($action === 'save_design') {
            // Campus-wide, so every card issued looks like the same document.
            set_setting('id_card_theme',       id_card_theme($_POST['id_card_theme'] ?? null));
            set_setting('id_card_orientation', id_card_orientation($_POST['id_card_orientation'] ?? null));
            set_setting('id_card_sides',       id_card_sides($_POST['id_card_sides'] ?? null));
            log_activity((int) $admin['id'], 'save_card_design');
            flash('ok', t('idcard_design_saved'));
        } elseif ($action === 'save_idcard') {
            $chiefTitle = (string) ($_POST['id_card_chief_title'] ?? 'principal');
            set_setting('id_card_chief_name',    trim((string) ($_POST['id_card_chief_name'] ?? '')) ?: null);
            set_setting('id_card_chief_name_ne', trim((string) ($_POST['id_card_chief_name_ne'] ?? '')) ?: null);
            set_setting('id_card_chief_title',   in_array($chiefTitle, designations(), true) ? $chiefTitle : 'principal');
            $coordTitle = (string) ($_POST['id_card_coord_title'] ?? 'coordinator');
            set_setting('id_card_coord_name',    trim((string) ($_POST['id_card_coord_name'] ?? '')) ?: null);
            set_setting('id_card_coord_name_ne', trim((string) ($_POST['id_card_coord_name_ne'] ?? '')) ?: null);
            set_setting('id_card_coord_title',   in_array($coordTitle, designations(), true) ? $coordTitle : 'coordinator');
            set_setting('id_card_valid_until',   parse_date((string) ($_POST['id_card_valid_until'] ?? '')));
            set_setting('id_card_session',       mb_substr(trim((string) ($_POST['id_card_session'] ?? '')), 0, 40) ?: null);
            log_activity((int) $admin['id'], 'save_idcard');
            flash('ok', t('idcard_saved'));
        } elseif ($action === 'save_school') {
            // What the card prints about the school besides its name. Each is
            // stored as typed, trimmed to a line; an empty box stores nothing,
            // which leaves that line off the card (place and approval fall
            // back to the wording in the language file).
            $line = fn(string $k, int $max): ?string
                => (($v = mb_substr(trim(str_replace(["\r", "\n"], ' ', (string) ($_POST[$k] ?? ''))), 0, $max)) !== '') ? $v : null;
            $email = $line('school_email', 190);
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', t('err_email_bad'));
            } else {
                set_setting('school_place',       $line('school_place', 120));
                set_setting('school_affiliation', $line('school_affiliation', 120));
                set_setting('school_phone',       $line('school_phone', 40));
                set_setting('school_email',       $email);
                set_setting('school_website',     $line('school_website', 120));
                // Only a web address becomes a QR code; anything else is
                // dropped rather than printed as a code that opens nothing.
                $map = $line('school_map_url', 500);
                set_setting('school_map_url', $map !== null && preg_match('#^https?://#i', $map) ? $map : null);
                log_activity((int) $admin['id'], 'save_school');
                flash('ok', t('school_saved'));
            }
        } elseif ($action === 'recrop_photos') {
            // Photographs uploaded before the crop existed are still whatever
            // shape they arrived in. Bring them to the card's frame so a card
            // printed today looks the same whenever its photo was added.
            $pass = recrop_stored_photos();
            log_activity((int) $admin['id'], 'recrop_photos', (string) $pass['done']);
            flash('ok', t(
                'photos_done',
                localize_digits((string) $pass['done']),
                localize_digits((string) $pass['skipped'])
            ));
            if ($pass['left']) {
                // Stopped on the budget rather than finished. Say so, or the
                // count simply looks wrong.
                flash('info', t('photos_more', localize_digits((string) $pass['left'])));
            }
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }

    if ($action === 'migrate' && $result) {
        // Keep the table list on screen rather than redirecting it away.
    } else {
        // Come back to the section that was being edited, not the top of a
        // long page.
        $anchor = [
            'save_design'   => '#id-cards',
            'save_idcard'   => '#id-cards',
            'save_school'   => '#school',
            'recrop_photos' => '#photos',
        ][$action] ?? '';
        header('Location: ' . portal_url('/admin/system.php' . $anchor));
        exit;
    }
}

// The design section previews the administrator's own card, so what they are
// judging is a real card in the colours they are choosing.
$design  = id_card_settings();
$preview = id_card_context($admin, $admin);

$counts = [
    'students' => (int) scalar('SELECT COUNT(*) FROM users WHERE role = \'student\''),
    'teachers' => (int) scalar('SELECT COUNT(*) FROM users WHERE role = \'teacher\''),
    'photos'   => (int) scalar('SELECT COUNT(*) FROM users WHERE avatar_path IS NOT NULL AND avatar_path <> \'\''),
];

layout_head(['title' => t('system_title'), 'active' => 'system', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('system_title') ?></h1>
  <p><?= te('system_intro') ?></p>
</div>

<section class="p-card">
  <h2><?= te('db_update') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;">
    <?= te('db_update_intro') ?>
  </p>
  <form method="post">
    <?= csrf_field() ?>
    <button class="p-btn p-btn-primary" type="submit" name="action" value="migrate"><?= te('db_update_run') ?></button>
  </form>

  <?php if ($result): ?>
    <div style="margin-top:20px;">
      <?php if ($result['added']): ?>
        <p style="font-weight:600;color:var(--ok);"><?= te('db_added') ?></p>
        <ul style="font-size:.92rem;color:var(--ink-soft);">
          <?php foreach ($result['added'] as $tbl): ?><li><code><?= e((string) $tbl) ?></code></li><?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p style="color:var(--ink-soft);"><?= te('db_no_change') ?></p>
      <?php endif; ?>
      <p style="font-size:.88rem;color:var(--ink-soft);">
        <?= te('db_tables_now', localize_digits((string) count($result['tables']))) ?>
      </p>
    </div>
  <?php endif; ?>
</section>

<section class="p-card" id="id-cards">
  <h2><?= te('idcard_admin_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('idcard_admin_intro') ?></p>

  <div class="idc-design" style="margin-top:20px;">
    <div class="idc-design-fields">
      <form method="post" id="idc-design-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_design">

        <div class="p-field">
          <label><?= te('idcard_theme') ?> <span class="hint"><?= te('idcard_theme_hint') ?></span></label>
          <div class="idc-themes">
            <?php foreach (id_card_themes() as $key => [$head, $band]): ?>
              <label class="idc-theme">
                <input type="radio" name="id_card_theme" value="<?= e($key) ?>"
                       <?= $design['theme'] === $key ? 'checked' : '' ?>>
                <span class="idc-swatch" aria-hidden="true">
                  <i style="background:<?= e($head) ?>;"></i><i style="background:<?= e($band) ?>;"></i>
                </span>
                <span><?= te('theme_' . $key) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="p-field">
          <label><?= te('idcard_orientation') ?></label>
          <div class="idc-choices">
            <?php foreach (['portrait', 'landscape'] as $o): ?>
              <label class="idc-choice">
                <input type="radio" name="id_card_orientation" value="<?= e($o) ?>"
                       <?= $design['orientation'] === $o ? 'checked' : '' ?>>
                <span><?= te('orientation_' . $o) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="p-field">
          <label><?= te('idcard_sides_default') ?> <span class="hint"><?= te('idcard_sides_hint') ?></span></label>
          <div class="idc-choices">
            <?php foreach (['both' => 'id_card_sides_both', 'front' => 'id_card_sides_front'] as $v => $key): ?>
              <label class="idc-choice">
                <input type="radio" name="id_card_sides" value="<?= e($v) ?>"
                       <?= $design['sides'] === $v ? 'checked' : '' ?>>
                <span><?= te($key) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="p-form-actions">
          <button class="p-btn p-btn-primary" type="submit"><?= te('idcard_design_save') ?></button>
          <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/id-card.php')) ?>"><?= te('id_card_preview') ?></a>
        </div>
      </form>
    </div>

    <div class="idc-design-preview">
      <span><?= te('idcard_preview_label') ?></span>
      <div class="idc-sheet" data-sides="<?= e($design['sides']) ?>" data-orientation="<?= e($design['orientation']) ?>">
        <figure class="idc-holder"><?php id_card_face($admin, $preview, 'front', $preview['holder_names']); ?></figure>
        <figure class="idc-holder idc-holder-back"><?php id_card_face($admin, $preview, 'back'); ?></figure>
      </div>
    </div>
  </div>

  <div style="margin-top:24px;padding-top:24px;border-top:1px solid var(--line);">
    <?php foreach (['id_card_coordinator' => 'coord_signature_title', 'id_card' => 'signature'] as $use => $heading): ?>
      <h3 style="margin:14px 0 6px;font-size:1rem;"><?= te($heading) ?></h3>
      <?php $cardSig = signature_for_use($use); ?>
      <?php if ($cardSig): ?>
        <div class="p-signature-preview">
          <img src="<?= e(signature_url($cardSig)) ?>" alt="<?= e($cardSig['label']) ?>">
        </div>
        <p style="color:var(--ink-soft);font-size:.94rem;">
          <?= te('idcard_signature_now', $cardSig['label'], t('sig_scope_' . signature_scope($cardSig['release_scope']))) ?>
        </p>
      <?php else: ?>
        <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('idcard_signature_none') ?></p>
      <?php endif; ?>
    <?php endforeach; ?>
    <p style="margin-top:12px;">
      <a class="p-btn p-btn-gold p-btn-sm" href="<?= e(portal_url('/admin/signatures.php')) ?>">
        <?= te('idcard_signature_manage') ?>
      </a>
    </p>
  </div>

  <form method="post" style="margin-top:24px;padding-top:24px;border-top:1px solid var(--line);">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_idcard">
    <div class="p-field-row">
      <div class="p-field">
        <label for="chief_name"><?= te('chief_name') ?></label>
        <input type="text" id="chief_name" name="id_card_chief_name" value="<?= e($design['chief_name']) ?>">
      </div>
      <div class="p-field">
        <label for="chief_name_ne"><?= te('chief_name_ne') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="text" id="chief_name_ne" name="id_card_chief_name_ne" lang="ne"
               value="<?= e($design['chief_name_ne']) ?>">
      </div>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="chief_title"><?= te('chief_title') ?></label>
        <select id="chief_title" name="id_card_chief_title">
          <?php foreach (designations() as $d): ?>
            <option value="<?= e($d) ?>" <?= setting('id_card_chief_title', 'principal') === $d ? 'selected' : '' ?>>
              <?= e(designation_label($d)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="p-field">
        <label for="valid_until"><?= te('id_card_valid') ?> <span class="hint"><?= te('valid_until_hint') ?></span></label>
        <input type="date" id="valid_until" name="id_card_valid_until" value="<?= e(setting('id_card_valid_until')) ?>">
      </div>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="coord_name"><?= te('coord_name') ?></label>
        <input type="text" id="coord_name" name="id_card_coord_name" value="<?= e($design['coord_name']) ?>">
      </div>
      <div class="p-field">
        <label for="coord_name_ne"><?= te('chief_name_ne') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="text" id="coord_name_ne" name="id_card_coord_name_ne" lang="ne" value="<?= e($design['coord_name_ne']) ?>">
      </div>
    </div>
    <div class="p-field" style="max-width:420px;">
      <label for="coord_title"><?= te('coord_title') ?></label>
      <select id="coord_title" name="id_card_coord_title">
        <?php foreach (designations() as $d): ?>
          <option value="<?= e($d) ?>" <?= $design['coord_title'] === $d ? 'selected' : '' ?>><?= e(designation_label($d)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="p-field" style="max-width:320px;">
      <label for="session"><?= te('id_card_session') ?> <span class="hint"><?= te('session_hint') ?></span></label>
      <input type="text" id="session" name="id_card_session" value="<?= e(setting('id_card_session')) ?>" placeholder="2082/83">
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
      <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/id-card.php')) ?>"><?= te('id_card_preview') ?></a>
    </div>
  </form>
</section>

<section class="p-card" id="school">
  <h2><?= te('school_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('school_intro') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_school">
    <div class="p-field-row">
      <div class="p-field">
        <label for="school_place"><?= te('school_place') ?></label>
        <input type="text" id="school_place" name="school_place" maxlength="120"
               value="<?= e(setting('school_place')) ?>" placeholder="<?= te('school_place_default') ?>">
      </div>
      <div class="p-field">
        <label for="school_affiliation"><?= te('school_affiliation') ?></label>
        <input type="text" id="school_affiliation" name="school_affiliation" maxlength="120"
               value="<?= e(setting('school_affiliation')) ?>" placeholder="<?= te('school_affiliation_default') ?>">
      </div>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="school_phone"><?= te('school_phone') ?></label>
        <input type="tel" id="school_phone" name="school_phone" maxlength="40" value="<?= e(setting('school_phone')) ?>">
      </div>
      <div class="p-field">
        <label for="school_email"><?= te('school_email') ?></label>
        <input type="email" id="school_email" name="school_email" maxlength="190" value="<?= e(setting('school_email')) ?>">
      </div>
    </div>
    <div class="p-field" style="max-width:420px;">
      <label for="school_website"><?= te('school_website') ?></label>
      <input type="text" id="school_website" name="school_website" maxlength="120"
             value="<?= e(setting('school_website')) ?>" placeholder="kamalasciencecampus.edu.np/plus2">
    </div>
    <div class="p-field">
      <label for="school_map_url"><?= te('school_map_url') ?></label>
      <input type="url" id="school_map_url" name="school_map_url" maxlength="500"
             value="<?= e(setting('school_map_url')) ?>" placeholder="<?= e(SCHOOL_MAP_URL) ?>">
      <span class="hint"><?= te('school_map_hint') ?></span>
    </div>
    <p class="hint"><?= te('school_hint') ?></p>
    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
    </div>
  </form>
</section>

<section class="p-card" id="photos">
  <h2><?= te('photos_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('photos_intro') ?></p>
  <?php /* A count, not a survey: working out how many still need cropping
           means opening every photograph on disk, which is no business of a
           page opened to change a mail setting. The pass itself reports what
           it found. */ ?>
  <p style="font-size:.9rem;color:var(--ink-soft);margin-top:10px;">
    <?= te('photos_count', localize_digits((string) $counts['photos'])) ?>
  </p>
  <form method="post" style="margin-top:14px;">
    <?= csrf_field() ?>
    <button class="p-btn p-btn-gold" type="submit" name="action" value="recrop_photos"><?= te('photos_run') ?></button>
  </form>
</section>

<section class="p-card">
  <h2><?= te('mail_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('mail_intro') ?></p>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_mail">
    <div class="p-field">
      <label style="display:flex;align-items:center;gap:9px;font-weight:500;">
        <input type="checkbox" name="mail_enabled" value="1" style="width:auto;"
               <?= setting_bool('mail_enabled') ? 'checked' : '' ?>>
        <?= te('mail_enable') ?>
      </label>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="mail_from"><?= te('mail_from') ?></label>
        <input type="email" id="mail_from" name="mail_from" value="<?= e(setting('mail_from')) ?>"
               placeholder="plus2@kamalasciencecampus.edu.np">
        <span class="hint"><?= te('mail_from_hint') ?></span>
      </div>
      <div class="p-field">
        <label for="mail_from_name"><?= te('mail_from_name') ?></label>
        <input type="text" id="mail_from_name" name="mail_from_name"
               value="<?= e(setting('mail_from_name', 'Shree Kamala Secondary School')) ?>">
      </div>
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
    </div>
  </form>

  <form method="post" style="margin-top:22px;padding-top:22px;border-top:1px solid var(--line);">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="test_mail">
    <div class="p-field">
      <label for="test_to"><?= te('mail_test') ?></label>
      <input type="email" id="test_to" name="test_to" value="<?= e($admin['email']) ?>">
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-ghost" type="submit"><?= te('mail_test_send') ?></button>
    </div>
  </form>
</section>

<section class="p-card">
  <h2><?= te('system_counts') ?></h2>
  <div class="p-grid p-grid-3">
    <dl class="p-stat"><dt><?= te('total_students') ?></dt><dd><?= e(localize_digits((string) $counts['students'])) ?></dd></dl>
    <dl class="p-stat"><dt><?= te('total_teachers') ?></dt><dd><?= e(localize_digits((string) $counts['teachers'])) ?></dd></dl>
    <dl class="p-stat"><dt><?= te('photo') ?></dt><dd><?= e(localize_digits((string) $counts['photos'])) ?></dd></dl>
  </div>
</section>
<?php id_card_scripts(); ?>
<script>
// The preview answers the picker straight away: the card reads its colourway
// and its orientation off two attributes, so nothing has to be re-rendered.
(function () {
  var form  = document.getElementById('idc-design-form');
  var sheet = document.querySelector('.idc-design-preview .idc-sheet');
  if (!form || !sheet) return;

  form.addEventListener('change', function () {
    var data = new FormData(form);
    sheet.querySelectorAll('.idc').forEach(function (card) {
      card.setAttribute('data-theme', data.get('id_card_theme') || 'school');
      card.setAttribute('data-orientation', data.get('id_card_orientation') || 'portrait');
    });
    sheet.setAttribute('data-sides', data.get('id_card_sides') || 'both');
    sheet.setAttribute('data-orientation', data.get('id_card_orientation') || 'portrait');
  });
})();
</script>
<?php layout_foot(); ?>
