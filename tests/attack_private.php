<?php
// Attack test for the Instagram-style private-post leak.
//
//   php tests/attack_private.php http://localhost:8080
//
// Seeds a private user "atk_alice" with a pin and two boards, logs in as "atk_bob" (a stranger) and tries
// every route that could hand over alice's pin. Every attempt must fail. Then alice checks she can still
// see it, so the test can't pass just because pages are broken. Test users are deleted at the end.

define('ROOT', dirname(__DIR__));
require ROOT . '/src/helpers.php';
require ROOT . '/src/db.php';

$base = rtrim($argv[1] ?? 'http://localhost:8080', '/');
$pw = 'attack-test-password';
$cookie = '';
$failed = 0;

function check(string $name, bool $ok): void
{
    global $failed;
    echo ($ok ? "PASS  " : "FAIL  ") . $name . PHP_EOL;
    if (!$ok) $failed++;
}

function http(string $method, string $path, array $form = []): array
{
    global $base, $cookie;
    $ctx = stream_context_create(['http' => [
        'method' => $method,
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n" . ($cookie ? "Cookie: $cookie\r\n" : ''),
        'content' => http_build_query($form),
        'ignore_errors' => true,
        'follow_location' => 0,
    ]]);
    $body = (string) @file_get_contents($base . $path, false, $ctx);
    $headers = $http_response_header ?? [];
    foreach ($headers as $h) {
        if (preg_match('/^Set-Cookie:\s*(mozaik=[^;]*)/i', $h, $m)) $cookie = $m[1];
    }
    return [(int) (explode(' ', $headers[0] ?? 'HTTP 0')[1] ?? 0), $body];
}

function csrf(string $path = '/'): string
{
    [, $body] = http('GET', $path);
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $body, $m);
    return $m[1] ?? '';
}

function login(string $username): void
{
    global $cookie, $pw;
    $cookie = '';
    [$status] = http('POST', '/login.php', ['csrf' => csrf('/login.php'), 'login' => $username, 'password' => $pw]);
    check("login as $username", $status === 302);
}

// --- seed ---
q("DELETE FROM users WHERE username IN ('atk_alice', 'atk_bob')");
$hash = password_hash($pw, PASSWORD_DEFAULT);
q("INSERT INTO users (username, email, password_hash, is_private) VALUES ('atk_alice', 'atk_alice@test.local', ?, 1)", [$hash]);
$alice = (int) db()->lastInsertId();
q("INSERT INTO users (username, email, password_hash) VALUES ('atk_bob', 'atk_bob@test.local', ?)", [$hash]);
$bob = (int) db()->lastInsertId();

