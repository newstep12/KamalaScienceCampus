<?php
declare(strict_types=1);

/**
 * Romanised Nepali names, written back in Devanagari.
 *
 * A campus identity card carries the holder's name in both scripts, and only
 * the English one is asked for: the Nepali box is optional, most students skip
 * it, and their card came out with one name on it. This writes the second one.
 *
 * Two layers, and the order matters:
 *
 *   1. A dictionary of the given names and surnames these students actually
 *      have. Romanised Nepali is not a spelling system — Paudel and Poudel are
 *      one name, Shrestha ends in a conjunct no rule would guess from its
 *      letters — so the names that recur are written out rather than derived.
 *   2. A syllable-by-syllable transliterator for everything else, which is an
 *      approximation and is meant to read as one.
 *
 * What it is not is authority over somebody's name. The result is derived at
 * the moment a card is drawn and stored nowhere: a student who types their own
 * Nepali name into the optional box overrides it permanently and everywhere,
 * which is the whole reason that box still exists. The card page says which of
 * the two it is printing, so nobody is left believing the campus holds a
 * spelling it does not.
 *
 * Nothing here consults the notices' translation service. This is
 * transliteration — the same name in another script — not translation, and it
 * makes no request, needs no key and cannot fail slowly.
 */

/**
 * Given names and surnames written out, keyed by their romanisation folded to
 * lower case. Several spellings map to one name on purpose: a student writing
 * Poudel and their cousin writing Paudel are both पौडेल.
 *
 * Kept deliberately to names whose Devanagari is not in doubt. A name that is
 * merely plausible belongs in the transliterator below, where it is at least
 * consistently wrong and the student can see it is a guess.
 */
