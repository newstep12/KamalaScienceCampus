<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/uploads.php';

/**
 * Photographs, made ready for the identity card as they are uploaded.
 *
 * A card has one photo frame, a circle, and whatever arrives has to end up in
 * it. Leaving that to the browser meant
 * three things went wrong quietly: a photo taken on a phone printed on its
 * side, because the rotation lives in an EXIF tag the CSS knows nothing about;
 * a wide holiday snap was centre-cropped to a strip of shoulder; and a five
 * megabyte original was sent down the wire every time anyone opened a card.
 *
 * So the photo is cropped, turned the right way up and re-encoded once, at
 * upload, and what is stored is the picture the card prints. Nothing else in
 * the portal has to think about it afterwards, and the card needs no crop of
 * its own.
 */

/**
 * The card's photo frame — .idc-photo is `aspect-ratio: 1` and round.
 *
 * This has to be the frame's ratio and not a passport photo's, because the
 * point of cropping at upload is that the card prints what was stored. Store
 * 25 × 32 for a round frame and the card crops it a second time at render,
 * silently, on top of the crop done here: two head-placement heuristics
 * stacked, and is_card_shaped() certifying photographs as matching a frame
 * they no longer match.
 */
const CARD_PHOTO_RATIO = 1.0;

/**
 * The stored size. The frame prints about 18 mm across, so 600 px is roughly
 * 850 dpi — more than any campus printer resolves, and small enough that a
 * card page stays quick to open.
 */
const CARD_PHOTO_WIDTH   = 600;
const CARD_PHOTO_QUALITY = 88;

/**
 * Ceilings on what is worth decoding. The first stops a picture whose only
 * purpose is to exhaust the machine; the second is the more careful figure
 * used when memory_limit is unset or unlimited and there is nothing at all to
 * measure against. Both are absolute: an ordinary phone photo, eight to
 * twelve megapixels, is far below either.
 */
const CARD_PHOTO_MAX_PIXELS       = 50000000;
const CARD_PHOTO_UNMETERED_PIXELS = 30000000;

/**
 * Where the crop sits when a photo is taller than the frame — which, the
 * frame being square, is nearly every photograph anyone uploads.
 *
 * Almost all of the excess comes off the bottom, and only a sliver off the
 * top. Not the middle, because people stand in the middle of their own
 * photographs, which puts the head in the upper third; centring the crop on a
 * full-length photo takes the top of the head off and keeps the knees.
 *
 * But not a quarter off the top either, which is what this used to take. The
 * frame is a CIRCLE inscribed in this square, and a circle meets the square
 * only at the middle of each edge: a head spanning the middle half of the
 * frame has its corners a quarter of the width out, where the circle has
 * already come down 6.7% of the height. So a head needs about a fifteenth of
 * the frame clear above it or the ring cuts into it, however comfortably it
 * sits inside the square — and a quarter of the excess is more than that on
 * every ordinary phone photo. On a 3 × 4 it took 6% off the top; on a 9 × 16
 * full-length shot it took 11%, which put the top of the head level with the
 * edge of the crop.
 *
 * A photograph arrives with whatever headroom the person framing it left. The
 * crop's job is not to spend it.
 *
 * A twentieth rather than some finer figure because this is also where the
 * portfolio's slider starts, and that slider moves in steps of five. A default
 * off the steps is snapped to the nearest one by the browser, so an ordinary
 * save of the profile would post a placement that differs from the stored one
 * and quietly re-cut a photograph nobody had asked to move.
 */
const CARD_PHOTO_TOP_BIAS = 0.05;

/**
 * Where the frame sits on the photograph, as a percentage of the excess taken
 * off the top: 0 keeps the very top of the picture, 100 keeps the very bottom,
 * and the default is CARD_PHOTO_TOP_BIAS.
 *
 * It is a per-person setting because no rule can do this job properly. The
 * crop above knows the shape of a photograph and nothing about what is in it:
 * where somebody's head actually sits is a fact about the picture, not about
 * its proportions, and the only reliable reader of it is the person whose face
 * it is. So the rule picks a sound starting point and the holder moves it.
 */
function card_photo_focus(?int $percent): int
{
    if ($percent === null) {
        return (int) round(CARD_PHOTO_TOP_BIAS * 100);
    }
    return max(0, min(100, $percent));
}

/**
 * The working copy kept beside the card photograph: the picture as it was
 * uploaded, turned the right way up and scaled down, printed on nothing.
 *
 * It exists because the crop is a decision, and a decision that cannot be
 * revisited is a decision made once and for ever. Until now the original was
 * thrown away the moment it was cropped, so the crop could never be moved, and
 * when the card's frame changed shape everyone's photograph had to be squeezed
 * into the new one from the old crop rather than re-cut from the picture. The
 * copy costs a couple of hundred kilobytes and buys back both.
 *
 * 1200 px on the long side is far more than the 600 px square the card needs,
 * and small enough that keeping one per person is not a burden on a shared
 * plan.
 */
const CARD_PHOTO_SOURCE_SIDE    = 1200;
const CARD_PHOTO_SOURCE_QUALITY = 82;
const CARD_PHOTO_SOURCE_DIR     = 'photo-source';

