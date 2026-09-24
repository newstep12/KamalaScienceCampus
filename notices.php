<?php
declare(strict_types=1);
/**
 * Public notices — TU, examination and campus announcements, readable without
 * signing in. Bilingual via ?lang=ne, matching the portal's language handling.
 *
 * Only published notices that apply to every year are shown: a notice aimed at
 * one year is internal to those students and stays inside the portal.
 *
 * Attachments are part of the notice, not an extra: for most TU notices the
 * PDF *is* the notice, so each one is offered here and served by
 * notice-file.php. portal/download.php cannot do it — it requires a sign-in.
 */
require_once __DIR__ . '/portal/inc/db.php';
require_once __DIR__ . '/portal/inc/lang.php';
require_once __DIR__ . '/portal/inc/uploads.php';
require_once __DIR__ . '/portal/inc/translate.php';

// Never cached by the CDN or browsers: the page varies with the language
// cookie (a cached copy could serve Nepali to an English visitor), and a new
// notice must appear at once. Sending Expires ourselves also stops the
// HTML rule in .htaccess from applying to this page. Sent before the query,
// so they also go out when the database is down.
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

$dbUp    = true;
$notices = [];
$cat     = (string) ($_GET['cat'] ?? '');
$cats    = ['tu', 'exam', 'scholarship', 'ugc', 'campus'];

try {
    $sql = 'SELECT * FROM notices WHERE is_published = 1 AND year_level IS NULL';
    $par = [];
    if (in_array($cat, $cats, true)) {
        $sql .= ' AND category = ?';
        $par[] = $cat;
    }
    $sql .= ' ORDER BY is_pinned DESC, published_at DESC LIMIT 60';
    $notices = all($sql, $par);
} catch (Throwable $e) {
    // The public site must never break because the portal database is down.
    error_log('Public notices unavailable: ' . $e->getMessage());
    $dbUp = false;
}

$ne   = is_nepali();
$base = '';
// The same notices in the other language, keeping the category filter.
$alt  = '?' . http_build_query(array_filter([
    'cat'  => in_array($cat, $cats, true) ? $cat : null,
    'lang' => $ne ? 'en' : 'ne',
]));
?>
<!doctype html>
<html lang="<?= $ne ? 'ne' : 'en' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $ne ? 'सूचनाहरू — कमला साइन्स क्याम्पस' : 'Notices — Kamala Science Campus' ?></title>
<meta name="description" content="<?= $ne
  ? 'त्रिभुवन विश्वविद्यालयका आधिकारिक सूचना, परीक्षा सूचना र कमला साइन्स क्याम्पसका जानकारी।'
  : 'Official Tribhuvan University notices, examination notices and announcements from Kamala Science Campus.' ?>">
<?php // One address per language for search engines; category filters count as the same page. ?>
<link rel="canonical" href="https://kamalasciencecampus.edu.np/notices.php<?= $ne ? '?lang=ne' : '' ?>">
<link rel="alternate" hreflang="en" href="https://kamalasciencecampus.edu.np/notices.php">
<link rel="alternate" hreflang="ne" href="https://kamalasciencecampus.edu.np/notices.php?lang=ne">
<link rel="alternate" hreflang="x-default" href="https://kamalasciencecampus.edu.np/notices.php">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&family=Noto+Sans+Devanagari:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/css/styles.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/styles.css') ?>">
<link rel="stylesheet" href="assets/css/portal.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/portal.css') ?>">
<link rel="icon" type="image/png" sizes="192x192" href="assets/img/logo-192.png">
<meta name="alt-page" content="<?= e($alt) ?>">
<script src="assets/js/lang.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/lang.js') ?>"></script>
</head>
<body data-page="notices"<?= $ne ? ' class="lang-ne"' : '' ?>>
<?php // The campus top bar and side menu, as build.py wrote them from src/partials. ?>
<?= str_replace('{{ALT}}', e($alt), (string) file_get_contents(__DIR__ . '/portal/inc/site-nav-' . ($ne ? 'ne' : 'en') . '.html')) ?>
<main id="main" class="site-main">
<section class="page-head">
  <div class="wrap">
    <h1><?= $ne ? 'सूचनाहरू' : 'Notices' ?></h1>
    <p><?= $ne
      ? 'बी.एस्सी. कार्यक्रमसम्बन्धी त्रिभुवन विश्वविद्यालयका आधिकारिक सूचना, परीक्षा सूचना र क्याम्पसका जानकारी।'
      : 'Official Tribhuvan University notices for the B.Sc. program, examination notices, and campus announcements.' ?></p>
  </div>
</section>

