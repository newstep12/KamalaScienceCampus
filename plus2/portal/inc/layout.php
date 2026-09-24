<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/idcard.php';

/**
 * Page chrome. Every portal page calls layout_head() then layout_foot().
 *
 * $opts: title, nav (array of [href,label,key]), active (key), wide (bool)
 *
 * The bar across the top carries only what belongs to the campus and to the
 * person: the crest and name, the language switch, who is signed in. The
 * sections live in a rail down the left, one under the other, because there
 * are eight of them for an administrator and eight tabs across a header is a
 * header that wraps onto a second row and still cannot be read at a glance.
 * A rail also has somewhere to grow.
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
    header('X-Frame-Options: SAMEORIGIN');
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
<?php /* The site-wide stylesheet is shared with the public pages; the portal
         and identity-card styles are this portal's own copy, so a change to
         the campus portal's cards never reaches the school's, or back. */ ?>
<link rel="stylesheet" href="<?= e(portal_url('/../../assets/css/styles.css?v=' . asset_version('/../../assets/css/styles.css'))) ?>">
<link rel="stylesheet" href="<?= e(portal_url('/../assets/css/portal.css?v=' . asset_version('/../assets/css/portal.css'))) ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?= e(school_logo_url()) ?>">
<?php /* Opaque, on white: a phone fills a home-screen icon's transparent
         corners with black. */ ?>
<link rel="apple-touch-icon" href="<?= e(portal_url('/../assets/img/logo-touch.png')) ?>">
</head>
<body class="portal<?= is_nepali() ? ' lang-ne' : '' ?>">
<a class="skip" href="#main"><?= te('nav_overview') ?></a>

<header class="p-header">
  <div class="p-header-inner">
    <?php if ($nav): ?>
      <button class="p-nav-toggle" type="button" aria-expanded="false" aria-controls="p-rail"
              aria-label="<?= te('menu') ?>">☰</button>
    <?php endif; ?>
    <a class="p-brand" href="<?= e($user ? home_for($user) : portal_url('/index.php')) ?>">
      <img class="crest" src="<?= e(school_logo_url()) ?>" alt="" width="44" height="44">
      <span class="p-brand-text">
        <span class="p-brand-name"><?= te('campus_name') ?></span>
        <span class="p-brand-sub"><?= te('portal') ?></span>
      </span>
    </a>

    <div class="p-header-right">
      <a class="p-lang" href="<?= e(lang_switch_url($other)) ?>" title="<?= te('language') ?>">
        <?= e(LANGUAGES[$other]) ?>
      </a>
      <?php if ($user): ?>
        <a class="p-user" href="<?= e(portal_url('/student/portfolio.php')) ?>" title="<?= te('nav_portfolio') ?>">
          <?php if ($src = photo_src($user)): ?>
            <img class="p-avatar" src="<?= e($src) ?>" alt="" width="34" height="34">
          <?php else: ?>
            <span class="p-avatar" aria-hidden="true"><?= e(initials($user['full_name'])) ?></span>
          <?php endif; ?>
          <span class="p-user-meta">
            <span class="p-user-name"><?= e(display_name($user)) ?></span>
            <span class="p-user-role"><?= e(role_label($user)) ?></span>
          </span>
        </a>
        <a class="p-signout" href="<?= e(logout_url()) ?>"><?= te('sign_out') ?></a>
      <?php else: ?>
        <a class="p-signout" href="<?= e(portal_url('/../index.html')) ?>"><?= te('back_to_site') ?></a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php /* The shell is always here, with or without a rail inside it, so that
         layout_foot() closes exactly what layout_head() opened without either
         of them having to remember which. The sign-in and registration pages
         pass no navigation and get the single centred column. */ ?>
