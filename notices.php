<?php
declare(strict_types=1);
/**
 * Public notices — TU, examination and campus announcements, readable without
 * signing in. Bilingual via ?lang=ne, matching the portal's language handling.
 *
 * Only published notices that apply to every year are shown: a notice aimed at
 * one year is internal to those students and stays inside the portal.
 */
require_once __DIR__ . '/portal/inc/db.php';
require_once __DIR__ . '/portal/inc/lang.php';

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
$alt  = $ne ? '?lang=en' : '?lang=ne';
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&family=Noto+Sans+Devanagari:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/css/styles.css">
<link rel="stylesheet" href="assets/css/portal.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><circle cx='16' cy='16' r='15' fill='%230b2545'/><text x='16' y='22' font-size='15' font-family='Georgia,serif' font-weight='700' fill='%23d99a2b' text-anchor='middle'>K</text></svg>">
</head>
<body<?= $ne ? ' class="lang-ne"' : '' ?>>
<a class="skip" href="#main"><?= $ne ? 'मुख्य सामग्रीमा जानुहोस्' : 'Skip to main content' ?></a>

<div class="topbar">
  <div class="wrap">
    <div class="topbar-meta">
      <span><?= $ne ? 'ढुंग्रेबास, कमलामाई, सिन्धुली' : 'Dhungrebas, Kamalamai, Sindhuli' ?></span>
    </div>
    <div class="topbar-meta">
      <a href="portal/index.php"><?= $ne ? 'विद्यार्थी पोर्टल' : 'Student portal' ?></a>
    </div>
  </div>
</div>

<header class="site-header">
  <div class="wrap">
    <a class="brand" href="<?= $ne ? 'ne/index.html' : 'index.html' ?>">
      <span class="crest" aria-hidden="true">KSC</span>
      <span class="brand-text">
        <span class="brand-name"><?= $ne ? 'कमला साइन्स क्याम्पस' : 'Kamala Science Campus' ?></span>
        <span class="brand-sub"><?= $ne ? 'सिन्धुली, नेपाल' : 'Sindhuli, Nepal' ?></span>
      </span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">&#9776; <?= $ne ? 'मेनु' : 'Menu' ?></button>
    <nav class="nav" id="primary-nav" aria-label="<?= $ne ? 'मुख्य मेनु' : 'Primary' ?>">
      <ul>
        <li><a href="<?= $ne ? 'ne/index.html' : 'index.html' ?>"><?= $ne ? 'गृहपृष्ठ' : 'Home' ?></a></li>
        <li><a href="<?= $ne ? 'ne/programs.html' : 'programs.html' ?>"><?= $ne ? 'बी.एस्सी.' : 'B.Sc. Program' ?></a></li>
        <li><a href="<?= $ne ? 'ne/admissions.html' : 'admissions.html' ?>"><?= $ne ? 'भर्ना' : 'Admissions' ?></a></li>
        <li><a href="notices.php" aria-current="page"><?= $ne ? 'सूचना' : 'Notices' ?></a></li>
        <li><a class="lang-switch" href="<?= e($alt) ?>" lang="<?= $ne ? 'en' : 'ne' ?>"><?= $ne ? 'English' : 'नेपाली' ?></a></li>
        <li><a class="nav-cta" href="<?= $ne ? 'ne/contact.html' : 'contact.html' ?>"><?= $ne ? 'सम्पर्क' : 'Contact' ?></a></li>
      </ul>
    </nav>
  </div>
</header>

<main id="main">
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
          <?php if ($n['source_url']): ?>
            <div class="p-form-actions">
              <a class="p-btn p-btn-ghost p-btn-sm" href="<?= e($n['source_url']) ?>"
                 target="_blank" rel="noopener noreferrer nofollow">
                <?= $ne ? 'आधिकारिक सूचना हेर्नुहोस्' : 'View the official notice' ?> ↗
              </a>
            </div>
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

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-bottom" style="border:0;">
      <span>&copy; <?= localize_digits(date('Y')) ?> <?= $ne ? 'कमला साइन्स क्याम्पस' : 'Kamala Science Campus' ?></span>
      <a href="<?= $ne ? 'ne/index.html' : 'index.html' ?>"><?= $ne ? 'मुख्य वेबसाइट' : 'Main site' ?></a>
    </div>
  </div>
</footer>
<script src="assets/js/main.js"></script>
</body>
</html>
