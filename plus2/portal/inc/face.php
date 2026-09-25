<?php
declare(strict_types=1);

/**
 * Where the face is in a photograph — well enough to centre the card's round
 * frame on it.
 *
 * Students upload whatever photograph they have: a selfie with the face to
 * one side, a picture taken at a function with the school building behind, a
 * passport photo. Cutting the middle square out of those misses the face as
 * often as it finds it, and a card whose photo is somebody's shoulder is no
 * identity card.
 *
 * Colour alone cannot find a face — a dry playing field, a yellow wall and a
 * wooden door are all the colour of skin — so this is a real face detector:
 * the one OpenCV ships, a cascade of local-binary-pattern tests trained on
 * thousands of faces (the model is in face-cascade.php, with its licence).
 * It needs nothing but PHP and GD, so it runs on the shared host as it is.
 *
 * How it works, in the form OpenCV uses. A window the size the model was
 * trained on (45 px) is slid over the picture, and the picture is shrunk a
 * little at a time so that larger faces come down to the window's size.
 * Every window is put through the cascade's stages in order; each stage is a
 * handful of cheap tests on how a 3 × 3 grid of patches compare in brightness,
 * and a window that fails one stage is dropped there — which is nearly every
 * window, after two or three tests, and why it is quick. A real face passes
 * all nineteen stages in several neighbouring windows at once, so only a
 * cluster of hits counts as a face; a lone hit is a coincidence.
 *
 * When there is no face to find — a photograph of something else, a face in
 * profile, one too small to matter — the answer is null and the caller keeps
 * its own rule. Being unsure costs nothing; being wrong would put the frame
 * on a door.
 */

/** The copy faces are first looked for on: faces down to 45/360 of it. */
const FACE_SEARCH_SIDE = 360;
/** How much smaller each pass makes the picture. */
const FACE_SCALE_STEP = 1.1;
/** Windows that must agree before a face is believed — OpenCV's minNeighbors. */
const FACE_MIN_NEIGHBOURS = 3;
/** How far apart two hits may be and still be the same face — OpenCV's eps. */
const FACE_GROUP_EPS = 0.2;
/** How far the head's middle is above the features', as a share of the face's size. */
const FACE_HEAD_RISE = 0.12;
/** The furthest the head's middle is taken to be from the features', across. */
const FACE_HEAD_SHIFT = 0.4;
/** The least share of a face's middle that must be skin-coloured. */
const FACE_MIN_SKIN = 0.6;

/**
 * The most prominent face as [centre x, centre y, size] in the picture's own
 * pixels — size being the side of the square the detector draws round it, from
 * the brows to the chin — or null when there is none.
 *
 * @return array{0: float, 1: float, 2: float}|null
 */
function find_face(GdImage $src): ?array
{
    // Faces down to about an eighth of the picture first. Only when that
    // finds none is the picture searched again at twice the detail, and then
    // only for the small faces the first pass could not see — a photograph
    // taken at a function, with the student some way off.
    $found = face_search($src, FACE_SEARCH_SIDE, INF);
    // A picture not much bigger than the first search's copy has no finer
    // detail to give a second one.
    if ($found === null && max(imagesx($src), imagesy($src)) > FACE_SEARCH_SIDE * 1.5) {
        $found = face_search($src, FACE_SEARCH_SIDE * 2, 2.3);
    }
    return $found;
}

/**
 * One search: the picture brought to $side on its longer side, then shrunk a
 * step at a time until it is no bigger than the window — or until it has been
 * shrunk by $maxShrink, when a coarser search has already covered the rest.
 *
 * @return array{0: float, 1: float, 2: float}|null
 */