<div class="p-shell<?= $nav ? '' : ' p-shell-bare' ?>">
<?php if ($nav): ?>
  <aside class="p-rail" id="p-rail">
    <nav aria-label="<?= te('menu') ?>">
      <ul>
        <?php foreach ($nav as $item): ?>
          <li><a href="<?= e($item['href']) ?>"<?= $active === $item['key'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </aside>
  <?php /* Tapping outside the rail closes it on a phone. Hidden until the
           rail is open, so it never sits over the page on a desktop. */ ?>
  <div class="p-rail-scrim" hidden></div>
<?php endif; ?>

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
</div>
<footer class="p-footer">
  <span>© <?= localize_digits(date('Y')) ?> <?= te('campus_name') ?></span>
  <a href="<?= e(portal_url('/../index.html')) ?>"><?= te('back_to_site') ?></a>
</footer>
<script>
(function () {
  var toggle = document.querySelector('.p-nav-toggle');
  var rail   = document.getElementById('p-rail');
  var scrim  = document.querySelector('.p-rail-scrim');
  if (toggle && rail) {
    var setOpen = function (open) {
      rail.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.textContent = open ? '✕' : '☰';
      if (scrim) { scrim.hidden = !open; }
    };
    toggle.addEventListener('click', function () { setOpen(!rail.classList.contains('open')); });
    if (scrim) { scrim.addEventListener('click', function () { setOpen(false); }); }
    // Escape closes it, and so does following a link — otherwise the rail is
    // still over the page when the next one loads from the back/forward cache.
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { setOpen(false); } });
    rail.addEventListener('click', function (e) { if (e.target.closest('a')) { setOpen(false); } });
  }
  // Two-step confirm rather than window.confirm(): once a user ticks Chrome's
  // "prevent this page from creating additional dialogs", confirm() returns
  // false immediately and a dialog-gated button silently stops working.
  document.querySelectorAll('[data-confirm]').forEach(function (form) {
    var btn = form.querySelector('button[type="submit"], button:not([type])');
    if (!btn) return;
    var original = btn.textContent;
    var armed = false;
    var reset = function () {
      armed = false;
      btn.textContent = original;
      btn.classList.remove('armed');
    };
    form.addEventListener('submit', function (e) {
      if (armed) return;              // second click: let it through
      e.preventDefault();
      armed = true;
      btn.textContent = form.getAttribute('data-confirm-label') || 'Click again to confirm';
      btn.classList.add('armed');
      setTimeout(reset, 5000);        // disarm if they walk away
    });
    btn.addEventListener('blur', function () { if (armed) setTimeout(reset, 150); });
  });
  // Copy a value the page is handing over — a temporary password. The
  // clipboard API needs a secure context; where there is not one, the text is
  // selected instead so it can still be copied with the keyboard.
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    var target = document.getElementById(btn.getAttribute('data-copy'));
    if (!target) return;
    // Captured once. Read inside the handler, a second click within the
    // timeout captures "Copied" as the label to restore, and the button says
    // Copied for ever after.
    var label = btn.textContent;
    var timer = null;
    var said = function () {
      btn.textContent = btn.getAttribute('data-copied') || label;
      clearTimeout(timer);
      timer = setTimeout(function () { btn.textContent = label; }, 1600);
    };
    // Selecting the text is the fallback wherever the clipboard API is not
    // available — it needs a secure context — so the value can still be copied
    // with the keyboard. getSelection() is null in some embedded documents.
    var select = function () {
      var sel = window.getSelection();
      if (!sel) { return; }
      var range = document.createRange();
      range.selectNodeContents(target);
      sel.removeAllRanges();
      sel.addRange(range);
      said();
    };
    btn.addEventListener('click', function () {
      var text = (target.textContent || '').trim();
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(said, select);
      } else {
        select();
      }
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

/**
 * Cache-busting suffix for a stylesheet: its modification time, which a deploy
 * updates. Browsers keep CSS for 7 days (.htaccess), so without it a change
 * would not reach returning visitors for a week.
 */
function asset_version(string $relToPortal): string
{
    return (string) (@filemtime(__DIR__ . '/..' . $relToPortal) ?: 0);
}

function default_nav(?array $user): array
{
    if (!$user) {
        return [];
    }
    if ($user['role'] === ROLE_ADMIN) {
        return [
            ['key' => 'home',      'href' => portal_url('/admin/index.php'),      'label' => t('nav_dashboard')],
            ['key' => 'approvals', 'href' => portal_url('/admin/approvals.php'),  'label' => t('nav_approvals')],
            ['key' => 'users',     'href' => portal_url('/admin/users.php'),      'label' => t('nav_users')],
            ['key' => 'signatures','href' => portal_url('/admin/signatures.php'), 'label' => t('nav_signatures')],
            ['key' => 'system',    'href' => portal_url('/admin/system.php'),     'label' => t('nav_system')],
            ['key' => 'portfolio', 'href' => portal_url('/student/portfolio.php'),'label' => t('nav_portfolio')],
            ['key' => 'idcard',    'href' => portal_url('/id-card.php'),          'label' => t('nav_id_card')],
        ];
    }
    return [
        ['key' => 'home',      'href' => portal_url('/student/index.php'),     'label' => t('nav_overview')],
        ['key' => 'portfolio', 'href' => portal_url('/student/portfolio.php'), 'label' => t('nav_portfolio')],
        ['key' => 'idcard',    'href' => portal_url('/id-card.php'),           'label' => t('nav_id_card')],
    ];
}

/**
 * The name to show this viewer: the script they are reading the portal in,
 * falling back to the one the person actually has.
 *
 * By script rather than by column, the same way the identity card resolves
 * its two lines — see name_by_script(). Reading full_name_ne as "the Nepali
 * one" showed a student who registered in Nepali, and whose Devanagari name
 * therefore sits in full_name, their English name in the Nepali portal and
 * their Devanagari one in the English portal: exactly backwards, and on every
 * page that greets them by name.
 */
function display_name(array $user): string
{
    $names = name_by_script($user);
    return is_nepali()
        ? ($names['deva'] ?? $names['latin'] ?? '')
        : ($names['latin'] ?? $names['deva'] ?? '');
}

function role_label(array $user): string
{
    $base = t('role_' . $user['role']);
    if ($user['role'] === ROLE_STUDENT) {
        return !empty($user['class_level']) ? $base . ' · ' . class_label((int) $user['class_level']) : $base;
    }
    // Staff go by the title the school office has set — Principal, Coordinator
    // — rather than the blanket "Teacher" the role column stores.
    return designation_label($user['designation'] ?? null) ?: $base;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}
