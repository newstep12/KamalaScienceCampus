<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/mail.php';

$admin = require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id     = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $target = one('SELECT * FROM users WHERE id = ? AND status = \'pending\'', [$id]);

    if ($target && $action === 'approve') {
        q('UPDATE users SET status = \'active\', approved_at = NOW(), approved_by = ? WHERE id = ?',
          [$admin['id'], $id]);
        log_activity((int) $admin['id'], 'approve_user', $target['email']);
        notify_student_approved($target);
        flash('ok', t('approved_ok', $target['full_name']));
    } elseif ($target && $action === 'delete') {
        // Only ever a pending registration, and never an admin account.
        if ($target['role'] !== ROLE_ADMIN && (int) $target['id'] !== (int) $admin['id']) {
            q('DELETE FROM users WHERE id = ?', [$id]);
            log_activity((int) $admin['id'], 'delete_user', $target['email']);
            flash('ok', t('deleted_ok', $target['full_name']));
        }
    } elseif ($target && $action === 'reject') {
        q('UPDATE users SET status = \'rejected\', rejection_note = ?, approved_by = ? WHERE id = ?',
          [trim((string) ($_POST['note'] ?? '')) ?: null, $admin['id'], $id]);
        log_activity((int) $admin['id'], 'reject_user', $target['email']);
        notify_student_rejected($target, trim((string) ($_POST['note'] ?? '')) ?: null);
        flash('ok', t('rejected_ok', $target['full_name']));
    }
    header('Location: ' . portal_url('/admin/approvals.php'));
    exit;
}

$pending = all('SELECT * FROM users WHERE status = \'pending\' ORDER BY created_at ASC');

layout_head(['title' => t('pending_approvals'), 'active' => 'approvals', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('pending_approvals') ?></h1>
  <p><?= te('reg_intro') ?></p>
</div>

<?php if (!$pending): ?>
  <div class="p-empty"><p><?= te('no_pending') ?></p></div>
<?php else: ?>
  <div class="p-table-wrap">
    <table class="p-table">
      <thead>
        <tr>
          <th><?= te('full_name') ?></th>
          <th><?= te('email') ?></th>
          <th><?= te('year_of_study') ?></th>
          <th><?= te('symbol_no') ?></th>
          <th><?= te('phone') ?></th>
          <th><?= te('registered_on') ?></th>
          <th><?= te('actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pending as $p): ?>
          <tr>
            <td>
              <strong><?= e($p['full_name']) ?></strong>
              <?php if ($p['full_name_ne']): ?>
                <div style="font-size:.85rem;color:var(--ink-soft);" class="deva"><?= e($p['full_name_ne']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e($p['email']) ?></td>
            <td class="nowrap"><?= e($p['year_level'] ? year_label((int) $p['year_level']) : '—') ?></td>
            <td class="nowrap"><?= e($p['symbol_no'] ?: '—') ?></td>
            <td class="nowrap"><?= e($p['phone'] ?: '—') ?></td>
            <td class="nowrap"><?= e(format_date($p['created_at'])) ?></td>
            <td class="nowrap">
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
                <button class="p-btn p-btn-primary p-btn-sm" type="submit" name="action" value="approve"><?= te('approve') ?></button>
              </form>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
                <button class="p-btn p-btn-ghost p-btn-sm" type="submit" name="action" value="reject"><?= te('reject') ?></button>
              </form>
              <form method="post" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
                <button class="p-btn p-btn-danger p-btn-sm" type="submit" name="action" value="delete"><?= te('delete') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php layout_foot(); ?>
