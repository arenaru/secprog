<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$q = param($_GET, 'q');
$type = param($_GET, 'type') === 'users' ? 'users' : 'pins';
$pins = $users = [];

if (len_between($q, 1, 100)) {
    $like = '%' . addcslashes($q, '%_\\') . '%'; // user's % and _ are literal, not wildcards
    if ($type === 'users') {
        $users = q('SELECT id, username, display_name, avatar_path, is_private FROM users
                    WHERE username LIKE ? AND is_active = 1 ORDER BY username LIMIT 50', [$like])->fetchAll();
    } else {
        // Search is a classic place for the private-post leak, so it uses the same visibility rule as everything else.
        [$vis, $params] = visible_pins_sql($me);
        $pins = q("SELECT p.id, p.title FROM pins p JOIN users u ON u.id = p.user_id
                   WHERE (p.title LIKE ? OR p.description LIKE ?) AND $vis
                   ORDER BY p.id DESC LIMIT 50", [$like, $like, ...$params])->fetchAll();
    }
}

$title = 'Search';
require ROOT . '/templates/header.php';
?>
<div class="tabs">
  <a class="<?= $type === 'pins' ? 'active' : '' ?>" href="/search.php?q=<?= urlencode($q) ?>">Pieces</a>
  <a class="<?= $type === 'users' ? 'active' : '' ?>" href="/search.php?type=users&amp;q=<?= urlencode($q) ?>">People</a>
</div>
<?php if ($q === ''): ?>
  <p class="empty">Type something to search.</p>
<?php elseif ($type === 'users'): ?>
  <?php if (!$users): ?><p class="empty">No people found.</p><?php endif ?>
  <ul class="people">
  <?php foreach ($users as $u): ?>
    <li><a href="/profile.php?u=<?= urlencode($u['username']) ?>"><?= avatar_html($u) ?>
      <strong><?= e($u['display_name'] ?: $u['username']) ?></strong> @<?= e($u['username']) ?>
      <?php if ($u['is_private']): ?><span class="badge">Private</span><?php endif ?></a></li>
  <?php endforeach ?>
  </ul>
<?php else: ?>
  <?php require ROOT . '/templates/pin_grid.php'; ?>
<?php endif ?>
<?php require ROOT . '/templates/footer.php'; ?>
