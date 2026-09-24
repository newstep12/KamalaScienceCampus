<?php
declare(strict_types=1);

/**
 * Lifting a signature off the photograph it arrived in.
 *
 * What people upload is a phone photograph of a page, not a scan: a sheet of
 * paper lit unevenly, the desk around it, a thumb holding it up, the torn
 * edge of the sheet, a shadow across one corner, and somewhere in the middle
 * a few thin lines of ink. What the card needs is those lines and nothing
 * else — ink on a transparent ground, trimmed to the writing.
 *
 * Judging each pixel against a fixed level cannot do it (a shadowed page is
 * darker than a lit desk), and judging it only against the paper around it
 * is not enough either: the edge of the sheet, the edge of the thumb and the
 * rim of a shadow are all "darker than the paper beside them", exactly as a
 * stroke is. So this works in three steps, each ruling out what the one
 * before could not tell apart from ink.
 *
 *   1. Find the sheet. The picture is read as a grid of small cells, each
 *      holding the lightest colour in it — the paper's, wherever there is
 *      any paper in the cell. The sheet is the largest connected run of
 *      cells that are light and close to grey; a desk is dark, a thumb or a
 *      coloured desk is not grey. Anything enclosed by the sheet (the cells
 *      a thick stroke fills) belongs to it, and a cell's width is trimmed off
 *      its rim, so the sheet's own edge never reads as writing.
 *
 *   2. Mark the ink. Inside the sheet, a pixel is ink by how much darker it
 *      is than the paper around it — the grid, smoothed between cells — so a
 *      signature reads the same in the light and in the shadow of the page.
 *
 *   3. Keep the signature. The ink is split into connected marks. A mark in
 *      the rim of the sheet is the edge of a shadow or of the sheet; a large
 *      solid one is a smudge or a shadow, since pen strokes are thin; a speck
 *      is grain. What is left is gathered round the largest mark, taking in
 *      marks near it (the dots of an initial, a flourish, the underline) and
 *      leaving a stray mark elsewhere on the page behind.
 *
 * Everything is measured on one working copy, at a size fine enough to keep a
 * thin stroke whole and small enough to measure in a request — held as byte
 * strings rather than PHP arrays, so a working copy costs a megabyte, not a
 * hundred.
 */

/** The working copy's longest side. */
const INK_WORK_SIDE    = 1400;
/** Cells across the longest side of the grid the paper is read on. */
const INK_CELLS        = 64;
/** How much lighter than the paper found in it a cell must be to be sheet. */
const INK_SHEET_LIGHT  = 0.55;
/** How far from grey a cell's paper may be — a thumb and a teal desk are not. */
const INK_SHEET_CHROMA = 70;
/** Darker than the local paper by this share: solid ink. */
const INK_DEPTH        = 0.38;
/** Darker than the local paper by less than this share: paper. */
const INK_EDGE         = 0.12;
/** The least opacity counted as part of a mark when marks are traced. */
const INK_SOLID        = 0.35;
/** A mark bigger than this share of the picture and this solid is a smudge. */
const INK_BLOB_AREA    = 0.002;
const INK_BLOB_FILL    = 0.45;
/** Too little ink left to be a signature: a blank page, or not a page at all. */
const INK_MIN_PX       = 40;
/** More separate marks than any signature is made of: a photograph of something else. */
const INK_MAX_MARKS    = 60;
/** The writing must span at least this share of the picture's longer side. */
const INK_MIN_SPAN     = 0.08;
/** Pen strokes cover little of the box round them; a picture covers much. */
const INK_MAX_COVER    = 0.30;
/** Clear space kept round the writing, as a share of its larger side. */
const INK_MARGIN       = 0.05;

/**
 * The signature as ink on a transparent ground, trimmed to the writing — or
 * null when the picture holds nothing that looks like one, in which case the
 * caller stores what arrived.
 *
 * $keepColour false paints the ink black, as a card holder's signature always
 * has been. True keeps the colour it was written in, deepened a little — the
 * school's signature library holds a blue-ink signature as well as a black
 * one, and the colour is the point of having both.
 */
