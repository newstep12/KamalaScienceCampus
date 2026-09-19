<?php
declare(strict_types=1);

/**
 * Runtime translation for the portal.
 *
 * Language resolution order: ?lang= in the URL, then the session, then the
 * kscl cookie, then English. A chosen language is remembered for a year.
 */

const LANGUAGES = ['en' => 'English', 'ne' => 'नेपाली'];

function current_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    if (function_exists('start_session')) {
        start_session();
    }

    $requested = $_GET['lang'] ?? null;
    if (is_string($requested) && isset(LANGUAGES[$requested])) {
        $lang = $requested;
        $_SESSION['lang'] = $lang;
        setcookie('kscl', $lang, [
            'expires'  => time() + 31536000,
            'path'     => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        return $lang;
    }

    if (isset($_SESSION['lang']) && isset(LANGUAGES[$_SESSION['lang']])) {
        return $lang = $_SESSION['lang'];
    }
    if (isset($_COOKIE['kscl']) && isset(LANGUAGES[$_COOKIE['kscl']])) {
        return $lang = $_COOKIE['kscl'];
    }
    return $lang = 'en';
}

function is_nepali(): bool
{
    return current_lang() === 'ne';
}

function strings(): array
{
    static $cache = [];
    $lang = current_lang();
    if (!isset($cache[$lang])) {
        $en = require __DIR__ . '/../lang/en.php';
        $cache['en'] = $en;
        if ($lang !== 'en') {
            // Fall back to English for any key a translation has not covered,
            // so a missing string shows real text rather than a raw key.
            $cache[$lang] = array_merge($en, require __DIR__ . '/../lang/' . $lang . '.php');
        }
    }
    return $cache[$lang];
}

/** Translate a key. Extra arguments are substituted for %s placeholders. */
function t(string $key, ...$args): string
{
    $s = strings()[$key] ?? $key;
    return $args ? vsprintf($s, $args) : $s;
}

/** Translate and HTML-escape — the default for anything echoed into markup. */
function te(string $key, ...$args): string
{
    return e(t($key, ...$args));
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Pick the right column for a bilingual database row, e.g. title_en/title_ne,
 * falling back to English when a Nepali value has not been entered.
 */
function bilingual(array $row, string $field): string
{
    // Trimmed on both sides, so a Nepali column holding nothing but spaces
    // falls back to English rather than printing as an empty heading.
    $english = trim((string) ($row[$field . '_en'] ?? ''));
    if (is_nepali()) {
        $nepali = trim((string) ($row[$field . '_ne'] ?? ''));
        if ($nepali !== '') {
            return $nepali;
        }
    }
    return $english;
}

/** The current URL with the language swapped — powers the language toggle. */
function lang_switch_url(string $to): string
{
    $params = $_GET;
    $params['lang'] = $to;
    $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    return $path . '?' . http_build_query($params);
}

/** Nepali (Devanagari) digits, used for dates and counts when in Nepali. */
function localize_digits(string $text): string
{
    if (!is_nepali()) {
        return $text;
    }
    return strtr($text, ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४',
                         '5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९']);
}

/**
 * The other direction: Devanagari digits back to ASCII.
 *
 * A number typed on a Nepali keyboard, or copied off a Nepali document, arrives
 * as ०-९. Stored that way it is stuck, because localize_digits() only ever
 * converts towards Devanagari: an English card would print the Devanagari
 * digits unconverted, and anything that compares or sorts the value would be
 * comparing two alphabets. So a number is stored in ASCII and the card
 * localises it as it prints — which is what every other number on the card
 * already does.
 */
function ascii_digits(string $text): string
{
    return strtr($text, ['०'=>'0','१'=>'1','२'=>'2','३'=>'3','४'=>'4',
                         '५'=>'5','६'=>'6','७'=>'7','८'=>'8','९'=>'9']);
}

/**
 * Which script a piece of text is in: true when it is mostly Devanagari.
 *
 * By majority, never by presence. A single Devanagari character is not a
 * Nepali name — "Binish Parajuli (बिनिश)" and a name pasted with one stray
 * danda are both Latin names — and a test that asked only whether the string
 * contained any Devanagari at all classed them as Nepali, which on the
 * identity card discarded the real Nepali name in the other column and set
 * the mixed string in a Devanagari face.
 */
function is_devanagari(string $text): bool
{
    $devanagari = preg_match_all('/\p{Devanagari}/u', $text);
    $latin      = preg_match_all('/\p{Latin}/u', $text);
    return $devanagari > 0 && $devanagari >= $latin;
}

function format_date(?string $datetime, bool $withTime = false): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '—';
    }
    $out = date($withTime ? 'j M Y, g:i a' : 'j M Y', $ts);
    if (is_nepali()) {
        $months = ['Jan'=>'जनवरी','Feb'=>'फेब्रुअरी','Mar'=>'मार्च','Apr'=>'अप्रिल',
                   'May'=>'मे','Jun'=>'जुन','Jul'=>'जुलाई','Aug'=>'अगस्ट',
                   'Sep'=>'सेप्टेम्बर','Oct'=>'अक्टोबर','Nov'=>'नोभेम्बर','Dec'=>'डिसेम्बर'];
        $out = strtr($out, $months);
        $out = localize_digits($out);
    }
    return $out;
}

/** $value as Y-m-d if it is a real calendar date, otherwise null. */
function parse_date(?string $value): ?string
{
    $value = trim((string) $value);
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    if (!$d || $d->format('Y-m-d') !== $value) {
        return null;
    }
    $year = (int) $d->format('Y');
    return ($year >= 1900 && $year <= 2100) ? $value : null;
}

function year_label(?int $year): string
{
    if (!$year) {
        return t('all_years');
    }
    return t('year_n', localize_digits((string) $year));
}

/**
 * The same year as an identity document names it: B.Sc. 3rd Year, not Year 3.
 *
 * Year 3 is how the portal talks about a year internally — it is the filter on
 * a notice, the heading over a class list, the column in a table where the
 * programme is already the subject. A card is read by people who have none of
 * that context: an examination hall, a bus conductor, an office in another
 * district. So the card spells the year out in full, the way the campus writes
 * it on everything else it issues.
 *
 * The ordinal comes from the language file rather than a suffix rule, because
 * there is no rule to share: English wants 1st/2nd/3rd/4th, Nepali wants the
 * words. A year with no ordinal written for it — nothing today, the programme
 * is four years — falls back to its digit, so a fifth year would read
 * B.Sc. 5 Year rather than not printing at all.
 */
function program_year_label(?int $year): string
{
    if (!$year) {
        return '';
    }
    $key = 'year_ord_' . $year;
    $ord = t($key);
    if ($ord === $key) {
        $ord = (string) $year;
    }
    return t('program_year_n', localize_digits($ord));
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

function format_bytes(?int $bytes): string
{
    if (!$bytes) {
        return '';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $n = (float) $bytes;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return localize_digits(($n >= 10 || $i === 0 ? round($n) : round($n, 1)) . ' ' . $units[$i]);
}