/**
 * Validate, crop and store one uploaded photograph, and keep the working copy
 * it was cropped from.
 *
 * Same contract as store_image() with one key added: ['ok' => true, 'path',
 * 'source', 'width', 'height'], or ['ok' => false, 'error'] with 'upload',
 * 'type' or 'small'. 'source' is the working copy's path, or null when there
 * is none — in which case the crop cannot be moved afterwards.
 *
 * If the image cannot be processed — GD missing, a picture too large to hold
 * in memory, a decoder that refuses it — the original is stored as it is
 * rather than the upload failing. The card still fits it to the frame with
 * object-fit; the person just does not get the tidier version, and has no
 * working copy to move it with.
 */
function store_card_photo(array $file, string $subdir = 'photos'): array
{
    // The same rules store_image() applies, because it is the same function:
    // the minimum side is measured on what was uploaded, not on what is left
    // after the frame takes its cut.
    $check = validate_image_upload($file, MIN_PHOTO_SIDE);
    if (!$check['ok']) {
        return $check;
    }

    $src = image_can_be_processed($check['width'], $check['height'])
        ? load_photo($file['tmp_name'], $check['mime'])
        : null;
    if (!$src) {
        $plain = store_image($file, $subdir, MIN_PHOTO_SIDE);
        $plain['source'] = null;
        return $plain;
    }

    // Written first, from the same decode, and already turned the right way
    // up — so moving the crop later never has to read an EXIF tag again. A
    // failure here is not a failed upload: it costs the placement control,
    // not the photograph.
    $source = store_photo_source($src);

    $card = crop_to_card_frame($src);
    imagedestroy($src);

    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        imagedestroy($card);
        // Nothing will ever name the working copy if the photograph itself
        // cannot be stored.
        delete_upload($source);
        return ['ok' => false, 'error' => 'upload'];
    }

    $stored = bin2hex(random_bytes(16)) . '.jpg';
    $ok     = imagejpeg($card, $dir . '/' . $stored, CARD_PHOTO_QUALITY);
    $w      = imagesx($card);
    $h      = imagesy($card);
    imagedestroy($card);

    if (!$ok) {
        // A failed write can still have left a truncated file, and nothing
        // will ever point at it. Out of the way before the fallback stores
        // the original under a different name — and the working copy goes
        // with it, since nothing will ever name that either.
        @unlink($dir . '/' . $stored);
        delete_upload($source);
        $plain = store_image($file, $subdir, MIN_PHOTO_SIDE);
        $plain['source'] = null;
        return $plain;
    }
    @chmod($dir . '/' . $stored, 0644);

    return [
        'ok'     => true,
        'path'   => $subdir . '/' . $stored,
        'source' => $source,
        'width'  => $w,
        'height' => $h,
    ];
}

/**
 * Write the working copy and return its path, or null if it could not be
 * written. Never fails an upload: a photograph without one simply cannot have
 * its crop moved afterwards.
 */
function store_photo_source(GdImage $src): ?string
{
    $dir = __DIR__ . '/../uploads/' . CARD_PHOTO_SOURCE_DIR;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return null;
    }
    $copy   = shrink_within($src, CARD_PHOTO_SOURCE_SIDE);
    $stored = bin2hex(random_bytes(16)) . '.jpg';
    $ok     = imagejpeg($copy, $dir . '/' . $stored, CARD_PHOTO_SOURCE_QUALITY);
    imagedestroy($copy);
    if (!$ok) {
        @unlink($dir . '/' . $stored);
        return null;
    }
    @chmod($dir . '/' . $stored, 0644);
    return CARD_PHOTO_SOURCE_DIR . '/' . $stored;
}

/**
 * Cut a card photograph again from a picture already in the uploads directory,
 * writing the result as a new file and returning its relative path — or null
 * when it cannot be done, in which case nothing has changed on disk and what
 * is on the card stays on it.
 *
 * Two callers, one job. The portfolio passes somebody's working copy and the
 * placement they have just chosen; the batch under Admin → System passes a
 * card photograph stored before any of this and no placement at all, to bring
 * it to the frame's shape.
 *
 * What it is given is deliberately left alone. Removing it is the caller's
 * job, and only once the database points at the new file: delete first and a
 * row that fails to update — or a request the host kills in between — names a
 * photograph that no longer exists, which is a good deal worse than an untidy
 * uploads directory.
 */
function recrop_from_source(?string $sourcePath, ?int $focus = null, string $subdir = 'photos'): ?string
{
    $full = resolve_upload($sourcePath);
    if ($full === null || !photo_can_be_cropped($sourcePath)) {
        return null;
    }
    $src = load_photo($full, (string) (new finfo(FILEINFO_MIME_TYPE))->file($full));
    if (!$src) {
        return null;
    }
    $card = crop_to_card_frame($src, $focus);
    imagedestroy($src);

    $dir    = __DIR__ . '/../uploads/' . $subdir;
    $stored = bin2hex(random_bytes(16)) . '.jpg';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        imagedestroy($card);
        return null;
    }
    $ok = imagejpeg($card, $dir . '/' . $stored, CARD_PHOTO_QUALITY);
    imagedestroy($card);
    if (!$ok) {
        @unlink($dir . '/' . $stored);
        return null;
    }
    @chmod($dir . '/' . $stored, 0644);
    return $subdir . '/' . $stored;
}

