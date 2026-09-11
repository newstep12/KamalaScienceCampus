<?php
declare(strict_types=1);
/**
 * One-time setup. Writes inc/config.php, creates the schema, and makes the
 * first administrator account. Refuses to run once inc/config.php exists, so
 * it cannot be replayed by a visitor.
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

$configPath = __DIR__ . '/inc/config.php';
$installed  = is_file($configPath);
$errors     = [];
$done       = false;
$repair     = null;

if ($installed && isset($_GET['repair'])) {
    try {
        $c = require $configPath;
        $d = $c['db'];
        $pdo = new PDO(
            "mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4",
            $d['user'], $d['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $repair = run_schema($pdo);
    } catch (Throwable $e) {
        $errors[] = 'Repair failed: ' . $e->getMessage();
    }
}

if (!$installed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass = (string) ($_POST['db_pass'] ?? '');

    $adminName  = trim((string) ($_POST['admin_name'] ?? ''));
    $adminEmail = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
    $adminPass  = (string) ($_POST['admin_pass'] ?? '');

    if ($dbName === '' || $dbUser === '')                  { $errors[] = 'Enter the database name and user.'; }
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
                    'base_url'    => '/portal',
                    'campus_name' => 'Kamala Science Campus',
                    'max_upload'  => 20 * 1024 * 1024,
                ], true) . ";\n";

            if (file_put_contents($configPath, $config) === false) {
                throw new RuntimeException('Could not write inc/config.php. Check the folder permissions.');
            }
            @chmod($configPath, 0640);
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
<title>Set up the portal · Kamala Science Campus</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap">
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal">
<div class="p-auth" style="max-width:600px;">
  <div class="p-card">
  <?php if ($repair !== null): ?>
    <h1>Tables checked</h1>
    <p class="p-auth-intro">
      Ran <?= (int) $repair['ran'] ?> statements. The database now holds these tables:
    </p>
    <ul style="font-size:.92rem;color:var(--ink-soft);line-height:1.9;">
      <?php foreach ($repair['tables'] as $tbl): ?>
        <li><code><?= htmlspecialchars((string) $tbl, ENT_QUOTES) ?></code></li>
      <?php endforeach; ?>
    </ul>
    <p class="p-auth-intro"><strong>Now delete <code>portal/install.php</code> from the server.</strong></p>
    <a class="p-btn p-btn-primary p-btn-block" href="index.php">Go to the portal</a>

  <?php elseif ($errors && $installed): ?>
    <h1>Repair failed</h1>
    <?php foreach ($errors as $err): ?>
      <div class="p-flash p-flash-error"><?= htmlspecialchars($err, ENT_QUOTES) ?></div>
    <?php endforeach; ?>

  <?php elseif ($installed): ?>
    <h1>Already set up</h1>
    <p class="p-auth-intro">
      The portal is configured. For safety, delete <code>portal/install.php</code>
      from the server — it will refuse to run again, but removing it is tidier.
    </p>
    <a class="p-btn p-btn-primary p-btn-block" href="index.php">Go to the portal</a>

  <?php elseif ($done): ?>
    <h1>Setup complete</h1>
    <p class="p-auth-intro">
      The database tables were created and your administrator account is ready.
      Sign in with the email and password you just chose.
    </p>
    <p class="p-auth-intro"><strong>Now delete <code>portal/install.php</code> from the server.</strong></p>
    <a class="p-btn p-btn-primary p-btn-block" href="index.php">Sign in</a>

  <?php else: ?>
    <h1>Set up the portal</h1>
    <p class="p-auth-intro">
      Create a MySQL database in hPanel first, then enter its details here.
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
