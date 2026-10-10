<?php
// Copy to config.php (git-ignored) and fill in real values.
return [
    'db_dsn' => 'mysql:host=127.0.0.1;dbname=mozaik;charset=utf8mb4',
    'db_user' => 'mozaik',
    'db_pass' => 'change-me',
    // Outside public/ on purpose: images are only reachable through public/image.php, which checks access.
    'upload_dir' => __DIR__ . '/../storage/uploads',
];
