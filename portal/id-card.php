<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/idcard-view.php';

/**
 * The printable identity card. One page for every role: students carry their
 * year and symbol number, staff the title the campus office has set for them.
 *
 * An administrator can print anyone's card (?user=) so the office can issue a
 * card for someone without an account of their own to hand; everybody else
 * only ever sees their own.
 *
 * The colourway and the orientation are campus-wide, set under Admin → System
 * → Identity cards, so every card issued looks like the same document. What
 * is chosen here is only how this print run comes out: which faces, and which
 * printer they are going to.
 */

$viewer = require_login();
$holder = $viewer;

$requested = (int) ($_GET['user'] ?? 0);
if ($requested > 0 && $requested !== (int) $viewer['id']) {
    if ($viewer['role'] !== ROLE_ADMIN) {
        http_response_code(403);
        require __DIR__ . '/403.php';
        exit;
    }
    $holder = one('SELECT * FROM users WHERE id = ? LIMIT 1', [$requested]);
    if (!$holder) {
        flash('error', t('notfound_body'));
        header('Location: ' . portal_url('/admin/users.php'));
        exit;
    }
}

$own     = (int) $holder['id'] === (int) $viewer['id'];
$ctx     = id_card_context($holder, $viewer);
$card    = $ctx['card'];
$missing = id_card_missing($holder);
$sides   = id_card_sides($_GET['sides'] ?? $card['sides']);
$target  = in_array($_GET['print'] ?? '', ['sheet', 'card'], true) ? $_GET['print'] : 'sheet';

layout_head([
    'title'  => $own ? t('id_card_title') : t('id_card_for', $holder['full_name']),
    'active' => 'idcard',
]);
?>
<div class="p-page-head p-noprint">
  <h1><?= $own ? te('id_card_title') : e(t('id_card_for', display_name($holder))) ?></h1>
  <p><?= te('id_card_intro') ?></p>
</div>

<?php if ($missing): ?>
  <div class="p-flash p-flash-info p-noprint">
    <?= e(t($own ? 'id_card_missing' : 'id_card_missing_other', join_list($missing))) ?>
    <?php if ($own): ?>
      <a href="<?= e(portal_url('/student/portfolio.php')) ?>"><?= te('id_card_missing_link') ?></a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (!$ctx['signature']): ?>
  <div class="p-flash p-flash-info p-noprint">
    <?php if (signature_withheld('id_card', $viewer)): ?>
      <?php /* There is a signature; the office simply does not release it this
               far. Say so, rather than letting the holder think one is missing
               and ask the office to upload what it has already uploaded. */ ?>
      <?= te('id_card_signature_held') ?>
    <?php else: ?>
      <?= te('id_card_no_signature') ?>
      <?php if ($viewer['role'] === ROLE_ADMIN): ?>
        <a href="<?= e(portal_url('/admin/signatures.php')) ?>"><?= te('signatures_title') ?></a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<form class="idc-controls p-noprint" method="get">
  <?php if (!$own): ?>
    <input type="hidden" name="user" value="<?= (int) $holder['id'] ?>">
  <?php endif; ?>

  <fieldset>
    <legend><?= te('id_card_sides') ?></legend>
    <div class="idc-choices">
      <?php foreach (['both' => 'id_card_sides_both', 'front' => 'id_card_sides_front'] as $v => $key): ?>
        <label class="idc-choice">
          <input type="radio" name="sides" value="<?= e($v) ?>" <?= $sides === $v ? 'checked' : '' ?>>
          <span><?= te($key) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <fieldset>
    <legend><?= te('id_card_print_target') ?></legend>
    <div class="idc-choices">
      <?php foreach (['sheet' => 'id_card_target_sheet', 'card' => 'id_card_target_card'] as $v => $key): ?>
        <label class="idc-choice">
          <input type="radio" name="print" value="<?= e($v) ?>" <?= $target === $v ? 'checked' : '' ?>>
          <span><?= te($key) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="idc-controls-actions">
    <button class="p-btn p-btn-primary" type="button" data-print><?= te('id_card_print') ?></button>
    <noscript><button class="p-btn p-btn-ghost" type="submit"><?= te('id_card_apply') ?></button></noscript>
    <?php if (!$own): ?>
      <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/admin/users.php')) ?>"><?= te('manage_people') ?></a>
    <?php endif; ?>
  </div>
