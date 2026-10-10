<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$boards = q('SELECT id, name FROM boards WHERE user_id = ? ORDER BY name', [$me['id']])->fetchAll();
$errors = [];
$f = ['title' => '', 'description' => '', 'source_url' => '', 'board_id' => 0];

if (posted()) {
    $f = [
        'title' => param($_POST, 'title'),
        'description' => param($_POST, 'description'),
        'source_url' => param($_POST, 'source_url'),
        'board_id' => id_param($_POST, 'board_id'),
    ];
    $errors = validate_pin($f);
    if ($f['board_id'] && !in_array($f['board_id'], array_map('intval', array_column($boards, 'id')), true)) {
        $errors[] = 'Pick one of your own collections.';
    }
    if (!$errors) {
        $image = store_image($_FILES['image'] ?? [], $error);
        if (!$image) $errors[] = $error;
    }
    if (!$errors) {
        q('INSERT INTO pins (user_id, title, description, image_path, source_url) VALUES (?, ?, ?, ?, ?)',
          [$me['id'], $f['title'], $f['description'], $image, $f['source_url'] ?: null]);
        $pinId = (int) db()->lastInsertId();
        if ($f['board_id']) q('INSERT INTO board_pins (board_id, pin_id) VALUES (?, ?)', [$f['board_id'], $pinId]);
        redirect('/pin.php?id=' . $pinId);
    }
}

$title = 'Add piece';
require ROOT . '/templates/header.php';
?>
<form method="post" enctype="multipart/form-data" class="card">
  <h1>Add piece</h1>
  <?php require ROOT . '/templates/errors.php'; ?>
  <?= csrf_field() ?>
  <label>Image (JPG, PNG, WebP, max 5 MB) <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label>
  <label>Title <input name="title" maxlength="100" required value="<?= e($f['title']) ?>"></label>
  <label>Description <textarea name="description" maxlength="2000" rows="4"><?= e($f['description']) ?></textarea></label>
  <label>Source link (optional) <input type="url" name="source_url" maxlength="2048" placeholder="https://" value="<?= e($f['source_url']) ?>"></label>
  <label>Collection (optional)
    <select name="board_id">
      <option value="">— none —</option>
      <?php foreach ($boards as $b): ?>
        <option value="<?= (int) $b['id'] ?>" <?= (int) $b['id'] === $f['board_id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
      <?php endforeach ?>
    </select>
  </label>
  <button class="btn">Publish</button>
</form>
<?php require ROOT . '/templates/footer.php'; ?>
