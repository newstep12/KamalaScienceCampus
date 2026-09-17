<?php
declare(strict_types=1);

require_once __DIR__ . '/documents.php';

/**
 * The document itself, on campus letterhead. Kept apart from the page that
 * issues it for the same reason the identity card is: what an administrator
 * sees before printing has to be the document, not a drawing of it.
 *
 * The signature is resolved when the page is rendered rather than read back
 * from the row, so a reprint follows the office's decision as it stands today:
 * withdraw a signature and reprints of an old certificate fall back to the
 * blank line, while the row still records which signature was applied when the
 * document was first issued.
 */
function document_letter(array $doc, ?array $subject, ?array $viewer = null): void
{
    $viewer = $viewer ?? current_user();
    $sig    = applied_signature('document', $viewer);
    $names  = campus_names();
    $card   = id_card_settings();
    $signer = $sig ?: signature_for_use('document');
    $title  = designation_label(($signer['owner_title'] ?? '') ?: ($card['chief_title'] ?: 'campus_chief'));
    $name   = $signer ? signature_owner_name($signer) : id_card_chief_name($card);
    ?>
    <article class="p-doc">
      <header class="p-doc-head">
        <img class="p-doc-crest" src="<?= e(portal_url('/../assets/img/logo-192.png')) ?>" alt="" width="84" height="84">
        <div class="p-doc-head-text">
          <span class="p-doc-campus-ne"><?= e($names['ne']) ?></span>
          <span class="p-doc-campus-en"><?= e($names['en']) ?></span>
          <span class="p-doc-place"><?= te('campus_place_full') ?></span>
          <span class="p-doc-affil"><?= te('campus_affiliation') ?></span>
          <span class="p-doc-contact">
            <?= e(localize_digits('+977-47-520203')) ?> · admin@kamalasciencecampus.edu.np · kamalasciencecampus.edu.np
          </span>
        </div>
      </header>

      <div class="p-doc-meta">
        <span><?= te('doc_ref') ?>: <strong><?= e(localize_digits((string) $doc['ref_no'])) ?></strong></span>
        <span><?= te('doc_date') ?>: <strong><?= e(format_date($doc['issued_on'])) ?></strong></span>
      </div>

      <h2 class="p-doc-title"><?= e(document_heading($doc)) ?></h2>

      <?php $rows = document_particulars($doc, $subject); ?>
      <?php if (count($rows) > 1): ?>
        <dl class="p-doc-particulars">
          <?php foreach ($rows as $label => $value): ?>
            <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
          <?php endforeach; ?>
        </dl>
      <?php endif; ?>

      <div class="p-doc-body">
        <?php foreach (document_paragraphs($doc) as $para): ?>
          <p><?= nl2br(e(trim($para))) ?></p>
        <?php endforeach; ?>
      </div>

      <div class="p-doc-sign">
        <?php if ($sig): ?>
          <img class="p-doc-sig-img" src="<?= e(signature_url($sig)) ?>" alt="">
        <?php else: ?>
          <span class="p-doc-sig-img"></span>
        <?php endif; ?>
        <span class="p-doc-sig-rule"></span>
        <?php if ($name !== ''): ?>
          <span class="p-doc-sig-name"><?= e($name) ?></span>
        <?php endif; ?>
        <span class="p-doc-sig-title"><?= e($title) ?></span>
        <span class="p-doc-sig-campus"><?= e($names['en']) ?></span>
      </div>

      <footer class="p-doc-foot">
        <?= te('doc_footer') ?>
      </footer>
    </article>
    <?php
}