/**
 * Whether moving the placement would do anything at all.
 *
 * A photograph taller than the frame has excess to take off one end or the
 * other, and the slider moves it. One already square — or wider — has none:
 * the frame takes the whole height whatever the setting says, and telling
 * somebody to drag a control that cannot move their photograph is worse than
 * not offering it. The portfolio says so instead.
 */
function photo_can_be_placed(?string $sourcePath): bool
{
    $full = resolve_upload($sourcePath);
    if ($full === null) {
        return false;
    }
    $size = @getimagesize($full);
    return $size && $size[1] > 0 && ($size[0] / $size[1]) < CARD_PHOTO_RATIO - 0.005;
}

/* ------------------------------------------------------------ signatures -- */

/**
 * The smallest signature worth printing, and the widest worth storing.
 *
 * A signature strip is wide and shallow, so the 200 px a passport photo needs
 * on every side would turn away a perfectly good scan. It prints about 24 mm
 * across, so 900 px is roughly 950 dpi — past anything a campus printer
 * resolves, and small enough that a card page stays quick to open.
 */
const MIN_SIGNATURE_SIDE     = 80;
const SIGNATURE_WIDTH        = 900;
const SIGNATURE_QUALITY      = 88;

/**
 * Lifting a signature off the paper it was written on.
 *
 * What people upload is a photograph of a page: grey-white paper, a shadow
 * across one corner, a desk at the edges, the ink some shade of dark blue or
 * black. Printed on a card as it arrived, that is a rectangle of somebody's
 * desk sitting on the card — which is why the form used to have to ask for "a
 * PNG with a transparent background", a thing most people have no way to
 * produce.
 *
 * The ink is lifted from the paper instead: every pixel becomes black at an
 * opacity taken from how much darker it is than the paper around it, which
 * leaves the greys of the stroke's own edge as partial opacity rather than a
 * staircase.
 *
 * "Than the paper around it" is the whole design. A single cutoff for the
 * whole picture cannot survive a photograph: the shadowed end of a page is
 * darker than the lit end, so any threshold dark enough to clear the shadow
 * loses the ink in the light, and any threshold light enough to keep the ink
 * turns the shadow into a grey haze. So the paper is estimated locally, as a
 * coarse grid of the lightest tone in each part of the picture, and each pixel
 * is judged against its own cell. A dark desk at the edge of the frame has a
 * dark local paper too, so it comes out as background rather than as a solid
 * black bar.
 *
 * The depths below are fractions of that local paper rather than absolute
 * tones, so they hold whether the page photographed white or grey.
 */
const SIGNATURE_ANALYSIS_SIDE = 420;    // the copy the page is measured on
const SIGNATURE_PAPER_CELLS    = 52;    // cells across the longest side
const SIGNATURE_PAPER_BLUR     = 3;     // cells each way the paper is read over
const SIGNATURE_INK_DEPTH     = 0.45;   // this far below local paper: solid ink
const SIGNATURE_INK_EDGE      = 0.10;   // this far below local paper: still paper

/**
 * How dark a part of the picture has to be before it is not paper at all, as a
 * share of the lightest paper in it.
 *
 * This is the desk a page gets photographed on. Its tone cannot be told from
 * ink — judged against the sheet beside it, it printed as a solid black bar
 * down the side of the signature — so it is ruled out by where it is instead,
 * cell by cell.
 *
 * Deliberately low. Paper in deep shadow is still paper and must stay in:
 * ruling it out at three fifths cropped the shaded end of a page away and took
 * a third of the signature with it. And nothing is lost by admitting a desk
 * that is merely mid-toned, because the paper found there is then its own
 * tone, against which nothing on it reads as ink.
 */
const SIGNATURE_SHEET_FLOOR   = 0.45;

/**
 * The least ink worth treating as a signature, counted on the analysis copy.
 * Below this the picture is a blank page, or a photograph of something that is
 * not a signature at all, and thresholding it would hand back a smear. Such an
 * image is stored exactly as it arrived instead.
 */
const SIGNATURE_MIN_INK_PX    = 24;

/** Kept clear around the ink when the paper around it is trimmed away. */
const SIGNATURE_INK_MARGIN    = 0.04;

/**
 * Validate and store one uploaded signature, turned the right way up.
 *
 * The form asks people to photograph a signature on white paper, and a phone
 * writes the picture in the sensor's orientation with the rotation in an EXIF
 * tag. Stored as it arrives, a signature photographed the usual way prints on
 * its side — the same failure store_card_photo() exists to prevent for
 * photographs, and worse here, because nobody looks twice at a squiggle to
 * notice it is lying down. The chance to fix it is at upload, once.
 *
 * $ink decides which of two jobs this does.
 *
 * With $ink false — the campus's own signature library — only a JPEG is
 * touched, and only to turn it the right way up and bring its size down. The
 * library deliberately holds a black-ink version and a blue-ink version of the
 * same signature, so the colour it was signed in is information, not noise,
 * and a PNG uploaded there is very often already a cut-out and is left exactly
 * as it arrived.
 *
 * With $ink true — a card holder's own signature — every format is decoded and
 * the ink is lifted off the paper: black on a transparent ground, trimmed to
 * the writing, stored as a PNG. That is the one form that sits on a card
 * without a rectangle of somebody's desk around it, and asking people to
 * produce it themselves was asking for something most of them cannot do.
 *
 * Same contract as store_image(), and the same fallback throughout: if GD is
 * missing, the picture is too large to decode, or it turns out to have no ink
 * and paper to tell apart, the original is stored untouched rather than the
 * upload failing. 'inked' says which of those happened, so a caller that has
 * promised somebody the paper would come off can say when it did not.
 */
