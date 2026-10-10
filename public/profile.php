<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$user = q('SELECT id, username, display_name, bio, avatar_path, is_private, is_active FROM users WHERE username = ?',
          [param($_GET, 'u')])->fetch() ?: not_found();
if (!$user['is_active'] && !is_admin($me)) not_found();

$tab = param($_GET, 'tab') === 'boards' ? 'boards' : 'created';
$canView = can_view_content($me, $user);
$isSelf = is_self($me, $user);
$pins = $boards = [];

if ($canView && $tab === 'created') {
    $pins = q('SELECT id, title FROM pins WHERE user_id = ? ORDER BY id DESC LIMIT 200', [$user['id']])->fetchAll();
}
if ($canView && $tab === 'boards') {
    // Private boards are listed for their owner only.
    $boards = q("SELECT id, name, visibility FROM boards
                 WHERE user_id = ? AND (visibility = 'public' OR ? = 1) ORDER BY name",
                [$user['id'], $isSelf ? 1 : 0])->fetchAll();
}

$title = $user['display_name'] ?: $user['username'];
require ROOT . '/templates/header.php';
$u = urlencode($user['username']);
?>
<section class="profile">
  <?= avatar_html($user) ?>
  <h1><?= e($user['display_name'] ?: $user['username']) ?></h1>
  <p class="muted">@<?= e($user['username']) ?>
    <?php if ($user['is_private']): ?><span class="badge">Private</span><?php endif ?>
    <?php if (!$user['is_active']): ?><span class="badge">Blocked</span><?php endif ?></p>
  <?php if ($user['bio']): ?><p><?= nl2br(e($user['bio'])) ?></p><?php endif ?>
  <?php if ($isSelf): ?><p><a class="btn light" href="/settings.php">Edit profile</a> <a class="btn light" href="/board_edit.php">New collection</a></p><?php endif ?>
</section>

<?php if (!$canView): ?>
  <p class="empty">This account is private.</p>
<?php else: ?>
  <div class="tabs">
    <a class="<?= $tab === 'created' ? 'active' : '' ?>" href="/profile.php?u=<?= $u ?>">Pieces</a>
    <a class="<?= $tab === 'boards' ? 'active' : '' ?>" href="/profile.php?tab=boards&amp;u=<?= $u ?>">Collections</a>
  </div>
  <?php if ($tab === 'created'): ?>
    <?php require ROOT . '/templates/pin_grid.php'; ?>
  <?php elseif (!$boards): ?>
    <p class="empty">No collections yet.</p>
  <?php else: ?>
    <div class="boards">
    <?php foreach ($boards as $b): ?>
      <a class="board c<?= $b['id'] % 4 ?>" href="/board.php?id=<?= (int) $b['id'] ?>"><?= e($b['name']) ?>
        <?php if ($b['visibility'] === 'private'): ?><span class="badge">Private</span><?php endif ?></a>
    <?php endforeach ?>
    </div>
  <?php endif ?>
<?php endif ?>
<?php require ROOT . '/templates/footer.php'; ?>
