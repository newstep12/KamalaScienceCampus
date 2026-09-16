<?php
declare(strict_types=1);

require_once __DIR__ . '/idcard.php';

/**
 * The card itself. Kept here rather than in id-card.php because the design
 * settings on the System page preview a real card, and a preview that is not
 * the same markup is not a preview.
 */

/** One label/value line. A value nobody has filled in prints as a rule. */
function id_card_row(string $label, ?string $value): void
{
    $blank = ($value === null || $value === '');
    ?>
    <div class="idc-row">
      <dt><?= e($label) ?></dt>
      <dd<?= $blank ? ' class="blank"' : '' ?>><?= e($value ?? '') ?></dd>
    </div>
    <?php
}

/**
 * One face of the card. $side is 'front' or 'back'; $ctx comes from
 * id_card_context(), optionally with 'theme' and 'orientation' overridden for
 * a preview.
 */
function id_card_face(array $holder, array $ctx, string $side = 'front'): void
{
    $theme  = id_card_theme($ctx['theme'] ?? null);
    $orient = id_card_orientation($ctx['orientation'] ?? null);
    $card   = $ctx['card'];
    $names  = $ctx['names'];
    ?>
    <div class="idc idc-<?= e($side) ?>" data-theme="<?= e($theme) ?>" data-orientation="<?= e($orient) ?>">
    <?php if ($side === 'front'): ?>
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
        <div class="idc-photo<?= $ctx['photo'] ? '' : ' empty' ?>">
          <?php if ($ctx['photo']): ?>
            <img src="<?= e($ctx['photo']) ?>" alt="">
          <?php else: ?>
            <span><?= te('id_card_photo_here') ?></span>
          <?php endif; ?>
        </div>

        <div class="idc-detail">
          <div class="idc-name"><?= e($holder['full_name']) ?></div>
          <?php if (!empty($holder['full_name_ne'])): ?>
            <div class="idc-name-ne"><?= e($holder['full_name_ne']) ?></div>
          <?php endif; ?>
          <div class="idc-role"><?= e(id_card_role_line($holder)) ?></div>

          <dl class="idc-rows">
            <?php if ($holder['role'] === ROLE_STUDENT): ?>
              <?php id_card_row(t('id_card_year'), $holder['year_level'] ? year_label((int) $holder['year_level']) : null); ?>
              <?php id_card_row(t('id_card_symbol'), $holder['symbol_no'] ?: null); ?>
            <?php else: ?>
              <?php id_card_row(t('phone'), $holder['phone'] ? localize_digits($holder['phone']) : null); ?>
            <?php endif; ?>
            <?php id_card_row(t('date_of_birth'), $holder['date_of_birth'] ? format_date($holder['date_of_birth']) : null); ?>
            <?php id_card_row(t('address'), $holder['address'] ?: null); ?>
          </dl>
        </div>
      </div>

      <footer class="idc-foot">
        <div class="idc-cardno">
          <span class="idc-cardno-label"><?= te('id_card_no') ?></span>
          <span class="idc-cardno-value"><?= e(id_card_number($holder)) ?></span>
        </div>
        <div class="idc-sig">
          <?php if ($ctx['signature']): ?>
            <img class="idc-sig-img" src="<?= e($ctx['signature']) ?>" alt="">
          <?php else: ?>
            <span class="idc-sig-img"></span>
          <?php endif; ?>
          <span class="idc-sig-rule"></span>
          <?php if ($ctx['chief'] !== ''): ?>
            <span class="idc-sig-name"><?= e($ctx['chief']) ?></span>
          <?php endif; ?>
          <span class="idc-sig-title"><?= e($ctx['chief_title']) ?></span>
        </div>
      </footer>

    <?php else: ?>
      <div class="idc-band"><?= te('id_card_heading') ?></div>
      <div class="idc-affil"><?= te('campus_affiliation') ?></div>

      <div class="idc-body">
        <dl class="idc-rows">
          <?php id_card_row(t('id_card_issued'), format_date($ctx['issued'])); ?>
          <?php id_card_row(t('id_card_valid'), $card['valid_until'] ? format_date($card['valid_until']) : null); ?>
          <?php if ($card['session']): ?>
            <?php id_card_row(t('id_card_session'), localize_digits((string) $card['session'])); ?>
          <?php endif; ?>
          <?php id_card_row(t('id_card_no'), id_card_number($holder)); ?>
          <?php /* Nobody records these, so they print as rules to fill in by hand. */ ?>
          <?php id_card_row(t('id_card_blood'), null); ?>
          <?php id_card_row(t('id_card_emergency'), null); ?>
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
    <?php endif; ?>
    </div>
    <?php
}