function face_search(GdImage $src, int $side, float $maxShrink): ?array
{
    static $model = null;
    $model ??= require __DIR__ . '/face-cascade.php';
    $win = (int) $model['width'];

    $sw    = imagesx($src);
    $sh    = imagesy($src);
    $scale = min(1.0, $side / max($sw, $sh));
    $bw    = max(1, (int) round($sw * $scale));
    $bh    = max(1, (int) round($sh * $scale));
    if (min($bw, $bh) < $win) {
        return null;
    }
    $base = imagecreatetruecolor($bw, $bh);
    imagefill($base, 0, 0, imagecolorallocate($base, 255, 255, 255));
    imagecopyresampled($base, $src, 0, 0, 0, 0, $bw, $bh, $sw, $sh);

    $hits = [];
    for ($f = 1.0; $bw / $f >= $win && $bh / $f >= $win && $f <= $maxShrink; $f *= FACE_SCALE_STEP) {
        $w = (int) round($bw / $f);
        $h = (int) round($bh / $f);
        $I = face_integral($base, $w, $h);
        $W = $w + 1;

        // Each feature's sixteen grid corners, as offsets into the integral
        // image, worked out once per pass rather than once per window.
        $ofs = [];
        foreach ($model['features'] as $k => [$fx, $fy, $fw, $fh]) {
            $o = [];
            for ($r = 0; $r < 4; $r++) {
                for ($c = 0; $c < 4; $c++) {
                    $o[] = ($fy + $r * $fh) * $W + $fx + $c * $fw;
                }
            }
            $ofs[$k] = $o;
        }

        $step = $f > 2 ? 1 : 2;
        for ($y = 0; $y + $win <= $h; $y += $step) {
            for ($x = 0; $x + $win <= $w; $x += $step) {
                if (face_window_passes($I, $y * $W + $x, $ofs, $model['stages'])) {
                    $hits[] = [$x * $f, $y * $f, $win * $f];
                }
            }
        }
    }

    // A face has skin in the middle of it. The cascade reads only light and
    // dark, and a row of windows in a building can fool it; colour cannot.
    $faces = [];
    foreach (face_group($hits) as $face) {
        $skin = face_skin_share($base, $face[0], $face[1], $face[2]);
        if ($skin >= FACE_MIN_SKIN) {
            $faces[] = $face;
        }
    }
    imagedestroy($base);
    if (!$faces) {
        return null;
    }
    // The largest face is the person the photograph is of; one behind them
    // is smaller.
    usort($faces, fn($a, $b) => $b[2] <=> $a[2]);
    [$x, $y, $s] = $faces[0];
    return [($x + $s / 2) / $scale, ($y + $s / 2) / $scale, $s / $scale];
}

/**
 * How much of the middle of a square is skin-coloured. Not a way to find a
 * face — see the top of this file — but a way to turn down a "face" the
 * cascade has seen in the windows of a building.
 */
function face_skin_share(GdImage $im, float $x, float $y, float $s): float
{
    $x1 = (int) max(0, round($x + $s * 0.25));
    $x2 = (int) min(imagesx($im) - 1, round($x + $s * 0.75));
    $y1 = (int) max(0, round($y + $s * 0.3));
    $y2 = (int) min(imagesy($im) - 1, round($y + $s * 0.8));
    $skin = $all = 0;
    for ($py = $y1; $py <= $y2; $py++) {
        for ($px = $x1; $px <= $x2; $px++) {
            $skin += face_skin_colour(imagecolorat($im, $px, $py)) ? 1 : 0;
            $all++;
        }
    }
    return $all ? $skin / $all : 0.0;
}

/**
 * Whether a colour could be skin. Wide on purpose: it rules out what is
 * plainly not skin, and a face under a yellow lamp or in blue shade must
 * still pass.
 */
function face_skin_colour(int $c): bool
{
    $r = ($c >> 16) & 255; $g = ($c >> 8) & 255; $b = $c & 255;
    $cb = 128 - 0.168736 * $r - 0.331264 * $g + 0.5 * $b;
    $cr = 128 + 0.5 * $r - 0.418688 * $g - 0.081312 * $b;
    return $r > $b && $cb >= 70 && $cb <= 135 && $cr >= 130 && $cr <= 195;
}