function lift_signature_ink(GdImage $src, bool $keepColour = false): ?GdImage
{
    $work = ink_working_copy($src);
    $w    = imagesx($work);
    $h    = imagesy($work);
    $n    = $w * $h;

    // Pass one: luminance of every pixel, and the lightest colour in each cell.
    $cell = max(4, (int) round(max($w, $h) / INK_CELLS));
    $gw   = (int) ceil($w / $cell);
    $gh   = (int) ceil($h / $cell);
    $lum  = str_repeat("\0", $n);
    $cMax = array_fill(0, $gw * $gh, -1);
    $cRgb = array_fill(0, $gw * $gh, 0);
    for ($y = 0; $y < $h; $y++) {
        $row = (int) ($y / $cell) * $gw;
        $off = $y * $w;
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($work, $x, $y);
            $l = (int) (0.2126 * (($c >> 16) & 255) + 0.7152 * (($c >> 8) & 255) + 0.0722 * ($c & 255));
            $lum[$off + $x] = chr($l);
            $i = $row + (int) ($x / $cell);
            if ($l > $cMax[$i]) {
                $cMax[$i] = $l;
                $cRgb[$i] = $c;
            }
        }
    }

    // Step 1: the sheet, and the part of it far enough from its edge to trust.
    $sorted = $cMax;
    sort($sorted);
    $paper = $sorted[(int) (count($sorted) * 0.95)];
    if ($paper < 60) {
        imagedestroy($work);
        return null;                                   // no paper anywhere
    }
    $light = [];
    foreach ($cMax as $i => $l) {
        $c = $cRgb[$i];
        $r = ($c >> 16) & 255; $g = ($c >> 8) & 255; $b = $c & 255;
        $light[$i] = $l >= $paper * INK_SHEET_LIGHT && (max($r, $g, $b) - min($r, $g, $b)) <= INK_SHEET_CHROMA;
    }
    $sheet    = ink_largest_region($light, $gw, $gh);
    $sheet    = ink_fill_holes($sheet, $gw, $gh);
    $interior = [];
    $rim      = [];
    for ($gy = 0; $gy < $gh; $gy++) {
        for ($gx = 0; $gx < $gw; $gx++) {
            $i = $gy * $gw + $gx;
            $interior[$i] = $sheet[$i] && ink_all_neighbours($sheet, $gw, $gh, $gx, $gy);
        }
    }
    for ($gy = 0; $gy < $gh; $gy++) {
        for ($gx = 0; $gx < $gw; $gx++) {
            $i = $gy * $gw + $gx;
            $rim[$i] = $interior[$i] && !ink_all_neighbours($interior, $gw, $gh, $gx, $gy);
        }
    }

    // The paper's tone in each cell, read over the cells around it, so a cell
    // a thick stroke fills borrows the paper from beside it.
    $bg = array_fill(0, $gw * $gh, 0);
    for ($gy = 0; $gy < $gh; $gy++) {
        for ($gx = 0; $gx < $gw; $gx++) {
            $m = 0;
            for ($dy = -1; $dy <= 1; $dy++) {
                for ($dx = -1; $dx <= 1; $dx++) {
                    $nx = $gx + $dx; $ny = $gy + $dy;
                    if ($nx >= 0 && $nx < $gw && $ny >= 0 && $ny < $gh && $sheet[$ny * $gw + $nx]) {
                        $m = max($m, $cMax[$ny * $gw + $nx]);
                    }
                }
            }
            $bg[$gy * $gw + $gx] = $m;
        }
    }

    // Step 2: how much ink is in every pixel inside the sheet, 0-255.
    $ink = str_repeat("\0", $n);
    for ($y = 0; $y < $h; $y++) {
        $fy  = max(0.0, min($gh - 1.0, ($y + 0.5) / $cell - 0.5));
        $gy0 = (int) $fy; $gy1 = min($gh - 1, $gy0 + 1); $ty = $fy - $gy0;
        $crow = (int) ($y / $cell) * $gw;
        $off  = $y * $w;
        for ($x = 0; $x < $w; $x++) {
            if (!$interior[$crow + (int) ($x / $cell)]) {
                continue;
            }
            $fx  = max(0.0, min($gw - 1.0, ($x + 0.5) / $cell - 0.5));
            $gx0 = (int) $fx; $gx1 = min($gw - 1, $gx0 + 1); $tx = $fx - $gx0;
            $p = ($bg[$gy0 * $gw + $gx0] * (1 - $tx) + $bg[$gy0 * $gw + $gx1] * $tx) * (1 - $ty)
               + ($bg[$gy1 * $gw + $gx0] * (1 - $tx) + $bg[$gy1 * $gw + $gx1] * $tx) * $ty;
            if ($p < 40) {
                continue;
            }
            $l    = ord($lum[$off + $x]);
            $edge = $p * (1 - INK_EDGE);
            if ($l >= $edge) {
                continue;
            }
            $solid = $p * (1 - INK_DEPTH);
            $a     = $l <= $solid ? 1.0 : ($edge - $l) / ($edge - $solid);
            $ink[$off + $x] = chr((int) round(255 * $a));
        }
    }

    // Step 3: trace the marks, and keep the ones that make up the signature.
    $marks = ink_marks($ink, $w, $h, $cell, $gw, $rim);
    $keep  = ink_signature_marks($marks, $w, $h);
    if (!$keep) {
        imagedestroy($work);
        return null;
    }

    $kept = str_repeat("\0", $n);
    $x1 = $w; $y1 = $h; $x2 = -1; $y2 = -1; $total = 0;
    foreach ($keep as $m) {
        foreach ($m['px'] as $p) {
            $kept[$p] = "\1";
        }
        $total += $m['area'];
        $x1 = min($x1, $m['x1']); $y1 = min($y1, $m['y1']);
        $x2 = max($x2, $m['x2']); $y2 = max($y2, $m['y2']);
    }
    // What is left has to look like handwriting: a handful of thin marks
    // spanning a fair part of the page. A photograph of something else —
    // a face, a building, a page of print — leaves hundreds of fragments, or
    // one tiny one, and printing either as a signature would be worse than
    // keeping the picture as it came.
    $span = max($x2 - $x1, $y2 - $y1) + 1;
    $boxArea = ($x2 - $x1 + 1) * ($y2 - $y1 + 1);
    if ($total < INK_MIN_PX || count($keep) > INK_MAX_MARKS
        || $span < INK_MIN_SPAN * max($w, $h) || $total / $boxArea > INK_MAX_COVER) {
        imagedestroy($work);
        return null;
    }
    $margin = (int) ceil(max($x2 - $x1, $y2 - $y1) * INK_MARGIN) + 3;
    $x1 = max(0, $x1 - $margin); $y1 = max(0, $y1 - $margin);
    $x2 = min($w - 1, $x2 + $margin); $y2 = min($h - 1, $y2 + $margin);

    // The colour to paint: black, or the ink's own deepened.
    [$ir, $ig, $ib] = $keepColour ? ink_colour($work, $ink, $kept, $w, $x1, $y1, $x2, $y2) : [0, 0, 0];

    $ow  = $x2 - $x1 + 1;
    $oh  = $y2 - $y1 + 1;
    $out = imagecreatetruecolor($ow, $oh);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefilledrectangle($out, 0, 0, $ow - 1, $oh - 1, imagecolorallocatealpha($out, $ir, $ig, $ib, 127));
    $shade = [];
    for ($y = $y1; $y <= $y2; $y++) {
        for ($x = $x1; $x <= $x2; $x++) {
            $a = ord($ink[$y * $w + $x]);
            if ($a === 0 || !ink_near_kept($kept, $w, $h, $x, $y)) {
                continue;
            }
            // A little firmer than measured: a photographed stroke is paler
            // than the pen made it, and its soft edge prints as a haze.
            $o = min(1.0, 1.15 * (($a / 255) ** 0.8));
            $alpha = (int) round(127 * (1 - $o));
            if ($alpha >= 126) {
                continue;
            }
            $shade[$alpha] ??= imagecolorallocatealpha($out, $ir, $ig, $ib, $alpha);
            imagesetpixel($out, $x - $x1, $y - $y1, $shade[$alpha]);
        }
    }
    imagedestroy($work);
    return $out;
}

