<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/mail.php';
require_once __DIR__ . '/../inc/uploads.php';
require_once __DIR__ . '/../inc/idcard-view.php';
require_once __DIR__ . '/../inc/signatures.php';
require_once __DIR__ . '/../inc/photos.php';
require_once __DIR__ . '/../inc/translate.php';

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
    return [
        // Duplicate-column errors are caught and logged by the caller, so this
        // is safe to re-run on an install that already has the column.
        'password reset flag' =>
            'ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0',
        'notice categories' =>
            "ALTER TABLE notices MODIFY COLUMN category
             ENUM('tu','exam','campus','ugc','scholarship') NOT NULL DEFAULT 'tu'",
        // The title a staff identity card carries — Assistant Professor and
        // the rest. Students do not use it.
        'staff designation' =>
            'ALTER TABLE users ADD COLUMN designation VARCHAR(80) NULL',
        // Marks Nepali text that automatic translation wrote, so a correction
        // an admin makes by hand is never replaced by the machine again.
        'notice translation flags' =>
            'ALTER TABLE notices ADD COLUMN title_ne_auto TINYINT(1) NOT NULL DEFAULT 0',
        'notice body translation flag' =>
            'ALTER TABLE notices ADD COLUMN body_ne_auto TINYINT(1) NOT NULL DEFAULT 0',
        // Registration looks a symbol number up on every submission. Adding
        // the index twice raises "Duplicate key name", which the caller
        // catches and logs, so this is safe to re-run.
        'symbol number index' =>
            'ALTER TABLE users ADD INDEX idx_users_symbol (symbol_no)',
        // The two details the back of a card now carries, both filled in by
        // the holder from their own portfolio.
        'blood group' =>
            'ALTER TABLE users ADD COLUMN blood_group VARCHAR(8) NULL',
        'holder signature' =>
            'ALTER TABLE users ADD COLUMN signature_path VARCHAR(255) NULL',
        // The photograph a card photo was cut from, and where the frame sits
        // on it — so the crop is a decision that can be revisited.
        'photograph working copy' =>
            'ALTER TABLE users ADD COLUMN avatar_source_path VARCHAR(255) NULL',
        'photograph placement' =>
            'ALTER TABLE users ADD COLUMN avatar_focus TINYINT UNSIGNED NULL',
        // The two government numbers on the front of the card.
        'national id number' =>
            'ALTER TABLE users ADD COLUMN national_id VARCHAR(30) NULL',
        'pan number' =>
            'ALTER TABLE users ADD COLUMN pan_no VARCHAR(20) NULL',
    ];
}

/**
 * Official reference links every student and lecturer needs. These are real,
 * verified URLs — each was checked to resolve before being added here.
 */
