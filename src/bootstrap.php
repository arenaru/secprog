<?php
// Every page starts with: require __DIR__ . '/../src/bootstrap.php';

define('ROOT', dirname(__DIR__));

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
set_exception_handler(function (Throwable $e) {
    error_log((string) $e); // details go to the server log, never to the user
    http_response_code(500);
    echo 'Something went wrong.';
});

require ROOT . '/src/helpers.php';
require ROOT . '/src/db.php';
require ROOT . '/src/csrf.php';
require ROOT . '/src/auth.php';
require ROOT . '/src/access.php';
require ROOT . '/src/upload.php';

header_remove('X-Powered-By');
header("Content-Security-Policy: default-src 'self'; img-src 'self'; style-src 'self'; script-src 'none'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('mozaik');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
