<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/signatures.php';
require_once __DIR__ . '/devanagari.php';

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
 * The eight blood groups, stored and printed exactly as they are written here.
 * The card carries one because it is the detail an ambulance crew reads off it,
 * and the campus office cannot invent it: only the holder knows it, so only the
 * holder fills it in, from their own portfolio.
 */
function blood_groups(): array
{
    return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
}

/**
 * One of the eight, or null for anything else — an empty choice, or a value
 * posted by something other than the form. Null is what leaves the line on the
 * card blank, to be written on, so an unrecognised value is never printed as
 * though the campus had recorded it.
 *
 * A minus sign or a dash typed where a hyphen belongs still means a negative
 * group; a keyboard that produces one is not the person's mistake.
 */
function blood_group(?string $value): ?string
{
    $value = strtoupper(trim((string) $value));
    $value = strtr($value, ["\u{2212}" => '-', "\u{2013}" => '-', "\u{2014}" => '-']);
    return in_array($value, blood_groups(), true) ? $value : null;
}

/**
 * A government number as it should be stored: an NID written 000-000-000-0, a
 * PAN written as its nine digits.
 *
 * Everything but digits and the separators people actually write between them
 * is dropped, and runs of space are closed up. Not to validate — the campus
 * office is not in a position to check somebody's NID against the register,
 * and a card that refused a number because it had an unexpected shape would be
 * worse than one carrying it. It is to keep a printed identity document free
 * of whatever a stray keystroke left in the field, since nobody proof-reads a
 * number they have already typed.
 *
 * Devanagari digits are turned into ASCII on the way in, so what is stored is
 * always the same alphabet whatever keyboard typed it, and the card puts them
 * back into Devanagari as it prints. The classes below are written 0-9 rather
 * than \d deliberately: with the /u modifier PHP's \d matches the digits of
 * every script, so a number typed in Devanagari would pass the strip and then
 * fail a guard written without /u — stored as nothing, on a page that said the
 * profile had been saved.
 *
 * '' comes back for anything with no digit left in it at all, which the caller
 * stores as null: a row of punctuation is not a number.
 */
function id_number(?string $value, int $max = 30): string
{
    $value = preg_replace('/[^0-9\- \/]+/u', '', ascii_digits(trim((string) $value))) ?? '';
    $value = trim((string) preg_replace('/\s+/', ' ', $value));
    return preg_match('/[0-9]/', $value) ? mb_substr($value, 0, $max) : '';
}

/**
 * The two names the card prints: what the record holds, and — where it holds
 * no Nepali name — one written from the English.
 *
 * The campus asks for one name and prints two. Only the English box is
 * required, because that is the one every student can type on any keyboard,
 * and the Nepali box beside it is optional and mostly skipped; a card with one
 * name on it was the result, on a document whose whole design is a line per
 * script. So the second line is derived when it has to be. nepali_name() does
 * the writing — a dictionary of the names these students have, and a
 * syllable-by-syllable transliterator behind it.
 *
 * 'deva_derived' says which of the two the card is printing, and it is not
 * decoration: a spelling the campus holds and a spelling the campus guessed
 * are different claims to make about somebody's name, and the pages that show
 * this card say which one this is. A student who disagrees types theirs into
 * the optional box, and from then on nothing is derived for them — it is read
 * from the record like any other detail, here and everywhere else in the
 * portal.
 *
 * @return array{latin: ?string, deva: ?string, deva_derived: bool}
 */