/**
 * The centre of the head, from the face the detector found — [x, y] in the
 * picture's own pixels.
 *
 * The detector's square sits on the features, brow to mouth. The head is more
 * than that: hair above, chin below, and on a face turned even a little, a
 * cheek and an ear on one side that the other side does not show. Centring the
 * frame on the features puts the head visibly off-centre in the circle — to one
 * side on a turned face, and high on every face, the hair running into the rim.
 *
 * Up and down, the head's middle is a known distance above the features' (hair
 * is taller than chin is deep), so it is moved by that. Across, the head is
 * measured, two ways, and the first that can be trusted is used:
 *
 *   - its skin: along the rows at the level of the eyes and nose, followed out
 *     from the middle of the face to where it ends on each side — the far
 *     cheek on one, the ear on the other;
 *   - its outline: along the rows just above the forehead, where the head is
 *     hair, followed out until the colour turns back into the background
 *     found at the far ends of the same row. This is what works in front of
 *     a beige wall or a wooden door, where the background is itself
 *     skin-coloured and the first measurement cannot see where the face ends.
 *
 * A measurement that runs on to the edge of the search on either side has met
 * a background it cannot tell from the head, and means nothing. When neither
 * can be trusted, the features' middle is kept.
 */
function face_head_centre(GdImage $src, float $cx, float $cy, float $s): array
{
    $hy = $cy - FACE_HEAD_RISE * $s;

    // A small copy of the head and the space either side of it: from above
    // the hair to below the nose, and wide enough for a head turned so far
    // that the features sit well to one side of it. Whole pixels, so the
    // copy's columns map back onto the picture exactly.
    $reach = 1.5 * $s;
    $sw = imagesx($src);
    $x0 = (int) max(0, floor($cx - $reach));
    $x1 = (int) min($sw, ceil($cx + $reach));
    $y0 = (int) max(0, floor($cy - 1.0 * $s));
    $y1 = (int) min(imagesy($src), ceil($cy + 0.3 * $s));
    if ($x1 - $x0 < 8 || $y1 - $y0 < 8) {
        return [$cx, $hy];
    }
    $k = min(1.0, 160 / ($x1 - $x0));                  // 160 px across is plenty
    $w = max(8, (int) round(($x1 - $x0) * $k));
    $h = max(8, (int) round(($y1 - $y0) * $k));
    $kx = $w / ($x1 - $x0);                            // the scale actually used
    $ky = $h / ($y1 - $y0);
    $im = imagecreatetruecolor($w, $h);
    imagecopyresampled($im, $src, 0, 0, $x0, $y0, $w, $h, $x1 - $x0, $y1 - $y0);

    $mid = (int) round(($cx - $x0) * $kx);
    $row = fn(float $y): int => (int) round(($y - $y0) * $ky);   // picture y -> copy row
    $sk  = $s * $kx;

    // First, the skin at the eyes and nose.
    $hx = face_measure_across($im, $w, $h, max(0, $row($cy - 0.15 * $s)), min($h - 1, $row($cy + 0.3 * $s)),
        $mid, fn(int $c, int $y): bool => face_skin_colour($c), $sk);

    // Then the outline at hair level, against the background — read from the
    // strips at the far ends of each row, but only where the copy stops short
    // of the picture's own edge. Where it runs to the edge, that strip may be
    // the subject's hair or shoulder rather than anything behind them.
    $leftOk  = $cx - $reach > 0;
    $rightOk = $cx + $reach < $sw;
    if ($hx === null && ($leftOk || $rightOk)) {
        $from = max(0, $row($cy - 0.95 * $s));
        $to   = min($h - 1, $row($cy - 0.55 * $s));
        $lum  = fn(int $c): float => 0.299 * (($c >> 16) & 255) + 0.587 * (($c >> 8) & 255) + 0.114 * ($c & 255);
        $edge = max(2, (int) round($w * 0.08));
        $background = [];
        for ($y = $from; $y <= $to; $y++) {
            $background[$y] = [];
            foreach ([[$leftOk, 0, $edge], [$rightOk, $w - $edge, $w]] as [$ok, $a, $b]) {
                if (!$ok) {
                    continue;
                }
                $px = [];
                for ($x = $a; $x < $b; $x++) {
                    $px[] = imagecolorat($im, $x, $y);
                }
                usort($px, fn($p, $q) => $lum($p) <=> $lum($q));
                $background[$y][] = $px[intdiv(count($px), 2)];
            }
        }
        $near = function (int $c, int $d): bool {
            $dr = (($c >> 16) & 255) - (($d >> 16) & 255);
            $dg = (($c >> 8) & 255) - (($d >> 8) & 255);
            $db = ($c & 255) - ($d & 255);
            return $dr * $dr + $dg * $dg + $db * $db < 45 * 45;
        };
        $hx = face_measure_across($im, $w, $h, $from, $to, $mid, function (int $c, int $y) use ($background, $near): bool {
            foreach ($background[$y] as $bg) {
                if ($near($c, $bg)) {
                    return false;
                }
            }
            return true;
        }, $sk);
    }
    if ($hx !== null) {
        $hx = $x0 + $hx / $kx;
    }
    imagedestroy($im);
    if ($hx === null) {
        return [$cx, $hy];
    }
    // Never further than a head's turn could account for.
    return [max($cx - FACE_HEAD_SHIFT * $s, min($cx + FACE_HEAD_SHIFT * $s, $hx)), $hy];
}

