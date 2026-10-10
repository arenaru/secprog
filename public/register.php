<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) redirect('/');

$errors = [];
$f = ['username' => '', 'email' => ''];

if (posted()) {
    $f = ['username' => param($_POST, 'username'), 'email' => param($_POST, 'email')];
    $password = param($_POST, 'password', false);

    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $f['username'])) $errors[] = 'Username: 3–30 letters, numbers or _.';
    if (!valid_email($f['email'])) $errors[] = 'Enter a valid email.';
    if (!valid_password($password)) $errors[] = 'Password must be 8–72 characters.';
    if ($password !== param($_POST, 'password_confirm', false)) $errors[] = 'Passwords do not match.';
    if (!$errors && q('SELECT 1 FROM users WHERE username = ? OR email = ?', [$f['username'], $f['email']])->fetch()) {
        $errors[] = 'Username or email is already taken.';
    }

    if (!$errors) {
        // role, is_active, etc. are not in this INSERT, so they can't be set from the form.
        q('INSERT INTO users (username, email, password_hash, display_name) VALUES (?, ?, ?, ?)',
          [$f['username'], $f['email'], password_hash($password, PASSWORD_DEFAULT), $f['username']]);
        $id = (int) db()->lastInsertId();
        login_session($id);
        log_action('register');
        redirect('/');
    }
}

$title = 'Sign up';
require ROOT . '/templates/header.php';
?>
<form method="post" class="card narrow">
  <h1>Sign up</h1>
  <?php require ROOT . '/templates/errors.php'; ?>
  <?= csrf_field() ?>
  <label>Username <input name="username" required maxlength="30" pattern="[A-Za-z0-9_]{3,30}" autocomplete="username" value="<?= e($f['username']) ?>"></label>
  <label>Email <input type="email" name="email" required maxlength="255" value="<?= e($f['email']) ?>"></label>
  <label>Password <input type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"></label>
  <label>Confirm password <input type="password" name="password_confirm" required autocomplete="new-password"></label>
  <button class="btn">Create account</button>
  <p class="muted">Have an account? <a href="/login.php">Log in</a></p>
</form>
<?php require ROOT . '/templates/footer.php'; ?>
