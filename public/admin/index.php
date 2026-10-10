<?php
require __DIR__ . '/../../src/bootstrap.php';

$me = require_admin(); // non-admins get 404: the page doesn't even admit it exists

if (posted()) {
    $action = param($_POST, 'action');

    if ($action === 'toggle_user') {
        // Only regular users can be blocked, so an admin can't lock themselves (or other admins) out.
        $u = q("SELECT id, username, is_active FROM users WHERE id = ? AND role = 'user'", [id_param($_POST, 'user_id')])->fetch() ?: not_found();
        $active = $u['is_active'] ? 0 : 1;
        q('UPDATE users SET is_active = ? WHERE id = ?', [$active, $u['id']]);
        log_action($active ? 'user_unblocked' : 'user_blocked', $u['username']);
        flash($u['username'] . ($active ? ' unblocked.' : ' blocked.'));
        redirect('/admin/');
    }

    if ($action === 'delete_pin') {
        $pin = q('SELECT id, user_id, title, image_path FROM pins WHERE id = ?', [id_param($_POST, 'pin_id')])->fetch() ?: not_found();
        q('DELETE FROM pins WHERE id = ?', [$pin['id']]);
        delete_image($pin['image_path']);
        log_action('pin_deleted_by_admin', "pin #{$pin['id']} of user #{$pin['user_id']}: {$pin['title']}");
        flash('Piece deleted.');
        redirect('/admin/');
    }
}

$users = q('SELECT id, username, email, role, is_active, created_at FROM users ORDER BY id DESC LIMIT 200')->fetchAll();
$logs = q('SELECT l.*, u.username FROM audit_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 200')->fetchAll();

$title = 'Admin';
require ROOT . '/templates/header.php';
?>
<h1>Admin</h1>
<p class="muted">To remove a piece, open it and use “Delete (admin)”.</p>

<h2>Users</h2>
<div class="table-wrap"><table>
  <tr><th>User</th><th>Email</th><th>Role</th><th>Joined</th><th></th></tr>
  <?php foreach ($users as $u): ?>
  <tr>
    <td><a href="/profile.php?u=<?= urlencode($u['username']) ?>"><?= e($u['username']) ?></a></td>
    <td><?= e($u['email']) ?></td>
    <td><?= e($u['role']) ?></td>
    <td><?= e($u['created_at']) ?></td>
    <td><?php if ($u['role'] === 'user'): ?>
      <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="action" value="toggle_user">
        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
        <button class="btn <?= $u['is_active'] ? 'danger' : 'light' ?>"><?= $u['is_active'] ? 'Block' : 'Unblock' ?></button>
      </form>
    <?php endif ?></td>
  </tr>
  <?php endforeach ?>
</table></div>

<h2>Audit log</h2>
<div class="table-wrap"><table>
  <tr><th>Time</th><th>User</th><th>Action</th><th>Detail</th><th>IP</th><th>User agent</th></tr>
  <?php foreach ($logs as $l): ?>
  <tr>
    <td><?= e($l['created_at']) ?></td>
    <td><?= e($l['username'] ?? ($l['user_id'] ? '#' . $l['user_id'] : '—')) ?></td>
    <td><?= e($l['action']) ?></td>
    <td><?= e($l['detail']) ?></td>
    <td><?= e($l['ip_address']) ?></td>
    <td class="muted"><?= e($l['user_agent']) ?></td>
  </tr>
  <?php endforeach ?>
</table></div>
<?php require ROOT . '/templates/footer.php'; ?>