/**
 * The picture on white, at the working size. Transparency is composited onto
 * white first, so a cut-out somebody made by hand comes through as ink on
 * paper like everything else. Never enlarged.
 */
function ink_working_copy(GdImage $src): GdImage
{
    $sw = imagesx($src);
    $sh = imagesy($src);
    $scale = min(1.0, INK_WORK_SIDE / max($sw, $sh));
    $w = max(1, (int) round($sw * $scale));
    $h = max(1, (int) round($sh * $scale));
    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, true);
    imagefilledrectangle($out, 0, 0, $w - 1, $h - 1, imagecolorallocate($out, 255, 255, 255));
    imagecopyresampled($out, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);
    return $out;
}

/** The largest 4-connected run of set cells; every other cell unset. */
function ink_largest_region(array $set, int $gw, int $gh): array
{
    $seen = array_fill(0, $gw * $gh, false);
    $best = [];
    foreach ($set as $start => $on) {
        if (!$on || $seen[$start]) {
            continue;
        }
        $region = [];
        $stack  = [$start];
        $seen[$start] = true;
        while ($stack) {
            $i = array_pop($stack);
            $region[] = $i;
            $x = $i % $gw;
            foreach ([$x > 0 ? $i - 1 : -1, $x < $gw - 1 ? $i + 1 : -1, $i - $gw, $i + $gw] as $j) {
                if ($j >= 0 && $j < $gw * $gh && $set[$j] && !$seen[$j]) {
                    $seen[$j] = true;
                    $stack[] = $j;
                }
            }
        }
        if (count($region) > count($best)) {
            $best = $region;
        }
    }
    $out = array_fill(0, $gw * $gh, false);
    foreach ($best as $i) {
        $out[$i] = true;
    }
    return $out;
}