<section>
  <div class="wrap">
    <nav class="p-filters">
      <a href="notices.php<?= $ne ? '?lang=ne' : '' ?>" <?= $cat === '' ? 'aria-current="true"' : '' ?>>
        <?= $ne ? 'सबै सूचना' : 'All notices' ?>
      </a>
      <?php
      $labels = $ne
        ? ['tu' => 'त्रि.वि. आधिकारिक', 'exam' => 'परीक्षा', 'scholarship' => 'छात्रवृत्ति',
           'ugc' => 'यू.जी.सी.', 'campus' => 'क्याम्पस']
        : ['tu' => 'TU official', 'exam' => 'Examination', 'scholarship' => 'Scholarship',
           'ugc' => 'UGC', 'campus' => 'Campus'];
      foreach ($cats as $c): ?>
        <a href="?cat=<?= e($c) ?><?= $ne ? '&lang=ne' : '' ?>" <?= $cat === $c ? 'aria-current="true"' : '' ?>>
          <?= e($labels[$c]) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <?php if (!$dbUp): ?>
      <div class="p-empty"><p><?= $ne
        ? 'सूचनाहरू अहिले उपलब्ध छैनन्। कृपया केही बेरपछि प्रयास गर्नुहोस्।'
        : 'Notices are unavailable right now. Please try again shortly.' ?></p></div>
    <?php elseif (!$notices): ?>
      <div class="p-empty"><p><?= $ne
        ? 'अहिलेसम्म कुनै सूचना प्रकाशित भएको छैन।'
        : 'No notices have been published yet.' ?></p></div>
    <?php else: ?>
      <?php foreach ($notices as $n): ?>
        <article class="p-notice<?= $n['is_pinned'] ? ' pinned' : '' ?>">
          <div class="p-notice-head">
            <span class="p-tag <?= e($n['category']) ?>"><?= e($labels[$n['category']] ?? $n['category']) ?></span>
            <?php if ($n['is_pinned']): ?>
              <span class="p-tag pin"><?= $ne ? 'महत्त्वपूर्ण' : 'Pinned' ?></span>
            <?php endif; ?>
            <span class="p-item-meta"><?= e(format_date($n['published_at'])) ?></span>
          </div>
          <h3><?= e(bilingual($n, 'title')) ?></h3>
          <?php if ($body = bilingual($n, 'body')): ?>
            <div class="p-notice-body"><?= e($body) ?></div>
          <?php endif; ?>

          <?php
          // The stored extension comes from the type sniffed at upload time,
          // so it tells us whether the browser can show this in the page.
          $kind = $n['file_path'] ? upload_kind($n['file_path']) : '';
          $file = 'notice-file.php?id=' . (int) $n['id'];
          ?>
          <?php if ($n['source_url'] || $n['file_path']): ?>
            <div class="p-form-actions">
              <?php if ($n['file_path']): ?>
                <a class="p-btn p-btn-primary p-btn-sm" href="<?= e($file) ?>" target="_blank" rel="noopener">
                  <?= $kind === 'pdf'
                    ? ($ne ? 'सूचना (PDF) हेर्नुहोस्' : 'Open the notice (PDF)')
                    : ($ne ? 'संलग्न फाइल हेर्नुहोस्' : 'Open the attachment') ?> ↗
                </a>
                <a class="p-btn p-btn-ghost p-btn-sm" href="<?= e($file) ?>&amp;download=1">
                  <?= $ne ? 'डाउनलोड' : 'Download' ?> ⤓
                </a>
              <?php endif; ?>
              <?php if ($n['source_url']): ?>
                <a class="p-btn p-btn-ghost p-btn-sm" href="<?= e($n['source_url']) ?>"
                   target="_blank" rel="noopener noreferrer nofollow">
                  <?= $ne ? 'आधिकारिक सूचना हेर्नुहोस्' : 'View the official notice' ?> ↗
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php // Read the notice without leaving the page. The frame is only
                // filled in once it is opened, so sixty notices do not mean
                // sixty PDFs downloaded on arrival; without JavaScript the
                // buttons above still do the job. ?>
          <?php if ($kind === 'pdf' || $kind === 'image'): ?>
            <details class="p-preview" data-preview-src="<?= e($file) ?>">
              <summary><?= $ne ? 'यहीँ पढ्नुहोस्' : 'Read it here' ?></summary>
              <div class="p-preview-frame">
                <?php if ($kind === 'image'): ?>
                  <img src="<?= e($file) ?>" alt="<?= e(bilingual($n, 'title')) ?>" loading="lazy">
                <?php else: ?>
                  <iframe title="<?= e(bilingual($n, 'title')) ?>" loading="lazy"></iframe>
                  <noscript>
                    <p><a href="<?= e($file) ?>" target="_blank" rel="noopener">
                      <?= $ne ? 'सूचना (PDF) खोल्नुहोस्' : 'Open the notice (PDF)' ?> ↗</a></p>
                  </noscript>
                <?php endif; ?>
              </div>
            </details>
          <?php endif; ?>

          <?php if (notice_is_machine_translated($n)): ?>
            <p class="p-auto-note"><?= $ne
              ? 'यो नेपाली रूपान्तरण स्वचालित रूपमा गरिएको हो। आधिकारिक भाषा अङ्ग्रेजी सूचना नै हो।'
              : 'This Nepali version was translated automatically.' ?></p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section class="cta">
  <div class="wrap">
    <h2><?= $ne ? 'विद्यार्थी हुनुहुन्छ?' : 'Are you a student here?' ?></h2>
    <p><?= $ne
      ? 'आफ्नो वर्षका सूचना, अध्ययन सामग्री, नतिजा र हाजिरी हेर्न पोर्टलमा लग इन गर्नुहोस्।'
      : 'Sign in to the portal for notices for your year, learning materials, results and attendance.' ?></p>
    <div class="cta-actions">
      <a class="btn btn-primary" href="portal/index.php"><?= $ne ? 'पोर्टलमा लग इन' : 'Sign in to the portal' ?></a>
    </div>
  </div>
</section>
</main>
</div>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-bottom" style="border:0;">
      <span>&copy; <?= localize_digits(date('Y')) ?> <?= $ne ? 'कमला साइन्स क्याम्पस' : 'Kamala Science Campus' ?></span>
      <a href="<?= $ne ? 'ne/index.html' : 'index.html' ?>"><?= $ne ? 'मुख्य वेबसाइट' : 'Main site' ?></a>
    </div>
  </div>
</footer>
<script src="assets/js/main.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
</body>
</html>
