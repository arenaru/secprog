<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
// Ownership is part of the query: someone else's pin id is simply "not found".
$pin = q('SELECT * FROM pins WHERE id = ? AND user_id = ?', [id_param($_GET, 'id'), $me['id']])->fetch() ?: not_found();
$errors = [];
$f = $pin;

if (posted()) {
    if (param($_POST, 'action') === 'delete') {
        q('DELETE FROM pins WHERE id = ? AND user_id = ?', [$pin['id'], $me['id']]);
        delete_image($pin['image_path']);
        flash('Piece deleted.');
        redirect('/profile.php?u=' . urlencode($me['username']));
    }
    $f = [
        'title' => param($_POST, 'title'),
        'description' => param($_POST, 'description'),
        'source_url' => param($_POST, 'source_url'),
    ];
    $errors = validate_pin($f);
    if (!$errors) {
        // Field whitelist: user_id, image_path, etc. are never taken from the request (no mass assignment).
        q('UPDATE pins SET title = ?, description = ?, source_url = ? WHERE id = ? AND user_id = ?',
          [$f['title'], $f['description'], $f['source_url'] ?: null, $pin['id'], $me['id']]);
        flash('Piece updated.');
        redirect('/pin.php?id=' . $pin['id']);
    }
}

$title = 'Edit piece';
require ROOT . '/templates/header.php';
?>
<form method="post" class="card">
  <h1>Edit piece</h1>
  <?php require ROOT . '/templates/errors.php'; ?>
  <?= csrf_field() ?>
  <label>Title <input name="title" maxlength="100" required value="<?= e($f['title']) ?>"></label>
  <label>Description <textarea name="description" maxlength="2000" rows="4"><?= e($f['description']) ?></textarea></label>
  <label>Source link (optional) <input type="url" name="source_url" maxlength="2048" value="<?= e($f['source_url']) ?>"></label>
  <button class="btn">Save changes</button>
</form>
<form method="post" class="card">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="delete">
  <button class="btn danger">Delete piece</button>
</form>
<?php require ROOT . '/templates/footer.php'; ?>