function official_links(): array
{
    return [
        [
            'url'      => 'https://ugc.pathway.com.np/login',
            'category' => 'scholarship',
            'pinned'   => 1,
            'title_en' => 'UGC Nepal — scholarship and grant application portal',
            'title_ne' => 'यू.जी.सी. नेपाल — छात्रवृत्ति तथा अनुदान आवेदन पोर्टल',
            'body_en'  => "Apply online for University Grants Commission higher-education scholarships. "
                        . "Create an account or sign in, then complete the application form.

"
                        . "UGC scholarships support students with disabilities, Dalit students, students "
                        . "from economically disadvantaged families, children of martyrs and Muslim women, "
                        . "among others. Check the current call and its closing date before applying.

"
                        . "Scholarship Section, UGC, Sanothimi, Bhaktapur — 01-6638549 / 6638550 / 6638551.",
            'body_ne'  => "विश्वविद्यालय अनुदान आयोगको उच्च शिक्षा छात्रवृत्तिका लागि अनलाइन आवेदन दिनुहोस्। "
                        . "खाता खोली वा लग इन गरी आवेदन फाराम भर्नुहोस्।

"
                        . "यू.जी.सी. छात्रवृत्तिले अपांगता भएका विद्यार्थी, दलित विद्यार्थी, आर्थिक रूपमा विपन्न "
                        . "परिवारका विद्यार्थी, सहिद परिवारका सन्तान र मुस्लिम महिलालगायतलाई सहयोग गर्दछ। "
                        . "आवेदन दिनुअघि हालको सूचना र अन्तिम मिति हेर्नुहोस्।

"
                        . "छात्रवृत्ति शाखा, यू.जी.सी., सानोठिमी, भक्तपुर — ०१-६६३८५४९ / ६६३८५५० / ६६३८५५१।",
        ],
        [
            'url'      => 'https://iost.tu.edu.np/notices',
            'category' => 'tu',
            'pinned'   => 1,
            'title_en' => 'TU Institute of Science and Technology — official notices',
            'title_ne' => 'त्रि.वि. विज्ञान तथा प्रविधि अध्ययन संस्थान — आधिकारिक सूचना',
            'body_en'  => "The Institute of Science and Technology (IOST) publishes every official notice "
                        . "for B.Sc. programs here: examination schedules, form fill-up notices, exam "
                        . "centres and results.

"
                        . "IOST sets the B.Sc. curriculum and issues the degree, so this page is the "
                        . "authoritative source — always confirm a date against it rather than relying on "
                        . "second-hand notices.",
            'body_ne'  => "विज्ञान तथा प्रविधि अध्ययन संस्थान (IOST) ले बी.एस्सी. कार्यक्रमका सबै आधिकारिक "
                        . "सूचना यहीँ प्रकाशित गर्दछ: परीक्षा तालिका, फाराम भर्ने सूचना, परीक्षा केन्द्र र नतिजा।

"
                        . "बी.एस्सी.को पाठ्यक्रम निर्धारण र डिग्री प्रदान IOST ले नै गर्ने भएकाले यो पृष्ठ नै "
                        . "आधिकारिक स्रोत हो — कुनै पनि मिति अन्यत्रको सूचनामा भर नपरी यहीँबाट पुष्टि गर्नुहोस्।",
        ],
        [
            'url'      => 'https://ugcnepal.edu.np/category/scholarship/',
            'category' => 'scholarship',
            'pinned'   => 0,
            'title_en' => 'UGC Nepal — scholarship announcements and results',
            'title_ne' => 'यू.जी.सी. नेपाल — छात्रवृत्ति सूचना तथा नतिजा',
            'body_en'  => "Current scholarship calls, eligibility criteria, required documents and "
                        . "published results from the University Grants Commission.

"
                        . "Check here for the closing date before starting an application.",
            'body_ne'  => "विश्वविद्यालय अनुदान आयोगका हालका छात्रवृत्ति सूचना, योग्यताका आधार, आवश्यक कागजात "
                        . "र प्रकाशित नतिजा।

"
                        . "आवेदन सुरु गर्नुअघि अन्तिम मिति यहीँ हेर्नुहोस्।",
        ],
        [
            'url'      => 'https://ugcnepal.edu.np/category/notice/',
            'category' => 'ugc',
            'pinned'   => 0,
            'title_en' => 'UGC Nepal — general notices',
            'title_ne' => 'यू.जी.सी. नेपाल — सामान्य सूचना',
            'body_en'  => "Notices from the University Grants Commission, the body that recognises this "
                        . "campus and oversees higher education in Nepal.",
            'body_ne'  => "विश्वविद्यालय अनुदान आयोगका सूचनाहरू — यही निकायले यस क्याम्पसलाई मान्यता दिन्छ "
                        . "र नेपालको उच्च शिक्षाको नियमन गर्दछ।",
        ],
    ];
}

/**
 * The B.Sc. General shape published for this campus: a broad base narrowing to
 * one major with project work. Codes are PROVISIONAL — replace them with the
 * real Tribhuvan University codes; they are not official.
 */
