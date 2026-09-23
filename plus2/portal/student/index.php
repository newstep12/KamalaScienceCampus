<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/idcard.php';

/**
 * The student's (and a teacher's) first page after signing in. This portal
 * exists for two things — keeping your details current and printing your
 * identity card — so the page is about exactly those: what the school holds
 * for you, what is still missing from your card, and the way to both.
 */

$user    = require_role(ROLE_STUDENT, ROLE_TEACHER);
$names   = id_card_names($user);
$missing = id_card_missing($user, $names);
$student = $user['role'] === ROLE_STUDENT;

layout_head(['title' => t('nav_overview'), 'active' => 'home']);
?>
<div class="p-page-head">
  <h1><?= e(t('hello_name', display_name($user))) ?></h1>
  <p><?= $student && $user['class_level']
        ? e(t('your_class', class_with_section((int) $user['class_level'], $user['section'] ?? null)))
        : te('portal_welcome') ?></p>
</div>

<div class="p-grid p-grid-3" style="margin-bottom:30px;">
  <?php if ($student): ?>
    <dl class="p-stat">
      <dt><?= te('class') ?></dt>
      <dd><?= $user['class_level'] ? e(localize_digits((string) $user['class_level'])) : '—' ?></dd>
    </dl>
    <dl class="p-stat">
      <dt><?= te('roll_no') ?></dt>
      <dd><?= $user['roll_no'] ? e(localize_digits((string) $user['roll_no'])) : '—' ?></dd>
    </dl>
  <?php else: ?>
    <dl class="p-stat">
      <dt><?= te('designation') ?></dt>
      <dd style="font-size:1.25rem;"><?= e(id_card_role_line($user)) ?></dd>
    </dl>
  <?php endif; ?>
  <dl class="p-stat">
    <dt><?= te('id_card_no') ?></dt>
    <dd style="font-size:1.25rem;"><?= e(id_card_number($user)) ?></dd>
  </dl>
</div>

<div class="p-grid p-grid-2" style="align-items:start;">
  <section class="p-card">
    <h2><?= te('id_card_title') ?></h2>
    <?php if ($missing): ?>
      <p class="p-idcard-missing"><?= e(t('id_card_missing', join_list($missing))) ?></p>
      <p style="color:var(--ink-soft);font-size:.94rem;"><?= te('dash_complete_first') ?></p>
      <div class="p-form-actions">
        <a class="p-btn p-btn-primary" href="<?= e(portal_url('/student/portfolio.php')) ?>"><?= te('dash_add_details') ?></a>
        <a class="p-btn p-btn-ghost" href="<?= e(portal_url('/id-card.php')) ?>"><?= te('id_card_open') ?></a>
      </div>
    <?php else: ?>
      <p style="color:var(--ink-soft);"><?= te('dash_card_ready') ?></p>
      <div class="p-form-actions">
        <a class="p-btn p-btn-primary" href="<?= e(portal_url('/id-card.php')) ?>"><?= te('id_card_open') ?></a>
      </div>
    <?php endif; ?>
  </section>

  <section class="p-card">
    <h2><?= te('dash_on_record') ?></h2>
    <table class="p-table p-table-compact" style="border:0;">
      <tbody>
        <tr><th scope="row"><?= te('full_name') ?></th>
            <td><?= e((string) ($names['latin'] ?? '—')) ?>
              <?php if ($names['deva']): ?><div class="deva"><?= e($names['deva']) ?></div><?php endif; ?></td></tr>
        <?php if ($student): ?>
          <tr><th scope="row"><?= te('guardian_name') ?></th><td><?= e($user['guardian_name'] ?: '—') ?></td></tr>
          <tr><th scope="row"><?= te('guardian_phone') ?></th><td><?= e($user['guardian_phone'] ?: '—') ?></td></tr>
        <?php endif; ?>
        <tr><th scope="row"><?= te('date_of_birth') ?></th><td><?= e($user['date_of_birth'] ? format_date($user['date_of_birth']) : '—') ?></td></tr>
        <tr><th scope="row"><?= te('address') ?></th><td><?= e($user['address'] ?: '—') ?></td></tr>
        <tr><th scope="row"><?= te('email') ?></th><td><?= e($user['email']) ?></td></tr>
      </tbody>
    </table>
    <p style="margin:14px 0 0;"><a href="<?= e(portal_url('/student/portfolio.php')) ?>"><?= te('dash_edit_details') ?> →</a></p>
  </section>
</div>
<?php layout_foot(); ?>