function store_signature_image(array $file, string $subdir, bool $ink = false): array
{
    $check = validate_image_upload($file, MIN_SIGNATURE_SIDE);
    if (!$check['ok']) {
        return $check;
    }
    $decodable = image_can_be_processed($check['width'], $check['height']);
    if (!$decodable || (!$ink && $check['mime'] !== 'image/jpeg')) {
        return store_signature_as_it_came($file, $subdir);
    }

    $src = load_photo($file['tmp_name'], $check['mime']);
    if (!$src) {
        return store_signature_as_it_came($file, $subdir);
    }
    $out = $ink ? lift_signature_ink($src) : shrink_within($src, SIGNATURE_WIDTH);
    imagedestroy($src);
    if ($out === null) {
        // Nothing here to tell ink from paper: a blank page, or a picture of
        // something that is not a signature.
        return store_signature_as_it_came($file, $subdir);
    }

    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        imagedestroy($out);
        return ['ok' => false, 'error' => 'upload'];
    }

    $stored = bin2hex(random_bytes(16)) . ($ink ? '.png' : '.jpg');
    $ok     = $ink
        ? imagepng($out, $dir . '/' . $stored)
        : imagejpeg($out, $dir . '/' . $stored, SIGNATURE_QUALITY);
    $w      = imagesx($out);
    $h      = imagesy($out);
    imagedestroy($out);

    if (!$ok) {
        // A failed write can leave a truncated file nothing will ever point
        // at. Out of the way before the fallback stores the original.
        @unlink($dir . '/' . $stored);
        return store_signature_as_it_came($file, $subdir);
    }
    @chmod($dir . '/' . $stored, 0644);

    return [
        'ok'     => true,
        'path'   => $subdir . '/' . $stored,
        'inked'  => $ink,
        'width'  => $w,
        'height' => $h,
    ];
}

/** The picture kept exactly as it arrived, and said to be. */
function store_signature_as_it_came(array $file, string $subdir): array
{
    $stored = store_image($file, $subdir, MIN_SIGNATURE_SIDE);
    $stored['inked'] = false;
    return $stored;
}

/**
 * Black ink on a transparent ground, trimmed to the writing — or null when the
 * picture holds no ink to lift, in which case the caller stores what arrived.
 *
 * The writing is found first and the picture brought down to the size a card
 * prints afterwards — never the other way round. Shrinking first, which is the
 * obvious way round, throws away almost all of the signature's resolution
 * before anything has looked at where the signature is: an A4 photograph
 * 3000 px across holds a signature perhaps 600 px wide, and bounding the whole
 * page to 900 px leaves that signature 180 px across, below what the card
 * wants and below what this file accepts as an upload.
 *
 * The box is measured on a small copy and expressed as fractions of the
 * picture, so it can be applied to the picture itself without a scale factor
 * to get wrong.
 *
 * Transparency in the source is composited onto white first. That turns a
 * cut-out somebody made by hand into what the rest of this expects — ink on
 * paper — so a blue cut-out comes out black, a black one comes out unchanged,
 * and paper left inside a cut-out is treated as paper rather than as ink.
 */
function lift_signature_ink(GdImage $src): ?GdImage
{
    $page = flatten_onto_paper($src);

    $small   = shrink_within($page, SIGNATURE_ANALYSIS_SIDE);
    $writing = ink_box($small, paper_grid($small, true));
    imagedestroy($small);
    if ($writing === null) {
        imagedestroy($page);
        return null;
    }

    $page = crop_fraction($page, $writing);
    $ink  = shrink_within($page, SIGNATURE_WIDTH);
    imagedestroy($page);

    // Checked again on the way out, not only on the way in. The box was found
    // on a small copy, and if what the full-size pass makes of the same area
    // is blank — or nearly — then storing it would put an empty rectangle on
    // the card, delete the photograph it came from, and report success.
    return ink_to_alpha($ink, paper_grid($ink));
}

/**
 * The picture composited onto white, so everything after this can assume it is
 * looking at ink on paper. An opaque photograph comes through unchanged.
 */
function flatten_onto_paper(GdImage $src): GdImage
{
    $w   = imagesx($src);
    $h   = imagesy($src);
    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, true);
    imagefilledrectangle($out, 0, 0, $w - 1, $h - 1, imagecolorallocate($out, 255, 255, 255));
    imagecopy($out, $src, 0, 0, 0, 0, $w, $h);
    return $out;
}

