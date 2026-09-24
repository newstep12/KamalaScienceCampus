<?php
declare(strict_types=1);
/**
 * One-time setup for the +2 Science portal of Shree Kamala Secondary School.
 * Writes inc/config.php, creates the schema, and makes the first
 * administrator account. Refuses to run once inc/config.php exists, so it
 * cannot be replayed by a visitor.
 *
 * Give it a database of its own — not the Kamala Science Campus portal's.
 * The two portals share no tables, and this refuses a database that already
 * holds the campus portal's tables, so the campus's records cannot be touched
 * from here even by mistake.
 */

/**
 * Execute sql/schema.sql.
 *
 * Comment lines are stripped BEFORE splitting. Skipping any chunk that merely
 * began with '--' silently discarded the CREATE TABLE that followed a comment,
 * which is how five tables came to be missing from the first install.
 */
function run_schema(PDO $pdo): array
{
    $sql = file_get_contents(__DIR__ . '/../sql/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('sql/schema.sql is missing.');
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);

    $ran = 0;
    foreach (preg_split('/;\s*[\r\n]/', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            $pdo->exec($stmt);
            $ran++;
        }
    }

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    return ['ran' => $ran, 'tables' => $tables];
}

require_once __DIR__ . '/inc/config-path.php';

// Outside public_html when the server allows it, so a deploy cannot delete
// it — see inc/config-path.php. $configPath is wherever it ends up.
$configPaths = portal_config_paths();
$configPath  = portal_config_file() ?? reset($configPaths);
$installed   = portal_config_file() !== null;
$savedInside = false;
$errors     = [];
$done       = false;

/**
 * The installer is locked unless the site's owner has unlocked it.
 *
 * A deploy puts this file on the live site before anybody has run it, and
 * until then whoever reaches it first could point the portal at a database
 * of their own and make themselves its administrator. So it does nothing —
 * no form, no repair — until a file named INSTALL-UNLOCK exists in inc/,
 * which only someone with access to the server's files can create (hPanel →
 * File Manager). inc/ is never served, and the file is git-ignored so it can
 * never arrive with a deploy. A successful setup deletes it again.
 */
$unlockPath = __DIR__ . '/inc/INSTALL-UNLOCK';
$locked     = !is_file($unlockPath);
if ($locked) {
    http_response_code(403);
}

/**
 * The database must be on this server. Hostinger's MySQL is "localhost", as
 * is XAMPP's; a remote host is refused, so the installer can never be used
 * to send the school's records to a database somewhere else.
 */
function local_db_host(string $host): bool
{
    return in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
}

// Once the portal is set up there is nothing left for this page to do: the
// schema is kept current from Admin → System → Run database updates, behind
// sign-in. An unlock file left behind — the delete below failed, or somebody
// created it again — is removed on sight, and if it cannot be the page says
// so plainly rather than sitting unlocked.
$unlockStuck = false;
if ($installed && is_file($unlockPath)) {
    @unlink($unlockPath);
    $unlockStuck = is_file($unlockPath);
}

