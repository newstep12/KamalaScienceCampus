<?php
declare(strict_types=1);

require_once __DIR__ . '/idcard.php';

/**
 * The card itself. Kept here rather than in id-card.php because the design
 * settings on the System page preview a real card, and a preview that is not
 * the same markup is not a preview.
 */

/**
 * One label/value line. A value nobody has filled in prints as a rule.
 *
 * The rule carries a zero-width space, which is not decoration. The row aligns
 * its label and its value on their baselines, and a box with nothing in it has
 * no baseline for the browser to use: it takes the bottom edge instead, which
 * drops the rule below where a line of text would have sat and makes every
 * blank row taller than a filled one. On a card whose body is overflow: hidden
 * that difference is enough to push the last row off a full front. One
 * invisible character gives the box a line to sit on.
 */
function id_card_row(string $label, ?string $value): void
{
    $blank = ($value === null || $value === '');
    ?>
    <div class="idc-row">
      <dt><?= e($label) ?></dt>
      <dd<?= $blank ? ' class="blank"' : '' ?>><?= $blank ? '&#8203;' : e($value) ?></dd>
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
          <?php
          /**
           * The line under the name says what this person is — but only when
           * no row below is already saying it.
           *
           * A staff card now carries the office title as a labelled row, so
           * printing it here as well would put Assistant Professor on the card
           * twice, once with a label and once without. A student card has no
           * such row, so the line is what identifies them.
           */
          ?>
          <?php if ($holder['role'] === ROLE_STUDENT): ?>
            <div class="idc-role"><?= e(id_card_role_line($holder)) ?></div>
          <?php endif; ?>

          <?php
          /**
           * Six rows, in the order the office reads them off the card.
           *
           * The first two are what identifies the holder within the campus:
           * the office title for staff, the year and symbol number for a
           * student, who has no designation and whose symbol number is the one
           * detail an examination hall asks for. Then the two government
           * numbers, then the details that are true of the person rather than
           * of the enrolment.
           *
           * Six is the design's limit — the front is measured against the
           * fullest card it accepts — which is why the phone number sits on a
           * staff card and not on a student's, where the year and the symbol
           * number have the space it would need.
           */
          ?>
          <dl class="idc-rows">
            <?php if ($holder['role'] === ROLE_STUDENT): ?>
              <?php id_card_row(t('id_card_year'), $holder['year_level'] ? year_label((int) $holder['year_level']) : null); ?>
              <?php id_card_row(t('id_card_symbol'), $holder['symbol_no'] ?: null); ?>
            <?php else: ?>
              <?php /* id_card_role_line(), not the designation on its own: the
                       column is optional and often unset, and the line under
                       the name no longer covers for it. A card with nothing at
                       all where the holder's position goes is worse than one
                       naming the role the account holds. */ ?>
              <?php id_card_row(t('designation'), id_card_role_line($holder)); ?>
            <?php endif; ?>
            <?php id_card_row(t('id_card_nid'), !empty($holder['national_id']) ? localize_digits($holder['national_id']) : null); ?>
            <?php id_card_row(t('id_card_pan'), !empty($holder['pan_no']) ? localize_digits($holder['pan_no']) : null); ?>
            <?php id_card_row(t('date_of_birth'), $holder['date_of_birth'] ? format_date($holder['date_of_birth']) : null); ?>
            <?php id_card_row(t('address'), $holder['address'] ?: null); ?>
            <?php if ($holder['role'] !== ROLE_STUDENT): ?>
              <?php id_card_row(t('phone'), $holder['phone'] ? localize_digits($holder['phone']) : null); ?>
            <?php endif; ?>
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
          <?php
          /**
           * How long the card is valid is a student's question: a programme
           * ends, and a card issued against it expires with it. A member of
           * staff holds theirs for as long as they hold the post, so the row
           * was either a date the office had to keep moving or an empty rule,
           * and neither says anything true about a staff card.
           */
          ?>
          <?php if ($holder['role'] === ROLE_STUDENT): ?>
            <?php id_card_row(t('id_card_valid'), $card['valid_until'] ? format_date($card['valid_until']) : null); ?>
          <?php endif; ?>
          <?php if ($card['session']): ?>
            <?php id_card_row(t('id_card_session'), localize_digits((string) $card['session'])); ?>
          <?php endif; ?>
          <?php id_card_row(t('id_card_no'), id_card_number($holder)); ?>
          <?php /* The blood group is back, because the portfolio now asks
                   for it. The emergency contact is not: nothing collects one,
                   and a row nobody can fill in is what took both of them off
                   this card in the first place. */ ?>
          <?php id_card_row(t('id_card_blood'), $holder['blood_group'] ?? null); ?>
        </dl>

        <div class="idc-terms">
          <h4><?= te('id_card_conditions') ?></h4>
          <ul>
            <?php foreach (explode("\n", t('id_card_terms')) as $line): ?>
              <li><?= e(trim($line)) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <?php /* The holder signs their own card. An uploaded signature prints
                 above the line; without one the line prints alone, to be
                 signed by hand. */ ?>
        <div class="idc-holder-sig">
          <?php if ($ctx['holder_sig'] ?? null): ?>
            <img class="idc-sig-img" src="<?= e($ctx['holder_sig']) ?>" alt="">
          <?php else: ?>
            <span class="idc-sig-img"></span>
          <?php endif; ?>
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
