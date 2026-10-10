<?php

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

// True for a POST with a valid CSRF token. A POST with a bad token is rejected outright,
// so every state-changing handler is written as: if (posted()) { ... }
function posted(): bool
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return false;
    if (!hash_equals(csrf_token(), param($_POST, 'csrf'))) {
        http_response_code(400);
        exit('Invalid or expired form. Go back, reload and try again.');
    }
    return true;
}
