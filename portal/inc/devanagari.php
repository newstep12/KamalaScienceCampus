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
        'kc' => 'के.सी.',            'kumari' => 'कुमारी',        'lal' => 'लाल',
        'maya' => 'माया',
        'prasad' => 'प्रसाद',        'raj' => 'राज',             'man' => 'मान',
        'hemanta' => 'हेमन्त',       'hemant' => 'हेमन्त',

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
    // No 'tt', 'dd' or 'ny'. Each looked like a digraph and was not: a
    // doubled letter in a romanised name is gemination, so Uttara is उत्तरा
    // and not उटरा, Buddha बुद्धा and not बुड्हा; and 'ny' is न् followed by
    // य, so Punya is पुन्या, not पुञ — which had eaten the य outright. Taking
    // them out lets the single-letter rules below produce the conjunct on
    // their own. (The final ा on those two is the word-final rule further
    // down, which is deliberate and right far more often than not — it is
    // what makes Sumitra सुमित्रा.) Retroflex ट and ड simply are not written
    // in plain romanisation; names that turn on them are in the dictionary.
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
    // 'ou' and 'ow' beside 'au', because that is how this vowel is actually
    // written here — Gourav, Sourav, Sangroula. Without them the pair broke
    // into a ो and a stray independent उ in the middle of the word, which is
    // not an approximation of the name but a syllable that is not in it.
    'aa' => ['आ', 'ा'], 'ai' => ['ऐ', 'ै'], 'au' => ['औ', 'ौ'],
    'ou' => ['औ', 'ौ'], 'ow' => ['औ', 'ौ'],
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
 * the spelling that separates them, so this list is kept to the clusters
 * where the answer is not in doubt — the -ndra of Mahendra and Rajendra.
 *
 * Not 'shna'. Krishna is in the dictionary and never reaches here, so all it
 * did was take the last syllable off Trishna, which is तृष्णा and came out
 * त्रिश्न — the very harm the rest of this note is about.
 *
 * Deliberately short. It once held every two-consonant cluster that ends a
 * Sanskrit-derived masculine name, and those same clusters end a great many
 * ordinary Nepali names that do take the ा: it printed Sumitra as सुमित्र,
 * Chanda as चन्द and Diksha as दिक्ष, dropping the last syllable of the
 * holder's name off their card. Where the two readings collide the ा is far
 * the commoner, so the ा is what an unlisted ending gets.
 */
const DEVANAGARI_INHERENT_ENDINGS = ['ndra', 'ntra', 'mbra'];

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

    while ($pos < $length) {
        $matched = false;

        foreach (DEVANAGARI_CONSONANTS as $roman => $letter) {
            $n = strlen($roman);
            if (substr($word, $pos, $n) !== $roman) {
                continue;
            }
            $pos += $n;
            $vowel = devanagari_vowel_at($word, $pos);

            /**
             * 'ng' reads three ways, and only one of them is the two letters
             * taken together.
             *
             * At the end of a word it is the bare velar nasal: Gurung is
             * गुरुङ. Between vowels it is that nasal joined to a ग — Ganga is
             * गङ्गा — but not when it opens the word, where Ngima is ङिमा and
             * the ग would be a consonant the name does not have.
             *
             * Before a consonant — and only a consonant; tested as "anything
             * follows", a digit stuck on the end of an imported surname was
             * enough to turn Gurung into गुरुङ्ग — it is not a pair at all.
             * Reading it as one
             * consumed the g and wrote neither it nor anything in its place:
             * Jangbahadur came out जङ्बहदुर, Sangh सङ्ह with the घ degraded to
             * a ह. So the match is given back, the n is written on its own,
             * and the g is read again on the next turn as the consonant it is.
             */
            if ($roman === 'ng' && $vowel === null && devanagari_consonant_at($word, $pos) !== null) {
                $pos--;                              // hand the g back
                // The velar nasal, the same letter the vowel case writes, so
                // Sangroula and Ganga do not spell one sound two ways.
                $out .= 'ङ' . DEVANAGARI_HALANTA;
                $matched = true;
                break;
            }
            $out .= ($roman === 'ng' && $vowel !== null && ($pos - $n) !== 0) ? 'ङ्ग' : $letter;

            // What comes after decides how this consonant is finished off.
            if ($vowel !== null) {
                [$roman2, $sign] = $vowel;
                $atEnd = ($pos + strlen($roman2)) >= $length;
                if ($atEnd && $roman2 === 'a') {
                    $sign = devanagari_ends_inherent($word)
                        ? ''                        // Mahendra, Krishna
                        : 'ा';                      // Sharma, Lama, Namuna
                } elseif ($atEnd && $roman2 === 'i') {
                    $sign = 'ी';                    // Adhikari, Joshi, Giri
                }
                $out .= $sign;
                $pos += strlen($roman2);
                // The syllable is closed here, which is what stops a second
                // vowel hanging a second sign on the same consonant: Deo once
                // came out देो, two matras stacked on one letter, instead of
                // the ओ opening the syllable it is.
            } else {
                // A halanta joins this consonant to the next one, so it is
                // written only when a consonant is what follows. Tested
                // against "anything at all follows", it fell before full
                // stops and apostrophes too: K.C., one of the commonest
                // surnames here, printed as क्.क.
                if (devanagari_consonant_at($word, $pos) !== null) {
                    $out .= DEVANAGARI_HALANTA;
                }
            }
            $matched = true;
            break;
        }
        if ($matched) {
            continue;
        }

        $vowel = devanagari_vowel_at($word, $pos);
        if ($vowel !== null) {
            // Reaching a vowel out here means no consonant is carrying it —
            // the branch above takes every vowel that follows one — so it
            // opens a syllable of its own and is written in full.
            [$roman] = $vowel;
            $out .= DEVANAGARI_VOWELS[$roman][0];
            $pos += strlen($roman);
            continue;
        }

        // Anything left is not part of the scheme — a digit, a stray mark.
        // Carried through as written rather than dropped, so nothing
        // disappears silently. Not rewritten into Devanagari digits: the
        // number rows on the card are localised at render time and follow the
        // card's language, an English card keeps its 0-9, and a name that
        // rewrote its own would be the only thing on the front that did not.
        $out .= $word[$pos];
        $pos++;
    }

    return $out;
}