$img = bin2hex(random_bytes(16)) . '.png'; // 1x1 PNG
file_put_contents(config('upload_dir') . '/' . $img, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$secret = 'secret-' . bin2hex(random_bytes(4));
q('INSERT INTO pins (user_id, title, image_path) VALUES (?, ?, ?)', [$alice, $secret, $img]);
$pin = (int) db()->lastInsertId();
q("INSERT INTO boards (user_id, name, visibility) VALUES (?, 'alice public board', 'public')", [$alice]);
$alicePublicBoard = (int) db()->lastInsertId();
q("INSERT INTO boards (user_id, name, visibility) VALUES (?, 'alice private board', 'private')", [$alice]);
$alicePrivateBoard = (int) db()->lastInsertId();
q('INSERT INTO board_pins (board_id, pin_id) VALUES (?, ?), (?, ?)', [$alicePublicBoard, $pin, $alicePrivateBoard, $pin]);
q("INSERT INTO boards (user_id, name) VALUES (?, 'bob board')", [$bob]);
$bobBoard = (int) db()->lastInsertId();
// 13 public pins: the oldest falls outside the 12-pin guest teaser.
$bobPins = [];
for ($i = 0; $i < 13; $i++) {
    q('INSERT INTO pins (user_id, title, image_path) VALUES (?, ?, ?)', [$bob, "bob public $i", $img]);
    $bobPins[] = (int) db()->lastInsertId();
}

// --- attacker: logged out ---
check('guest: private pin page -> login redirect', http('GET', "/pin.php?id=$pin")[0] === 302);
check('guest: private image -> 404', http('GET', "/image.php?pin=$pin")[0] === 404);

// --- guest gate: teaser only ---
[$s, $body] = http('GET', '/?page=2');
check('guest: feed shows the teaser with the sign-up popup', $s === 200 && str_contains($body, 'id="join"') && str_contains($body, "image.php?pin={$bobPins[12]}"));
check('guest: tiles open the popup, not the piece', !str_contains($body, 'pin.php?id='));
check('guest: ?page=2 ignored, pin outside teaser not listed', !str_contains($body, "image.php?pin={$bobPins[0]}\""));
check('guest: teaser image -> 200', http('GET', "/image.php?pin={$bobPins[12]}")[0] === 200);
check('guest: public image outside teaser -> 404', http('GET', "/image.php?pin={$bobPins[0]}")[0] === 404);
check('guest: public pin page -> login redirect', http('GET', "/pin.php?id={$bobPins[12]}")[0] === 302);
check('guest: search -> login redirect', http('GET', '/search.php?q=bob')[0] === 302);
check('guest: profile -> login redirect', http('GET', '/profile.php?u=atk_bob')[0] === 302);

// --- attacker: bob, logged in, not alice ---
login('atk_bob');
check('bob: pin page by id -> 404', http('GET', "/pin.php?id=$pin")[0] === 404);
check('bob: image by id -> 404', http('GET', "/image.php?pin=$pin")[0] === 404);
[$s, $body] = http('GET', '/profile.php?u=atk_alice');
check('bob: profile shows "private", no pins', $s === 200 && str_contains($body, 'This account is private') && !str_contains($body, $secret));
[, $body] = http('GET', '/profile.php?tab=boards&u=atk_alice');
check('bob: profile boards tab hidden', !str_contains($body, 'alice public board') && !str_contains($body, 'alice private board'));
check('bob: search by exact title finds nothing', !str_contains(http('GET', '/search.php?q=' . urlencode($secret))[1], "pin.php?id=$pin"));
check('bob: not in home feed', !str_contains(http('GET', '/')[1], "pin.php?id=$pin"));
check('bob: alice public board -> 404', http('GET', "/board.php?id=$alicePublicBoard")[0] === 404);
check('bob: alice private board -> 404', http('GET', "/board.php?id=$alicePrivateBoard")[0] === 404);
[$s] = http('POST', '/save.php', ['csrf' => csrf(), 'board_id' => $bobBoard, 'pin_id' => $pin]);
$saved = q('SELECT COUNT(*) FROM board_pins WHERE board_id = ?', [$bobBoard])->fetchColumn();
check('bob: save alice pin into own board -> 404, nothing saved', $s === 404 && (int) $saved === 0);
check('bob: edit page of alice pin -> 404', http('GET', "/pin_edit.php?id=$pin")[0] === 404);
[$s] = http('POST', "/pin_edit.php?id=$pin", ['csrf' => csrf(), 'action' => 'delete']);
check('bob: delete alice pin -> 404, pin still there', $s === 404 && q('SELECT 1 FROM pins WHERE id = ?', [$pin])->fetch());
[$s] = http('POST', '/save.php', ['board_id' => $bobBoard, 'pin_id' => $pin]);
check('bob: POST without CSRF token -> 400', $s === 400);

// --- control: alice still sees her own stuff ---
login('atk_alice');
[$s, $body] = http('GET', "/pin.php?id=$pin");
check('alice: own pin page -> 200', $s === 200 && str_contains($body, $secret));
check('alice: own image -> 200', http('GET', "/image.php?pin=$pin")[0] === 200);
check('alice: own private board -> 200', http('GET', "/board.php?id=$alicePrivateBoard")[0] === 200);

// --- cleanup ---
q("DELETE FROM users WHERE username IN ('atk_alice', 'atk_bob')");
@unlink(config('upload_dir') . '/' . $img);

echo $failed ? "\n$failed check(s) FAILED\n" : "\nAll checks passed\n";
exit($failed ? 1 : 0);
