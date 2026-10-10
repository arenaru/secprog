<?php
// Demo content so the feed isn't empty: user "demo" with 16 generated tile-pattern pieces.
//   php -d extension=gd database/seed_demo.php
// Re-running replaces the demo user. Demo login: demo / demo-password-123 (local use only).

define('ROOT', dirname(__DIR__));
require ROOT . '/src/helpers.php';
require ROOT . '/src/db.php';

$palette = [[43, 58, 140], [138, 92, 196], [30, 156, 116], [201, 154, 62], [122, 134, 196], [91, 107, 179], [77, 181, 146], [216, 181, 114]];
$titles = ['Night in Lisbon', 'Lilac hour', 'Fern study', 'Brass and linen', 'Blue tile kitchen', 'Moss wall', 'Byzantine dome',
           'Ceramic studio', 'Plaster textures', 'Harbour glass', 'Olive grove', 'Indigo stairs', 'Market awnings', 'Courtyard light',
           'Terrazzo floor', 'Evening in Fez'];

foreach (q("SELECT image_path FROM pins p JOIN users u ON u.id = p.user_id WHERE u.username = 'demo'")->fetchAll(PDO::FETCH_COLUMN) as $old) {
    @unlink(config('upload_dir') . '/' . basename($old));
}
q("DELETE FROM users WHERE username = 'demo'");
q("INSERT INTO users (username, email, password_hash, display_name, bio) VALUES ('demo', 'demo@mozaik.test', ?, 'Demo studio', 'Tiles, glass and plaster.')",
  [password_hash('demo-password-123', PASSWORD_DEFAULT)]);
$user = (int) db()->lastInsertId();

foreach ($titles as $i => $title) {
    $w = 600;
    $h = [450, 800, 600, 900, 500, 700][$i % 6];
    $img = imagecreatetruecolor($w, $h);
    [$r, $g, $b] = $palette[$i % count($palette)];
    imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));
    // A loose scatter of lighter tesserae so each piece has texture.
    for ($k = 0; $k < 40; $k++) {
        [$r2, $g2, $b2] = $palette[random_int(0, count($palette) - 1)];
        $x = random_int(0, $w / 30) * 30;
        $y = random_int(0, $h / 30) * 30;
        imagefilledrectangle($img, $x + 2, $y + 2, $x + 27, $y + 27, imagecolorallocatealpha($img, $r2, $g2, $b2, 70));
    }
    $name = bin2hex(random_bytes(16)) . '.jpg';
    imagejpeg($img, config('upload_dir') . '/' . $name, 85);
    imagedestroy($img);
    q('INSERT INTO pins (user_id, title, description, image_path) VALUES (?, ?, ?, ?)', [$user, $title, 'Demo piece.', $name]);
}
echo "Seeded demo user with " . count($titles) . " pieces\n";
