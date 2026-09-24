<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const ROLE_STUDENT  = 'student';
const ROLE_TEACHER = 'teacher';
const ROLE_ADMIN    = 'admin';

const MAX_LOGIN_ATTEMPTS = 6;      // per email+IP
const MAX_REGISTRATIONS_PER_HOUR = 60;   // per IP, successful ones — two classes on one school connection fit
const LOGIN_WINDOW_MIN   = 15;     // minutes

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    // A cookie of its own, under a name of its own and scoped to this portal's
    // own path, so it is never sent to — or confused with — the Kamala
    // Science Campus portal's `kscportal` session on the same domain. Signing
    // in to one portal signs you in to nothing in the other.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => rtrim(config()['base_url'] ?? '/plus2/portal', '/') . '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_name('sksportal');
    session_start();

    // Bind the session to the user agent to blunt trivial session theft.
    $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|sks');
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
    // sks_uid, not uid: the campus portal keeps its user in $_SESSION['uid'],
    // in the same PHP session store. A campus session id presented under this
    // portal's cookie name is already refused by the fingerprint check, but a
    // key of its own means it could never be read as a signed-in user here
    // even if that check changed.
    if (empty($_SESSION['sks_uid'])) {
        return null;
    }
    $user = one('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $_SESSION['sks_uid']]);

    // A user deleted or suspended mid-session loses access immediately — and
    // so does every session signed in before the password last changed, so a
    // reset by the office actually shuts out whoever had the old one.
    if (!$user || $user['status'] !== 'active'
        || !hash_equals((string) ($_SESSION['sks_pwv'] ?? ''), password_version((string) $user['password_hash']))) {
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
    $base = rtrim(config()['base_url'] ?? '/plus2/portal', '/');
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

/**
 * Registrations are counted in login_attempts under a marker that can never
 * be an email address, so no second table is needed and the same daily
 * pruning clears them.
 */
function recent_registrations(string $ip): int
{
    return (int) scalar(
        'SELECT COUNT(*) FROM login_attempts
          WHERE email = \'#register\' AND ip_address = ?
            AND attempted_at > (NOW() - INTERVAL 1 HOUR)',
        [$ip]
    );
}

function record_registration(string $ip): void
{
    record_attempt('#register', $ip, false);
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

    // Do the same hashing work whether or not the account exists, so response
    // time cannot reveal which emails are registered. Hashing at the default
    // cost takes as long as verifying a hash made at that cost; a hard-coded
    // dummy hash does not (the old one was cost 12, PHP 8.3 hashes at cost 10).
    if ($user) {
        $hash  = (string) $user['password_hash'];
        $valid = password_verify($password, $hash);
    } else {
        password_hash($password, PASSWORD_DEFAULT);
        $hash  = '';
        $valid = false;
    }

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
        $hash = password_hash($password, PASSWORD_DEFAULT);
        q('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $user['id']]);
    }

    record_attempt($email, $ip, true);
    start_session();
    session_regenerate_id(true);
    $_SESSION['sks_uid'] = (int) $user['id'];
    $_SESSION['sks_pwv'] = password_version($hash);    // the hash as stored now
    q('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);

    return ['ok' => true, 'user' => $user];
}

/**
 * A short fingerprint of the stored password hash, kept in the session at
 * sign-in. When the password changes the hash changes, and every session
 * holding the old fingerprint is signed out on its next request.
 */
function password_version(string $hash): string
{
    return substr(hash('sha256', $hash), 0, 24);
}

/**
 * Keep the current session signed in after its own user changes their
 * password — every other session of theirs is signed out.
 */
function keep_session_after_password_change(string $newHash): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['sks_pwv'] = password_version($newHash);
}

/** The sign-out link, carrying a token so another site cannot sign people out. */
function logout_url(): string
{
    return portal_url('/logout.php?t=' . urlencode(csrf_token()));
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

/**
 * The one rule for a password somebody chooses: at registration, when an
 * administrator sets one for a new member of staff, and when a temporary one
 * is replaced. Written once so the three cannot drift apart.
 */
function password_is_strong(string $pw): bool
{
    return mb_strlen($pw) >= 8
        && preg_match('/\p{L}/u', $pw) === 1
        && preg_match('/\d/', $pw) === 1;
}

/* ---------------------------------------------------------- credentials --- */

/**
 * A password an administrator has to pass on, held for exactly one page load.
 *
 * Deliberately not a flash. A flash is a sentence; this is a value somebody
 * copies by hand, and thirteen random characters buried mid-sentence between a
 * colon and an em dash is how a brand-new account ends up unable to sign in —
 * a trailing space or the dash caught with it reads to the login page as the
 * wrong password, which is the one thing it cannot explain.
 *
 * It does mean the password sits in the administrator's session file, in the
 * clear, from the redirect until the page that shows it is loaded. That is the
 * cost of a POST-redirect-GET, which is how every form in this portal works;
 * the alternative is rendering the panel straight out of the POST and leaving
 * a reload to re-create the account. It is read and cleared by the first page
 * that asks for it.
 */
function stash_credentials(
    string $name,
    string $email,
    string $password,
    bool $emailed,
    bool $mailOn = false
): void {
    start_session();
    $_SESSION['new_credentials'] = [
        'name'    => $name,
        'email'   => $email,
        'password' => $password,
        'emailed' => $emailed,
        'mail_on' => $mailOn,
    ];
}

function take_credentials(): ?array
{
    start_session();
    $c = $_SESSION['new_credentials'] ?? null;
    unset($_SESSION['new_credentials']);
    return is_array($c) ? $c : null;
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
