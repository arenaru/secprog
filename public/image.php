<?php
require __DIR__ . '/../src/bootstrap.php';

$me = current_user();
session_write_close(); // don't hold the session lock while streaming; a grid loads many images at once

if ($pinId = id_param($_GET, 'pin')) {
    // Guests: only the images shown in the home-feed teaser.
    if (!$me && !in_array($pinId, array_map('intval', array_column(guest_preview_pins(), 'id')), true)) not_found();
    // Same check as pin.php. Files live outside the web root, so this is the ONLY way to get one:
    // a leaked or guessed id/filename of a private pin gets you a 404, not the picture.
    $file = find_visible_pin($pinId, $me)['image_path'] ?? not_found();
} else {
    // Avatars are public to members, like the name and bio on a private profile.
    if (!$me) not_found();
    $file = q('SELECT avatar_path FROM users WHERE id = ? AND is_active = 1', [id_param($_GET, 'avatar')])->fetchColumn() ?: not_found();
}

$path = config('upload_dir') . '/' . basename($file);
if (!is_file($path)) not_found();

$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
header('Content-Type: ' . ($types[pathinfo($path, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=300'); // never in shared caches; short so going private takes effect fast
readfile($path);