function nepali_name_words(): array
{
    static $words = null;
    if ($words !== null) {
        return $words;
    }

    return $words = [
        /* ---- surnames ---- */
        'acharya' => 'आचार्य',      'adhikari' => 'अधिकारी',    'aryal' => 'अर्याल',
        'bajracharya' => 'बज्राचार्य', 'bam' => 'बम',            'baral' => 'बराल',
        'basnet' => 'बस्नेत',        'basnyat' => 'बस्न्यात',     'bhandari' => 'भण्डारी',
        'bhatta' => 'भट्ट',          'bhattarai' => 'भट्टराई',    'bhattrai' => 'भट्टराई',
        'bhusal' => 'भुसाल',         'bishwakarma' => 'विश्वकर्मा', 'bista' => 'बिष्ट',
        'bisht' => 'बिष्ट',          'bohara' => 'बोहरा',        'budha' => 'बुढा',
        'budhathoki' => 'बुढाथोकी',   'chand' => 'चन्द',          'chapagain' => 'चापागाईं',
        'chaudhary' => 'चौधरी',      'chaudhari' => 'चौधरी',      'chhetri' => 'क्षेत्री',
        'chettri' => 'क्षेत्री',        'kshetri' => 'क्षेत्री',       'dahal' => 'दाहाल',
        'damai' => 'दमाई',          'danuwar' => 'दनुवार',       'devkota' => 'देवकोटा',
        'dhakal' => 'ढकाल',         'dhungana' => 'ढुङ्गाना',     'dixit' => 'दीक्षित',
        'gautam' => 'गौतम',         'ghale' => 'घले',           'gharti' => 'घर्ती',
        'ghimire' => 'घिमिरे',        'giri' => 'गिरी',           'gurung' => 'गुरुङ',
        'joshi' => 'जोशी',          'kafle' => 'काफ्ले',         'kandel' => 'कँडेल',
        'karki' => 'कार्की',          'katwal' => 'कटुवाल',        'khadka' => 'खड्का',
        'khanal' => 'खनाल',         'kharel' => 'खरेल',         'koirala' => 'कोइराला',
        'kumal' => 'कुमाल',         'kunwar' => 'कुँवर',         'lama' => 'लामा',
        'lamichhane' => 'लामिछाने',  'limbu' => 'लिम्बू',          'lohani' => 'लोहनी',
        'luitel' => 'लुइटेल',         'magar' => 'मगर',          'mahat' => 'महत',
        'mahato' => 'महतो',         'maharjan' => 'महर्जन',      'majhi' => 'माझी',
        'malla' => 'मल्ल',           'manandhar' => 'मानन्धर',    'marasini' => 'मरासिनी',
        'nepal' => 'नेपाल',          'nepali' => 'नेपाली',        'neupane' => 'न्यौपाने',
        'newar' => 'नेवार',          'niraula' => 'निरौला',       'ojha' => 'ओझा',
        'oli' => 'ओली',             'pandey' => 'पाण्डे',        'pant' => 'पन्त',
        'panta' => 'पन्त',           'parajuli' => 'पराजुली',      'pariyar' => 'परियार',
        'paudel' => 'पौडेल',         'poudel' => 'पौडेल',         'phuyal' => 'फुयाल',
        'pokharel' => 'पोखरेल',      'pokhrel' => 'पोखरेल',       'pradhan' => 'प्रधान',
        'pun' => 'पुन',             'puri' => 'पुरी',            'rai' => 'राई',
        'rana' => 'राणा',           'raut' => 'राउत',           'regmi' => 'रेग्मी',
        'rijal' => 'रिजाल',          'rimal' => 'रिमाल',          'roka' => 'रोका',
        'sah' => 'साह',             'sapkota' => 'सापकोटा',      'sarki' => 'सार्की',
        'saud' => 'साउद',           'sedhai' => 'सेढाईं',         'shah' => 'शाह',
        'shahi' => 'शाही',          'shakya' => 'शाक्य',         'sharma' => 'शर्मा',
        'sherpa' => 'शेर्पा',         'shrestha' => 'श्रेष्ठ',        'silwal' => 'सिलवाल',
        'singh' => 'सिंह',           'subedi' => 'सुवेदी',         'sunar' => 'सुनार',
        'tamang' => 'तामाङ',        'thakur' => 'ठाकुर',         'thapa' => 'थापा',
        'tharu' => 'थारू',           'timalsina' => 'तिमल्सिना',   'tiwari' => 'तिवारी',
        'upadhyay' => 'उपाध्याय',    'wagle' => 'वाग्ले',          'yadav' => 'यादव',

        /* ---- the names that sit between a given name and a surname ---- */
        'bahadur' => 'बहादुर',       'devi' => 'देवी',            'kumar' => 'कुमार',
        'kumari' => 'कुमारी',        'lal' => 'लाल',             'maya' => 'माया',
        'prasad' => 'प्रसाद',        'raj' => 'राज',             'man' => 'मान',

        /* ---- given names ---- */
        'aayush' => 'आयुष',         'abhishek' => 'अभिषेक',      'aditya' => 'आदित्य',
        'ajay' => 'अजय',           'akash' => 'आकाश',          'alisha' => 'आलिशा',
        'amrit' => 'अमृत',          'anil' => 'अनिल',           'anita' => 'अनिता',
        'anjali' => 'अञ्जली',        'anup' => 'अनुप',           'anusha' => 'अनुशा',
        'arjun' => 'अर्जुन',          'ashish' => 'आशिष',         'asmita' => 'अस्मिता',
        'bharat' => 'भरत',          'bhim' => 'भीम',            'bibek' => 'विवेक',
        'bikash' => 'विकास',        'bikram' => 'विक्रम',         'binish' => 'बिनिश',
        'binod' => 'विनोद',         'bishal' => 'विशाल',         'chandra' => 'चन्द्र',
        'chandeshwar' => 'चन्देश्वर', 'deepak' => 'दीपक',         'dipak' => 'दीपक',
        'dinesh' => 'दिनेश',         'dipesh' => 'दिपेश',         'ganesh' => 'गणेश',
        'gita' => 'गीता',           'gopal' => 'गोपाल',         'hari' => 'हरि',
        'indra' => 'इन्द्र',          'jeevan' => 'जीवन',         'kamal' => 'कमल',
        'keshav' => 'केशव',         'keshab' => 'केशव',         'kiran' => 'किरण',
        'krishna' => 'कृष्ण',       'laxmi' => 'लक्ष्मी',         'lok' => 'लोक',
        'madan' => 'मदन',
        'mahesh' => 'महेश',         'manisha' => 'मनीषा',        'manoj' => 'मनोज',
        'mohan' => 'मोहन',         'nabin' => 'नवीन',          'nabaraj' => 'नवराज',
        'namuna' => 'नमुना',        'nanda' => 'नन्द',          'narayan' => 'नारायण',
        'nawaraj' => 'नवराज',
        'nikita' => 'निकिता',        'nirmal' => 'निर्मल',         'padam' => 'पदम',
        'pooja' => 'पूजा',          'puja' => 'पूजा',            'prabin' => 'प्रवीण',
        'prakash' => 'प्रकाश',       'pramod' => 'प्रमोद',        'pratik' => 'प्रतीक',
        'priya' => 'प्रिया',          'purna' => 'पूर्ण',           'rabin' => 'रवीन',
        'rabindra' => 'रवीन्द्र',      'rajendra' => 'राजेन्द्र',      'rajesh' => 'राजेश',
        'raju' => 'राजु',           'rakesh' => 'राकेश',        'ram' => 'राम',
        'ramesh' => 'रमेश',         'rashmi' => 'रश्मी',         'ravi' => 'रवि',
        'rita' => 'रीता',           'sabina' => 'सबिना',         'sagar' => 'सागर',
        'samir' => 'समीर',          'sandeep' => 'सन्दीप',       'sangita' => 'संगीता',
        'sanjay' => 'संजय',         'santosh' => 'सन्तोष',       'sarita' => 'सरिता',
        'saroj' => 'सरोज',          'shanti' => 'शान्ति',         'shiva' => 'शिव',
        'shyam' => 'श्याम',         'sita' => 'सीता',            'sudarshan' => 'सुदर्शन',
        'sudip' => 'सुदीप',          'sujan' => 'सुजन',          'sunita' => 'सुनिता',
        'suraj' => 'सुरज',          'surendra' => 'सुरेन्द्र',       'sushma' => 'सुष्मा',
        'tara' => 'तारा',           'tej' => 'तेज',             'umesh' => 'उमेश',
        'upendra' => 'उपेन्द्र',       'uttam' => 'उत्तम',          'yogesh' => 'योगेश',
    ];
}

