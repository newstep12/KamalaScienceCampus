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
 * One signature over its rule, with the signer's name and title beneath. An
 * image the office has not released prints as an empty space above the rule,
 * to be signed by hand; the name and title print either way, so the card
 * reads the same whether or not the signature is on it.
 */
function id_card_signature(?string $img, string $name, string $title): void
{
    ?>
    <div class="idc-sig">
      <?php if ($img): ?>
        <img class="idc-sig-img" src="<?= e($img) ?>" alt="">
      <?php else: ?>
        <span class="idc-sig-img"></span>
      <?php endif; ?>
      <span class="idc-sig-rule"></span>
      <?php if ($name !== ''): ?>
        <span class="idc-sig-name"><?= e($name) ?></span>
      <?php endif; ?>
      <span class="idc-sig-title"><?= e($title) ?></span>
    </div>
    <?php
}

/**
 * One face of the card. $side is 'front' or 'back'; $ctx comes from
 * id_card_context(), optionally with 'theme' and 'orientation' overridden for
 * a preview. $holderNames is id_card_names($holder) when the caller already
 * has it — a card page draws two faces and asks the same question in its own
 * text — and is worked out here when it does not.
 */
function id_card_face(array $holder, array $ctx, string $side = 'front', ?array $holderNames = null): void
{
    $theme  = id_card_theme($ctx['theme'] ?? null);
    $orient = id_card_orientation($ctx['orientation'] ?? null);
    $card   = $ctx['card'];
    $names  = $ctx['names'];
    ?>
    <div class="idc idc-<?= e($side) ?>" data-theme="<?= e($theme) ?>" data-orientation="<?= e($orient) ?>">
    <?php if ($side === 'front'): ?>
      <header class="idc-top">
        <img class="idc-crest" src="<?= e(school_logo_url(true)) ?>" alt="" width="60" height="60">
        <div class="idc-top-text">
          <span class="idc-campus-ne"><?= e($names['ne']) ?></span>
          <span class="idc-campus-en"><?= e($names['en']) ?></span>
          <span class="idc-place"><?= e($ctx['school']['place']) ?></span>
        </div>
      </header>

      <?php /* The card number rides in the band, beside the words it belongs
               to, which frees the foot for two signatures without the front
               growing any taller — it is already full. */ ?>
      <div class="idc-band">
        <?= te('id_card_heading') ?>
        <span class="idc-band-no">· <?= e(id_card_number($holder)) ?></span>
      </div>

      <div class="idc-body">
        <div class="idc-photo<?= $ctx['photo'] ? '' : ' empty' ?>">
          <?php if ($ctx['photo']): ?>
            <img src="<?= e($ctx['photo']) ?>" alt="">
          <?php else: ?>
            <span><?= te('id_card_photo_here') ?></span>
          <?php endif; ?>
        </div>

        <div class="idc-detail">
          <?php
          /**
           * Two lines, one per script — id_card_names() says which name is in
           * which, whichever column the campus record keeps it in, and writes
           * the Devanagari one from the English where the record has none.
           *
           * A holder with only a Devanagari name gets it on the line the
           * English name would have had, rather than in the smaller type
           * underneath an empty space: the big line is the name, and a card
           * that has one name prints it as the name. It takes the Devanagari
           * face with it, because the line above is set in the card's Latin
           * one and would otherwise fall back to whatever the system offers.
           */
          // not $names: that is the campus's.
          //
          // Passed in where the caller already has them, worked out here when
          // it does not — and never lifted out of $ctx on the quiet. A guard
          // that took the context's names only when its holder id matched
          // made this function look safe to draw a batch of cards from one
          // context, and it is not: the photograph, the holder's signature
          // and the issue date on this same face all come from $ctx and none
          // of them is checked. One field quietly right among five quietly
          // wrong is worse than the honest rule, which is that a context
          // belongs to one holder. An argument says so where a lookup did
          // not.
          $holderNames ??= id_card_names($holder);
          $primary = $holderNames['latin'] ?? $holderNames['deva'];
          $second  = $holderNames['latin'] !== null ? $holderNames['deva'] : null;
          ?>
          <div class="idc-name<?= $holderNames['latin'] === null ? ' idc-name-deva' : '' ?>"><?= e((string) $primary) ?></div>
          <?php if ($second !== null): ?>
            <div class="idc-name-ne"><?= e($second) ?></div>
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
           * A school student's card: the class and roll number that identify
           * them within the school, then the guardian and the number to ring
           * — the detail a school card exists to carry — then date of birth
           * and address. The government numbers a staff card carries mean
           * nothing on a sixteen-year-old's card, so they are not here.
           *
           * Six is the design's limit — the front is measured against the
           * fullest card it accepts.
           */
          ?>
          <dl class="idc-rows">
            <?php if ($holder['role'] === ROLE_STUDENT): ?>
              <?php id_card_row(t('class'), $holder['class_level'] ? class_with_section((int) $holder['class_level'], $holder['section'] ?? null) : null); ?>
              <?php id_card_row(t('id_card_roll'), !empty($holder['roll_no']) ? localize_digits($holder['roll_no']) : null); ?>
              <?php id_card_row(t('id_card_guardian'), $holder['guardian_name'] ?? null); ?>
              <?php id_card_row(t('id_card_guardian_phone'), !empty($holder['guardian_phone']) ? localize_digits($holder['guardian_phone']) : null); ?>
            <?php else: ?>
              <?php /* id_card_role_line(), not the designation on its own: the
                       column is optional and often unset, and the line under
                       the name no longer covers for it. A card with nothing at
                       all where the holder's position goes is worse than one
                       naming the role the account holds. */ ?>
              <?php id_card_row(t('designation'), id_card_role_line($holder)); ?>
              <?php id_card_row(t('id_card_nid'), !empty($holder['national_id']) ? localize_digits($holder['national_id']) : null); ?>
              <?php id_card_row(t('id_card_pan'), !empty($holder['pan_no']) ? localize_digits($holder['pan_no']) : null); ?>
            <?php endif; ?>
            <?php id_card_row(t('date_of_birth'), $holder['date_of_birth'] ? format_date($holder['date_of_birth']) : null); ?>
            <?php id_card_row(t('address'), $holder['address'] ?: null); ?>
            <?php if ($holder['role'] !== ROLE_STUDENT): ?>
              <?php id_card_row(t('phone'), $holder['phone'] ? localize_digits($holder['phone']) : null); ?>
            <?php endif; ?>
          </dl>
        </div>
      </div>

      <?php /* Two signatures: the +2 Coordinator's on the left and the
               Principal's on the right, where the head of the school signs. */ ?>
      <footer class="idc-foot idc-foot-two">
        <?php id_card_signature($ctx['coord_signature'] ?? null, (string) ($ctx['coord'] ?? ''), (string) ($ctx['coord_title'] ?? '')); ?>
        <?php id_card_signature($ctx['signature'], (string) $ctx['chief'], (string) $ctx['chief_title']); ?>
      </footer>

    <?php else: ?>
      <?php /* The seal again, faint, behind the back's text: the side a
               forger copies is the plain one. */ ?>
      <img class="idc-watermark" src="<?= e(school_logo_url(true)) ?>" alt="" aria-hidden="true">
      <div class="idc-band"><?= te('id_card_heading') ?></div>
      <?php if ($ctx['school']['affiliation'] !== ''): ?>
        <div class="idc-affil"><?= e($ctx['school']['affiliation']) ?></div>
      <?php endif; ?>

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
        <?php
        /* Only what the school office has actually set. A phone number or an
           email printed on a card has to be one somebody answers, so a blank
           setting leaves the line off rather than printing the campus's. */
        $contact = array_filter([
            $ctx['school']['phone'] ? localize_digits((string) $ctx['school']['phone']) : null,
            $ctx['school']['email'] ?: null,
        ]);
        ?>
        <div class="idc-return">
          <strong><?= te('id_card_return') ?></strong>
          <span><?= e($names['en']) ?><?= $ctx['school']['place'] !== '' ? ' · ' . e($ctx['school']['place']) : '' ?></span>
          <?php if ($contact): ?>
            <span><?= e(implode(' · ', $contact)) ?></span>
          <?php endif; ?>
          <?php if ($ctx['school']['website']): ?>
            <span><?= e($ctx['school']['website']) ?></span>
          <?php endif; ?>
        </div>
        <?php /* The school's location as a QR code, drawn by
                 assets/js/idcard-qr.js. A phone camera opens it in maps. */ ?>
        <?php if (!empty($ctx['school']['map_url'])): ?>
          <div class="idc-qr-wrap">
            <span class="idc-qr" data-qr="<?= e($ctx['school']['map_url']) ?>" role="img"
                  aria-label="<?= te('id_card_map') ?>"></span>
            <span class="idc-qr-label"><?= te('id_card_map') ?></span>
          </div>
        <?php endif; ?>
      </footer>
    <?php endif; ?>
    </div>
    <?php
}