/** The consonant written at $pos, or null when there is not one there. */
function devanagari_consonant_at(string $word, int $pos): ?string
{
    foreach (DEVANAGARI_CONSONANTS as $roman => $letter) {
        if (substr($word, $pos, strlen($roman)) === $roman) {
            return $letter;
        }
    }
    return null;
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
 * The letters of the alphabet as they are said, for a name written with an
 * initial.
 *
 * A single letter standing on its own is not a syllable to transliterate, it
 * is a letter being named — and read as a syllable, two different initials
 * came out as one Devanagari letter, because 'c' and 'k' are both क. "K C
 * Sharma" printed as क क शर्मा.
 */
const DEVANAGARI_LETTER_NAMES = [
    'a' => 'ए',  'b' => 'बी', 'c' => 'सी', 'd' => 'डी', 'e' => 'ई',
    'f' => 'एफ', 'g' => 'जी', 'h' => 'एच', 'i' => 'आई', 'j' => 'जे',
    'k' => 'के', 'l' => 'एल', 'm' => 'एम', 'n' => 'एन', 'o' => 'ओ',
    'p' => 'पी', 'q' => 'क्यू', 'r' => 'आर', 's' => 'एस', 't' => 'टी',
    'u' => 'यू', 'v' => 'भी', 'w' => 'डब्ल्यू', 'x' => 'एक्स', 'y' => 'वाई',
    'z' => 'जेड',
];

/**
 * One word of a name, looked up and otherwise transliterated.
 *
 * Tried as written, then with its punctuation taken out — so the surname
 * written K.C., KC and K.C. all reach the one entry — and then, if it is
 * joined by a hyphen or a stop, part by part: Poudel-Sharma is two names the
 * dictionary knows, and read whole it matched neither and fell through to the
 * transliterator as पोउदेल-शर्मा, losing the very conjuncts the dictionary is
 * there for.
 *
 * Where the entry already ends in punctuation the caller's trailing mark is
 * not added on top, or K.C. came out के.सी.. and plain Kc as के.सी — the same
 * surname printed two ways on two students' cards.
 */
function devanagari_word(string $core, array $dictionary): string
{
    $key   = strtolower($core);
    $plain = preg_replace('/\p{P}+/u', '', $key) ?? $key;
    if (isset($dictionary[$key]))   { return $dictionary[$key]; }
    if (isset($dictionary[$plain])) { return $dictionary[$plain]; }
    if (isset(DEVANAGARI_LETTER_NAMES[$plain])) { return DEVANAGARI_LETTER_NAMES[$plain]; }

    // Split on the punctuation that joins names, keeping it in place.
    $parts = preg_split("/([\\-.'])/u", $core, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (count($parts) > 1) {
        $joined = '';
        foreach ($parts as $part) {
            if ($part === '' || !preg_match('/[A-Za-z]/', $part)) {
                $joined .= $part;                   // the joining mark itself
                continue;
            }
            // Transliterated whether or not it is purely letters. Copied
            // through on that test, a part like "Kumar2" reached the card as
            // Latin text inside the line it sets in a Devanagari face.
            $lower   = strtolower($part);
            $joined .= $dictionary[$lower]
                    ?? DEVANAGARI_LETTER_NAMES[$lower]
                    ?? transliterate_to_devanagari($part);
        }
        return $joined;
    }

    return transliterate_to_devanagari($core);
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
    // Typographic spacing and quotes folded to their ASCII equivalents first,
    // so a name is judged on its letters rather than on how it was pasted —
    // and genuinely first, because the initials below are found with \s,
    // which does not match a non-breaking space. Read in the other order, a
    // name pasted out of a spreadsheet kept its NBSP between the initials and
    // went the long way round to क. क. anyway.
    $latin = trim(strtr($latin, [
        "\u{00A0}" => ' ', "\u{2007}" => ' ', "\u{202F}" => ' ', "\u{2009}" => ' ',
        "\u{2018}" => "'", "\u{2019}" => "'", "\u{2032}" => "'",
        "\u{2010}" => '-', "\u{2011}" => '-', "\u{2013}" => '-', "\u{2014}" => '-',
    ]));

    // "K. C." is "K.C." is "KC": closed up before the split on whitespace, or
    // the surname arrives as two lone letters and misses the dictionary.
    $latin = preg_replace('/\b([A-Za-z])\.\s+(?=[A-Za-z]\b)/', '$1.', $latin) ?? $latin;

    // Nothing to work from: no Latin letter to read.
    if ($latin === '' || !preg_match('/[A-Za-z]/', $latin)) {
        return null;
    }
    // Not valid UTF-8 — a Latin-1 byte pasted or imported into the field.
    // Every /u test below quietly returns false on such a string rather than
    // matching, so the guards that follow would all pass it, and preg_split()
    // would then hand foreach a false. Refused here instead.
    if (!preg_match('//u', $latin)) {
        return null;
    }
    // Already carries Devanagari. Transliterating it would put a name the
    // record already holds on the card a second time — "Binish Parajuli
    // बिनिश" came back as बिनिश पराजुली बिनिश — and under a notice telling
    // the holder the campus had written it.
    if (preg_match('/\p{Devanagari}/u', $latin)) {
        return null;
    }
    // A letter this scheme has no reading for. The tables are ASCII, so an
    // accented or non-Latin letter fell through the loop and was emitted
    // unchanged: José came out जोस्é, a Latin letter inside the line the card
    // sets in its Devanagari face. There is no honest transliteration to
    // offer here, so none is offered and id_card_missing() asks for the name
    // instead.
    //
    // A letter, though, and not merely a byte over 127. Tested that way, one
    // invisible character was enough: a name pasted with a non-breaking space
    // in it, which trim() does not touch, or carrying a typographic
    // apostrophe, lost its Devanagari line altogether and was reported to the
    // holder as a missing detail while reading as perfectly ordinary ASCII.
    // Those are spacing and punctuation, so they are normalised just below
    // and never reach this test.
    if (preg_match('/\p{L}/u', preg_replace('/[A-Za-z]/', '', $latin) ?? '')) {
        return null;
    }

    $dictionary = nepali_name_words();
    $out = [];

    // Split on whitespace, keeping the separators out of the way; a name is
    // one or more words and nothing here needs to know which is which.
    foreach (preg_split('/\s+/u', $latin) ?: [] as $word) {
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
        $word = devanagari_word($core, $dictionary);
        // The trailing mark goes back unless the entry already ends in that
        // same mark — K.C. carries its own stop and must not get two. Asking
        // only whether the entry ended in any punctuation swallowed whatever
        // had actually followed the name: "(Kc)" lost its closing bracket and
        // "Kc," the comma dividing surname from given name.
        if ($after !== '' && $word !== '' && mb_substr($word, -1) === mb_substr($after, 0, 1)) {
            $after = mb_substr($after, 1);
        }
        $out[] = $before . $word . $after;
    }

    $name = trim(implode(' ', $out));
    return $name === '' ? null : $name;
}