/** Cells the region encloses — the ones no path from the border reaches — join it. */
function ink_fill_holes(array $region, int $gw, int $gh): array
{
    $outside = array_fill(0, $gw * $gh, false);
    $stack   = [];
    for ($gx = 0; $gx < $gw; $gx++) {
        $stack[] = $gx;
        $stack[] = ($gh - 1) * $gw + $gx;
    }
    for ($gy = 0; $gy < $gh; $gy++) {
        $stack[] = $gy * $gw;
        $stack[] = $gy * $gw + $gw - 1;
    }
    while ($stack) {
        $i = array_pop($stack);
        if ($i < 0 || $i >= $gw * $gh || $outside[$i] || $region[$i]) {
            continue;
        }
        $outside[$i] = true;
        $x = $i % $gw;
        if ($x > 0) $stack[] = $i - 1;
        if ($x < $gw - 1) $stack[] = $i + 1;
        $stack[] = $i - $gw;
        $stack[] = $i + $gw;
    }
    $out = [];
    foreach ($region as $i => $on) {
        $out[$i] = $on || !$outside[$i];
    }
    return $out;
}

/**
 * Whether every neighbour of a cell inside the picture is set. The picture's
 * own edge does not count against it: a photograph cropped close to the page
 * has the page running off the frame, and that is not an edge of the sheet.
 */
function ink_all_neighbours(array $set, int $gw, int $gh, int $gx, int $gy): bool
{
    for ($dy = -1; $dy <= 1; $dy++) {
        for ($dx = -1; $dx <= 1; $dx++) {
            $nx = $gx + $dx; $ny = $gy + $dy;
            if ($nx >= 0 && $nx < $gw && $ny >= 0 && $ny < $gh && !$set[$ny * $gw + $nx]) {
                return false;
            }
        }
    }
    return true;
}

/**
 * The connected marks of ink, 8-connected, each with its pixels, box, area and
 * whether most of it lies in the rim of the sheet.
 *
 * @return list<array{px: list<int>, area: int, x1: int, y1: int, x2: int, y2: int, rim: bool}>
 */
function ink_marks(string $ink, int $w, int $h, int $cell, int $gw, array $rim): array
{
    $solid = (int) round(255 * INK_SOLID);
    $seen  = str_repeat("\0", $w * $h);
    $marks = [];
    $n     = $w * $h;
    for ($start = 0; $start < $n; $start++) {
        if ($seen[$start] !== "\0" || ord($ink[$start]) < $solid) {
            continue;
        }
        $px    = [];
        $stack = [$start];
        $seen[$start] = "\1";
        $x1 = $w; $y1 = $h; $x2 = -1; $y2 = -1; $onRim = 0;
        while ($stack) {
            $i = array_pop($stack);
            $px[] = $i;
            $x = $i % $w;
            $y = intdiv($i, $w);
            if ($x < $x1) $x1 = $x;
            if ($x > $x2) $x2 = $x;
            if ($y < $y1) $y1 = $y;
            if ($y > $y2) $y2 = $y;
            if ($rim[(int) ($y / $cell) * $gw + (int) ($x / $cell)]) {
                $onRim++;
            }
            for ($dy = -1; $dy <= 1; $dy++) {
                $ny = $y + $dy;
                if ($ny < 0 || $ny >= $h) {
                    continue;
                }
                for ($dx = -1; $dx <= 1; $dx++) {
                    $nx = $x + $dx;
                    if ($nx < 0 || $nx >= $w) {
                        continue;
                    }
                    $j = $ny * $w + $nx;
                    if ($seen[$j] === "\0" && ord($ink[$j]) >= $solid) {
                        $seen[$j] = "\1";
                        $stack[]  = $j;
                    }
                }
            }
        }
        // Mostly in the rim: the edge of the sheet, or of a shadow or a thumb
        // on it. A stroke that merely passes near one is mostly elsewhere.
        $marks[] = ['px' => $px, 'area' => count($px), 'x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2,
                    'rim' => $onRim > 0.5 * count($px)];
    }
    return $marks;
}