function starter_courses(): array
{
    return [
        // [code, year, en, ne, credits]
        ['PHY-101', 1, 'Physics I',                  'भौतिकशास्त्र I',            3.0],
        ['CHE-101', 1, 'Chemistry I',                'रसायनशास्त्र I',            3.0],
        ['MTH-101', 1, 'Mathematics I',              'गणित I',                     3.0],
        ['BOT-101', 1, 'Botany I',                   'वनस्पतिशास्त्र I',          3.0],
        ['ZOO-101', 1, 'Zoology I',                  'प्राणीशास्त्र I',           3.0],
        ['SCM-101', 1, 'Scientific Communication',   'वैज्ञानिक सञ्चार',          2.0],

        ['PHY-201', 2, 'Physics II',                 'भौतिकशास्त्र II',           3.0],
        ['CHE-201', 2, 'Chemistry II',               'रसायनशास्त्र II',           3.0],
        ['MTH-201', 2, 'Mathematics II',             'गणित II',                    3.0],
        ['BOT-201', 2, 'Botany II',                  'वनस्पतिशास्त्र II',         3.0],
        ['ZOO-201', 2, 'Zoology II',                 'प्राणीशास्त्र II',          3.0],
        ['STA-201', 2, 'Applied Statistics',         'व्यावहारिक तथ्यांकशास्त्र', 2.0],

        ['PHY-301', 3, 'Physics III',                'भौतिकशास्त्र III',          3.0],
        ['CHE-301', 3, 'Chemistry III',              'रसायनशास्त्र III',          3.0],
        ['BOT-301', 3, 'Botany III',                 'वनस्पतिशास्त्र III',        3.0],
        ['ZOO-301', 3, 'Zoology III',                'प्राणीशास्त्र III',         3.0],
        ['RSM-301', 3, 'Research Methodology',       'अनुसन्धान विधि',            2.0],
        ['ELE-301', 3, 'Elective Course',            'ऐच्छिक विषय',                3.0],

        ['MAJ-401', 4, 'Major Subject',              'मुख्य विषय',                 4.0],
        ['PRJ-401', 4, 'Project / Field Work',       'परियोजना / क्षेत्रगत कार्य', 4.0],
        ['CMP-401', 4, 'Computational Course',       'कम्प्युटेसनल विषय',         3.0],
        ['IDC-401', 4, 'Interdisciplinary Course',   'अन्तरविषयक विषय',           3.0],
    ];
}

