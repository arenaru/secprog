<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$board = q('SELECT b.*, u.username, u.display_name, u.is_private, u.is_active
            FROM boards b JOIN users u ON u.id = b.user_id WHERE b.id = ?', [id_param($_GET, 'id')])->fetch();
if (!$board || !can_view_board($me, $board)) not_found();

// Pins saved here may belong to people who have since gone private, so they're filtered too.
[$vis, $params] = visible_pins_sql($me);
$pins = q("SELECT p.id, p.title FROM board_pins bp
           JOIN pins p ON p.id = bp.pin_id JOIN users u ON u.id = p.user_id
           WHERE bp.board_id = ? AND $vis ORDER BY bp.saved_at DESC", [$board['id'], ...$params])->fetchAll();
$isOwner = is_self($me, ['id' => $board['user_id']]);
$removeFromBoard = $isOwner ? $board['id'] : 0;

$title = $board['name'];
require ROOT . '/templates/header.php';
?>
<section class="board-head">
  <h1><?= e($board['name']) ?> <?php if ($board['visibility'] === 'private'): ?><span class="badge">Private</span><?php endif ?></h1>
  <?php if ($board['description']): ?><p><?= e($board['description']) ?></p><?php endif ?>
  <p class="muted">by <a href="/profile.php?u=<?= urlencode($board['username']) ?>"><?= e($board['display_name'] ?: $board['username']) ?></a>
  <?php if ($isOwner): ?> · <a href="/board_edit.php?id=<?= (int) $board['id'] ?>">Edit collection</a><?php endif ?></p>
</section>
<?php require ROOT . '/templates/pin_grid.php'; ?>
<?php require ROOT . '/templates/footer.php'; ?>