/**
 * Which marks are the signature: not in the rim, not a smudge, not a speck,
 * and gathered round the largest mark that is left.
 */
function ink_signature_marks(array $marks, int $w, int $h): array
{
    $n      = $w * $h;
    $speck  = max(6, (int) round($n * 0.000006));
    $blob   = $n * INK_BLOB_AREA;
    $usable = [];
    foreach ($marks as $m) {
        $boxArea = ($m['x2'] - $m['x1'] + 1) * ($m['y2'] - $m['y1'] + 1);
        if ($m['rim'] || $m['area'] < $speck || ($m['area'] > $blob && $m['area'] / $boxArea > INK_BLOB_FILL)) {
            continue;
        }
        $usable[] = $m;
    }
    if (!$usable) {
        return [];
    }
    usort($usable, fn($a, $b) => $b['area'] <=> $a['area']);

    $keep = [array_shift($usable)];
    $box  = [$keep[0]['x1'], $keep[0]['y1'], $keep[0]['x2'], $keep[0]['y2']];
    do {
        $grown = false;
        $reach = 0.10 * max($box[2] - $box[0], $box[3] - $box[1]) + 0.01 * max($w, $h);
        foreach ($usable as $k => $m) {
            $gapX = max(0, $m['x1'] - $box[2], $box[0] - $m['x2']);
            $gapY = max(0, $m['y1'] - $box[3], $box[1] - $m['y2']);
            if (max($gapX, $gapY) <= $reach) {
                $keep[] = $m;
                $box = [min($box[0], $m['x1']), min($box[1], $m['y1']), max($box[2], $m['x2']), max($box[3], $m['y2'])];
                unset($usable[$k]);
                $grown = true;
            }
        }
    } while ($grown);
    return $keep;
}

/** Whether a kept mark lies within two pixels — which takes in a stroke's soft edge. */
function ink_near_kept(string $kept, int $w, int $h, int $x, int $y): bool
{
    for ($dy = -2; $dy <= 2; $dy++) {
        $ny = $y + $dy;
        if ($ny < 0 || $ny >= $h) {
            continue;
        }
        for ($dx = -2; $dx <= 2; $dx++) {
            $nx = $x + $dx;
            if ($nx >= 0 && $nx < $w && $kept[$ny * $w + $nx] !== "\0") {
                return true;
            }
        }
    }
    return false;
}

/**
 * The colour the signature was written in, from its darkest pixels, deepened
 * to a pen's full strength. Black ink stays black; blue ink comes out a clear
 * dark blue rather than the grey-blue a photograph makes of it.
 *
 * @return array{0:int,1:int,2:int}
 */
function ink_colour(GdImage $work, string $ink, string $kept, int $w, int $x1, int $y1, int $x2, int $y2): array
{
    $r = $g = $b = $n = 0;
    for ($y = $y1; $y <= $y2; $y++) {
        for ($x = $x1; $x <= $x2; $x++) {
            $i = $y * $w + $x;
            if ($kept[$i] === "\0" || ord($ink[$i]) < 230) {
                continue;
            }
            $c = imagecolorat($work, $x, $y);
            $r += ($c >> 16) & 255; $g += ($c >> 8) & 255; $b += $c & 255;
            $n++;
        }
    }
    if ($n === 0) {
        return [0, 0, 0];
    }
    $r /= $n; $g /= $n; $b /= $n;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    if ($max - $min < 25) {
        return [0, 0, 0];                                  // grey: black ink
    }
    // Keep the hue, darken to a pen's depth, and make it a little more vivid.
    $scale = 95 / max(1.0, $max);
    $mid   = ($r + $g + $b) / 3;
    $vivid = fn(float $v): int => (int) max(0, min(255, round(($mid + ($v - $mid) * 1.5) * $scale)));
    return [$vivid($r), $vivid($g), $vivid($b)];
}
