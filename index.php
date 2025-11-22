<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    require __DIR__ . '/app/views/auth/login.php';
    exit;
}

require __DIR__ . '/app/router.php';
router();
?>
