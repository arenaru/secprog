<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) redirect('/');

// Verified against when the account doesn't exist, so response time doesn't reveal valid usernames.
const DUMMY_HASH = '$2y$10$b7oyNm/g1mf6iXbix7boC.weN732BrKDcH5/RmMMTw1eCuFMCrp4a';

$error = '';
$login = '';

if (posted()) {
    $login = param($_POST, 'login');
    $password = param($_POST, 'password', false);
    $u = q('SELECT id, password_hash, is_active, locked_until > NOW() AS locked
            FROM users WHERE username = ? OR email = ?', [$login, $login])->fetch();

    if ($u && $u['locked']) {
        $error = 'Too many failed attempts. Try again in 15 minutes.';
        log_action('login_locked', $login, (int) $u['id']);
    } elseif ($u && $u['is_active'] && password_verify($password, $u['password_hash'])) {
        q('UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = ?', [$u['id']]);
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
        }
        login_session((int) $u['id']);
        log_action('login_success');
        redirect('/');
    } else {
        if ($u) {
            // 5th failure in a row locks the account for 15 minutes. locked_until is assigned first
            // because MySQL applies SET clauses left to right (it must see the old count).
            q('UPDATE users SET
                 locked_until = IF(failed_login_count >= 4, NOW() + INTERVAL 15 MINUTE, NULL),
                 failed_login_count = IF(failed_login_count >= 4, 0, failed_login_count + 1)
               WHERE id = ?', [$u['id']]);
        } else {
            password_verify($password, DUMMY_HASH);
        }
        log_action('login_failed', $login, $u ? (int) $u['id'] : null);
        $error = 'Wrong username/email or password.';
    }
}

$title = 'Log in';
require ROOT . '/templates/header.php';
?>
<form method="post" class="card narrow">
  <h1>Log in</h1>
  <?php if ($error): ?><ul class="errors"><li><?= e($error) ?></li></ul><?php endif ?>
  <?= csrf_field() ?>
  <label>Username or email <input name="login" required maxlength="255" autocomplete="username" value="<?= e($login) ?>"></label>
  <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
  <button class="btn">Log in</button>
  <p class="muted">No account? <a href="/register.php">Sign up</a></p>
</form>
<?php require ROOT . '/templates/footer.php'; ?>
