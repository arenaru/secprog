<?php $me = current_user(); $flash = flash(); $gated = $gated ?? false; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'mozaik') ?> · mozaik</title>
<link rel="icon" href="/assets/logo.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/style.css">
</head>
<body<?= $gated ? ' class="gated"' : '' ?>>
<header class="top">
  <a class="brand" href="/"><img src="/assets/logo.svg" alt="" width="34" height="28"><span>mozaik</span></a>
  <?php if ($me): ?>
  <form class="search" action="/search.php" method="get">
    <input type="search" name="q" placeholder="Search pieces or people" maxlength="100" aria-label="Search" value="<?= e(param($_GET, 'q')) ?>">
  </form>
  <nav>
    <a class="btn" href="/pin_create.php">Add piece</a>
    <?php if ($me['role'] === 'admin'): ?><a href="/admin/">Admin</a><?php endif ?>
    <a href="/settings.php">Settings</a>
    <form method="post" action="/logout.php"><?= csrf_field() ?><button class="link">Log out</button></form>
    <a href="/profile.php?u=<?= urlencode($me['username']) ?>" aria-label="Your profile"><?= avatar_html($me) ?></a>
  </nav>
  <?php else: ?>
  <a class="search fake" href="<?= $gated ? '#join' : '/login.php' ?>">Search pieces or people</a>
  <nav>
    <a href="/login.php">Log in</a>
    <a class="btn" href="/register.php">Sign up</a>
  </nav>
  <?php endif ?>
</header>
<main>
<?php if ($flash): ?><p class="flash"><?= e($flash) ?></p><?php endif ?>