/** A box given as fractions of the picture, cut out of it. */
function crop_fraction(GdImage $im, array $box): GdImage
{
    $w  = imagesx($im);
    $h  = imagesy($im);
    $x1 = (int) max(0, floor($box[0] * $w));
    $y1 = (int) max(0, floor($box[1] * $h));
    $x2 = (int) min($w - 1, ceil($box[2] * $w));
    $y2 = (int) min($h - 1, ceil($box[3] * $h));
    if ($x2 <= $x1 || $y2 <= $y1) {
        return $im;
    }
    $crop = imagecrop($im, ['x' => $x1, 'y' => $y1, 'width' => $x2 - $x1 + 1, 'height' => $y2 - $y1 + 1]);
    if (!$crop) {
        return $im;
    }
    imagedestroy($im);
    return $crop;
}

/**
 * How light the paper is in each part of the picture, and which parts are
 * paper at all.
 *
 * A grid of small cells, each holding the lightest tone found in it — the
 * lightest rather than the average, because a cell the stroke passes through
 * would otherwise report the ink as part of its own paper and stop reading as
 * ink. What comes back is read over a window of cells either way, so a cell
 * the stroke fills completely borrows its paper from around it instead of
 * reporting the darkest thing in the picture as white.
 *
 * A cell far darker than the lightest paper in the picture is not paper: it is
 * the desk the page is lying on. Those come back as nought, which
 * ink_opacity() reads as "no ink here". The cells are small so that the line
 * where desk meets paper — where one cell holds some of each, and the paper in
 * it makes the desk beside it look like ink — is a few pixels wide instead of
 * a bar across the card.
 *
 * @return array{paper: list<int>, gw: int, gh: int, cw: int, ch: int}
 */
function paper_grid(GdImage $im, bool $findDesk = false): array
{
    $w = imagesx($im);
    $h = imagesy($im);
    // A cell is a share of the picture, not a fixed number of pixels. This
    // runs at two very different scales — a whole page at 420 px across to
    // find the writing, then the crop at up to 900 px to render it — and a
    // cell that is small at one of them is smaller than a stroke is thick at
    // the other. Cells inside the stroke then look exactly like cells of desk,
    // and a thick signature came out shredded, or gone altogether.
    $cell = max(4, (int) round(max($w, $h) / SIGNATURE_PAPER_CELLS));
    $gw   = max(1, (int) ceil($w / $cell));
    $gh   = max(1, (int) ceil($h / $cell));
    $cw   = (int) ceil($w / $gw);
    $ch   = (int) ceil($h / $gh);

    $max = array_fill(0, $gw * $gh, 0);
    for ($y = 0; $y < $h; $y++) {
        $row = ((int) ($y / $ch)) * $gw;
        for ($x = 0; $x < $w; $x++) {
            $lum = luminance_of(imagecolorat($im, $x, $y));
            $i   = $row + (int) ($x / $cw);
            if ($lum > $max[$i]) {
                $max[$i] = $lum;
            }
        }
    }

    // The lightest paper in the picture, read a little inside the very top so
    // that one glaring highlight cannot set the level for everything else.
    $sorted = $max;
    sort($sorted);
    $floor = $sorted[(int) (count($sorted) * 0.98)] * SIGNATURE_SHEET_FLOOR;

    /**
     * Which cells are the desk rather than the sheet, in two passes.
     *
     * Dark on its own is not enough to say. A cell the stroke fills completely
     * is as dark as a cell of desk, and ruling those out took the signature
     * with them — the whole picture came back blank. What separates them is
     * how far the dark goes: a desk is a wide dark area, so most of what
     * surrounds a cell of it is dark as well, while a stroke is a line with
     * paper on both sides of it however thick it is.
     *
     * Then the same again for the cells next to it. A cell on the edge of the
     * sheet holds some desk and some paper; its lightest tone is the paper's,
     * so it is not dark and survives the first pass, and the desk inside it is
     * measured against paper and comes out as ink. What that draws is a
     * one-pixel rectangle around the sheet — not much in itself, but it spans
     * nearly the whole frame, so the trim box grew to the edges and the
     * signature printed small in the middle of a box drawn round the page. So
     * every cell touching the desk goes with the desk, which costs a cell of
     * paper around the rim of the sheet. Nobody signs there.
     */
    $dark = [];
    foreach ($max as $i => $lightest) {
        // Only the pass that goes looking for the writing rules anything out.
        // By the time the crop is rendered the desk is outside it, and running
        // the test again there can only take ink away.
        $dark[$i] = $findDesk && $lightest < $floor;
    }
    $desk = [];
    for ($gy = 0; $gy < $gh; $gy++) {
        for ($gx = 0; $gx < $gw; $gx++) {
            $i = $gy * $gw + $gx;
            $desk[$i] = $dark[$i] && neighbours_dark($dark, $gw, $gh, $gx, $gy) >= 5;
        }
    }
    $onSheet = [];
    for ($gy = 0; $gy < $gh; $gy++) {
        for ($gx = 0; $gx < $gw; $gx++) {
            $i = $gy * $gw + $gx;
            $onSheet[$i] = !$desk[$i] && neighbours_dark($desk, $gw, $gh, $gx, $gy) === 0;
        }
    }

    $paper = $max;
    for ($gy = 0; $gy < $gh; $gy++) {
        for ($gx = 0; $gx < $gw; $gx++) {
            if (!$onSheet[$gy * $gw + $gx]) {
                $paper[$gy * $gw + $gx] = 0;          // not paper: the desk, or its edge
                continue;
            }
            $sum = 0;
            $n   = 0;
            for ($dy = -SIGNATURE_PAPER_BLUR; $dy <= SIGNATURE_PAPER_BLUR; $dy++) {
                for ($dx = -SIGNATURE_PAPER_BLUR; $dx <= SIGNATURE_PAPER_BLUR; $dx++) {
                    $nx = $gx + $dx;
                    $ny = $gy + $dy;
                    if ($nx >= 0 && $nx < $gw && $ny >= 0 && $ny < $gh) {
                        $sum += $max[$ny * $gw + $nx];
                        $n++;
                    }
                }
            }
            $paper[$gy * $gw + $gx] = (int) round($sum / $n);
        }
    }
    return ['paper' => $paper, 'gw' => $gw, 'gh' => $gh, 'cw' => $cw, 'ch' => $ch];
}