if (!$locked && !$installed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass = (string) ($_POST['db_pass'] ?? '');

    $adminName  = trim((string) ($_POST['admin_name'] ?? ''));
    $adminEmail = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
    $adminPass  = (string) ($_POST['admin_pass'] ?? '');

    if (!local_db_host($dbHost))                            { $errors[] = 'The database host must be localhost — the database on this server.'; }
    if ($dbName === '' || $dbUser === '')                  { $errors[] = 'Enter the database name and user.'; }
    // Letters, digits and underscores only — every real name looks like
    // u849870167_plus2. The name goes into the connection string, where a
    // second "host=" smuggled in after a semicolon would override the
    // localhost check above, since PDO takes the last value it reads.
    elseif (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbName) || !preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbUser)) {
        $errors[] = 'The database name and user may contain only letters, digits and underscores.';
    }
    if ($adminName === '')                                  { $errors[] = 'Enter the administrator name.'; }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL))    { $errors[] = 'Enter a valid administrator email.'; }
    if (mb_strlen($adminPass) < 10)                         { $errors[] = 'The administrator password must be at least 10 characters.'; }

    $pdo = null;
    if (!$errors) {
        try {
            $pdo = new PDO(
                "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
            );
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), '1045')) {
                $errors[] = 'The database rejected those credentials. Check the password, '
                    . 'and make sure your browser has not autofilled a saved password over it — '
                    . 'clear the field and type the password from hPanel by hand.';
            } else {
                $errors[] = 'Could not connect to the database: ' . $e->getMessage();
            }
        }
    }

    // The campus portal's database has a `courses` table; this one never
    // does. Refuse it, so a mistyped database name cannot mix the school's
    // accounts in with the campus's.
    if (!$errors && $pdo) {
        $campus = $pdo->query("SHOW TABLES LIKE 'courses'")->fetchColumn();
        if ($campus !== false) {
            $errors[] = 'That database belongs to the Kamala Science Campus portal. '
                . 'Create a new, empty database for the +2 portal in hPanel and use that one.';
        }
    }

    if (!$errors && $pdo) {
        try {
            run_schema($pdo);

            $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $exists->execute([$adminEmail]);
            if ($exists->fetchColumn()) {
                $pdo->prepare('UPDATE users SET password_hash = ?, role = \'admin\', status = \'active\' WHERE email = ?')
                    ->execute([password_hash($adminPass, PASSWORD_DEFAULT), $adminEmail]);
            } else {
                $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role, status, approved_at)
                     VALUES (?, ?, ?, \'admin\', \'active\', NOW())'
                )->execute([$adminName, $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT)]);
            }

            $config = "<?php\n// Written by install.php. Keep this file out of version control.\nreturn "
                . var_export([
                    'db' => [
                        'host' => $dbHost, 'name' => $dbName, 'user' => $dbUser,
                        'password' => $dbPass, 'charset' => 'utf8mb4',
                    ],
                    // Where this portal is actually being served from: /plus2/portal
                    // on the live site, /KamalaScienceCampus/plus2/portal when
                    // testing from a folder under XAMPP's htdocs.
                    'base_url'    => rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/plus2/portal/install.php'))), '/'),
                    'school_name' => 'Shree Kamala Secondary School',
                ], true) . ";\n";

            // Outside the website first; inside inc/ only if the server will not
            // allow that, in which case the page says so.
            $written = false;
            foreach ($configPaths as $where => $candidate) {
                if (@file_put_contents($candidate, $config) !== false) {
                    $configPath  = $candidate;
                    $savedInside = $where === 'inside' && isset($configPaths['outside']);
                    $written     = true;
                    break;
                }
            }
            if (!$written) {
                throw new RuntimeException('Could not write the settings file. Check the folder permissions.');
            }
            // A copy left inside from an earlier install is removed, so there
            // is only ever one file holding the password.
            if ($configPath !== $configPaths['inside'] && is_file($configPaths['inside'])) {
                @unlink($configPaths['inside']);
            }
            @chmod($configPath, 0640);
            // Locked again: the unlock file has served its purpose.
            @unlink($unlockPath);
            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'Setup failed: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Set up the +2 portal · Shree Kamala Secondary School</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap">
