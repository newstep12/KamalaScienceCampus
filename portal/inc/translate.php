<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/lang.php';

/**
 * Automatic English → Nepali translation for notices.
 *
 * Notices are written in English and the Nepali fields are optional, so until
 * now a Nepali visitor simply saw the English title and body (bilingual()
 * falls back to English when title_ne/body_ne are empty). This turns that
 * fallback into a translation.
 *
 * Two layers, in order:
 *
 *   1. A translation service, when the campus has configured one. This is what
 *      handles a full notice body written in ordinary prose.
 *   2. A built-in glossary of the vocabulary TU and campus notices actually
 *      use. It needs no account, no key and no outbound request, and it
 *      renders the formulaic titles this board is mostly made of — "B.Sc.
 *      First Year Partial Examination Notice" and its like — correctly.
 *
 * Whatever a layer produces is written back into title_ne/body_ne, so every
 * notice is translated at most once and an admin can correct the wording
 * afterwards. A corrected translation is never overwritten: the *_ne_auto
 * flags record which text this file wrote and which a person did.
 *
 * Nothing here ever throws. A translation that cannot be made is not an error
 * — the page falls back to English exactly as it did before.
 */

/** Providers the admin can choose between on the System page. */
const TRANSLATE_PROVIDERS = ['glossary', 'mymemory', 'libretranslate', 'google'];

/** How long to stop calling a service that has just failed. */
const TRANSLATE_BACKOFF_SECONDS = 900;

/**
 * How long one run of the System page's backfill may take. Comfortably inside
 * the 30-second max_execution_time shared hosting usually sets, so the pass
 * always ends with a count on screen rather than a dead page.
 */
const BACKFILL_SECONDS = 20;

function translation_enabled(): bool
{
    return setting_bool('translate_enabled', true);
}

function translation_provider(): string
{
    $p = (string) setting('translate_provider', 'glossary');
    return in_array($p, TRANSLATE_PROVIDERS, true) ? $p : 'glossary';
}

/**
 * True when the chosen provider has everything it needs. The glossary always
 * does; the others may be missing a key or an endpoint, in which case we quietly
 * fall back to the glossary rather than making a request that cannot succeed.
 */
function translation_service_ready(): bool
{
    switch (translation_provider()) {
        case 'google':
            return setting('translate_api_key') !== null;
        case 'libretranslate':
            return setting('translate_endpoint') !== null;
        case 'mymemory':
            return true;
        default:
            return false;
    }
}

/* ------------------------------------------------------------- entry point -- */

/**
 * Translate English text into Nepali, or null when no layer could.
 *
 * @return array{text:string, source:string}|null  source is the layer that produced it
 */
function translate_to_nepali(string $text): ?array
{
    $text = trim($text);
    if ($text === '' || !translation_enabled() || is_devanagari($text)) {
        return null;
    }

    if (translation_service_ready() && !translation_backing_off()) {
        $remote = remote_translate($text);
        if ($remote !== null && trim($remote) !== '' && $remote !== $text) {
            return ['text' => $remote, 'source' => translation_provider()];
        }
    }

    $offline = glossary_translate($text);
    return $offline === null ? null : ['text' => $offline, 'source' => 'glossary'];
}

/** True when the text is already mostly Nepali, so there is nothing to do. */
function is_devanagari(string $text): bool
{
    $devanagari = preg_match_all('/\p{Devanagari}/u', $text);
    $latin      = preg_match_all('/\p{Latin}/u', $text);
    return $devanagari > 0 && $devanagari >= $latin;
}

/* ---------------------------------------------------------------- glossary -- */

/**
 * English → Nepali for the words these notices are built from.
 *
 * Deliberately nouns, adjectives and fixed phrases only. English prepositions
 * ("of", "for", "in") are left out on purpose: Nepali puts them in a different
 * place in the sentence, and a word-for-word substitution would produce
 * confident nonsense. Anything containing a word that is not in here is
 * refused outright by glossary_translate() and stays in English, which is
 * honest; half-translated Nepali would not be.
 *
 * Longest phrases win, so 'first year' is matched before 'year' and
 * 'partial examination' before 'examination'.
 */
