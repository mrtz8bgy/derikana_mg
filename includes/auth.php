<?php
// includes/auth.php
// جلوگیری از اجرای دوباره session_start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        return true;
    }
    
    return false;
}

function registerUser($name, $username, $email, $password) {
    global $pdo;
    
    // بررسی تکراری نبودن
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'نام کاربری یا ایمیل قبلاً ثبت شده است'];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $username, $email, $hashedPassword]);
    
    return ['success' => true, 'id' => $pdo->lastInsertId()];
}

function logoutUser() {
    session_destroy();
    redirect('/');
}

function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

// توابع isLoggedIn و isAdmin در functions.php تعریف شده‌اند
// برای جلوگیری از خطای تعریف مجدد، با شرط بررسی می‌شوند
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        // تشخیص اینکه در کدام مسیر هستیم
        $current_path = $_SERVER['REQUEST_URI'];
        if (strpos($current_path, '/admin/') !== false) {
            redirect('/admin/login.php');
        } else {
            redirect('/login.php');
        }
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('/admin/login.php');
    }
}
?>