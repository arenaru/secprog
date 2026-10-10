<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
if (!posted()) not_found();

$boardId = id_param($_POST, 'board_id');
$pinId = id_param($_POST, 'pin_id');

// Only into your own board...
q('SELECT 1 FROM boards WHERE id = ? AND user_id = ?', [$boardId, $me['id']])->fetch() ?: not_found();

if (param($_POST, 'action') === 'remove') {
    q('DELETE FROM board_pins WHERE board_id = ? AND pin_id = ?', [$boardId, $pinId]);
    redirect('/board.php?id=' . $boardId);
}

// ...and only pins you can see. Otherwise "save" would let you probe private pin ids and then view
// them through your own board.
find_visible_pin($pinId, $me) ?? not_found();
q('INSERT IGNORE INTO board_pins (board_id, pin_id) VALUES (?, ?)', [$boardId, $pinId]);
flash('Saved to collection.');
redirect('/pin.php?id=' . $pinId);
