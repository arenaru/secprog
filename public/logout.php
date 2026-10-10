<?php
require __DIR__ . '/../src/bootstrap.php';

// POST + CSRF only: a GET logout could be triggered by any <img src="/logout.php"> on another site.
if (posted() && current_user()) {
    log_action('logout');
    logout_session();
}
redirect('/');