/** How many of a cell's eight neighbours are set in $flags. */
function neighbours_dark(array $flags, int $gw, int $gh, int $gx, int $gy): int
{
    $n = 0;
    for ($dy = -1; $dy <= 1; $dy++) {
        for ($dx = -1; $dx <= 1; $dx++) {
            if ($dx === 0 && $dy === 0) {
                continue;
            }
            $nx = $gx + $dx;
            $ny = $gy + $dy;
            if ($nx >= 0 && $nx < $gw && $ny >= 0 && $ny < $gh && $flags[$ny * $gw + $nx]) {
                $n++;
            }
        }
    }
    return $n;
}

/** Rec. 709 luminance of a packed colour, without the cost of unpacking it. */
function luminance_of(int $colour): int
{
    return (int) (0.2126 * (($colour >> 16) & 255)
                + 0.7152 * (($colour >> 8) & 255)
                + 0.0722 * ($colour & 255));
}

/** How opaque a tone is, against the paper the grid found around it. */
function ink_opacity(int $lum, int $paper): float
{
    if ($paper < 8) {
        return 0.0;                       // nothing here is paper, so nothing is ink
    }
    $dark  = $paper * (1 - SIGNATURE_INK_DEPTH);
    $light = $paper * (1 - SIGNATURE_INK_EDGE);
    if ($lum <= $dark) {
        return 1.0;
    }
    if ($lum >= $light) {
        return 0.0;
    }
    return ($light - $lum) / ($light - $dark);
}

/**
 * Where the writing is, as fractions of the picture with a little air around
 * it — or null when there is not enough ink to be a signature.
 *
 * @return array{0:float,1:float,2:float,3:float}|null
 */
function ink_box(GdImage $im, array $grid): ?array
{
    $paper = $grid['paper'];
    $gw    = $grid['gw'];
    $cw    = $grid['cw'];
    $ch    = $grid['ch'];
    $w     = imagesx($im);
    $h     = imagesy($im);

    $minX = $w; $minY = $h; $maxX = -1; $maxY = -1; $ink = 0;
    for ($y = 0; $y < $h; $y++) {
        $row = ((int) ($y / $ch)) * $gw;
        for ($x = 0; $x < $w; $x++) {
            if (ink_opacity(luminance_of(imagecolorat($im, $x, $y)), $paper[$row + (int) ($x / $cw)]) < 0.5) {
                continue;
            }
            $ink++;
            $minX = min($minX, $x); $maxX = max($maxX, $x);
            $minY = min($minY, $y); $maxY = max($maxY, $y);
        }
    }
    if ($ink < SIGNATURE_MIN_INK_PX) {
        return null;
    }

    $margin = max($maxX - $minX, $maxY - $minY) * SIGNATURE_INK_MARGIN;
    return [
        max(0.0, ($minX - $margin) / $w),
        max(0.0, ($minY - $margin) / $h),
        min(1.0, ($maxX + 1 + $margin) / $w),
        min(1.0, ($maxY + 1 + $margin) / $h),
    ];
}

/**
 * The picture as black ink at the opacity the grid gives each pixel, or null
 * when what comes out has next to nothing in it.
 */
function ink_to_alpha(GdImage $im, array $grid): ?GdImage
{
    $paper = $grid['paper'];
    $gw    = $grid['gw'];
    $cw    = $grid['cw'];
    $ch    = $grid['ch'];
    $w     = imagesx($im);
    $h     = imagesy($im);

    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefilledrectangle($out, 0, 0, $w - 1, $h - 1, imagecolorallocatealpha($out, 0, 0, 0, 127));

    // One allocation per level of opacity, rather than one per pixel.
    $inks = [];
    $ink  = 0;
    for ($y = 0; $y < $h; $y++) {
        $row = ((int) ($y / $ch)) * $gw;
        for ($x = 0; $x < $w; $x++) {
            $lum = luminance_of(imagecolorat($im, $x, $y));
            $a   = (int) round(127 * (1 - ink_opacity($lum, $paper[$row + (int) ($x / $cw)])));
            if ($a >= 126) {
                continue;
            }
            if ($a <= 63) {
                $ink++;
            }
            $inks[$a] ??= imagecolorallocatealpha($out, 0, 0, 0, $a);
            imagesetpixel($out, $x, $y, $inks[$a]);
        }
    }
    imagedestroy($im);
    if ($ink < SIGNATURE_MIN_INK_PX) {
        imagedestroy($out);
        return null;
    }
    return $out;
}