function notice_glossary(): array
{
    static $glossary = null;
    if ($glossary !== null) {
        return $glossary;
    }

    return $glossary = [
        /* institutions */
        'kamala science campus'             => 'कमला साइन्स क्याम्पस',
        'tribhuvan university'              => 'त्रिभुवन विश्वविद्यालय',
        'institute of science and technology' => 'विज्ञान तथा प्रविधि अध्ययन संस्थान',
        'university grants commission'      => 'विश्वविद्यालय अनुदान आयोग',
        'examination controller office'     => 'परीक्षा नियन्त्रण कार्यालय',
        'office of the controller of examinations' => 'परीक्षा नियन्त्रण कार्यालय',
        'examination control division'      => 'परीक्षा नियन्त्रण महाशाखा',
        'iost'                              => 'आई.ओ.एस.टी.',
        'ugc'                               => 'यू.जी.सी.',
        't.u.'                              => 'त्रि.वि.',
        'tu'                                => 'त्रि.वि.',

        /* programme and year */
        'b.sc.csit'   => 'बी.एस्सी.सी.एस.आई.टी.',
        'b.sc. csit'  => 'बी.एस्सी. सी.एस.आई.टी.',
        'bsc csit'    => 'बी.एस्सी. सी.एस.आई.टी.',
        'b.sc.'       => 'बी.एस्सी.',
        'b.sc'        => 'बी.एस्सी.',
        'bsc'         => 'बी.एस्सी.',
        'bachelor of science' => 'विज्ञान स्नातक',
        'bachelor'    => 'स्नातक',
        'master'      => 'स्नातकोत्तर',
        'first year'  => 'प्रथम वर्ष',
        'second year' => 'द्वितीय वर्ष',
        'third year'  => 'तृतीय वर्ष',
        'fourth year' => 'चतुर्थ वर्ष',
        'final year'  => 'अन्तिम वर्ष',
        'all years'   => 'सबै वर्ष',
        'academic year' => 'शैक्षिक सत्र',
        'year'        => 'वर्ष',
        'semester'    => 'सेमेस्टर',
        'annual'      => 'वार्षिक',
        'batch'       => 'ब्याच',

        /* examinations */
        'partial examination' => 'आंशिक परीक्षा',
        'partial exam'        => 'आंशिक परीक्षा',
        'partial'             => 'आंशिक',
        'final examination'   => 'अन्तिम परीक्षा',
        'final exam'          => 'अन्तिम परीक्षा',
        'board examination'   => 'बोर्ड परीक्षा',
        'entrance examination' => 'प्रवेश परीक्षा',
        'entrance exam'       => 'प्रवेश परीक्षा',
        'practical examination' => 'प्रयोगात्मक परीक्षा',
        'practical exam'      => 'प्रयोगात्मक परीक्षा',
        'internal examination' => 'आन्तरिक परीक्षा',
        'internal assessment' => 'आन्तरिक मूल्याङ्कन',
        'terminal examination' => 'त्रैमासिक परीक्षा',
        'examination schedule' => 'परीक्षा तालिका',
        'exam schedule'       => 'परीक्षा तालिका',
        'examination routine' => 'परीक्षा तालिका',
        'exam routine'        => 'परीक्षा तालिका',
        'examination centre'  => 'परीक्षा केन्द्र',
        'examination center'  => 'परीक्षा केन्द्र',
        'exam centre'         => 'परीक्षा केन्द्र',
        'exam center'         => 'परीक्षा केन्द्र',
        'examination form'    => 'परीक्षा फाराम',
        'exam form'           => 'परीक्षा फाराम',
        'admit card'          => 'प्रवेशपत्र',
        'examinations'        => 'परीक्षा',
        'examination'         => 'परीक्षा',
        'exams'               => 'परीक्षा',
        'exam'                => 'परीक्षा',
        'theory'              => 'सैद्धान्तिक',
        'practical'           => 'प्रयोगात्मक',
        'internal'            => 'आन्तरिक',
        'viva'                => 'मौखिक परीक्षा',
        'result'              => 'नतिजा',
        'results'             => 'नतिजा',
        'marksheet'           => 'लब्धाङ्कपत्र',
        'transcript'          => 'लब्धाङ्क प्रमाणपत्र',
        'grade sheet'         => 'ग्रेड शीट',
        're-totalling'        => 'पुनर्योग',
        'retotalling'         => 'पुनर्योग',

        /* notice vocabulary */
        'urgent notice'    => 'जरुरी सूचना',
        'important notice' => 'महत्त्वपूर्ण सूचना',
        'public notice'    => 'सार्वजनिक सूचना',
        'notices'          => 'सूचना',
        'notice'           => 'सूचना',
        'announcement'     => 'जानकारी',
        'information'      => 'जानकारी',
        'urgent'           => 'जरुरी',
        'important'        => 'महत्त्वपूर्ण',
        'published'        => 'प्रकाशित',
        'cancelled'        => 'रद्द',
        'postponed'        => 'स्थगित',
        'revised'          => 'संशोधित',
        'correction'       => 'संशोधन',

        /* admissions, fees, dates */
        'form fill-up'   => 'फाराम भराइ',
        'form fill up'   => 'फाराम भराइ',
        'form filling'   => 'फाराम भराइ',
        'fill-up'        => 'भराइ',
        'fill up'        => 'भराइ',
        'name list'      => 'नामावली',
        'list'           => 'सूची',
        'open'           => 'खुला',
        'closed'         => 'बन्द',
        'new'            => 'नयाँ',
        'application form' => 'आवेदन फाराम',
        'registration'   => 'दर्ता',
        'admission'      => 'भर्ना',
        'admissions'     => 'भर्ना',
        'scholarship'    => 'छात्रवृत्ति',
        'scholarships'   => 'छात्रवृत्ति',
        'last date'      => 'अन्तिम मिति',
        'deadline'       => 'अन्तिम म्याद',
        'examination fee' => 'परीक्षा शुल्क',
        'late fee'       => 'विलम्ब शुल्क',
        'fees'           => 'शुल्क',
        'fee'            => 'शुल्क',
        'date'           => 'मिति',
        'time'           => 'समय',
        'schedule'       => 'तालिका',
        'routine'        => 'तालिका',
        'form'           => 'फाराम',
        'forms'          => 'फाराम',
        'holiday'        => 'बिदा',
        'meeting'        => 'बैठक',

        /* people and places */
        'all students' => 'सबै विद्यार्थी',
        'students'     => 'विद्यार्थी',
        'student'      => 'विद्यार्थी',
        'lecturers'    => 'प्राध्यापक',
        'lecturer'     => 'प्राध्यापक',
        'teachers'     => 'शिक्षक',
        'teacher'      => 'शिक्षक',
        'campus chief' => 'क्याम्पस प्रमुख',
        'campus'       => 'क्याम्पस',
        'college'      => 'कलेज',
        'department'   => 'विभाग',
        'office'       => 'कार्यालय',
        'library'      => 'पुस्तकालय',
        'laboratory'   => 'प्रयोगशाला',
        'lab'          => 'प्रयोगशाला',
        'classroom'    => 'कक्षाकोठा',
        'class'        => 'कक्षा',
        'attendance'   => 'हाजिरी',

        /* subjects */
        'computer science'      => 'कम्प्युटर विज्ञान',
        'environmental science' => 'वातावरण विज्ञान',
        'microbiology'          => 'सूक्ष्मजीवशास्त्र',
        'biotechnology'         => 'जैविक प्रविधि',
        'mathematics'           => 'गणित',
        'statistics'            => 'तथ्याङ्कशास्त्र',
        'physics'               => 'भौतिकशास्त्र',
        'chemistry'             => 'रसायनशास्त्र',
        'botany'                => 'वनस्पतिशास्त्र',
        'zoology'               => 'प्राणीशास्त्र',
        'science'               => 'विज्ञान',
        'technology'            => 'प्रविधि',

        /* the one connective that keeps its position in Nepali */
        'and' => 'र',
    ];
}

