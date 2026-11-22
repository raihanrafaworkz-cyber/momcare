<?php
require __DIR__ . '/includes/bootstrap.php';
$u = user_saat_ini();
redirect($u ? beranda_untuk($u['role']) : 'login.php');