/**
 * A copy with neither side longer than $max, keeping the proportions.
 *
 * The longest side is what is bounded, not the width: a signature strip is
 * wide and a photograph is tall, and one of them would otherwise come through
 * at two and a half thousand pixels.
 *
 * Never enlarged: a picture already inside the bound keeps its own size rather
 * than being blown up into a blurry one. It is still copied, because the
 * caller destroys both this and the image it passed in, and handing back the
 * same image would have it destroyed twice.
 *
 * The copy is laid on white. Nothing here needs transparency carried through:
 * a photograph is printed on white card stock and cannot carry it through a
 * JPEG anyway, and a signature has been composited onto white before it gets
 * this far, precisely so that one recipe serves both.
 */
function shrink_within(GdImage $src, int $max): GdImage
{
    $sw = imagesx($src);
    $sh = imagesy($src);
    $bounded = $sw <= $max && $sh <= $max;

    $scale = $bounded ? 1.0 : $max / max($sw, $sh);
    $w     = $bounded ? $sw : max(1, (int) round($sw * $scale));
    $h     = $bounded ? $sh : max(1, (int) round($sh * $scale));

    $out = imagecreatetruecolor($w, $h);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));

    // The caller destroys what comes back and must not be handed the image it
    // still holds, so an already-bounded picture is copied rather than passed
    // straight through.
    if ($bounded) {
        imagecopy($out, $src, 0, 0, 0, 0, $sw, $sh);
    } else {
        imagecopyresampled($out, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);
    }
    return $out;
}

/**
 * Whether a stored photograph is something this can actually act on — the
 * file is there, it is an image, and it is small enough to decode.
 *
 * The distinction matters to the batch below: a photograph whose file has
 * gone, or that GD will not read, is not work waiting to be done. Counting it
 * as outstanding would leave the page reporting a figure that no amount of
 * clicking could ever clear.
 */
function photo_can_be_cropped(?string $relPath): bool
{
    $full = resolve_upload($relPath);
    if ($full === null) {
        return false;
    }
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($full);
    $size = @getimagesize($full);
    return isset(ALLOWED_IMAGES[$mime]) && $size && image_can_be_processed($size[0], $size[1]);
}

/**
 * How long a pass may run. Decoding and re-encoding a photograph takes a
 * appreciable fraction of a second, and a campus with four hundred of them
 * would run well past the 30-second max_execution_time shared hosting sets —
 * killing the request mid-loop, with no redirect, no log line and nothing on
 * screen to say how far it got.
 */
const RECROP_SECONDS = 20;

/**
 * Bring stored photographs to the card's frame, as many as the budget allows.
 *
 * Each row is committed as it goes, so stopping early loses nothing and
 * running it again simply continues. The order within one photograph matters:
 * the new file is written, then the row is pointed at it, and only then is the
 * original removed — so a failed update leaves the person's photograph exactly
 * where it was rather than pointing at a file that has been deleted.
 *
 * @return array{done:int, skipped:int, left:int, total:int}
 */
function recrop_stored_photos(): array
{
    $deadline = microtime(true) + RECROP_SECONDS;
    $rows = all('SELECT id, avatar_path FROM users WHERE avatar_path IS NOT NULL AND avatar_path <> \'\'');

    $done = $skipped = $left = 0;
    foreach ($rows as $person) {
        $old = (string) $person['avatar_path'];
        if (is_card_shaped($old)) {
            continue;
        }
        if (!photo_can_be_cropped($old)) {
            $skipped++;
            continue;
        }
        if (microtime(true) > $deadline) {
            $left++;
            continue;
        }

        $new = recrop_from_source($old);
        if ($new === null) {
            $skipped++;
            continue;
        }
        try {
            q('UPDATE users SET avatar_path = ? WHERE id = ?', [$new, (int) $person['id']]);
        } catch (Throwable $e) {
            // The row still names the original, so the copy just written is
            // the one that has to go.
            delete_upload($new);
            $skipped++;
            continue;
        }
        delete_upload($old);
        $done++;
    }

    return ['done' => $done, 'skipped' => $skipped, 'left' => $left, 'total' => count($rows)];
}

/** True when a photograph is already stored at the card's proportions. */
function is_card_shaped(?string $relPath): bool
{
    $full = resolve_upload($relPath);
    if ($full === null) {
        return false;
    }
    $size = @getimagesize($full);
    if (!$size || $size[1] < 1) {
        return false;
    }
    return abs(($size[0] / $size[1]) - CARD_PHOTO_RATIO) < 0.005;
}

/* ------------------------------------------------------------- the work -- */

/**
 * Whether this image may be decoded at all.
 *
 * The pixel count is the guard that matters, and it is checked first, because
 * memory_limit cannot be relied on here: GD allocates its pixel buffers
 * through libgd rather than PHP's allocator, so they are not counted against
 * the limit and a limit of -1 bounds nothing whatsoever. A PNG a couple of
 * hundred kilobytes long can declare 30000 x 30000 and ask the decoder for
 * three and a half gigabytes — and register.php takes uploads before anyone
 * has signed in.
 *
 * Refusing is not an error: the caller stores the original untouched, and the
 * card crops it in CSS as it always did.
 */