$result = null;
$seeded = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'migrate') {
            $result = apply_schema();
            log_activity((int) $admin['id'], 'apply_schema', implode(', ', $result['added']) ?: 'no change');
            flash('ok', t('db_updated'));
        } elseif ($action === 'seed_links') {
            $added = 0;
            foreach (official_links() as $l) {
                // Keyed on the URL so re-running never duplicates, and never
                // overwrites wording the campus has since edited.
                if (scalar('SELECT 1 FROM notices WHERE source_url = ?', [$l['url']])) {
                    continue;
                }
                q('INSERT INTO notices (category, title_en, title_ne, body_en, body_ne,
                          source_url, year_level, is_pinned, is_published, created_by)
                   VALUES (?, ?, ?, ?, ?, ?, NULL, ?, 1, ?)',
                  [$l['category'], $l['title_en'], $l['title_ne'], $l['body_en'], $l['body_ne'],
                   $l['url'], $l['pinned'], $admin['id']]);
                $added++;
            }
            log_activity((int) $admin['id'], 'seed_links', (string) $added);
            flash('ok', t('links_seeded', localize_digits((string) $added)));
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
        } elseif ($action === 'save_translation') {
            $provider = (string) ($_POST['translate_provider'] ?? 'glossary');
            $email    = trim((string) ($_POST['translate_contact_email'] ?? ''));
            $endpoint = trim((string) ($_POST['translate_endpoint'] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', t('err_email_bad'));
            } elseif ($endpoint !== '' && !preg_match('#^https?://#i', $endpoint)) {
                flash('error', t('err_bad_url'));
            } else {
                set_setting('translate_enabled',  isset($_POST['translate_enabled']) ? '1' : '0');
                set_setting('translate_provider', in_array($provider, TRANSLATE_PROVIDERS, true) ? $provider : 'glossary');
                set_setting('translate_endpoint', $endpoint ?: null);
                set_setting('translate_contact_email', $email ?: null);
                // An empty key box leaves the stored key alone: it is never
                // shown back, so blanking it would be the usual outcome of
                // changing any other setting on this form.
                $key = trim((string) ($_POST['translate_api_key'] ?? ''));
                if ($key !== '') {
                    set_setting('translate_api_key', $key);
                } elseif (isset($_POST['clear_api_key'])) {
                    set_setting('translate_api_key', null);
                }
                // A provider change deserves a fresh attempt rather than the
                // rest of a backoff the previous one earned.
                set_setting('translate_backoff_until', null);
                flash('ok', t('translate_saved'));
            }
        } elseif ($action === 'translate_notices') {
            set_setting('translate_backoff_until', null);
            $done = backfill_notice_translations(isset($_POST['redo_auto']));
            log_activity((int) $admin['id'], 'translate_notices', (string) $done['translated']);
            flash(
                $done['translated'] ? 'ok' : 'info',
                t('translate_done', localize_digits((string) $done['translated']), localize_digits((string) $done['left']))
            );
        } elseif ($action === 'test_mail') {
            $to = trim((string) ($_POST['test_to'] ?? '')) ?: (string) $admin['email'];
            $sent = send_notification(
                $to,
                'Test email from the Kamala Science Campus portal',
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
            $chiefTitle = (string) ($_POST['id_card_chief_title'] ?? 'campus_chief');
            set_setting('id_card_chief_name',    trim((string) ($_POST['id_card_chief_name'] ?? '')) ?: null);
            set_setting('id_card_chief_name_ne', trim((string) ($_POST['id_card_chief_name_ne'] ?? '')) ?: null);
            set_setting('id_card_chief_title',   in_array($chiefTitle, designations(), true) ? $chiefTitle : 'campus_chief');
            set_setting('id_card_valid_until',   parse_date((string) ($_POST['id_card_valid_until'] ?? '')));
            set_setting('id_card_session',       mb_substr(trim((string) ($_POST['id_card_session'] ?? '')), 0, 40) ?: null);
            log_activity((int) $admin['id'], 'save_idcard');
            flash('ok', t('idcard_saved'));
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
        } elseif ($action === 'seed_courses') {
            $added = 0;
            foreach (starter_courses() as [$code, $year, $en, $ne, $cr]) {
                // INSERT IGNORE on the unique code: re-running never duplicates,
                // and never overwrites a course the campus has since edited.
                $stmt = q(
                    'INSERT IGNORE INTO courses (code, title_en, title_ne, year_level, credit_hours)
                     VALUES (?, ?, ?, ?, ?)',
                    [$code, $en, $ne, $year, $cr]
                );
                $added += $stmt->rowCount();
            }
            $seeded = $added;
            log_activity((int) $admin['id'], 'seed_courses', (string) $added);
            flash('ok', t('courses_seeded', localize_digits((string) $added)));
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
    'courses'  => (int) scalar('SELECT COUNT(*) FROM courses'),
    'students' => (int) scalar('SELECT COUNT(*) FROM users WHERE role = \'student\''),
    'notices'  => (int) scalar('SELECT COUNT(*) FROM notices'),
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

<section class="p-card">
  <h2><?= te('seed_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('seed_intro') ?></p>
  <p class="p-flash p-flash-info" style="margin-top:14px;"><?= te('seed_codes_warning') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <button class="p-btn p-btn-gold" type="submit" name="action" value="seed_courses"><?= te('seed_run') ?></button>
  </form>
</section>

<section class="p-card">
  <h2><?= te('links_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('links_intro') ?></p>
  <ul style="font-size:.92rem;color:var(--ink-soft);line-height:1.9;margin:14px 0;">
    <?php foreach (official_links() as $l): ?>
      <li><?= e(is_nepali() ? $l['title_ne'] : $l['title_en']) ?>
        <?php if ($l['pinned']): ?><span class="p-tag pin"><?= te('pinned') ?></span><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <form method="post">
    <?= csrf_field() ?>
    <button class="p-btn p-btn-gold" type="submit" name="action" value="seed_links"><?= te('links_run') ?></button>
  </form>
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
        <figure class="idc-holder"><?php id_card_face($admin, $preview, 'front'); ?></figure>
        <figure class="idc-holder idc-holder-back"><?php id_card_face($admin, $preview, 'back'); ?></figure>
      </div>
    </div>
  </div>

  <div style="margin-top:24px;padding-top:24px;border-top:1px solid var(--line);">
    <h3 style="margin:0 0 6px;font-size:1rem;"><?= te('signature') ?></h3>
    <?php $cardSig = signature_for_use('id_card'); ?>
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
        <input type="text" id="chief_name" name="id_card_chief_name" value="<?= e(setting('id_card_chief_name')) ?>">
      </div>
      <div class="p-field">
        <label for="chief_name_ne"><?= te('chief_name_ne') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="text" id="chief_name_ne" name="id_card_chief_name_ne" lang="ne"
               value="<?= e(setting('id_card_chief_name_ne')) ?>">
      </div>
    </div>
    <div class="p-field-row">
      <div class="p-field">
        <label for="chief_title"><?= te('chief_title') ?></label>
        <select id="chief_title" name="id_card_chief_title">
          <?php foreach (designations() as $d): ?>
            <option value="<?= e($d) ?>" <?= setting('id_card_chief_title', 'campus_chief') === $d ? 'selected' : '' ?>>
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
               placeholder="admin@kamalasciencecampus.edu.np">
        <span class="hint"><?= te('mail_from_hint') ?></span>
      </div>
      <div class="p-field">
        <label for="mail_from_name"><?= te('mail_from_name') ?></label>
        <input type="text" id="mail_from_name" name="mail_from_name"
               value="<?= e(setting('mail_from_name', 'Kamala Science Campus')) ?>">
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

<section class="p-card" id="translation">
  <h2><?= te('translate_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('translate_intro') ?></p>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_translation">

    <div class="p-field">
      <label style="display:flex;align-items:center;gap:9px;font-weight:500;">
        <input type="checkbox" name="translate_enabled" value="1" style="width:auto;"
               <?= translation_enabled() ? 'checked' : '' ?>>
        <?= te('translate_enable') ?>
      </label>
    </div>

    <div class="p-field">
      <label><?= te('translate_provider') ?></label>
      <?php foreach (TRANSLATE_PROVIDERS as $provider): ?>
        <label style="display:flex;align-items:flex-start;gap:9px;font-weight:400;margin-bottom:8px;">
          <input type="radio" name="translate_provider" value="<?= e($provider) ?>" style="width:auto;margin-top:4px;"
                 <?= translation_provider() === $provider ? 'checked' : '' ?>>
          <span>
            <strong><?= te('translate_' . $provider) ?></strong><br>
            <span class="hint"><?= te('translate_' . $provider . '_hint') ?></span>
          </span>
        </label>
      <?php endforeach; ?>
    </div>

    <div class="p-field-row">
      <div class="p-field">
        <label for="translate_contact_email"><?= te('translate_contact') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="email" id="translate_contact_email" name="translate_contact_email"
               value="<?= e(setting('translate_contact_email')) ?>" placeholder="<?= e($admin['email']) ?>">
        <span class="hint"><?= te('translate_contact_hint') ?></span>
      </div>
      <div class="p-field">
        <label for="translate_endpoint"><?= te('translate_endpoint') ?> <span class="hint"><?= te('optional') ?></span></label>
        <input type="url" id="translate_endpoint" name="translate_endpoint"
               value="<?= e(setting('translate_endpoint')) ?>" placeholder="https://libretranslate.example.org">
        <span class="hint"><?= te('translate_endpoint_hint') ?></span>
      </div>
    </div>

    <div class="p-field">
      <label for="translate_api_key"><?= te('translate_key') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="password" id="translate_api_key" name="translate_api_key" value="" autocomplete="off"
             placeholder="<?= setting('translate_api_key') ? e(t('translate_key_stored')) : '' ?>">
      <span class="hint"><?= te('translate_key_hint') ?></span>
      <?php if (setting('translate_api_key')): ?>
        <label style="display:flex;align-items:center;gap:9px;font-weight:500;margin-top:8px;">
          <input type="checkbox" name="clear_api_key" value="1" style="width:auto;">
          <?= te('translate_key_clear') ?>
        </label>
      <?php endif; ?>
    </div>

    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('save') ?></button>
    </div>
  </form>

  <form method="post" style="margin-top:22px;padding-top:22px;border-top:1px solid var(--line);">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="translate_notices">
    <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('translate_backfill_intro') ?></p>
    <div class="p-field">
      <label style="display:flex;align-items:center;gap:9px;font-weight:500;">
        <input type="checkbox" name="redo_auto" value="1" style="width:auto;">
        <?= te('translate_redo') ?>
      </label>
    </div>
    <div class="p-form-actions">
      <button class="p-btn p-btn-gold" type="submit"><?= te('translate_backfill_run') ?></button>
    </div>
  </form>
</section>

<section class="p-card">
  <h2><?= te('system_counts') ?></h2>
  <div class="p-grid p-grid-3">
    <dl class="p-stat"><dt><?= te('total_courses') ?></dt><dd><?= e(localize_digits((string) $counts['courses'])) ?></dd></dl>
    <dl class="p-stat"><dt><?= te('total_students') ?></dt><dd><?= e(localize_digits((string) $counts['students'])) ?></dd></dl>
    <dl class="p-stat"><dt><?= te('notices_title') ?></dt><dd><?= e(localize_digits((string) $counts['notices'])) ?></dd></dl>
  </div>
</section>
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
      card.setAttribute('data-theme', data.get('id_card_theme') || 'navy');
      card.setAttribute('data-orientation', data.get('id_card_orientation') || 'portrait');
    });
    sheet.setAttribute('data-sides', data.get('id_card_sides') || 'both');
    sheet.setAttribute('data-orientation', data.get('id_card_orientation') || 'portrait');
  });
})();
</script>
<?php layout_foot(); ?>
