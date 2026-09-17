<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/document-view.php';

/**
 * Official documents — administrators only.
 *
 * This is the other half of the signature library: the page from which the
 * Campus Chief's signature reaches something that is not an identity card.
 * Nobody but an administrator can open it, and the signature reaches the page
 * only while the office has one released for documents, so a certificate
 * cannot be produced over a signature the office is holding back.
 */

$admin = require_role(ROLE_ADMIN);

// The tables arrive with a database update, which an administrator runs after
// a deploy. Until then, say so and point at the button rather than failing.
if (!signature_tables_ready()) {
    flash('error', t('db_update_needed'));
    header('Location: ' . portal_url('/admin/system.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'issue') {
        $kind      = document_kind($_POST['kind'] ?? null);
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $typedName = trim((string) ($_POST['subject_name'] ?? ''));

        // An id that no longer names an active account falls back to the typed
        // name rather than issuing a certificate to an empty one.
        if ($subjectId > 0 && !one('SELECT id FROM users WHERE id = ? AND status = \'active\' LIMIT 1', [$subjectId])) {
            $subjectId = 0;
        }

        if ($subjectId <= 0 && mb_strlen($typedName) < 3) {
            flash('error', t('err_doc_subject'));
        } elseif ($kind === 'custom' && trim((string) ($_POST['body'] ?? '')) === '') {
            flash('error', t('err_doc_body'));
        } else {
            $doc = issue_document([
                'kind'         => $kind,
                'subject_id'   => $subjectId,
                'subject_name' => $typedName,
                'title'        => $_POST['title'] ?? '',
                'body'         => $_POST['body'] ?? '',
                'purpose'      => $_POST['purpose'] ?? '',
                'issued_on'    => $_POST['issued_on'] ?? '',
            ], $admin);

            flash('ok', t('doc_issued', $doc['ref_no'] ?? ''));
            header('Location: ' . portal_url('/admin/documents.php?view=' . (int) ($doc['id'] ?? 0)));
            exit;
        }
    } elseif ($action === 'delete') {
        $id  = (int) ($_POST['document_id'] ?? 0);
        $doc = document_row($id);
        if ($doc) {
            q('DELETE FROM documents WHERE id = ?', [$id]);
            log_activity((int) $admin['id'], 'document_delete', (string) $doc['ref_no'], (string) $doc['subject_name']);
            flash('ok', t('doc_deleted'));
        }
    }

    header('Location: ' . portal_url('/admin/documents.php'));
    exit;
}

/* ------------------------------------------------- one document, to print -- */

$viewing = document_row((int) ($_GET['view'] ?? 0));
if ($viewing) {
    $subject = $viewing['subject_id']
        ? one('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $viewing['subject_id']])
        : null;

    layout_head(['title' => document_heading($viewing), 'active' => 'documents']);
    ?>
    <div class="p-page-head p-noprint">
      <h1><?= e(document_heading($viewing)) ?></h1>
      <p><?= te('doc_view_intro', localize_digits((string) $viewing['ref_no']), $viewing['issuer'] ?: t('unknown')) ?></p>
    </div>

    <?php if (!applied_signature('document', $admin)): ?>
      <div class="p-flash p-flash-info p-noprint">
        <?= te('doc_no_signature') ?>
        <a href="<?= e(portal_url('/admin/signatures.php')) ?>"><?= te('signatures_title') ?></a>
      </div>
    <?php endif; ?>

    <div class="p-doc-actions p-noprint">
      <button class="p-btn p-btn-primary" type="button" data-print><?= te('doc_print') ?></button>
      <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/admin/documents.php')) ?>"><?= te('doc_back') ?></a>
    </div>
    <p class="hint p-noprint" style="margin-bottom:20px;"><?= te('doc_print_hint') ?></p>

    <?php document_letter($viewing, $subject, $admin); ?>

    <style id="doc-page-rule">@page { size: A4; margin: 0; }</style>
    <script>
      document.querySelectorAll('[data-print]').forEach(function (b) {
        b.addEventListener('click', function () { window.print(); });
      });
    </script>
    <?php
    layout_foot();
    exit;
}

/* --------------------------------------------------- issue, and the register */

$signature = applied_signature('document', $admin);
$register  = issued_documents(50);
$subjects  = document_subjects();

layout_head(['title' => t('documents_title'), 'active' => 'documents', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('documents_title') ?></h1>
  <p><?= te('documents_intro') ?></p>
</div>

<?php if ($signature): ?>
  <div class="p-flash p-flash-ok">
    <?= te('doc_signature_ready', $signature['label'], signature_owner_name($signature)) ?>
  </div>
<?php else: ?>
  <div class="p-flash p-flash-info">
    <?= te('doc_no_signature') ?>
    <a href="<?= e(portal_url('/admin/signatures.php')) ?>"><?= te('signatures_title') ?></a>
  </div>
<?php endif; ?>

<section class="p-card">
  <h2><?= te('doc_issue_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('doc_issue_intro') ?></p>

  <form method="post" style="margin-top:18px;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="issue">

    <div class="p-field-row">
      <div class="p-field">
        <label for="kind"><?= te('doc_kind') ?></label>
        <select id="kind" name="kind" data-doc-kind>
          <?php foreach (document_kinds() as $k): ?>
            <option value="<?= e($k) ?>"><?= te('doc_kind_' . $k) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="p-field">
        <label for="issued_on"><?= te('doc_date') ?></label>
        <input type="date" id="issued_on" name="issued_on" value="<?= e(date('Y-m-d')) ?>">
      </div>
    </div>

    <div class="p-field">
      <label for="subject_id"><?= te('doc_subject') ?></label>
      <select id="subject_id" name="subject_id">
        <option value="0"><?= te('doc_subject_none') ?></option>
        <?php foreach ($subjects as $p): ?>
          <option value="<?= (int) $p['id'] ?>">
            <?= e($p['full_name']) ?>
            — <?= e($p['role'] === ROLE_STUDENT ? year_label($p['year_level'] ? (int) $p['year_level'] : null) : t('role_' . $p['role'])) ?>
            <?= $p['symbol_no'] ? '· ' . e($p['symbol_no']) : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
      <span class="hint"><?= te('doc_subject_hint') ?></span>
    </div>

    <div class="p-field">
      <label for="subject_name"><?= te('doc_subject_name') ?> <span class="hint"><?= te('doc_subject_name_hint') ?></span></label>
      <input type="text" id="subject_name" name="subject_name" maxlength="120">
    </div>

    <div class="p-field" data-doc-standard>
      <label for="purpose"><?= te('doc_purpose') ?> <span class="hint"><?= te('optional') ?></span></label>
      <input type="text" id="purpose" name="purpose" maxlength="190" placeholder="<?= te('doc_purpose_placeholder') ?>">
    </div>

    <div class="p-field" data-doc-custom hidden>
      <label for="title"><?= te('doc_custom_title') ?></label>
      <input type="text" id="title" name="title" maxlength="190">
    </div>

    <div class="p-field" data-doc-custom hidden>
      <label for="body"><?= te('doc_custom_body') ?> <span class="hint"><?= te('doc_custom_body_hint') ?></span></label>
      <textarea id="body" name="body" rows="8"></textarea>
    </div>

    <div class="p-form-actions">
      <button class="p-btn p-btn-primary" type="submit"><?= te('doc_issue') ?></button>
    </div>
  </form>
</section>

<section class="p-card">
  <h2><?= te('doc_register_title') ?></h2>
  <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('doc_register_intro') ?></p>

  <?php if (!$register): ?>
    <div class="p-empty" style="margin-top:16px;"><p><?= te('none_yet') ?></p></div>
  <?php else: ?>
    <div class="p-table-wrap" style="margin-top:16px;">
      <table class="p-table">
        <thead>
          <tr>
            <th><?= te('doc_ref') ?></th>
            <th><?= te('doc_kind') ?></th>
            <th><?= te('doc_subject') ?></th>
            <th><?= te('doc_date') ?></th>
            <th><?= te('signature') ?></th>
            <th><?= te('actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($register as $d): ?>
            <tr>
              <td class="nowrap"><code><?= e(localize_digits((string) $d['ref_no'])) ?></code></td>
              <td><?= te('doc_kind_' . $d['kind']) ?></td>
              <td>
                <strong><?= e($d['subject_name']) ?></strong>
                <?php if ($d['issuer']): ?>
                  <div style="font-size:.82rem;color:var(--ink-soft);"><?= te('doc_issued_by', $d['issuer']) ?></div>
                <?php endif; ?>
              </td>
              <td class="nowrap"><?= e(format_date($d['issued_on'])) ?></td>
              <td>
                <?php if ($d['signature_label']): ?>
                  <?= e($d['signature_label']) ?>
                <?php else: ?>
                  <span style="color:var(--ink-soft);"><?= te('doc_signed_by_hand') ?></span>
                <?php endif; ?>
              </td>
              <td class="nowrap">
                <a class="p-btn p-btn-ghost p-btn-sm"
                   href="<?= e(portal_url('/admin/documents.php?view=' . (int) $d['id'])) ?>"><?= te('doc_open') ?></a>
                <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="document_id" value="<?= (int) $d['id'] ?>">
                  <button class="p-btn p-btn-danger p-btn-sm" type="submit"><?= te('delete') ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<script>
(function () {
  // A custom letter needs its own heading and text; a standard certificate
  // writes both itself and asks only what it is for. Showing both at once
  // invites someone to fill in the wrong pair.
  var kind = document.querySelector('[data-doc-kind]');
  if (!kind) return;
  var custom   = document.querySelectorAll('[data-doc-custom]');
  var standard = document.querySelectorAll('[data-doc-standard]');
  function apply() {
    var isCustom = kind.value === 'custom';
    custom.forEach(function (el) { el.hidden = !isCustom; });
    standard.forEach(function (el) { el.hidden = isCustom; });
  }
  kind.addEventListener('change', apply);
  apply();
})();
</script>
<?php layout_foot(); ?>
