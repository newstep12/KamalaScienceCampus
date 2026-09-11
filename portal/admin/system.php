<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/mail.php';

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

    $after = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    return ['added' => array_values(array_diff($after, $before)), 'tables' => $after];
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
        } elseif ($action === 'save_mail') {
            set_setting('mail_enabled',   isset($_POST['mail_enabled']) ? '1' : '0');
            set_setting('mail_from',      trim((string) ($_POST['mail_from'] ?? '')) ?: null);
            set_setting('mail_from_name', trim((string) ($_POST['mail_from_name'] ?? '')) ?: null);
            flash('ok', t('mail_saved'));
        } elseif ($action === 'test_mail') {
            $to = trim((string) ($_POST['test_to'] ?? '')) ?: (string) $admin['email'];
            $sent = send_notification(
                $to,
                'Test email from the Kamala Science Campus portal',
                "This is a test message.\n\nIf you are reading it, notifications are working.\n"
            );
            flash($sent ? 'ok' : 'error', $sent ? t('mail_test_sent', $to) : t('mail_test_failed'));
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
        header('Location: ' . portal_url('/admin/system.php'));
        exit;
    }
}

$counts = [
    'courses'  => (int) scalar('SELECT COUNT(*) FROM courses'),
    'students' => (int) scalar('SELECT COUNT(*) FROM users WHERE role = \'student\''),
    'notices'  => (int) scalar('SELECT COUNT(*) FROM notices'),
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
               placeholder="noreply@kamalasciencecampus.edu.np">
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

<section class="p-card">
  <h2><?= te('system_counts') ?></h2>
  <div class="p-grid p-grid-3">
    <dl class="p-stat"><dt><?= te('total_courses') ?></dt><dd><?= e(localize_digits((string) $counts['courses'])) ?></dd></dl>
    <dl class="p-stat"><dt><?= te('total_students') ?></dt><dd><?= e(localize_digits((string) $counts['students'])) ?></dd></dl>
    <dl class="p-stat"><dt><?= te('notices_title') ?></dt><dd><?= e(localize_digits((string) $counts['notices'])) ?></dd></dl>
  </div>
</section>
<?php layout_foot(); ?>
