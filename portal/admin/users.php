<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';

$admin = require_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id     = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // An admin must never be able to lock themselves out.
    if ($id === (int) $admin['id'] && in_array($action, ['suspend', 'set_role'], true)) {
        flash('error', t('forbidden_body'));
        header('Location: ' . portal_url('/admin/users.php'));
        exit;
    }

    $target = one('SELECT * FROM users WHERE id = ?', [$id]);
    if ($target) {
        if ($action === 'suspend') {
            q('UPDATE users SET status = \'suspended\' WHERE id = ?', [$id]);
            log_activity((int) $admin['id'], 'suspend_user', $target['email']);
        } elseif ($action === 'reactivate') {
            q('UPDATE users SET status = \'active\', approved_at = NOW(), approved_by = ? WHERE id = ?', [$admin['id'], $id]);
            log_activity((int) $admin['id'], 'reactivate_user', $target['email']);
        } elseif ($action === 'set_role') {
            $role = in_array($_POST['role'] ?? '', ['student', 'lecturer', 'admin'], true) ? $_POST['role'] : null;
            if ($role) {
                q('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
                log_activity((int) $admin['id'], 'set_role', $target['email'], $role);
            }
        } elseif ($action === 'set_year') {
            $year = (int) ($_POST['year_level'] ?? 0);
            q('UPDATE users SET year_level = ? WHERE id = ?', [$year >= 1 && $year <= 4 ? $year : null, $id]);
            log_activity((int) $admin['id'], 'set_year', $target['email'], (string) $year);
        }
        flash('ok', t('user_updated'));
    }
    header('Location: ' . portal_url('/admin/users.php?' . http_build_query(array_filter([
        'role' => $_GET['role'] ?? null, 'year' => $_GET['year'] ?? null, 'q' => $_GET['q'] ?? null,
    ]))));
    exit;
}

$role   = (string) ($_GET['role'] ?? '');
$year   = (int) ($_GET['year'] ?? 0);
$search = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT * FROM users WHERE 1 = 1';
$par = [];
if (in_array($role, ['student', 'lecturer', 'admin'], true)) { $sql .= ' AND role = ?';       $par[] = $role; }
if ($year >= 1 && $year <= 4)                                { $sql .= ' AND year_level = ?'; $par[] = $year; }
if ($search !== '') {
    $sql .= ' AND (full_name LIKE ? OR email LIKE ? OR symbol_no LIKE ?)';
    $like = '%' . $search . '%';
    array_push($par, $like, $like, $like);
}
$sql .= ' ORDER BY role, year_level, full_name LIMIT 400';
$users = all($sql, $par);

layout_head(['title' => t('manage_people'), 'active' => 'users', 'wide' => true]);
?>
<div class="p-page-head">
  <h1><?= te('manage_people') ?></h1>
</div>

<form method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin-bottom:20px;">
  <div class="p-field" style="margin:0;min-width:180px;">
    <label for="q"><?= te('search') ?></label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>">
  </div>
  <div class="p-field" style="margin:0;min-width:150px;">
    <label for="role"><?= te('actions') ?></label>
    <select id="role" name="role">
      <option value=""><?= te('all_categories') ?></option>
      <?php foreach (['student', 'lecturer', 'admin'] as $r): ?>
        <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= te('role_' . $r) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="p-field" style="margin:0;min-width:140px;">
    <label for="year"><?= te('year_of_study') ?></label>
    <select id="year" name="year">
      <option value="0"><?= te('all_years') ?></option>
      <?php foreach ([1, 2, 3, 4] as $y): ?>
        <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= e(year_label($y)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="p-btn p-btn-ghost" type="submit"><?= te('search') ?></button>
</form>

<?php if (!$users): ?>
  <div class="p-empty"><p><?= te('none_yet') ?></p></div>
<?php else: ?>
  <div class="p-table-wrap">
    <table class="p-table">
      <thead>
        <tr>
          <th><?= te('full_name') ?></th>
          <th><?= te('email') ?></th>
          <th><?= te('actions') ?></th>
          <th><?= te('year_of_study') ?></th>
          <th><?= te('status_active') ?></th>
          <th><?= te('actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u):
            $tagClass = ['active' => 'ok', 'pending' => 'pending', 'rejected' => 'bad', 'suspended' => 'bad'][$u['status']] ?? ''; ?>
          <tr>
            <td>
              <strong><?= e($u['full_name']) ?></strong>
              <?php if ($u['symbol_no']): ?>
                <div style="font-size:.82rem;color:var(--ink-soft);"><?= e($u['symbol_no']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e($u['email']) ?></td>
            <td>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="action" value="set_role">
                <select name="role" onchange="this.form.submit()" <?= (int) $u['id'] === (int) $admin['id'] ? 'disabled' : '' ?>>
                  <?php foreach (['student', 'lecturer', 'admin'] as $r): ?>
                    <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= te('role_' . $r) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td>
              <?php if ($u['role'] === 'student'): ?>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <input type="hidden" name="action" value="set_year">
                  <select name="year_level" onchange="this.form.submit()">
                    <option value="0">—</option>
                    <?php foreach ([1, 2, 3, 4] as $y): ?>
                      <option value="<?= $y ?>" <?= (int) $u['year_level'] === $y ? 'selected' : '' ?>><?= e(year_label($y)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><span class="p-tag <?= e($tagClass) ?>"><?= te('status_' . $u['status']) ?></span></td>
            <td class="nowrap">
              <?php if ($u['status'] === 'active' && (int) $u['id'] !== (int) $admin['id']): ?>
                <form method="post" data-confirm="<?= te('confirm_delete') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <button class="p-btn p-btn-danger p-btn-sm" name="action" value="suspend"><?= te('suspend') ?></button>
                </form>
              <?php elseif (in_array($u['status'], ['suspended', 'rejected'], true)): ?>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <button class="p-btn p-btn-ghost p-btn-sm" name="action" value="reactivate"><?= te('reactivate') ?></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php layout_foot(); ?>
