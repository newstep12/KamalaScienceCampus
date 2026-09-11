<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';

/**
 * Page chrome. Every portal page calls layout_head() then layout_foot().
 *
 * $opts: title, nav (array of [href,label,key]), active (key), wide (bool)
 */
function layout_head(array $opts = []): void
{
    $user   = current_user();
    $lang   = current_lang();
    $title  = $opts['title'] ?? t('portal');
    $active = $opts['active'] ?? '';
    $nav    = $opts['nav'] ?? default_nav($user);
    $other  = $lang === 'en' ? 'ne' : 'en';

    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    ?>
<!doctype html>
<html lang="<?= e($lang) ?>"<?= is_nepali() ? ' class="ne"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · <?= te('campus_name') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&family=Noto+Sans+Devanagari:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(portal_url('/../assets/css/styles.css')) ?>">
<link rel="stylesheet" href="<?= e(portal_url('/../assets/css/portal.css')) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><circle cx='16' cy='16' r='15' fill='%230b2545'/><text x='16' y='22' font-size='15' font-family='Georgia,serif' font-weight='700' fill='%23d99a2b' text-anchor='middle'>K</text></svg>">
</head>
<body class="portal<?= is_nepali() ? ' lang-ne' : '' ?>">
<a class="skip" href="#main"><?= te('nav_overview') ?></a>

<header class="p-header">
  <div class="p-header-inner">
    <a class="p-brand" href="<?= e($user ? home_for($user) : portal_url('/index.php')) ?>">
      <span class="crest" aria-hidden="true">KSC</span>
      <span class="p-brand-text">
        <span class="p-brand-name"><?= te('campus_name') ?></span>
        <span class="p-brand-sub"><?= te('portal') ?></span>
      </span>
    </a>

    <?php if ($nav): ?>
      <button class="p-nav-toggle" type="button" aria-expanded="false" aria-controls="p-nav">☰</button>
      <nav class="p-nav" id="p-nav" aria-label="<?= te('menu') ?>">
        <ul>
          <?php foreach ($nav as $item): ?>
            <li><a href="<?= e($item['href']) ?>"<?= $active === $item['key'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>

    <div class="p-header-right">
      <a class="p-lang" href="<?= e(lang_switch_url($other)) ?>" title="<?= te('language') ?>">
        <?= e(LANGUAGES[$other]) ?>
      </a>
      <?php if ($user): ?>
        <div class="p-user">
          <span class="p-avatar" aria-hidden="true"><?= e(initials($user['full_name'])) ?></span>
          <span class="p-user-meta">
            <span class="p-user-name"><?= e(display_name($user)) ?></span>
            <span class="p-user-role"><?= e(role_label($user)) ?></span>
          </span>
        </div>
        <a class="p-signout" href="<?= e(portal_url('/logout.php')) ?>"><?= te('sign_out') ?></a>
      <?php else: ?>
        <a class="p-signout" href="<?= e(portal_url('/../index.html')) ?>"><?= te('back_to_site') ?></a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main id="main" class="p-main<?= !empty($opts['wide']) ? ' wide' : '' ?>">
<?php foreach (take_flashes() as $f): ?>
  <div class="p-flash p-flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
<?php endforeach; ?>
<?php
}

function layout_foot(): void
{
    ?>
</main>
<footer class="p-footer">
  <span>© <?= localize_digits(date('Y')) ?> <?= te('campus_name') ?></span>
  <a href="<?= e(portal_url('/../index.html')) ?>"><?= te('back_to_site') ?></a>
</footer>
<script>
(function () {
  var t = document.querySelector('.p-nav-toggle'), n = document.querySelector('.p-nav');
  if (t && n) {
    t.addEventListener('click', function () {
      var open = n.classList.toggle('open');
      t.setAttribute('aria-expanded', open ? 'true' : 'false');
      t.textContent = open ? '✕' : '☰';
    });
  }
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) { e.preventDefault(); }
    });
  });
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-toggle-password'));
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      btn.classList.toggle('on', input.type === 'text');
    });
  });
})();
</script>
</body>
</html>
<?php
}

function default_nav(?array $user): array
{
    if (!$user) {
        return [];
    }
    if ($user['role'] === ROLE_ADMIN) {
        return [
            ['key' => 'home',     'href' => portal_url('/admin/index.php'),    'label' => t('nav_dashboard')],
            ['key' => 'approvals','href' => portal_url('/admin/approvals.php'),'label' => t('nav_approvals')],
            ['key' => 'users',    'href' => portal_url('/admin/users.php'),    'label' => t('nav_users')],
            ['key' => 'courses',  'href' => portal_url('/admin/courses.php'),  'label' => t('nav_manage_courses')],
            ['key' => 'notices',  'href' => portal_url('/admin/notices.php'),  'label' => t('nav_manage_notices')],
        ];
    }
    if ($user['role'] === ROLE_LECTURER) {
        return [
            ['key' => 'home',    'href' => portal_url('/lecturer/index.php'),  'label' => t('nav_dashboard')],
            ['key' => 'courses', 'href' => portal_url('/lecturer/index.php'),  'label' => t('nav_courses')],
            ['key' => 'notices', 'href' => portal_url('/student/notices.php'), 'label' => t('nav_notices')],
            ['key' => 'profile', 'href' => portal_url('/student/portfolio.php'),'label' => t('nav_portfolio')],
        ];
    }
    return [
        ['key' => 'home',      'href' => portal_url('/student/index.php'),     'label' => t('nav_overview')],
        ['key' => 'portfolio', 'href' => portal_url('/student/portfolio.php'), 'label' => t('nav_portfolio')],
        ['key' => 'courses',   'href' => portal_url('/student/courses.php'),   'label' => t('nav_courses')],
        ['key' => 'notices',   'href' => portal_url('/student/notices.php'),   'label' => t('nav_notices')],
    ];
}

function display_name(array $user): string
{
    if (is_nepali() && !empty($user['full_name_ne'])) {
        return $user['full_name_ne'];
    }
    return $user['full_name'];
}

function role_label(array $user): string
{
    $base = t('role_' . $user['role']);
    if ($user['role'] === ROLE_STUDENT && !empty($user['year_level'])) {
        return $base . ' · ' . year_label((int) $user['year_level']);
    }
    return $base;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}
