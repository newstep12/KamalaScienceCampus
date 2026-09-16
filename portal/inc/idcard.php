<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/settings.php';

/**
 * Identity cards.
 *
 * One card design serves every role: a student card carries the year and
 * symbol number, a staff card carries the title the campus office has set
 * (Lecturer, Assistant Professor, and so on). The details all come from the
 * person's own portfolio, so the card is only ever as complete as the profile
 * behind it — id_card_missing() says what is still blank.
 */

/**
 * The titles a staff card may carry. The key is what the users.designation
 * column stores, so the printed title follows the card's language; a value
 * that is not one of these (typed in by hand before this list existed) is
 * printed as it stands.
 */
function designations(): array
{
    return [
        'professor', 'assoc_professor', 'asst_professor', 'lecturer', 'teaching_asst',
        'campus_chief', 'asst_campus_chief', 'admin_officer', 'account_officer',
        'librarian', 'lab_assistant',
    ];
}

function designation_label(?string $key): string
{
    $key = trim((string) $key);
    if ($key === '') {
        return '';
    }
    return in_array($key, designations(), true) ? t('desig_' . $key) : $key;
}

/**
 * The card number. Derived from the account id rather than stored, so it is
 * stable for the life of the account and cannot drift out of step with it:
 * KSC-S-0042 for students, KSC-T-0007 for teaching staff, KSC-A-0001 for
 * administrators.
 */
function id_card_number(array $u): string
{
    $letter = [ROLE_STUDENT => 'S', ROLE_LECTURER => 'T', ROLE_ADMIN => 'A'][$u['role']] ?? 'X';
    return sprintf('KSC-%s-%04d', $letter, (int) $u['id']);
}

/** The line under the name: the staff title, or the student's year. */
function id_card_role_line(array $u): string
{
    if ($u['role'] === ROLE_STUDENT) {
        return t('role_student');
    }
    return designation_label($u['designation'] ?? null) ?: t('role_' . $u['role']);
}

/** Card settings an administrator controls under Admin → System. */
function id_card_settings(): array
{
    return [
        'chief_name'    => setting('id_card_chief_name'),
        'chief_name_ne' => setting('id_card_chief_name_ne'),
        'chief_title'   => setting('id_card_chief_title', 'campus_chief'),
        'signature'     => setting('id_card_signature_path'),
        'valid_until'   => setting('id_card_valid_until'),
        'session'       => setting('id_card_session'),
    ];
}

/** The Campus Chief's name in the card's language, or '' if none is set. */
function id_card_chief_name(array $s): string
{
    if (is_nepali() && !empty($s['chief_name_ne'])) {
        return (string) $s['chief_name_ne'];
    }
    return (string) ($s['chief_name'] ?? '');
}

function photo_src(array $u): ?string
{
    return empty($u['avatar_path'])
        ? null
        : portal_url('/download.php?photo=' . (int) $u['id']);
}

function signature_src(): ?string
{
    return setting('id_card_signature_path') ? portal_url('/download.php?signature=1') : null;
}

/**
 * Who may see a photograph. Everyone sees their own; administrators see all;
 * a lecturer sees the students enrolled in a course they teach, and other
 * staff. A student sees nobody else's.
 */
function can_view_photo(array $viewer, int $targetId): bool
{
    if ((int) $viewer['id'] === $targetId) {
        return true;
    }
    if ($viewer['role'] === ROLE_ADMIN) {
        return true;
    }
    if ($viewer['role'] !== ROLE_LECTURER) {
        return false;
    }
    return (bool) scalar(
        'SELECT 1 FROM users u
          WHERE u.id = ?
            AND (u.role <> \'student\'
                 OR EXISTS (SELECT 1 FROM enrolments e
                              JOIN courses c ON c.id = e.course_id
                             WHERE e.user_id = u.id AND c.lecturer_id = ?))
          LIMIT 1',
        [$targetId, (int) $viewer['id']]
    );
}

/**
 * Details the card needs that the person has not filled in yet, as labels
 * ready to list back to them.
 */
function id_card_missing(array $u): array
{
    $missing = [];
    if (empty($u['avatar_path']))   { $missing[] = t('photo'); }
    if (empty($u['date_of_birth'])) { $missing[] = t('date_of_birth'); }
    if (empty($u['address']))       { $missing[] = t('address'); }
    if ($u['role'] === ROLE_STUDENT) {
        if (empty($u['year_level'])) { $missing[] = t('year_of_study'); }
        if (empty($u['symbol_no']))  { $missing[] = t('symbol_no'); }
    }
    return $missing;
}

/** A list of labels as a sentence fragment: "a, b and c". */
function join_list(array $items): string
{
    if (count($items) <= 1) {
        return (string) ($items[0] ?? '');
    }
    $last = array_pop($items);
    return implode(', ', $items) . ' ' . t('and') . ' ' . $last;
}

/**
 * The campus name in both scripts. An identity card carries both whichever
 * language the portal is being read in, so this reads the two language files
 * directly rather than going through t().
 */
function campus_names(): array
{
    static $names = null;
    if ($names === null) {
        $en = require __DIR__ . '/../lang/en.php';
        $ne = require __DIR__ . '/../lang/ne.php';
        $names = ['en' => (string) $en['campus_name'], 'ne' => (string) $ne['campus_name']];
    }
    return $names;
}