/**
 * Translate with the glossary alone, or null when it cannot do the whole job.
 *
 * All-or-nothing on purpose. A sentence half in Devanagari and half in Latin
 * reads worse than the English it came from, so unless every English word is
 * accounted for the caller keeps the original. That also keeps this to the
 * short, formulaic text it is good at: anything longer than a heading is left
 * to a real translation service.
 */
function glossary_translate(string $text): ?string
{
    $text = trim($text);
    if ($text === '' || str_word_count($text) > 24) {
        return null;
    }

    $terms = notice_glossary();
    // Longest first, so 'partial examination' wins over 'examination'.
    $keys = array_keys($terms);
    usort($keys, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

    // Letters, not \b, decide the boundaries: half these keys end in a full
    // stop ('b.sc.'), where \b would sit in the wrong place.
    $pattern = '/(?<![\p{L}])(' . implode('|', array_map(
        static fn(string $k): string => preg_quote($k, '/'),
        $keys
    )) . ')(?![\p{L}])/iu';

    // Matches become markers first. Checking for leftover English afterwards
    // would otherwise trip over any Latin character inside a replacement.
    $found  = [];
    $marked = preg_replace_callback($pattern, static function (array $m) use ($terms, &$found): string {
        $found[] = $terms[strtolower($m[1])];
        return "\x01" . (count($found) - 1) . "\x01";
    }, $text);

    if ($marked === null || !$found) {
        return null;
    }
    // Any English left means we do not understand the whole line.
    if (preg_match('/\p{Latin}/u', $marked)) {
        return null;
    }

    $out = preg_replace_callback('/\x01(\d+)\x01/', static fn(array $m): string => $found[(int) $m[1]], $marked);
    if ($out === null) {
        return null;
    }

    $out = devanagari_digits($out);
    // Substitutions leave gaps where an English word used to be.
    $out = preg_replace('/[ \t]{2,}/', ' ', $out) ?? $out;
    $out = preg_replace('/\s+([,.;:!?])/u', '$1', $out) ?? $out;
    return trim($out);
}

/** Nepali digits, whatever the viewer's current language happens to be. */
function devanagari_digits(string $text): string
{
    return strtr($text, ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४',
                         '5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९']);
}

