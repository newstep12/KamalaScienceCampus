<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const ROLE_STUDENT  = 'student';
const ROLE_LECTURER = 'lecturer';
const ROLE_ADMIN    = 'admin';

const MAX_LOGIN_ATTEMPTS = 6;      // per email+IP
const LOGIN_WINDOW_MIN   = 15;     // minutes

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_name('kscportal');
    session_start();

    // Bind the session to the user agent to blunt trivial session theft.
    $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|ksc');
    if (!isset($_SESSION['fp'])) {
        $_SESSION['fp'] = $fingerprint;
    } elseif (!hash_equals($_SESSION['fp'], $fingerprint)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['fp'] = $fingerprint;
    }
}

function current_user(): ?array
{
    static $user = null;
    static $loaded = false;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    start_session();
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $user = one('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $_SESSION['uid']]);

    // A user deleted or suspended mid-session loses access immediately.
    if (!$user || $user['status'] !== 'active') {
        $user = null;
        session_unset();
        session_destroy();
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_role(): ?string
{
    $u = current_user();
    return $u['role'] ?? null;
}

/** Require any logged-in user; otherwise bounce to login. */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $target = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . portal_url('/index.php') . '?next=' . urlencode($target));
        exit;
    }

    // An account created by an admin carries a temporary password. Hold the
    // user on the change-password page until they have chosen their own.
    if (!empty($user['must_change_password'])) {
        $here = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if (!in_array($here, ['change-password.php', 'logout.php'], true)) {
            header('Location: ' . portal_url('/change-password.php'));
            exit;
        }
    }
    return $user;
}

/**
 * A temporary password an admin can read aloud or type into a message.
 * Ambiguous characters (0/O, 1/l/I) are left out so it survives being
 * written on paper and retyped.
 */
function temporary_password(int $length = 12): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    // Guarantee the mix the strength check expects.
    return $out . random_int(2, 9);
}

/** Require one of the given roles. */
function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/../403.php';
        exit;
    }
    return $user;
}

function portal_url(string $path = ''): string
{
    $base = rtrim(config()['base_url'] ?? '/portal', '/');
    return $base . $path;
}

/* ---------------------------------------------------------------- login --- */

function recent_failed_attempts(string $email, string $ip): int
{
    return (int) scalar(
        // LOGIN_WINDOW_MIN is interpolated, not bound: MySQL does not accept a
        // placeholder inside INTERVAL. It is an integer constant, never input.
        'SELECT COUNT(*) FROM login_attempts
          WHERE email = ? AND ip_address = ? AND succeeded = 0
            AND attempted_at > (NOW() - INTERVAL ' . (int) LOGIN_WINDOW_MIN . ' MINUTE)',
        [$email, $ip]
    );
}

function record_attempt(string $email, string $ip, bool $ok): void
{
    q('INSERT INTO login_attempts (email, ip_address, succeeded) VALUES (?, ?, ?)', [$email, $ip, $ok ? 1 : 0]);
    // Opportunistic cleanup so the table cannot grow without bound.
    if (random_int(1, 50) === 1) {
        q('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    }
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/**
 * Attempt a login.
 *
 * @return array{ok:bool, error?:string, user?:array}
 */
function attempt_login(string $email, string $password): array
{
    $email = strtolower(trim($email));
    $ip    = client_ip();

    if (recent_failed_attempts($email, $ip) >= MAX_LOGIN_ATTEMPTS) {
        return ['ok' => false, 'error' => 'too_many'];
    }

    $user = one('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);

    // Always run a hash verification so a missing account and a wrong password
    // take a comparable amount of time.
    $hash = $user['password_hash'] ?? '$2y$12$usesomesillystringfaketohashagainstx.timingattackdummy00';
    $valid = password_verify($password, $hash);

    if (!$user || !$valid) {
        record_attempt($email, $ip, false);
        return ['ok' => false, 'error' => 'bad_credentials'];
    }

    if ($user['status'] === 'pending') {
        record_attempt($email, $ip, false);
        return ['ok' => false, 'error' => 'pending'];
    }
    if ($user['status'] === 'rejected') {
        record_attempt($email, $ip, false);
        return ['ok' => false, 'error' => 'rejected', 'user' => $user];
    }
    if ($user['status'] !== 'active') {
        record_attempt($email, $ip, false);
        return ['ok' => false, 'error' => 'suspended'];
    }

    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?',
          [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }

    record_attempt($email, $ip, true);
    start_session();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
    q('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);

    return ['ok' => true, 'user' => $user];
}

function logout(): void
{
    start_session();
    session_unset();
    session_destroy();
}

/** Where a user lands after signing in. */
function home_for(array $user): string
{
    switch ($user['role']) {
        case ROLE_ADMIN:    return portal_url('/admin/index.php');
        case ROLE_LECTURER: return portal_url('/lecturer/index.php');
        default:            return portal_url('/student/index.php');
    }
}

/* ----------------------------------------------------------------- CSRF --- */

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function verify_csrf(): void
{
    start_session();
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(419);
        exit('Your session expired. Please go back, reload the page and try again.');
    }
}

/* ---------------------------------------------------------------- flash --- */

function flash(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    start_session();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
