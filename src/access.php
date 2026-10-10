<?php
// Single source of truth for "who may see what".
//
// The Instagram private-account bugs all had the same shape: the profile page checked privacy,
// but some other endpoint (post-by-id, media URL, search, embeds) returned the post without the check.
// Here, every page that returns a pin, an image, a board or profile content goes through these
// functions, and image files live outside the web root so there is no unchecked path to them.

function is_admin(?array $viewer): bool
{
    return $viewer !== null && $viewer['role'] === 'admin';
}

function is_self(?array $viewer, array $owner): bool
{
    return $viewer !== null && (int) $viewer['id'] === (int) $owner['id'];
}

// $owner needs id, is_private, is_active. Admins can see for moderation.
function can_view_content(?array $viewer, array $owner): bool
{
    return is_self($viewer, $owner)
        || is_admin($viewer)
        || (!$owner['is_private'] && $owner['is_active']);
}

// $board is a boards row joined with its owner's is_private and is_active.
// Private boards are owner-only (not even admins); a public board still inherits its owner's privacy.
function can_view_board(?array $viewer, array $board): bool
{
    $owner = ['id' => $board['user_id'], 'is_private' => $board['is_private'], 'is_active' => $board['is_active']];
    return is_self($viewer, $owner)
        || ($board['visibility'] === 'public' && can_view_content($viewer, $owner));
}

// can_view_content() as a SQL condition for listings (feed, search, boards, single pin).
// The query must join the pin owner as `u`. The returned fragment is a constant, never user input.
function visible_pins_sql(?array $viewer): array
{
    if (is_admin($viewer)) return ['1 = 1', []];
    if ($viewer) return ['((u.is_private = 0 AND u.is_active = 1) OR u.id = ?)', [(int) $viewer['id']]];
    return ['(u.is_private = 0 AND u.is_active = 1)', []];
}

// Guests only get the newest few public pins (the home-feed teaser). The feed and image.php both use
// this list, so later pages, other pin ids and their image URLs all need an account.
function guest_preview_pins(): array
{
    [$vis, $params] = visible_pins_sql(null);
    return q("SELECT p.id, p.title FROM pins p JOIN users u ON u.id = p.user_id
              WHERE $vis ORDER BY p.id DESC LIMIT ?", [...$params, 12])->fetchAll();
}

// The only way to load a pin by id. Returns null for "doesn't exist" and "not allowed" alike.
function find_visible_pin(int $id, ?array $viewer): ?array
{
    [$vis, $params] = visible_pins_sql($viewer);
    $pin = q("SELECT p.*, u.username, u.display_name FROM pins p JOIN users u ON u.id = p.user_id
              WHERE p.id = ? AND $vis", [$id, ...$params])->fetch();
    return $pin ?: null;
}
