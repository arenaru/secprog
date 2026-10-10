<?php
require __DIR__ . '/../src/bootstrap.php';

$me = current_user();
$hasMore = false;

if ($me) {
    $perPage = 24;
    $page = min(max(1, (int) param($_GET, 'page')), 500);
    [$vis, $params] = visible_pins_sql($me);
    $pins = q("SELECT p.id, p.title FROM pins p JOIN users u ON u.id = p.user_id
               WHERE $vis ORDER BY p.id DESC LIMIT ? OFFSET ?",
              [...$params, $perPage + 1, ($page - 1) * $perPage])->fetchAll();
    $hasMore = count($pins) > $perPage;
    $pins = array_slice($pins, 0, $perPage);
} else {
    // Guests get a fixed teaser; ?page= is ignored, so there's nothing more to fetch without an account.
    $pins = guest_preview_pins();
    $gated = true;
}

$title = 'Home';
require ROOT . '/templates/header.php';
require ROOT . '/templates/pin_grid.php';
?>
<?php if ($hasMore): ?><p class="center"><a class="btn light" href="/?page=<?= $page + 1 ?>">Load more</a></p><?php endif ?>
<?php require ROOT . '/templates/footer.php'; ?>
