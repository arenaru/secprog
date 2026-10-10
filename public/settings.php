<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$errors = [];

if (posted()) {
    $action = param($_POST, 'action');
    $hash = q('SELECT password_hash FROM users WHERE id = ?', [$me['id']])->fetchColumn();
    $passwordOk = password_verify(param($_POST, 'current_password', false), $hash);

    if ($action === 'profile') {
        $name = param($_POST, 'display_name');
        $bio = param($_POST, 'bio');
        $private = isset($_POST['is_private']) ? 1 : 0;
        if (!len_between($name, 0, 50)) $errors[] = 'Display name: max 50 characters.';
        if (!len_between($bio, 0, 300)) $errors[] = 'Bio: max 300 characters.';

        $avatar = $me['avatar_path'];
        if (!$errors && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $new = store_image($_FILES['avatar'], $error);
            if ($new) {
                delete_image($avatar);
                $avatar = $new;
            } else {
                $errors[] = $error;
            }
        }
        if (!$errors) {
            // Field whitelist: role/is_active/email can't be changed through this form.
            q('UPDATE users SET display_name = ?, bio = ?, avatar_path = ?, is_private = ? WHERE id = ?',
              [$name ?: null, $bio ?: null, $avatar, $private, $me['id']]);
            if ($private !== (int) $me['is_private']) log_action($private ? 'account_private' : 'account_public');
            flash('Profile updated.');
            redirect('/settings.php');
        }
    } elseif ($action === 'email') {
        $email = param($_POST, 'email');
        if (!$passwordOk) $errors[] = 'Current password is wrong.';
        elseif (!valid_email($email)) $errors[] = 'Enter a valid email.';
        elseif (q('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $me['id']])->fetch()) $errors[] = 'That email is already in use.';
        else {
            q('UPDATE users SET email = ? WHERE id = ?', [$email, $me['id']]);
            log_action('email_changed', 'from ' . $me['email'] . ' to ' . $email);
            flash('Email updated.');
            redirect('/settings.php');
        }
    } elseif ($action === 'password') {
        $new = param($_POST, 'new_password', false);
        if (!$passwordOk) $errors[] = 'Current password is wrong.';
        elseif (!valid_password($new)) $errors[] = 'New password must be 8–72 characters.';
        elseif ($new !== param($_POST, 'new_password_confirm', false)) $errors[] = 'New passwords do not match.';
        else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            session_regenerate_id(true);
            log_action('password_changed');
            flash('Password changed.');
            redirect('/settings.php');
        }
    } elseif ($action === 'delete') {
        if (!$passwordOk) $errors[] = 'Current password is wrong.';
        else {
            foreach (q('SELECT image_path FROM pins WHERE user_id = ?', [$me['id']])->fetchAll(PDO::FETCH_COLUMN) as $img) {
                delete_image($img);
            }
            delete_image($me['avatar_path']);
            log_action('account_deleted', 'username ' . $me['username']);
            q('DELETE FROM users WHERE id = ?', [$me['id']]); // pins, boards, saves cascade
            logout_session();
            flash('Your account was deleted.');
            redirect('/');
        }
    }
}

$title = 'Settings';
require ROOT . '/templates/header.php';
?>
<?php require ROOT . '/templates/errors.php'; ?>

<form method="post" enctype="multipart/form-data" class="card">
  <h2>Profile</h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="profile">
  <label>Display name <input name="display_name" maxlength="50" value="<?= e($me['display_name']) ?>"></label>
  <label>Bio <textarea name="bio" maxlength="300" rows="3"><?= e($me['bio']) ?></textarea></label>
  <label>Avatar (optional) <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
  <label class="check"><input type="checkbox" name="is_private" value="1" <?= $me['is_private'] ? 'checked' : '' ?>>
    Private account — only you can see your pieces and collections</label>
  <button class="btn">Save profile</button>
</form>

<form method="post" class="card">
  <h2>Email</h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="email">
  <label>New email <input type="email" name="email" required maxlength="255" value="<?= e($me['email']) ?>"></label>
  <label>Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
  <button class="btn">Change email</button>
</form>

<form method="post" class="card">
  <h2>Password</h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <label>Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
  <label>New password <input type="password" name="new_password" required minlength="8" maxlength="72" autocomplete="new-password"></label>
  <label>Confirm new password <input type="password" name="new_password_confirm" required autocomplete="new-password"></label>
  <button class="btn">Change password</button>
</form>

<form method="post" class="card">
  <h2>Delete account</h2>
  <p class="muted">Deletes your pieces, collections and saves. This can't be undone.</p>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="delete">
  <label>Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
  <button class="btn danger">Delete my account</button>
</form>
<?php require ROOT . '/templates/footer.php'; ?>
