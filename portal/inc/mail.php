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
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
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
    $admins = all('SELECT email, full_name FROM users WHERE role = \'admin\' AND status = \'active\'');
    $body = "A new student registration is waiting for approval.\n\n"
        . "Name:   {$student['full_name']}\n"
        . "Email:  {$student['email']}\n"
        . "Year:   " . ($student['year_level'] ?? '—') . "\n"
        . "Symbol: " . ($student['symbol_no'] ?? '—') . "\n\n"
        . "Approve or reject it here:\n"
        . portal_absolute_url('/admin/approvals.php') . "\n";

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
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'kamalasciencecampus.edu.np';
    $base   = rtrim(config()['base_url'] ?? '/portal', '/');
    return $scheme . '://' . $host . $base . $path;
}