/**
 * The middle, across, of whatever $is($colour, $row) picks out on rows $from to $to, followed
 * out from column $mid to where it ends on each side — or null when it cannot
 * be trusted: too few rows found both ends, it ran on to the edge of the copy
 * (the background is the same kind of thing), or it is too narrow to be a
 * head. $s is the face's size in the copy's pixels.
 */
function face_measure_across(GdImage $im, int $w, int $h, int $from, int $to, int $mid, callable $is, float $s): ?float
{
    $gap  = max(2, (int) round(0.08 * $s));          // this much of anything else ends it
    $seed = (int) round(0.25 * $s);                   // how far from the middle it may start
    $lefts = $rights = [];
    $rows = 0;
    for ($y = $from; $y <= $to; $y++) {
        $rows++;
        // Where it is nearest the middle, on this row.
        $start = null;
        for ($d = 0; $d <= $seed && $start === null; $d++) {
            foreach ([$mid - $d, $mid + $d] as $x) {
                if ($x >= 0 && $x < $w && $is(imagecolorat($im, $x, $y), $y)) {
                    $start = $x;
                    break;
                }
            }
        }
        if ($start === null) {
            continue;
        }
        $ends = [];
        foreach ([-1, 1] as $dir) {
            $edge = $start;
            $miss = 0;
            for ($x = $start; $x >= 0 && $x < $w; $x += $dir) {
                if ($is(imagecolorat($im, $x, $y), $y)) {
                    $miss = 0;
                    $edge = $x;
                } elseif (++$miss >= $gap) {
                    break;
                }
            }
            $ends[] = $miss >= $gap ? $edge : null;   // null: ran off the copy
        }
        if ($ends[0] !== null && $ends[1] !== null) {
            $lefts[]  = $ends[0];
            $rights[] = $ends[1];
        }
    }
    if ($rows === 0 || count($lefts) < max(3, $rows * 0.4)) {
        return null;
    }
    // Towards the outer ends, not the middle: on the rows where the head is
    // lit, or its hair close in colour to the wall, the run stops short of
    // the head's edge, and the rows that do reach it are the ones that say
    // where it is. A quarter of the way in, so one row that leaks into the
    // background cannot decide it alone.
    sort($lefts);
    sort($rights);
    $l = $lefts[(int) floor((count($lefts) - 1) * 0.25)];
    $r = $rights[(int) ceil((count($rights) - 1) * 0.75)];
    if ($r - $l < 0.6 * $s) {
        return null;
    }
    return ($l + $r) / 2;
}

/** The summed-area table of the picture's brightness at w × h, row width w + 1. */
function face_integral(GdImage $base, int $w, int $h): array
{
    $im = imagecreatetruecolor($w, $h);
    imagecopyresampled($im, $base, 0, 0, 0, 0, $w, $h, imagesx($base), imagesy($base));
    $W = $w + 1;
    $I = array_fill(0, $W * ($h + 1), 0);
    for ($y = 0; $y < $h; $y++) {
        $run  = 0;
        $row  = ($y + 1) * $W;
        $prev = $y * $W;
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($im, $x, $y);
            // The same weights OpenCV's grey conversion uses, in integers.
            $run += (((($c >> 16) & 255) * 4899) + ((($c >> 8) & 255) * 9617) + (($c & 255) * 1868) + 8192) >> 14;
            $I[$row + $x + 1] = $I[$prev + $x + 1] + $run;
        }
    }
    imagedestroy($im);
    return $I;
}