/**
 * Consonants, longest romanisation first, because 'chh' must be tried before
 * 'ch' and 'ch' before 'c'. The order of this array is the order they are
 * tried in.
 */
const DEVANAGARI_CONSONANTS = [
    'chh' => 'छ',  'shh' => 'ष',  'gy'  => 'ज्ञ', 'ksh' => 'क्ष',
    'kh'  => 'ख',  'gh'  => 'घ',  'ng'  => 'ङ',  'ch'  => 'च',  'jh' => 'झ',
    'th'  => 'थ',  'dh'  => 'ध',  'ph'  => 'फ',  'bh'  => 'भ',  'sh' => 'श',
    'ny'  => 'ञ',  'tt'  => 'ट',  'dd'  => 'ड',
    'k' => 'क', 'g' => 'ग', 'c' => 'क', 'j' => 'ज', 't' => 'त', 'd' => 'द',
    'n' => 'न', 'p' => 'प', 'f' => 'फ', 'b' => 'ब', 'm' => 'म', 'y' => 'य',
    'r' => 'र', 'l' => 'ल', 'v' => 'व', 'w' => 'व', 's' => 'स', 'h' => 'ह',
    'z' => 'ज', 'x' => 'क्ष', 'q' => 'क',
];

/**
 * Vowels as [independent, dependent]: the first is how the vowel is written
 * when it opens a syllable of its own, the second the sign hung on the
 * consonant before it. Longest first, as above.
 */
const DEVANAGARI_VOWELS = [
    'aa' => ['आ', 'ा'], 'ai' => ['ऐ', 'ै'], 'au' => ['औ', 'ौ'],
    'ee' => ['ई', 'ी'], 'ii' => ['ई', 'ी'], 'oo' => ['ऊ', 'ू'], 'uu' => ['ऊ', 'ू'],
    'a'  => ['अ', ''],  'i'  => ['इ', 'ि'], 'u'  => ['उ', 'ु'],
    'e'  => ['ए', 'े'],  'o'  => ['ओ', 'ो'],
];

/** Devanagari's own halanta, which cancels a consonant's built-in 'a'. */
const DEVANAGARI_HALANTA = '्';

/**
 * Endings where a final romanised 'a' is the consonant's own built-in vowel
 * rather than the long ा — Mahendra is महेन्द्र, not महेन्द्रा.
 *
 * Romanised Nepali writes both with the same letter and there is no rule in
 * the spelling that separates them, so what separates them is that these
 * clusters are how Sanskrit-derived names end. Everything else takes the ा of
 * Sharma and Lama, which is the commoner case by a wide margin.
 */
const DEVANAGARI_INHERENT_ENDINGS = [
    'ndra', 'ntra', 'mbra', 'dra', 'tra', 'kra', 'gra', 'bra', 'shra',
    'nta', 'nda', 'mba', 'nga', 'nka', 'shna', 'shma', 'ksha', 'tva', 'sya',
];

/**
 * One romanised word, transliterated syllable by syllable.
 *
 * Devanagari consonants carry an 'a' of their own, so the work is deciding,
 * at each consonant, what follows it: a vowel sign, another consonant it has
 * to be joined to with a halanta, or the end of the word, where the built-in
 * 'a' is simply left to be silent.
 *
 * Two endings are treated as the conventions they are rather than as the
 * letters they are. A final 'a' is the long ा of Sharma and Lama, not a silent
 * inherent vowel nobody would have written down; a final 'i' is the ी of
 * Adhikari and Joshi. Both are what a romanised Nepali name means by them far
 * more often than not, and the words where they are not are in the dictionary.
 */
