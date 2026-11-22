<?php
require __DIR__ . '/includes/bootstrap.php';
if (is_post()) {
    csrf_check();
    keluar();
}
redirect('login.php');
