<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/idcard.php';

/**
 * The printable identity card. One page for every role: students carry their
 * year and symbol number, staff the title the campus office has set for them.
 *
 * An administrator can print anyone's card (?user=) so the office can issue a
 * card for someone without an account of their own to hand; everybody else
 * only ever sees their own.
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

$own        = (int) $holder['id'] === (int) $viewer['id'];
$card       = id_card_settings();
$missing    = id_card_missing($holder);
$photo      = photo_src($holder);
$signature  = signature_src();
$chiefName  = id_card_chief_name($card);
$chiefTitle = designation_label($card['chief_title'] ?: 'campus_chief');
$names      = campus_names();
$issuedOn   = $holder['approved_at'] ?: $holder['created_at'];
$isStudent  = $holder['role'] === ROLE_STUDENT;

/** One label/value line on the card. Blank values print as a rule to write on. */
$row = function (string $label, ?string $value): void {
    ?>
    <div class="idc-row">
      <dt><?= e($label) ?></dt>
      <dd<?= ($value === null || $value === '') ? ' class="blank"' : '' ?>><?= e($value ?? '') ?></dd>
    </div>
    <?php
};

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

<?php if (!$signature): ?>
  <div class="p-flash p-flash-info p-noprint"><?= te('id_card_no_signature') ?></div>
<?php endif; ?>

<div class="p-noprint" style="margin-bottom:22px;">
  <button class="p-btn p-btn-primary" type="button" data-print><?= te('id_card_print') ?></button>
  <?php if (!$own): ?>
    <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/admin/users.php')) ?>"><?= te('manage_people') ?></a>
  <?php endif; ?>
  <p class="hint" style="margin-top:10px;max-width:56ch;"><?= te('id_card_print_hint') ?></p>
</div>

<div class="idc-sheet">

  <!-- ------------------------------------------------------------- front -- -->
  <figure class="idc-holder">
    <div class="idc idc-front">
      <header class="idc-top">
        <img class="idc-crest" src="<?= e(portal_url('/../assets/img/logo-192.png')) ?>" alt="" width="60" height="60">
        <div class="idc-top-text">
          <span class="idc-campus-ne"><?= e($names['ne']) ?></span>
          <span class="idc-campus-en"><?= e($names['en']) ?></span>
          <span class="idc-place"><?= te('campus_place') ?></span>
        </div>
      </header>

      <div class="idc-band"><?= te('id_card_heading') ?></div>

      <div class="idc-body">
        <div class="idc-photo<?= $photo ? '' : ' empty' ?>">
          <?php if ($photo): ?>
            <img src="<?= e($photo) ?>" alt="">
          <?php else: ?>
            <span><?= te('id_card_photo_here') ?></span>
          <?php endif; ?>
        </div>

        <div class="idc-name"><?= e($holder['full_name']) ?></div>
        <?php if (!empty($holder['full_name_ne'])): ?>
          <div class="idc-name-ne"><?= e($holder['full_name_ne']) ?></div>
        <?php endif; ?>
        <div class="idc-role"><?= e(id_card_role_line($holder)) ?></div>

        <dl class="idc-rows">
          <?php if ($isStudent): ?>
            <?php $row(t('id_card_year'), $holder['year_level'] ? year_label((int) $holder['year_level']) : null); ?>
            <?php $row(t('id_card_symbol'), $holder['symbol_no'] ?: null); ?>
          <?php else: ?>
            <?php $row(t('phone'), $holder['phone'] ? localize_digits($holder['phone']) : null); ?>
          <?php endif; ?>
          <?php $row(t('date_of_birth'), $holder['date_of_birth'] ? format_date($holder['date_of_birth']) : null); ?>
          <?php $row(t('address'), $holder['address'] ?: null); ?>
        </dl>
      </div>

      <footer class="idc-foot">
        <div class="idc-cardno">
          <span class="idc-cardno-label"><?= te('id_card_no') ?></span>
          <span class="idc-cardno-value"><?= e(id_card_number($holder)) ?></span>
        </div>
        <div class="idc-sig">
          <?php if ($signature): ?>
            <img class="idc-sig-img" src="<?= e($signature) ?>" alt="">
          <?php else: ?>
            <span class="idc-sig-img"></span>
          <?php endif; ?>
          <span class="idc-sig-rule"></span>
          <?php if ($chiefName !== ''): ?>
            <span class="idc-sig-name"><?= e($chiefName) ?></span>
          <?php endif; ?>
          <span class="idc-sig-title"><?= e($chiefTitle) ?></span>
        </div>
      </footer>
    </div>
    <figcaption class="p-noprint"><?= te('id_card_front') ?></figcaption>
  </figure>

  <!-- -------------------------------------------------------------- back -- -->
  <figure class="idc-holder">
    <div class="idc idc-back">
      <div class="idc-band"><?= te('id_card_heading') ?></div>
      <div class="idc-affil"><?= te('campus_affiliation') ?></div>

      <div class="idc-body">
        <dl class="idc-rows">
          <?php $row(t('id_card_issued'), format_date($issuedOn)); ?>
          <?php $row(t('id_card_valid'), $card['valid_until'] ? format_date($card['valid_until']) : null); ?>
          <?php if ($card['session']): ?>
            <?php $row(t('id_card_session'), localize_digits((string) $card['session'])); ?>
          <?php endif; ?>
          <?php $row(t('id_card_no'), id_card_number($holder)); ?>
          <?php /* Nobody records these, so they print as rules to fill in by hand. */ ?>
          <?php $row(t('id_card_blood'), null); ?>
          <?php $row(t('id_card_emergency'), null); ?>
        </dl>

        <div class="idc-terms">
          <h4><?= te('id_card_conditions') ?></h4>
          <ul>
            <?php foreach (explode("\n", t('id_card_terms')) as $line): ?>
              <li><?= e(trim($line)) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="idc-holder-sig">
          <span class="idc-sig-rule"></span>
          <span class="idc-sig-title"><?= te('id_card_holder_sig') ?></span>
        </div>
      </div>

      <footer class="idc-back-foot">
        <strong><?= te('id_card_return') ?></strong>
        <span><?= e($names['en']) ?> · <?= te('campus_place_full') ?></span>
        <span><?= e(localize_digits('+977-47-520203')) ?> · admin@kamalasciencecampus.edu.np</span>
        <span>kamalasciencecampus.edu.np</span>
      </footer>
    </div>
    <figcaption class="p-noprint"><?= te('id_card_back') ?></figcaption>
  </figure>
</div>

<script>
document.querySelectorAll('[data-print]').forEach(function (b) {
  b.addEventListener('click', function () { window.print(); });
});
</script>
<?php layout_foot(); ?>
