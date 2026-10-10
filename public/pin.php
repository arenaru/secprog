<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login(); // guests only get the home-feed teaser
// Private pins answer 404, same as missing ones, so guessing ids reveals nothing.
$pin = find_visible_pin(id_param($_GET, 'id'), $me) ?? not_found();
$myBoards = $me ? q('SELECT id, name FROM boards WHERE user_id = ? ORDER BY name', [$me['id']])->fetchAll() : [];
$isOwner = is_self($me, ['id' => $pin['user_id']]);

$title = $pin['title'];
require ROOT . '/templates/header.php';
?>
<article class="pin-detail">
  <img src="/image.php?pin=<?= (int) $pin['id'] ?>" alt="<?= e($pin['title']) ?>">
  <div>
    <h1><?= e($pin['title']) ?></h1>
    <?php if ($pin['description']): ?><p><?= nl2br(e($pin['description'])) ?></p><?php endif ?>
    <?php if ($pin['source_url']): ?>
      <p><a href="<?= e($pin['source_url']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e(parse_url($pin['source_url'], PHP_URL_HOST)) ?></a></p>
    <?php endif ?>
    <p class="muted">by <a href="/profile.php?u=<?= urlencode($pin['username']) ?>"><?= e($pin['display_name'] ?: $pin['username']) ?></a></p>

    <?php if ($myBoards): ?>
    <form method="post" action="/save.php" class="row">
      <?= csrf_field() ?>
      <input type="hidden" name="pin_id" value="<?= (int) $pin['id'] ?>">
      <select name="board_id">
        <?php foreach ($myBoards as $b): ?><option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach ?>
      </select>
      <button class="btn">Save</button>
    </form>
    <?php else: ?>
    <p><a href="/board_edit.php">Create a collection</a> to save pieces.</p>
    <?php endif ?>

    <?php if ($isOwner): ?><p><a href="/pin_edit.php?id=<?= (int) $pin['id'] ?>">Edit piece</a></p><?php endif ?>

    <?php if (is_admin($me) && !$isOwner): ?>
    <form method="post" action="/admin/">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_pin">
      <input type="hidden" name="pin_id" value="<?= (int) $pin['id'] ?>">
      <button class="btn danger">Delete (admin)</button>
    </form>
    <?php endif ?>
  </div>
</article>
<?php require ROOT . '/templates/footer.php'; ?>