function transliterate_to_devanagari(string $word): string
{
    $word = strtolower($word);
    $length = strlen($word);
    $out = '';
    $pos = 0;
    $afterConsonant = false;

    while ($pos < $length) {
        $matched = false;

        foreach (DEVANAGARI_CONSONANTS as $roman => $letter) {
            $n = strlen($roman);
            if (substr($word, $pos, $n) !== $roman) {
                continue;
            }
            $pos += $n;
            $out .= $letter;

            // What comes after decides how this consonant is finished off.
            $vowel = devanagari_vowel_at($word, $pos);
            if ($vowel !== null) {
                [$roman2, $sign] = $vowel;
                $atEnd = ($pos + strlen($roman2)) >= $length;
                if ($atEnd && $roman2 === 'a') {
                    $sign = devanagari_ends_inherent($word)
                        ? ''                        // Mahendra, Hemanta
                        : 'ा';                      // Sharma, Lama, Namuna
                } elseif ($atEnd && $roman2 === 'i') {
                    $sign = 'ी';                    // Adhikari, Joshi, Giri
                }
                $out .= $sign;
                $pos += strlen($roman2);
            } elseif ($pos < $length) {
                $out .= DEVANAGARI_HALANTA;          // joined to the next consonant
            }
            $matched = true;
            $afterConsonant = true;
            break;
        }
        if ($matched) {
            continue;
        }

        $vowel = devanagari_vowel_at($word, $pos);
        if ($vowel !== null) {
            [$roman, $sign] = $vowel;
            // A vowel reached without a consonant in front of it opens its own
            // syllable, and is written in full.
            $out .= $afterConsonant && $sign !== '' ? $sign : DEVANAGARI_VOWELS[$roman][0];
            $pos += strlen($roman);
            $afterConsonant = false;
            continue;
        }

        // Anything left is not part of the scheme — a digit, a stray mark.
        // Carried through rather than dropped, so nothing disappears silently.
        $out .= $word[$pos];
        $pos++;
        $afterConsonant = false;
    }

    return $out;
}

/** Whether this word ends in one of the clusters that keep their built-in 'a'. */
function devanagari_ends_inherent(string $word): bool
{
    foreach (DEVANAGARI_INHERENT_ENDINGS as $ending) {
        if (str_ends_with($word, $ending)) {
            return true;
        }
    }
    return false;
}

/**
 * The vowel written at $pos as [romanisation, dependent sign], or null when
 * there is not one there. Longest match, so 'aa' is never read as two 'a's.
 */
function devanagari_vowel_at(string $word, int $pos): ?array
{
    foreach (DEVANAGARI_VOWELS as $roman => [$independent, $sign]) {
        if (substr($word, $pos, strlen($roman)) === $roman) {
            return [$roman, $sign];
        }
    }
    return null;
}

/**
 * A whole romanised name in Devanagari, or null when there is nothing to work
 * with.
 *
 * Word by word, because that is how the dictionary is keyed and how a name is
 * built: a given name the dictionary knows and a surname it does not still
 * gets the half it knows exactly right.
 *
 * Null for a name with no Latin letters in it at all — already Devanagari,
 * or a row of punctuation — because there is nothing to transliterate and a
 * caller offering this as a second line should print no second line.
 */
function nepali_name(string $latin): ?string
{
    $latin = trim($latin);
    if ($latin === '' || !preg_match('/\p{Latin}/u', $latin)) {
        return null;
    }

    $dictionary = nepali_name_words();
    $out = [];

    // Split on whitespace, keeping the separators out of the way; a name is
    // one or more words and nothing here needs to know which is which.
    foreach (preg_split('/\s+/u', $latin) as $word) {
        if ($word === '') {
            continue;
        }
        // Trailing punctuation — the full stop after an initial, a comma — is
        // set aside so it cannot spoil a dictionary hit, then put back.
        preg_match('/^(\p{P}*)(.*?)(\p{P}*)$/u', $word, $parts);
        [, $before, $core, $after] = $parts;
        if ($core === '') {
            $out[] = $word;
            continue;
        }
        $key = mb_strtolower($core);
        $out[] = $before . ($dictionary[$key] ?? transliterate_to_devanagari($core)) . $after;
    }

    $name = trim(implode(' ', $out));
    return $name === '' ? null : $name;
}
