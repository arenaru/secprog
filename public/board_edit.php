<?php
// Create (no ?id) or edit/delete (?id=N) a board.
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$id = id_param($_GET, 'id');
$board = ['name' => '', 'description' => '', 'visibility' => 'public'];
if ($id) {
    $board = q('SELECT * FROM boards WHERE id = ? AND user_id = ?', [$id, $me['id']])->fetch() ?: not_found();
}
$errors = [];

if (posted()) {
    if ($id && param($_POST, 'action') === 'delete') {
        q('DELETE FROM boards WHERE id = ? AND user_id = ?', [$id, $me['id']]);
        flash('Collection deleted.');
        redirect('/profile.php?tab=boards&u=' . urlencode($me['username']));
    }
    $board = [
        'name' => param($_POST, 'name'),
        'description' => param($_POST, 'description'),
        'visibility' => param($_POST, 'visibility'),
    ];
    if (!len_between($board['name'], 1, 50)) $errors[] = 'Name is required (max 50 characters).';
    if (!len_between($board['description'], 0, 300)) $errors[] = 'Description is too long (max 300 characters).';
    if (!in_array($board['visibility'], ['public', 'private'], true)) $errors[] = 'Pick public or private.';

    if (!$errors) {
        if ($id) {
            q('UPDATE boards SET name = ?, description = ?, visibility = ? WHERE id = ? AND user_id = ?',
              [$board['name'], $board['description'], $board['visibility'], $id, $me['id']]);
        } else {
            q('INSERT INTO boards (user_id, name, description, visibility) VALUES (?, ?, ?, ?)',
              [$me['id'], $board['name'], $board['description'], $board['visibility']]);
            $id = (int) db()->lastInsertId();
        }
        redirect('/board.php?id=' . $id);
    }
}

$title = $id ? 'Edit collection' : 'New collection';
require ROOT . '/templates/header.php';
?>
<form method="post" class="card">
  <h1><?= e($title) ?></h1>
  <?php require ROOT . '/templates/errors.php'; ?>
  <?= csrf_field() ?>
  <label>Name <input name="name" maxlength="50" required value="<?= e($board['name']) ?>"></label>
  <label>Description <textarea name="description" maxlength="300" rows="3"><?= e($board['description']) ?></textarea></label>
  <label>Visibility
    <select name="visibility">
      <option value="public" <?= $board['visibility'] === 'public' ? 'selected' : '' ?>>Public</option>
      <option value="private" <?= $board['visibility'] === 'private' ? 'selected' : '' ?>>Private (only you)</option>
    </select>
  </label>
  <button class="btn">Save</button>
</form>
<?php if ($id): ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="delete">
  <button class="btn danger">Delete collection</button>
</form>
<?php endif ?>
<?php require ROOT . '/templates/footer.php'; ?>
