<?php
// logout.php
session_start();
require_once 'includes/auth.php';

// حذف کوکی مرا به خاطر بسپار
if (isset($_COOKIE['remember_username'])) {
    setcookie('remember_username', '', time() - 3600, '/');
}

// خروج از حساب
logoutUser();
header('Location: index.php');
exit;
?>