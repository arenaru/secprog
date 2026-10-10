<?php
// Expects $pins (id, title). Optional: $removeFromBoard (board id, shows "Remove" on each tile),
// $gated (guest teaser: tiles open the sign-up popup instead of the piece).
// Tile shape comes from the id, so the mosaic is stable between reloads.
$shapes = ['t3', 't4', 't2', 't5', 'w2 t3', 't3', 't4', 't2'];
?>
<?php if (!$pins): ?>
<p class="empty">No pieces yet.</p>
<?php else: ?>
<div class="grid">
<?php foreach ($pins as $pin): ?>
  <div class="tile <?= $shapes[$pin['id'] % count($shapes)] ?>">
    <a href="<?= empty($gated) ? '/pin.php?id=' . (int) $pin['id'] : '#join' ?>">
      <img src="/image.php?pin=<?= (int) $pin['id'] ?>" alt="<?= e($pin['title']) ?>" loading="lazy">
      <span class="cap"><?= e($pin['title']) ?></span>
    </a>
    <?php if (!empty($removeFromBoard)): ?>
    <form method="post" action="/save.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="remove">
      <input type="hidden" name="board_id" value="<?= (int) $removeFromBoard ?>">
      <input type="hidden" name="pin_id" value="<?= (int) $pin['id'] ?>">
      <button class="link">Remove</button>
    </form>
    <?php endif ?>
  </div>
<?php endforeach ?>
</div>
<?php endif ?>