/* ---------------------------------------------------------------- services -- */

/** True while we are waiting out a service that has just failed. */
function translation_backing_off(): bool
{
    $until = (int) setting('translate_backoff_until', '0');
    return $until > time();
}

function translation_back_off(string $why): void
{
    error_log('Translation provider "' . translation_provider() . '" failed: ' . $why);
    try {
        set_setting('translate_backoff_until', (string) (time() + TRANSLATE_BACKOFF_SECONDS));
    } catch (Throwable $e) {
        // A settings write is a convenience, not a requirement.
    }
}

/**
 * Translate through the configured service, or null on any failure.
 *
 * Long text is sent in pieces: every service has a request-size limit, and
 * MyMemory's is small. Splitting on blank lines and then on sentences keeps
 * each piece something a translator can make sense of on its own.
 */
function remote_translate(string $text): ?string
{
    $provider = translation_provider();
    $limit    = $provider === 'mymemory' ? 450 : 2000;
    $chunks   = split_for_translation($text, $limit);

    // The budget counts requests, not calls to this function: a long notice
    // body splits into many chunks, and one page load must not fire them all.
    // Checked up front so we never spend half a budget on a translation we
    // cannot finish — a body half in Nepali is worse than one left in English.
    $needed = count(array_filter($chunks, static fn(string $c): bool => trim($c) !== ''));
    if ($needed < 1 || $needed > translation_budget()) {
        return null;
    }

    $out = [];
    foreach ($chunks as $chunk) {
        if (trim($chunk) === '') {
            $out[] = $chunk;
            continue;
        }
        translation_budget(translation_budget() - 1);
        $piece = match ($provider) {
            'google'         => google_translate($chunk),
            'libretranslate' => libre_translate($chunk),
            'mymemory'       => mymemory_translate($chunk),
            default          => null,
        };
        if ($piece === null) {
            return null;
        }
        $out[] = $piece;
    }
    return $out ? implode('', $out) : null;
}

