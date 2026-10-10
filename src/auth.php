<?php

// Reloaded from the DB on every request, so blocking or deleting a user ends their session immediately.
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (isset($_SESSION['uid'])) {
            $user = q('SELECT id, username, email, display_name, bio, avatar_path, role, is_private, is_active
                       FROM users WHERE id = ? AND is_active = 1', [$_SESSION['uid']])->fetch() ?: null;
            if (!$user) logout_session();
        }
    }
    return $user;
}

function require_login(): array
{
    return current_user() ?? redirect('/login.php');
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') not_found();
    return $user;
}

function login_session(int $userId): void
{
    session_regenerate_id(true); // prevents session fixation
    $_SESSION = ['uid' => $userId];
}

function logout_session(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}