/**
 * Whether the window whose top-left corner is at offset $p in the integral
 * image passes every stage of the cascade.
 *
 * Each test reads a 3 × 3 grid of patches and forms an eight-bit code from
 * which of the outer eight are at least as bright as the middle one — the
 * local binary pattern — then looks the code up in the test's table.
 */
function face_window_passes(array $I, int $p, array $ofs, array $stages): bool
{
    foreach ($stages as [$threshold, $tests]) {
        $sum = 0.0;
        foreach ($tests as [$feature, $mask, $in, $out]) {
            $o = $ofs[$feature];
            $a0 = $I[$p + $o[0]];  $a1 = $I[$p + $o[1]];  $a2 = $I[$p + $o[2]];  $a3 = $I[$p + $o[3]];
            $b0 = $I[$p + $o[4]];  $b1 = $I[$p + $o[5]];  $b2 = $I[$p + $o[6]];  $b3 = $I[$p + $o[7]];
            $c0 = $I[$p + $o[8]];  $c1 = $I[$p + $o[9]];  $c2 = $I[$p + $o[10]]; $c3 = $I[$p + $o[11]];
            $d0 = $I[$p + $o[12]]; $d1 = $I[$p + $o[13]]; $d2 = $I[$p + $o[14]]; $d3 = $I[$p + $o[15]];
            $mid = $b1 - $b2 - $c1 + $c2;
            $code = (($a0 - $a1 - $b0 + $b1) >= $mid ? 128 : 0)     // top left
                  | (($a1 - $a2 - $b1 + $b2) >= $mid ? 64 : 0)      // top
                  | (($a2 - $a3 - $b2 + $b3) >= $mid ? 32 : 0)      // top right
                  | (($b2 - $b3 - $c2 + $c3) >= $mid ? 16 : 0)      // right
                  | (($c2 - $c3 - $d2 + $d3) >= $mid ? 8 : 0)       // bottom right
                  | (($c1 - $c2 - $d1 + $d2) >= $mid ? 4 : 0)       // bottom
                  | (($c0 - $c1 - $d0 + $d1) >= $mid ? 2 : 0)       // bottom left
                  | (($b0 - $b1 - $c0 + $c1) >= $mid ? 1 : 0);      // left
            $sum += ($mask[$code >> 5] & (1 << ($code & 31))) ? $in : $out;
        }
        if ($sum < $threshold) {
            return false;
        }
    }
    return true;
}

/**
 * Hits that overlap closely are the same face; clusters with enough of them
 * become one face each, averaged. The rule OpenCV's groupRectangles applies.
 *
 * @param list<array{0: float, 1: float, 2: float}> $hits  [x, y, size]
 * @return list<array{0: float, 1: float, 2: float}>
 */
function face_group(array $hits): array
{
    $n = count($hits);
    $parent = range(0, max(0, $n - 1));
    $root = function (int $i) use (&$parent): int {
        while ($parent[$i] !== $i) {
            $parent[$i] = $parent[$parent[$i]];
            $i = $parent[$i];
        }
        return $i;
    };
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            [$x1, $y1, $s1] = $hits[$i];
            [$x2, $y2, $s2] = $hits[$j];
            $d = FACE_GROUP_EPS * min($s1, $s2);
            if (abs($x1 - $x2) <= $d && abs($y1 - $y2) <= $d
                && abs($x1 + $s1 - $x2 - $s2) <= $d && abs($y1 + $s1 - $y2 - $s2) <= $d) {
                $parent[$root($i)] = $root($j);
            }
        }
    }
    $groups = [];
    foreach ($hits as $i => $h) {
        $groups[$root($i)][] = $h;
    }
    $faces = [];
    foreach ($groups as $g) {
        if (count($g) <= FACE_MIN_NEIGHBOURS) {
            continue;
        }
        $k = count($g);
        $faces[] = [
            array_sum(array_column($g, 0)) / $k,
            array_sum(array_column($g, 1)) / $k,
            array_sum(array_column($g, 2)) / $k,
            $k,
        ];
    }
    return $faces;
}