<link rel="stylesheet" href="../../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal">
<div class="p-auth" style="max-width:600px;">
  <div class="p-card">
  <?php if ($locked): ?>
    <h1>The installer is locked</h1>
    <p class="p-auth-intro">
      <?php if ($installed): ?>
        The +2 portal is already set up. <a href="index.php">Go to the portal</a>.
      <?php else: ?>
        For safety, the installer runs only after the site&rsquo;s owner unlocks
        it. In hPanel&rsquo;s <strong>File Manager</strong>, open
        <code>public_html/plus2/portal/inc/</code>, create an empty file named
        <code>INSTALL-UNLOCK</code>, then reload this page. Setting up the portal
        locks it again.
      <?php endif; ?>
    </p>

  <?php elseif ($installed): ?>
    <h1>Already set up</h1>
    <?php if ($unlockStuck): ?>
      <div class="p-flash p-flash-error">
        <strong>Delete <code>plus2/portal/inc/INSTALL-UNLOCK</code> now</strong> in hPanel&rsquo;s
        File Manager. This page could not remove it itself.
      </div>
    <?php endif; ?>
    <p class="p-auth-intro">
      The portal is configured. For safety, delete <code>plus2/portal/install.php</code>
      from the server — it will refuse to run again, but removing it is tidier.
    </p>
    <a class="p-btn p-btn-primary p-btn-block" href="index.php">Go to the portal</a>

  <?php elseif ($done): ?>
    <h1>Setup complete</h1>
    <p class="p-auth-intro">
      The database tables were created and your administrator account is ready.
      Sign in with the email and password you just chose.
    </p>
    <?php if ($savedInside): ?>
      <div class="p-flash p-flash-error">
        The server would not allow the settings file outside the website, so it was
        saved in <code>plus2/portal/inc/config.php</code>. A deploy that rewrites the
        site can remove it; if the portal ever says it is being set up again, run
        this installer again — nothing already in the portal is lost.
      </div>
    <?php else: ?>
      <p class="p-auth-intro" style="font-size:.9rem;">
        The settings file was saved outside the website, where updates to the site
        cannot remove it.
      </p>
    <?php endif; ?>
    <a class="p-btn p-btn-primary p-btn-block" href="index.php">Sign in</a>

  <?php else: ?>
    <h1>Set up the +2 Science portal</h1>
    <p class="p-auth-intro">
      Shree Kamala Secondary School's portal keeps its own database, separate
      from the Kamala Science Campus portal's. Create a <strong>new, empty</strong>
      MySQL database in hPanel first, then enter its details here.
      Nothing you type on this page leaves your own server.
      If your browser offers to fill a saved password, dismiss it &mdash; type
      the database password from hPanel yourself.
    </p>

    <?php foreach ($errors as $err): ?>
      <div class="p-flash p-flash-error"><?= htmlspecialchars($err, ENT_QUOTES) ?></div>
    <?php endforeach; ?>

    <form method="post" autocomplete="off" spellcheck="false">
      <h2 style="font-size:1.05rem;margin-top:6px;">Database</h2>
      <div class="p-field">
        <label for="db_host">Host</label>
        <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars((string) ($_POST['db_host'] ?? 'localhost'), ENT_QUOTES) ?>">
      </div>
      <div class="p-field">
        <label for="db_name">Database name</label>
        <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars((string) ($_POST['db_name'] ?? ''), ENT_QUOTES) ?>" required placeholder="u849870167_…">
      </div>
      <div class="p-field">
        <label for="db_user">Database user</label>
        <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars((string) ($_POST['db_user'] ?? ''), ENT_QUOTES) ?>" required placeholder="u849870167_…">
      </div>
      <div class="p-field">
        <label for="db_pass">Database password</label>
        <input type="password" id="db_pass" name="db_pass" autocomplete="new-password" readonly required data-unlock>
      </div>

      <h2 style="font-size:1.05rem;margin-top:26px;">Administrator account</h2>
      <div class="p-field">
        <label for="admin_name">Full name</label>
        <input type="text" id="admin_name" name="admin_name" value="<?= htmlspecialchars((string) ($_POST['admin_name'] ?? ''), ENT_QUOTES) ?>" required>
      </div>
      <div class="p-field">
        <label for="admin_email">Email address</label>
        <input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars((string) ($_POST['admin_email'] ?? ''), ENT_QUOTES) ?>" required>
      </div>
      <div class="p-field">
        <label for="admin_pass">Password <span class="hint">At least 10 characters. This is the account that approves students.</span></label>
        <input type="password" id="admin_pass" name="admin_pass" autocomplete="new-password" readonly required data-unlock>
      </div>

      <div class="p-form-actions">
        <button class="p-btn p-btn-primary p-btn-block" type="submit">Create the portal</button>
      </div>
    </form>
  <?php endif; ?>
  </div>
</div>
<script>
// See the note on the password inputs: they start readonly so Chrome cannot
// autofill them, and unlock the moment the user actually goes to type.
document.querySelectorAll('[data-unlock]').forEach(function (el) {
  var unlock = function () { el.removeAttribute('readonly'); };
  el.addEventListener('focus', unlock);
  el.addEventListener('mousedown', unlock);
  el.addEventListener('touchstart', unlock);
});
// Refuse to submit a password field the browser filled but never showed us.
var form = document.querySelector('form');
if (form) {
  form.addEventListener('submit', function (e) {
    var pw = form.elements['db_pass'];
    if (pw && pw.value.length === 0) {
      e.preventDefault();
      pw.removeAttribute('readonly');
      pw.focus();
      alert('Type the database password from hPanel into the password field.');
    }
  });
}
</script>
</body>
</html>