function image_can_be_processed(int $w, int $h): bool
{
    if (!function_exists('imagecreatetruecolor') || $w < 1 || $h < 1) {
        return false;
    }
    $pixels = $w * $h;
    if ($pixels > CARD_PHOTO_MAX_PIXELS) {
        return false;
    }

    $limit = ini_bytes((string) ini_get('memory_limit'));
    if ($limit <= 0) {
        // No limit to read, so nothing to reason with beyond the cap above.
        return $pixels <= CARD_PHOTO_UNMETERED_PIXELS;
    }

    // Below the caps, memory_limit is used as a rough proxy for how much the
    // host has to spare — not as a bound, since it does not bind GD. Three
    // full-size buffers: the decoded source, the copy imagerotate() makes when
    // the photo needs turning, and the resampled destination. It is deliberately
    // pessimistic, so on a small plan a very large photograph is stored as it
    // arrived rather than risking the request. That is a worse-looking card,
    // never a broken upload.
    $need = ($pixels * 2 + CARD_PHOTO_WIDTH * (int) round(CARD_PHOTO_WIDTH / CARD_PHOTO_RATIO)) * 4;
    return $need < $limit;
}

/**
 * Decode an image and turn it the right way up.
 *
 * A phone writes the picture in the sensor's orientation and records which way
 * the phone was held in an EXIF tag. Ignore the tag and a portrait taken in
 * the usual way prints lying on its side — which is what used to happen, and
 * is the single most common thing wrong with an uploaded photograph.
 */
function load_photo(string $path, string $mime): ?GdImage
{
    $im = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png'  => @imagecreatefrompng($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default      => false,
    };
    if (!$im) {
        return null;
    }
    return $mime === 'image/jpeg' ? apply_exif_orientation($im, $path) : $im;
}

/**
 * What to do with each orientation a camera may record: flip first, then
 * rotate. 1 is upright and needs nothing.
 *
 * Read straight off the EXIF spec, which defines each value by where row 0
 * and column 0 of the stored image belong in the displayed one, and then
 * checked against it pixel by pixel rather than reasoned about — 4, 5 and 7
 * are exactly the three that are easy to get wrong, and a wrong one prints
 * somebody's face upside down.
 *
 * A positive angle is anticlockwise, which is imagerotate()'s convention.
 */
function exif_corrections(): array
{
    return [
        2 => [IMG_FLIP_HORIZONTAL, 0],
        3 => [null,                180],
        4 => [IMG_FLIP_VERTICAL,   0],
        5 => [IMG_FLIP_HORIZONTAL, 90],
        6 => [null,                -90],
        7 => [IMG_FLIP_HORIZONTAL, -90],
        8 => [null,                90],
    ];
}

function apply_exif_orientation(GdImage $im, string $path): GdImage
{
    if (!function_exists('exif_read_data')) {
        return $im;
    }
    $exif = @exif_read_data($path);
    $plan = exif_corrections()[(int) ($exif['Orientation'] ?? 1)] ?? null;
    if ($plan === null) {
        return $im;
    }
    [$flip, $angle] = $plan;

    if ($flip !== null) {
        imageflip($im, $flip);
    }
    if ($angle !== 0) {
        $rotated = @imagerotate($im, $angle, 0);
        if ($rotated) {
            imagedestroy($im);
            $im = $rotated;
        }
    }
    return $im;
}

/**
 * Crop to the frame's square and resample to the stored size.
 *
 * Too wide, and the sides come off evenly — a person photographed against a
 * wall is in the middle of it. Too tall, and the excess comes off according to
 * $focus, which defaults to CARD_PHOTO_TOP_BIAS: nearly all of it off the
 * bottom. Never enlarged: a small photograph stays its own size rather than
 * being blown up into a blurry one.
 */
function crop_to_card_frame(GdImage $src, ?int $focus = null): GdImage
{
    $sw = imagesx($src);
    $sh = imagesy($src);

    if (($sw / $sh) > CARD_PHOTO_RATIO) {
        $cropW = (int) round($sh * CARD_PHOTO_RATIO);
        $cropH = $sh;
        $cropX = (int) round(($sw - $cropW) / 2);
        $cropY = 0;
    } else {
        $cropW = $sw;
        $cropH = (int) round($sw / CARD_PHOTO_RATIO);
        $cropX = 0;
        $cropY = (int) round(($sh - $cropH) * (card_photo_focus($focus) / 100));
    }
    // Rounding can put the window a pixel over the edge on an exact ratio.
    $cropW = min($cropW, $sw);
    $cropH = min($cropH, $sh);
    $cropX = max(0, min($cropX, $sw - $cropW));
    $cropY = max(0, min($cropY, $sh - $cropH));

    $outW = min(CARD_PHOTO_WIDTH, $cropW);
    $outH = (int) round($outW / CARD_PHOTO_RATIO);

    $out = imagecreatetruecolor($outW, $outH);
    // A PNG or WebP may carry transparency, and a card is printed on white
    // card stock — so transparency becomes white rather than black.
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
    imagecopyresampled($out, $src, 0, 0, $cropX, $cropY, $outW, $outH, $cropW, $cropH);
    return $out;
}
