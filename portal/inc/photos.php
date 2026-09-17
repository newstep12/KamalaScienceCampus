<?php
declare(strict_types=1);

require_once __DIR__ . '/uploads.php';

/**
 * Photographs, made ready for the identity card as they are uploaded.
 *
 * A card has one photo frame, 25 × 32 — the proportions of a passport photo —
 * and whatever arrives has to end up in it. Leaving that to the browser meant
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

/** The card's photo frame — .idc-photo is `aspect-ratio: 25 / 32`. */
const CARD_PHOTO_RATIO = 25 / 32;

/**
 * The stored size. The frame prints about 17 mm wide, so 600 px across is
 * roughly 900 dpi — more than any campus printer resolves, and small enough
 * that a card page stays quick to open.
 */
const CARD_PHOTO_WIDTH   = 600;
const CARD_PHOTO_QUALITY = 88;

/**
 * Where the crop sits when a photo is taller than the frame.
 *
 * Not the middle. People stand in the middle of their own photographs, which
 * puts the head in the upper third; centring the crop on a full-length photo
 * takes the top of the head off and keeps the knees. A quarter of the excess
 * comes off the top and three quarters off the bottom.
 */
const CARD_PHOTO_TOP_BIAS = 0.25;

/**
 * Validate, crop and store one uploaded photograph.
 *
 * Same contract as store_image(), so the callers are unchanged apart from the
 * name: ['ok' => true, 'path', 'width', 'height'], or ['ok' => false, 'error']
 * with 'upload', 'type' or 'small'.
 *
 * If the image cannot be processed — GD missing, a picture too large to hold
 * in memory, a decoder that refuses it — the original is stored as it is
 * rather than the upload failing. The card still crops it with object-fit;
 * the person just does not get the tidier version.
 */
function store_card_photo(array $file, string $subdir = 'photos'): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'upload'];
    }
    if ($file['size'] > MAX_IMAGE_UPLOAD || $file['size'] <= 0) {
        return ['ok' => false, 'error' => 'upload'];
    }

    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(ALLOWED_IMAGES[$mime])) {
        return ['ok' => false, 'error' => 'type'];
    }

    $size = @getimagesize($file['tmp_name']);
    if (!$size || $size[0] < 1 || $size[1] < 1) {
        return ['ok' => false, 'error' => 'type'];
    }
    // Measured before any cropping: an image large enough to print is judged
    // on what was uploaded, not on what is left after the frame takes its cut.
    if ($size[0] < 200 || $size[1] < 200) {
        return ['ok' => false, 'error' => 'small'];
    }

    $src = image_can_be_processed($size[0], $size[1]) ? load_photo($file['tmp_name'], $mime) : null;
    if (!$src) {
        return store_image($file, $subdir, 200);
    }

    $card = crop_to_card_frame($src);
    imagedestroy($src);

    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        imagedestroy($card);
        return ['ok' => false, 'error' => 'upload'];
    }

    $stored = bin2hex(random_bytes(16)) . '.jpg';
    $ok     = imagejpeg($card, $dir . '/' . $stored, CARD_PHOTO_QUALITY);
    $w      = imagesx($card);
    $h      = imagesy($card);
    imagedestroy($card);

    if (!$ok) {
        return store_image($file, $subdir, 200);
    }
    @chmod($dir . '/' . $stored, 0644);

    return ['ok' => true, 'path' => $subdir . '/' . $stored, 'width' => $w, 'height' => $h];
}

/**
 * Re-crop a photograph already in the uploads directory, replacing it in
 * place and returning the new relative path — or null when it cannot be done,
 * in which case the original is left exactly as it was.
 *
 * This is what Admin → System runs over photographs uploaded before the crop
 * existed, so a card printed today looks the same whenever its photo arrived.
 */
function recrop_stored_photo(?string $relPath, string $subdir = 'photos'): ?string
{
    $full = resolve_upload($relPath);
    if ($full === null) {
        return null;
    }
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($full);
    $size = @getimagesize($full);
    if (!isset(ALLOWED_IMAGES[$mime]) || !$size || !image_can_be_processed($size[0], $size[1])) {
        return null;
    }

    $src = load_photo($full, $mime);
    if (!$src) {
        return null;
    }
    $card = crop_to_card_frame($src);
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
        return null;
    }
    @chmod($dir . '/' . $stored, 0644);

    // Written first, then the old one removed: a failed write must never take
    // the only copy of somebody's photograph with it.
    delete_upload($relPath);
    return $subdir . '/' . $stored;
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
 * Whether there is room to hold this image in memory.
 *
 * GD works on uncompressed pixels: a 12 megapixel phone photo is about 48 MB
 * once decoded, and two of them — source and destination — will end a request
 * on a shared plan whose memory_limit is 128 MB. Better to store the original
 * untouched than to hand somebody a blank page.
 */
function image_can_be_processed(int $w, int $h): bool
{
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }
    $limit = ini_bytes((string) ini_get('memory_limit'));
    if ($limit <= 0) {
        return true;                         // unlimited
    }
    // Source plus destination, and half as much again for the decoder's own
    // working space.
    $need = ($w * $h + CARD_PHOTO_WIDTH * (int) round(CARD_PHOTO_WIDTH / CARD_PHOTO_RATIO)) * 4 * 1.5;
    return $need < ($limit - memory_get_usage(true));
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

function apply_exif_orientation(GdImage $im, string $path): GdImage
{
    if (!function_exists('exif_read_data')) {
        return $im;
    }
    $exif = @exif_read_data($path);
    $o    = (int) ($exif['Orientation'] ?? 0);
    if ($o < 2 || $o > 8) {
        return $im;
    }

    // 1 is upright; the other seven are the rotations and mirrors a camera
    // may record. Mirrors are flipped before the rotation, as EXIF defines.
    if (in_array($o, [2, 4, 5, 7], true)) {
        imageflip($im, $o === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
    }
    $angle = match ($o) {
        3, 4    => 180,
        5, 6    => -90,
        7, 8    => 90,
        default => 0,
    };
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
 * Crop to 25 × 32 and resample to the stored size.
 *
 * Too wide, and the sides come off evenly — a person photographed against a
 * wall is in the middle of it. Too tall, and the crop sits high, per
 * CARD_PHOTO_TOP_BIAS. Never enlarged: a small photograph stays its own size
 * rather than being blown up into a blurry one.
 */
function crop_to_card_frame(GdImage $src): GdImage
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
        $cropY = (int) round(($sh - $cropH) * CARD_PHOTO_TOP_BIAS);
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
