<?php

function config(string $key)
{
    static $config;
    $config ??= require ROOT . '/config/config.php';
    return $config[$key];
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Reads a string from $_GET/$_POST. Arrays (?q[]=x) and missing keys become ''.
function param(array $src, string $key, bool $trim = true): string
{
    $v = $src[$key] ?? '';
    if (!is_string($v)) return '';
    return $trim ? trim($v) : $v;
}

// Positive integer id, or 0.
function id_param(array $src, string $key): int
{
    $v = filter_var($src[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $v === false ? 0 : $v;
}

function redirect(string $path)
{
    header('Location: ' . $path);
    exit;
}

// Used for "doesn't exist" AND "not allowed", so responses never confirm a private thing exists.
function not_found()
{
    http_response_code(404);
    $title = 'Not found';
    require ROOT . '/templates/header.php';
    echo '<p class="empty">Nothing here.</p>';
    require ROOT . '/templates/footer.php';
    exit;
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $msg = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $msg;
}

function avatar_html(array $user): string
{
    if ($user['avatar_path']) {
        return '<img class="avatar" src="/image.php?avatar=' . (int) $user['id'] . '" alt="">';
    }
    $letter = mb_strtoupper(mb_substr($user['display_name'] ?: $user['username'], 0, 1));
    return '<span class="avatar">' . e($letter) . '</span>';
}

// --- validation ---

function len_between(string $s, int $min, int $max): bool
{
    if (!mb_check_encoding($s, 'UTF-8')) return false;
    $n = mb_strlen($s, 'UTF-8');
    return $n >= $min && $n <= $max;
}

function valid_email(string $email): bool
{
    return strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// bcrypt only uses the first 72 bytes, so longer passwords are rejected instead of silently truncated.
function valid_password(string $pw): bool
{
    return strlen($pw) >= 8 && strlen($pw) <= 72;
}

// Blocks javascript:, data:, etc. Only http(s) links are ever stored.
function valid_source_url(string $url): bool
{
    return strlen($url) <= 2048
        && filter_var($url, FILTER_VALIDATE_URL) !== false
        && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
}

function validate_pin(array $f): array
{
    $errors = [];
    if (!len_between($f['title'], 1, 100)) $errors[] = 'Title is required (max 100 characters).';
    if (!len_between($f['description'], 0, 2000)) $errors[] = 'Description is too long (max 2000 characters).';
    if ($f['source_url'] !== '' && !valid_source_url($f['source_url'])) $errors[] = 'Source link must be a valid http(s) URL.';
    return $errors;
}

// --- audit log ---

function log_action(string $action, string $detail = '', ?int $userId = null): void
{
    // mb_scrub: an invalid UTF-8 user agent must not make the INSERT (and therefore login) fail.
    q('INSERT INTO audit_logs (user_id, action, detail, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)', [
        $userId ?? ($_SESSION['uid'] ?? null),
        $action,
        mb_substr(mb_scrub($detail, 'UTF-8'), 0, 500),
        $_SERVER['REMOTE_ADDR'] ?? '',
        mb_substr(mb_scrub($_SERVER['HTTP_USER_AGENT'] ?? '', 'UTF-8'), 0, 255),
    ]);
}