/**
 * Split text into pieces no longer than $limit, preferring paragraph breaks,
 * then sentence ends, then a hard cut.
 *
 * Every separator is kept inside a piece, so concatenating the pieces gives
 * back the original exactly — which is what lets remote_translate() rebuild a
 * notice from its translated parts without inventing or losing a line break.
 *
 * @return string[]
 */
function split_for_translation(string $text, int $limit): array
{
    if (mb_strlen($text) <= $limit) {
        return [$text];
    }

    $pieces = [];
    foreach (preg_split('/(\n\s*\n)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text] as $para) {
        if (mb_strlen($para) <= $limit) {
            $pieces[] = $para;
            continue;
        }

        // Captured so the whitespace after a full stop travels with the text
        // rather than being dropped on the floor.
        $parts  = preg_split('/(?<=[.!?।])(\s+)/u', $para, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$para];
        $buffer = '';
        foreach ($parts as $part) {
            // A single sentence over the limit still has to be cut somewhere.
            while (mb_strlen($part) > $limit) {
                if ($buffer !== '') {
                    $pieces[] = $buffer;
                    $buffer   = '';
                }
                $pieces[] = mb_substr($part, 0, $limit);
                $part     = mb_substr($part, $limit);
            }
            if ($buffer !== '' && mb_strlen($buffer) + mb_strlen($part) > $limit) {
                $pieces[] = $buffer;
                $buffer   = '';
            }
            $buffer .= $part;
        }
        if ($buffer !== '') {
            $pieces[] = $buffer;
        }
    }
    return $pieces;
}

/** Google Cloud Translation v2 — an API key, billed per character. */
function google_translate(string $text): ?string
{
    $key = setting('translate_api_key');
    if ($key === null) {
        return null;
    }
    $body = translate_http(
        'https://translation.googleapis.com/language/translate/v2?key=' . rawurlencode($key),
        http_build_query(['q' => $text, 'source' => 'en', 'target' => 'ne', 'format' => 'text']),
        'application/x-www-form-urlencoded'
    );
    $json = $body === null ? null : json_decode($body, true);
    $out  = $json['data']['translations'][0]['translatedText'] ?? null;
    return is_string($out) ? html_entity_decode($out, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
}

/** LibreTranslate — self-hosted or a public instance; the key is optional. */
function libre_translate(string $text): ?string
{
    $endpoint = setting('translate_endpoint');
    if ($endpoint === null) {
        return null;
    }
    $payload = ['q' => $text, 'source' => 'en', 'target' => 'ne', 'format' => 'text'];
    if ($key = setting('translate_api_key')) {
        $payload['api_key'] = $key;
    }
    $body = translate_http(
        rtrim($endpoint, '/') . '/translate',
        json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '',
        'application/json'
    );
    $json = $body === null ? null : json_decode($body, true);
    $out  = $json['translatedText'] ?? null;
    return is_string($out) ? $out : null;
}

/**
 * MyMemory — no account needed, which is why it is the recommended first
 * choice here. The contact address raises the daily allowance and is sent
 * only because MyMemory's own terms ask for it.
 */
function mymemory_translate(string $text): ?string
{
    $query = ['q' => $text, 'langpair' => 'en|ne'];
    if ($email = setting('translate_contact_email')) {
        $query['de'] = $email;
    }
    $body = translate_http('https://api.mymemory.translated.net/get?' . http_build_query($query));
    $json = $body === null ? null : json_decode($body, true);

    $out    = $json['responseData']['translatedText'] ?? null;
    $status = (int) ($json['responseStatus'] ?? 0);
    if (!is_string($out) || $status !== 200) {
        if ($body !== null) {
            // A quota refusal comes back as a normal 200 with a message in the
            // body, so it has to be caught here rather than by the HTTP code.
            translation_back_off('MyMemory returned status ' . $status);
        }
        return null;
    }
    // MyMemory echoes the English back when it has no translation.
    return strcasecmp(trim($out), trim($text)) === 0 ? null : html_entity_decode($out, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * One HTTP request, with short timeouts so a slow or unreachable service can
 * never hold up a page. Returns the body, or null — and starts a backoff so
 * the next visitor does not wait for the same failure.
 */
function translate_http(string $url, ?string $payload = null, ?string $contentType = null): ?string
{
    if (!function_exists('curl_init')) {
        translation_back_off('cURL is not available on this server');
        return null;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT      => 'KamalaScienceCampus/1.0 (+https://kamalasciencecampus.edu.np)',
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . ($contentType ?? 'application/json')]);
    }

    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error  = curl_error($ch);
    curl_close($ch);

    if (!is_string($body) || $status < 200 || $status > 299) {
        translation_back_off($error !== '' ? $error : 'HTTP ' . $status);
        return null;
    }
    return $body;
}

/* ----------------------------------------------------------- notice layer -- */

/** Bilingual notice columns this file is allowed to read and write. */
const TRANSLATABLE_FIELDS = ['title', 'body'];

/**
 * How many pieces of text this request may still send to a translation
 * service. The public board lists up to sixty notices, and sixty round trips
 * would turn one page load into a minute of waiting. Whatever the budget does
 * not cover stays in English and is picked up by a later visit, or all at once
 * from "Translate every notice now" on the System page — which raises this.
 *
 * The glossary is not counted: it makes no request at all.
 */
function translation_budget(?int $newBudget = null): int
{
    static $budget = 6;
    if ($newBudget !== null) {
        $budget = max(0, $newBudget);
    }
    return $budget;
}

/**
 * Whether the notices table carries the flags recording which Nepali text was
 * machine-made. Installs that have not run the database update yet simply do
 * without them: the translation is still stored, we just cannot tell it apart
 * from a human one afterwards.
 */
function notices_track_auto_translation(): bool
{
    static $has = null;
    if ($has === null) {
        try {
            // Both columns, not just one. They arrive as two separate
            // migrations, either of which can be skipped on its own — and
            // writing to a column that is not there fails the whole save.
            $has = one("SHOW COLUMNS FROM notices LIKE 'title_ne_auto'") !== null
                && one("SHOW COLUMNS FROM notices LIKE 'body_ne_auto'") !== null;
        } catch (Throwable $e) {
            $has = false;
        }
    }
    return $has;
}

/** Notice ids translated during this request, so the page can say so. */
function translated_this_request(?int $add = null): array
{
    static $ids = [];
    if ($add !== null) {
        $ids[$add] = true;
    }
    return $ids;
}

/**
 * The notice text for the viewer's language, translating it on the spot when
 * the Nepali column is empty.
 *
 * This is bilingual() with the English fallback replaced by a translation, so
 * notices published before any of this existed become readable in Nepali
 * without anyone having to edit them. The result is written back to the row,
 * so the work happens once rather than on every visit.
 */
function notice_bilingual(array $n, string $field): string
{
    $english = trim((string) ($n[$field . '_en'] ?? ''));
    if (!is_nepali()) {
        return $english;
    }

    $nepali = trim((string) ($n[$field . '_ne'] ?? ''));
    if ($nepali !== '') {
        return $nepali;
    }
    if ($english === '' || !in_array($field, TRANSLATABLE_FIELDS, true)) {
        return $english;
    }

    $made = translate_to_nepali($english);
    if ($made === null) {
        return $english;
    }

    $id = (int) ($n['id'] ?? 0);
    if ($id > 0) {
        store_notice_translation($id, $field, $made['text']);
        translated_this_request($id);
    }
    return $made['text'];
}

/** True when the Nepali a viewer is reading was produced by this file. */
function notice_is_machine_translated(array $n): bool
{
    if (!is_nepali()) {
        return false;
    }
    if (isset(translated_this_request()[(int) ($n['id'] ?? 0)])) {
        return true;
    }
    return !empty($n['title_ne_auto']) || !empty($n['body_ne_auto']);
}

/**
 * Cache a translation on the notice. A failure here is deliberately silent:
 * the visitor already has their Nepali text, and a read-only database should
 * not turn a notice board into an error page.
 */
function store_notice_translation(int $id, string $field, string $nepali): void
{
    if (!in_array($field, TRANSLATABLE_FIELDS, true)) {
        return;
    }
    try {
        // $field is interpolated because a column name cannot be bound; the
        // allowlist above is what makes that safe.
        $sql = notices_track_auto_translation()
            ? "UPDATE notices SET {$field}_ne = ?, {$field}_ne_auto = 1 WHERE id = ?"
            : "UPDATE notices SET {$field}_ne = ? WHERE id = ?";
        q($sql, [$nepali, $id]);
    } catch (Throwable $e) {
        error_log('Could not store the Nepali ' . $field . ' of notice ' . $id . ': ' . $e->getMessage());
    }
}

/**
 * Translate every notice that has no Nepali text yet, in one pass.
 *
 * $redoAuto also replaces translations this file made earlier, which is what
 * you want after switching provider — text an admin typed or corrected is
 * never touched either way.
 *
 * @return array{scanned:int, translated:int, left:int}
 */
function backfill_notice_translations(bool $redoAuto = false, int $limit = 200): array
{
    // Admin-initiated and not holding up a visitor, so the per-request budget
    // that protects page loads does not apply.
    translation_budget(4 * $limit);

    // It does have to finish, though. A translation service answering slowly
    // could otherwise run past the host's max_execution_time and kill the
    // page mid-pass, with nothing on screen to say how far it got. Stopping
    // ourselves means the admin gets a count and can simply run it again —
    // everything already translated is saved as it goes.
    $deadline = microtime(true) + BACKFILL_SECONDS;

    $tracking = notices_track_auto_translation();
    $rows     = all('SELECT * FROM notices ORDER BY published_at DESC LIMIT ' . (int) $limit);

    $translated = 0;
    $left       = 0;
    foreach ($rows as $n) {
        foreach (TRANSLATABLE_FIELDS as $field) {
            $english = trim((string) ($n[$field . '_en'] ?? ''));
            if ($english === '') {
                continue;
            }
            $nepali = trim((string) ($n[$field . '_ne'] ?? ''));
            $isAuto = $tracking && !empty($n[$field . '_ne_auto']);
            if ($nepali !== '' && !($redoAuto && $isAuto)) {
                continue;
            }
            if (microtime(true) > $deadline) {
                $left++;
                continue;
            }

            $made = translate_to_nepali($english);
            if ($made === null) {
                $left++;
                continue;
            }
            store_notice_translation((int) $n['id'], $field, $made['text']);
            $translated++;
        }
    }

    return ['scanned' => count($rows), 'translated' => $translated, 'left' => $left];
}
