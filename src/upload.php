<?php

// Validates an uploaded image and stores a re-encoded copy. Returns the stored filename, or null with $error set.
// Re-encoding with GD drops anything that isn't pixels: embedded PHP/HTML polyglots and EXIF (incl. GPS location).
function store_image(array $file, ?string &$error): ?string
{
    $error = null;
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Choose an image to upload (max 5 MB).';
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        $error = 'Image must be 5 MB or smaller.';
        return null;
    }

    // Type comes from the file's bytes, never from the client's filename or Content-Type.
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $size = @getimagesize($file['tmp_name']);
    if (!isset($types[$mime]) || !$size) {
        $error = 'Only JPG, PNG or WebP images are allowed.';
        return null;
    }
    if ($size[0] * $size[1] > 40_000_000) { // decompression bomb guard
        $error = 'Image dimensions are too large.';
        return null;
    }

    $img = @imagecreatefromstring(file_get_contents($file['tmp_name']));
    if (!$img) {
        $error = 'That image could not be read.';
        return null;
    }

    $name = bin2hex(random_bytes(16)) . '.' . $types[$mime]; // random name: nothing user-controlled hits the disk path
    $dest = config('upload_dir') . '/' . $name;
    if ($types[$mime] === 'png') {
        imagesavealpha($img, true);
        $ok = imagepng($img, $dest);
    } elseif ($types[$mime] === 'webp') {
        $ok = imagewebp($img, $dest, 85);
    } else {
        $ok = imagejpeg($img, $dest, 85);
    }
    imagedestroy($img);

    if (!$ok) {
        $error = 'Could not save the image.';
        return null;
    }
    return $name;
}

function delete_image(?string $name): void
{
    if ($name) @unlink(config('upload_dir') . '/' . basename($name));
}