function id_card_names(array $u): array
{
    $names = name_by_script($u) + ['deva_derived' => false];
    if ($names['deva'] === null && $names['latin'] !== null) {
        $derived = nepali_name($names['latin']);
        if ($derived !== null) {
            $names['deva'] = $derived;
            $names['deva_derived'] = true;
        }
    }
    return $names;
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

/**
 * The colourways a card may be printed in: a header colour and a band colour
 * that were picked together, each pair checked for legible text on paper as
 * well as on screen. The two values are also the swatches the picker shows.
 *
 * 'ink' deliberately floods no colour at all. It is the one to choose for a
 * home printer, and the only one that still looks deliberate when someone
 * prints with background graphics switched off.
 */
function id_card_themes(): array
{
    return [
        'navy'    => ['#0b2545', '#d99a2b'],
        'teal'    => ['#0e5f5e', '#efe3c6'],
        'crimson' => ['#7a1f2b', '#d9a13b'],
        'forest'  => ['#14452f', '#d9bd6a'],
        'slate'   => ['#263244', '#c8d2de'],
        'ink'     => ['#ffffff', '#f4f7fa'],
    ];
}

function id_card_theme(?string $key): string
{
    return isset(id_card_themes()[(string) $key]) ? (string) $key : 'navy';
}

function id_card_orientation(?string $key): string
{
    return in_array($key, ['portrait', 'landscape'], true) ? (string) $key : 'portrait';
}

/** Which faces to print: the front alone, or the front and the back. */
function id_card_sides(?string $key): string
{
    return in_array($key, ['front', 'both'], true) ? (string) $key : 'both';
}

/** Card settings an administrator controls under Admin → System. */
function id_card_settings(): array
{
    return [
        'chief_name'    => setting('id_card_chief_name'),
        'chief_name_ne' => setting('id_card_chief_name_ne'),
        'chief_title'   => setting('id_card_chief_title', 'campus_chief'),
        'valid_until'   => setting('id_card_valid_until'),
        'session'       => setting('id_card_session'),
        'theme'         => id_card_theme(setting('id_card_theme')),
        'orientation'   => id_card_orientation(setting('id_card_orientation')),
        'sides'         => id_card_sides(setting('id_card_sides')),
    ];
}

/**
 * Everything one card needs, gathered once. The page renders the faces from
 * this, and so does the design preview on the System page.
 *
 * $viewer is whoever the card is being rendered for, and it decides what is
 * not the same for everybody: the Campus Chief's signature is printed only
 * when the office has released it that far. An administrator printing a card
 * from Admin → People carries it; a student printing the same card for
 * themselves gets the blank line to be signed by hand. The name and title
 * beneath it print either way, so the card reads the same whether the
 * signature is on it or waiting to be written.
 *
 * The holder's own signature on the back is asked for the same way, though
 * only the holder and an administrator can open a card page at all, so in
 * practice it is on every card that has one.
 *
 * 'holder_names' is resolved here for the page's own text — which says
 * whether the Nepali name on the card was derived — and for the missing-
 * details list beside it, because id_card_names() may have to transliterate a
 * name to answer and both ask at once. The faces do not read it: a context
 * belongs to one holder, and a face reads the holder it was handed.
 */
function id_card_context(array $holder, ?array $viewer = null): array
{
    $card   = id_card_settings();
    $viewer = $viewer ?? current_user();
    $sig    = applied_signature('id_card', $viewer);
    $named  = $sig ?: signature_for_use('id_card');   // withheld, but it still names the signer

    return [
        'card'        => $card,
        'theme'       => $card['theme'],
        'orientation' => $card['orientation'],
        'photo'       => photo_src($holder),
        'signature'   => $sig ? signature_url($sig) : null,
        'holder_sig'  => can_view_holder_signature($viewer, (int) $holder['id'])
            ? holder_signature_src($holder)
            : null,
        'chief'       => $named ? signature_owner_name($named) : id_card_chief_name($card),
        'chief_title' => designation_label(
            ($named['owner_title'] ?? '') ?: ($card['chief_title'] ?: 'campus_chief')
        ),
        'holder_names' => id_card_names($holder),
        'names'       => campus_names(),
        'issued'      => $holder['approved_at'] ?: $holder['created_at'],
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

/**
 * The holder's own signature, uploaded from their portfolio. Nothing to do
 * with the signature library in signatures.php: that holds the campus's
 * signatures, which only an administrator may upload, release or apply. This
 * is the holder signing their own card, and it is theirs to add and to remove.
 */
function holder_signature_src(array $u): ?string
{
    return empty($u['signature_path'])
        ? null
        : portal_url('/download.php?holder_signature=' . (int) $u['id']);
}

/**
 * Who may see a holder's signature: the holder, and an administrator — who is
 * the only other person who can print that card, and cannot print it complete
 * without it.
 *
 * Deliberately narrower than can_view_photo(). A lecturer sees the faces of
 * the students they teach because a class list without faces is no use; a
 * signature is not a likeness but a thing that can be copied onto a document,
 * and no part of teaching a course needs one.
 */
function can_view_holder_signature(?array $viewer, int $targetId): bool
{
    // Nullable, like signature_released_to(): id_card_context() falls back to
    // current_user(), which is null when nobody is signed in. Nobody is not an
    // administrator, so the answer is no — not a fatal error on a card page.
    if ($viewer === null) {
        return false;
    }
    return (int) $viewer['id'] === $targetId || $viewer['role'] === ROLE_ADMIN;
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
 *
 * $names is id_card_names($u) when the caller already has it; left out, it is
 * worked out here.
 */
function id_card_missing(array $u, ?array $names = null): array
{
    $missing = [];
    if (empty($u['avatar_path']))   { $missing[] = t('photo'); }
    // By script, not by column — see name_by_script() — and in the order the
    // card is read, so a name sits with the name rather than at the end of
    // the sentence. A student who typed their Devanagari name into the
    // required "full name" box is missing the English line, not the Nepali
    // one, and was told nothing at all while the question was which column
    // was empty.
    //
    // The Nepali name is asked for only when the card would still have no
    // second line: id_card_names() writes one from the English wherever it
    // can, so for almost every holder there is nothing to ask for, and what
    // the card page says instead is that the name it printed was derived —
    // a different thing from a detail being missing.
    //
    // It cannot always. A name already in Devanagari never gets here (it
    // fills the Devanagari line itself, and what is asked for is the English
    // one), but a name in a script this has no reading for, or one mixing
    // Latin and Devanagari in a single value, comes back with nothing — and
    // that card's second line really is blank, so that holder is asked.
    // Taken from the caller where it already has them — a card page resolves
    // them in id_card_context() and asks this in the same breath, and
    // id_card_names() may have had to transliterate a name to answer.
    $names ??= id_card_names($u);
    if ($names['latin'] === null)   { $missing[] = t('full_name_en'); }
    if ($names['deva'] === null)    { $missing[] = t('full_name_ne'); }
    if (empty($u['date_of_birth'])) { $missing[] = t('date_of_birth'); }
    if (empty($u['address']))       { $missing[] = t('address'); }
    if ($u['role'] === ROLE_STUDENT) {
        if (empty($u['year_level'])) { $missing[] = t('year_of_study'); }
        if (empty($u['symbol_no']))  { $missing[] = t('symbol_no'); }
    }
    return $missing;
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
