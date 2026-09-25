<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/ink.php';
require_once __DIR__ . '/face.php';

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
/**
 * Marks a card photograph cut by the current way of cutting, at the front of
 * its file name. It is how recentre_stored_photos() knows which photographs
 * are already up to date — without a column to migrate, a cursor to keep, or
 * any way for two runs to cut the same photograph twice. Change it whenever
 * the cut changes, and the office's button brings every card up to date.
 */
const CARD_CROP_VERSION = 'h2-';

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

    $dir = upload_dir($subdir);
    if ($dir === null) {
        imagedestroy($card);
        // Nothing will ever name the working copy if the photograph itself
        // cannot be stored.
        delete_upload($source);
        return ['ok' => false, 'error' => 'upload'];
    }

    $stored = CARD_CROP_VERSION . bin2hex(random_bytes(16)) . '.jpg';
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
    $dir = upload_dir(CARD_PHOTO_SOURCE_DIR);
    if ($dir === null) {
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

    $dir    = upload_dir($subdir);
    $stored = CARD_CROP_VERSION . bin2hex(random_bytes(16)) . '.jpg';
    if ($dir === null) {
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
 * With $ink false — a signature the office uploads as a finished file — only
 * a JPEG is touched, and only to turn it the right way up and bring its size
 * down. A PNG uploaded that way is very often already a cut-out and is left
 * exactly as it arrived.
 *
 * With $ink true, every format is decoded and the signature is lifted off
 * the photograph it is in (ink.php): the paper, the desk, a thumb and any
 * shadow go, and what is stored is the writing alone on a transparent
 * ground, trimmed to it, as a PNG. A card holder's signature always goes this
 * way, in black. The office's library goes this way when the box on its form
 * is ticked, with $keepColour, because the library keeps a blue-ink signature
 * beside a black one and the colour is the point of having both.
 *
 * Same contract as store_image(), and the same fallback throughout: if GD is
 * missing, the picture is too large to decode, or it turns out to have no ink
 * and paper to tell apart, the original is stored untouched rather than the
 * upload failing. 'inked' says which of those happened, so a caller that has
 * promised somebody the paper would come off can say when it did not.
 */
function store_signature_image(array $file, string $subdir, bool $ink = false, bool $keepColour = false): array
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
    $out = $ink ? lift_signature_ink($src, $keepColour) : shrink_within($src, SIGNATURE_WIDTH);
    imagedestroy($src);
    if ($out === null) {
        // Nothing here to tell ink from paper: a blank page, or a picture of
        // something that is not a signature.
        return store_signature_as_it_came($file, $subdir);
    }

    $dir = upload_dir($subdir);
    if ($dir === null) {
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
    // The longest one photograph has taken so far. Finding the face can take
    // a few seconds on a slow host, so a photograph is started only when
    // there is time for another like the slowest yet — checking the clock
    // only before each one let the last run on past the host's limit.
    $slowest = 0.0;
    foreach ($rows as $person) {
        $old = (string) $person['avatar_path'];
        if (is_card_shaped($old)) {
            continue;
        }
        if (!photo_can_be_cropped($old)) {
            $skipped++;
            continue;
        }
        if (microtime(true) + $slowest * 1.5 > $deadline) {
            $left++;
            continue;
        }

        $began   = microtime(true);
        $new     = recrop_from_source($old);
        $slowest = max($slowest, microtime(true) - $began);
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

/**
 * Cut every automatically placed photograph again from its working copy, so
 * photographs already on cards get what a new upload gets today: the frame
 * centred on the head. Photographs somebody has placed by hand are left
 * alone, and so are those with no working copy to cut from.
 *
 * Only photographs not yet cut the current way are touched — their file
 * names do not start with CARD_CROP_VERSION — so stopping on the time budget
 * loses nothing and running it again simply carries on, and a photograph cut
 * once is never cut again for nothing.
 *
 * Each photograph follows the same order as recrop_stored_photos(): new file,
 * then the row, then the old file removed. The row is changed only if it
 * still names the photograph this read — the holder may have uploaded a new
 * one, or placed it by hand, or another run may have got there first — and
 * otherwise the file just written is the one that goes.
 *
 * @return array{done:int, skipped:int, left:int}
 */
function recentre_stored_photos(): array
{
    $deadline = microtime(true) + RECROP_SECONDS;
    $rows     = all(
        'SELECT id, avatar_path, avatar_source_path FROM users
          WHERE avatar_source_path IS NOT NULL AND avatar_source_path <> \'\'
            AND avatar_focus IS NULL AND avatar_path IS NOT NULL
            AND avatar_path NOT LIKE ?
          ORDER BY id',
        ['%/' . CARD_CROP_VERSION . '%']
    );

    $done = $skipped = 0;
    $slowest = 0.0;
    foreach ($rows as $n => $person) {
        if (microtime(true) + $slowest * 1.5 > $deadline) {
            return ['done' => $done, 'skipped' => $skipped, 'left' => count($rows) - $n];
        }
        $began   = microtime(true);
        $new     = recrop_from_source((string) $person['avatar_source_path'], null);
        $slowest = max($slowest, microtime(true) - $began);
        if ($new === null) {
            $skipped++;
            continue;
        }
        try {
            $moved = q(
                'UPDATE users SET avatar_path = ?
                  WHERE id = ? AND avatar_focus IS NULL AND avatar_path = ? AND avatar_source_path = ?',
                [$new, (int) $person['id'], $person['avatar_path'], $person['avatar_source_path']]
            )->rowCount();
        } catch (Throwable $e) {
            $moved = 0;
        }
        if ($moved === 0) {
            delete_upload($new);
            $skipped++;
            continue;
        }
        delete_upload((string) $person['avatar_path']);
        $done++;
    }
    return ['done' => $done, 'skipped' => $skipped, 'left' => 0];
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
 * How much of the picture the round frame takes in round a face, as a
 * multiple of the face's own size (brows to mouth): the whole head with clear
 * space round it and the top of the shoulders, as a passport photograph frames
 * them — the head about half the circle's height.
 */
const CARD_FACE_ZOOM = 3.3;
/**
 * Where the head's middle sits, as a share of the frame's height from the
 * top: a touch above the circle's middle, so the space above the hair matches
 * the space the shoulders take up below the chin.
 */
const CARD_FACE_LEVEL = 0.47;
/** The least room round a face worth cutting: less, and the circle clips the head. */
const CARD_FACE_MIN_ZOOM = 2.6;
/** The smallest square worth cutting round a face: below it the card prints soft. */
const CARD_FACE_MIN_CROP = 320;

/**
 * Crop to the frame's square and resample to the stored size.
 *
 * With no placement given — every new photograph — the frame is centred on the
 * face (face.php): the face in the middle of the circle, with room above the
 * hair and the shoulders below, wherever in the picture the face was and
 * however much else the picture holds. When no face is found, or when the
 * holder has moved the frame themselves, the rule below does it instead.
 *
 * The rule: too wide, and the sides come off evenly — a person photographed
 * against a wall is in the middle of it. Too tall, and the excess comes off
 * according to $focus, which defaults to CARD_PHOTO_TOP_BIAS: nearly all of
 * it off the bottom.
 *
 * Never enlarged: a small photograph stays its own size rather than being
 * blown up into a blurry one.
 */
function crop_to_card_frame(GdImage $src, ?int $focus = null): GdImage
{
    $sw = imagesx($src);
    $sh = imagesy($src);

    $box = $focus === null ? face_crop_box($src) : null;
    if ($box !== null) {
        [$cropX, $cropY, $cropW, $cropH] = $box;
    } elseif (($sw / $sh) > CARD_PHOTO_RATIO) {
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

/**
 * The square to cut round the head in a photograph, as [x, y, width,
 * height], or null when there is no face to cut round.
 *
 * Centred on the head (face_head_centre()), and kept centred: the square is
 * made smaller, down to CARD_FACE_MIN_ZOOM, before it is ever slid off the
 * head to stay inside the picture. Sliding is what used to happen first, and
 * on a photograph with the face near an edge — a tight crop, a head at one side
 * of a group — it left the head hard against one side of the circle. Only
 * where even the smallest square will not fit round the head centred is it
 * slid, as little as it has to be.
 *
 * @return array{0: int, 1: int, 2: int, 3: int}|null
 */
function face_crop_box(GdImage $src): ?array
{
    $face = find_face($src);
    if ($face === null) {
        return null;
    }
    [$cx, $cy, $size] = $face;
    [$hx, $hy] = face_head_centre($src, $cx, $cy, $size);
    $sw = imagesx($src);
    $sh = imagesy($src);

    // As much as the zoom asks for (and enough to print sharp), then no more
    // than fits round the head with the head in the middle...
    $side = max($size * CARD_FACE_ZOOM, min(CARD_FACE_MIN_CROP, $sw, $sh));
    $fits = min(2 * $hx, 2 * ($sw - $hx), $hy / CARD_FACE_LEVEL, ($sh - $hy) / (1 - CARD_FACE_LEVEL));
    $side = min($side, max($fits, $size * CARD_FACE_MIN_ZOOM));
    // ...but still enough to print sharp, and never more than the picture.
    $side = max($side, min(CARD_FACE_MIN_CROP, $sw, $sh));
    $side = (int) round(min($side, $sw, $sh));

    $x = (int) round($hx - $side / 2);
    $y = (int) round($hy - $side * CARD_FACE_LEVEL);
    return [max(0, min($x, $sw - $side)), max(0, min($y, $sh - $side)), $side, $side];
}
