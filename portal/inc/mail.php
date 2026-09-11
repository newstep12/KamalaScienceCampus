<?php
declare(strict_types=1);

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/lang.php';

/**
 * Notification email.
 *
 * Uses PHP's mail(), which Hostinger routes through its own MTA — no SMTP
 * credentials are stored anywhere. Every failure is swallowed and logged: a
 * bounced notification must never break a registration or an approval.
 */
function mail_enabled(): bool
{
    return setting_bool('mail_enabled', false) && setting('mail_from') !== null;
}

function send_notification(string $to, string $subject, string $body): bool
{
    if (!mail_enabled()) {
        return false;
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $from     = (string) setting('mail_from');
    $fromName = (string) setting('mail_from_name', 'Kamala Science Campus');
    // The address goes into headers and onto sendmail's command line; an
    // invalid one (say, with a line break in it) must never get that far.
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        error_log('Portal mail skipped: the from-address is not a valid email');
        return false;
    }

    // Strip anything that could inject an extra header.
    $subject  = str_replace(["\r", "\n"], ' ', $subject);
    $fromName = str_replace(['"', "\r", "\n"], '', $fromName);

    $headers = [
        'From: "' . $fromName . '" <' . $from . '>',
        'Reply-To: ' . $from,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'MIME-Version: 1.0',
        'X-Mailer: KSC-Portal',
    ];

    try {
        $encoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        // -f makes the campus address the envelope sender, so SPF checks see
        // the domain rather than the hosting account's internal address. Some
        // hosts refuse -f; fall back to a plain send rather than fail.
        $ok = @mail($to, $encoded, $body, implode("\r\n", $headers), '-f' . $from)
           || @mail($to, $encoded, $body, implode("\r\n", $headers));
        if (!$ok) {
            error_log('Portal mail failed to ' . $to . ': ' . $subject);
        }
        return (bool) $ok;
    } catch (Throwable $e) {
        error_log('Portal mail exception: ' . $e->getMessage());
        return false;
    }
}

/** Tell every active admin that someone has registered. */
function notify_admins_of_registration(array $student): void
{
    if (!mail_enabled()) {
        return;
    }
    // At most one alert every ten minutes. The approval queue lists every
    // pending registration anyway, and the form is public: unthrottled, it
    // would let anyone flood the administrators' inboxes.
    if (time() - (int) setting('reg_alert_sent_at', '0') < 600) {
        return;
    }
    set_setting('reg_alert_sent_at', (string) time());

    $admins = all('SELECT email, full_name FROM users WHERE role = \'admin\' AND status = \'active\'');
    $body = "A new student registration is waiting for approval.\n\n"
        . "Name:   {$student['full_name']}\n"
        . "Email:  {$student['email']}\n"
        . "Year:   " . ($student['year_level'] ?? '—') . "\n"
        . "Symbol: " . ($student['symbol_no'] ?? '—') . "\n\n"
        . "Approve or reject it here:\n"
        . portal_absolute_url('/admin/approvals.php') . "\n\n"
        . "Registrations in the next 10 minutes join the same queue without another email.\n";

    foreach ($admins as $a) {
        send_notification($a['email'], 'New student registration — ' . $student['full_name'], $body);
    }
}

function notify_student_approved(array $student): void
{
    $body = "Dear {$student['full_name']},\n\n"
        . "Your Kamala Science Campus portal account has been approved. "
        . "You can now sign in with the email and password you registered with.\n\n"
        . portal_absolute_url('/index.php') . "\n\n"
        . "Kamala Science Campus\nDhungrebas, Kamalamai, Sindhuli\n";
    send_notification($student['email'], 'Your portal account has been approved', $body);
}

function notify_student_rejected(array $student, ?string $note): void
{
    $body = "Dear {$student['full_name']},\n\n"
        . "Your Kamala Science Campus portal registration was not approved.\n"
        . ($note ? "\nReason: {$note}\n" : '')
        . "\nIf you believe this is a mistake, please contact the campus office "
        . "on +977-47-520203.\n\n"
        . "Kamala Science Campus\nDhungrebas, Kamalamai, Sindhuli\n";
    send_notification($student['email'], 'About your portal registration', $body);
}

/** Absolute URL for links inside emails. */
function portal_absolute_url(string $path): string
{
    // Not from the Host header alone: the registration form is public, and a
    // forged Host would turn the "approve here" link in the administrators'
    // email into a link to someone else's site.
    $known = ['kamalasciencecampus.edu.np', 'www.kamalasciencecampus.edu.np'];
    $host  = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if (!in_array($host, $known, true)) {
        $host = $known[0];
    }
    $base = rtrim(config()['base_url'] ?? '/portal', '/');
    return 'https://' . $host . $base . $path;
}