</form>

<p class="hint p-noprint idc-print-hint" data-hint-sheet="<?= te('id_card_hint_sheet') ?>"
   data-hint-card="<?= te('id_card_hint_card') ?>"><?= te('id_card_hint_sheet') ?></p>

<?php if ($viewer['role'] === ROLE_ADMIN): ?>
  <p class="hint p-noprint" style="margin-bottom:22px;">
    <?= te('id_card_design_note') ?>
    <a href="<?= e(portal_url('/admin/system.php#id-cards')) ?>"><?= te('id_card_design_link') ?></a>
  </p>
<?php endif; ?>

<div class="idc-sheet" data-sides="<?= e($sides) ?>" data-print-target="<?= e($target) ?>"
     data-orientation="<?= e($ctx['orientation']) ?>">
  <figure class="idc-holder">
    <?php id_card_face($holder, $ctx, 'front'); ?>
    <figcaption class="p-noprint"><?= te('id_card_front') ?></figcaption>
  </figure>

  <figure class="idc-holder idc-holder-back">
    <?php id_card_face($holder, $ctx, 'back'); ?>
    <figcaption class="p-noprint"><?= te('id_card_back') ?></figcaption>
  </figure>
</div>

<?php
// Rendered server-side as well as swapped by the script, so the page box is
// right even with JavaScript off, when the choice arrives as ?print=.
$pageRule = $target === 'card'
    ? ($ctx['orientation'] === 'landscape'
        ? '@page { size: 85.6mm 53.98mm; margin: 0; }'
        : '@page { size: 53.98mm 85.6mm; margin: 0; }')
    : '@page { size: A4; margin: 12mm; }';
?>
<style id="idc-page-rule"><?= $pageRule ?></style>

<script>
(function () {
  var form  = document.querySelector('.idc-controls');
  var sheet = document.querySelector('.idc-sheet');
  var rule  = document.getElementById('idc-page-rule');
  var hint  = document.querySelector('.idc-print-hint');
  if (!form || !sheet) return;

  // Exact page boxes, so a card printer feeds one CR80 blank per card and an
  // A4 printer keeps a margin to cut inside. @page cannot be written as a
  // normal selector, so the rule is swapped wholesale.
  var PAGES = {
    'sheet':             '@page { size: A4; margin: 12mm; }',
    'card-portrait':     '@page { size: 53.98mm 85.6mm; margin: 0; }',
    'card-landscape':    '@page { size: 85.6mm 53.98mm; margin: 0; }'
  };

  function apply() {
    var data = new FormData(form);
    var sides = data.get('sides') || 'both';
    var target = data.get('print') || 'sheet';
    var orientation = sheet.querySelector('.idc').getAttribute('data-orientation') || 'portrait';

    sheet.setAttribute('data-sides', sides);
    sheet.setAttribute('data-print-target', target);
    rule.textContent = target === 'card' ? PAGES['card-' + orientation] : PAGES.sheet;
    if (hint) hint.textContent = hint.getAttribute('data-hint-' + target);

    // Keep the address in step, so a reload or a shared link prints the same.
    if (window.history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.set('sides', sides);
      url.searchParams.set('print', target);
      window.history.replaceState({}, '', url);
    }
  }

  form.addEventListener('change', apply);
  apply();

  document.querySelectorAll('[data-print]').forEach(function (b) {
    b.addEventListener('click', function () { window.print(); });
  });
})();
</script>
<?php layout_foot(); ?>
