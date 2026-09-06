<?php
// includes/functions.php
require_once __DIR__ . '/../config/database.php';

// ============================================================
//  توابع عمومی
// ============================================================

function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * slugify - تبدیل رشته به اسلاگ (فقط انگلیسی)
 */
function slugify($string) {
    $string = trim($string);
    $string = str_replace(' ', '-', $string);
    $string = preg_replace('/[^a-zA-Z0-9\-]/u', '', $string);
    $string = strtolower($string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

/**
 * slugify_persian - تبدیل رشته به اسلاگ با پشتیبانی از فارسی
 */
function slugify_persian($string) {
    if (empty($string)) {
        return 'untitled-' . time();
    }
    
    $string = trim($string);
    // تبدیل فاصله به -
    $string = preg_replace('/\s+/', '-', $string);
    // حذف کاراکترهای غیرمجاز (فقط حروف انگلیسی، اعداد، حروف فارسی و -)
    $string = preg_replace('/[^a-zA-Z0-9\-آ-ی]/u', '', $string);
    // حذف -های تکراری
    $string = preg_replace('/-+/', '-', $string);
    // حذف - از ابتدا و انتها
    $string = trim($string, '-');
    
    // اگر خالی شد، یک مقدار پیش‌فرض برگردان
    if (empty($string)) {
        $string = 'post-' . time();
    }
    
    // تبدیل به حروف کوچک (فقط برای کاراکترهای انگلیسی)
    $string = strtolower($string);
    return $string;
}

function getPersianDate($timestamp = null) {
    if ($timestamp === null) $timestamp = time();
    return jdate('Y/m/d', $timestamp);
}

function getPersianDateTime($timestamp = null) {
    if ($timestamp === null) $timestamp = time();
    return jdate('Y/m/d H:i', $timestamp);
}

// ============================================================
//  توابع امنیتی
// ============================================================

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// ============================================================
//  توابع دیتابیس - مقالات
// ============================================================

function getArticleById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT a.*, u.name as author_name, c.name as category_name, c.slug as category_slug 
                           FROM articles a 
                           LEFT JOIN users u ON a.author_id = u.id 
                           LEFT JOIN categories c ON a.category_id = c.id 
                           WHERE a.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getArticleBySlug($slug) {
    global $pdo;
    
    if (empty($slug)) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT a.*, u.name as author_name, c.name as category_name, c.slug as category_slug 
                           FROM articles a 
                           LEFT JOIN users u ON a.author_id = u.id 
                           LEFT JOIN categories c ON a.category_id = c.id 
                           WHERE a.slug = ? AND a.status = 'published'");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getArticles($limit = 9, $offset = 0, $category = null, $search = null) {
    global $pdo;
    
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    if ($limit < 1) $limit = 1;
    if ($offset < 0) $offset = 0;
    
    $sql = "SELECT a.*, u.name as author_name, c.name as category_name, c.slug as category_slug 
            FROM articles a 
            LEFT JOIN users u ON a.author_id = u.id 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'published'";
    $params = [];
    
    if ($category) {
        $sql .= " AND c.slug = ?";
        $params[] = $category;
    }
    
    if ($search && !empty(trim($search))) {
        $search = trim($search);
        $sql .= " AND (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getTotalArticles($category = null, $search = null) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'published'";
    $params = [];
    
    if ($category) {
        $sql .= " AND c.slug = ?";
        $params[] = $category;
    }
    
    if ($search && !empty(trim($search))) {
        $search = trim($search);
        $sql .= " AND (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// ============================================================
//  توابع دیتابیس - دسته‌بندی‌ها
// ============================================================

function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
    return $stmt->fetchAll();
}

function getCategoryBySlug($slug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getCategoryById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ============================================================
//  توابع دیتابیس - بنرها
// ============================================================

function getBanners($position = null) {
    global $pdo;
    $sql = "SELECT * FROM banners WHERE active = 1";
    if ($position) {
        $sql .= " AND position = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$position]);
    } else {
        $stmt = $pdo->query($sql);
    }
    return $stmt->fetchAll();
}

// ============================================================
//  توابع دیتابیس - منوها
// ============================================================

function getMenus($position = 'header') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM menus WHERE position = ? AND active = 1 ORDER BY sort_order ASC");
    $stmt->execute([$position]);
    return $stmt->fetchAll();
}

// ============================================================
//  توابع دیتابیس - تنظیمات
// ============================================================

function getSetting($key) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

function updateSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $value, $value]);
    return true;
}

// ============================================================
//  توابع دیتابیس - نظرات
// ============================================================

function getComments($article_id, $limit = 10, $offset = 0) {
    global $pdo;
    
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    if ($limit < 1) $limit = 1;
    if ($offset < 0) $offset = 0;
    
    $sql = "SELECT c.*, u.name as user_name 
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.article_id = ? AND c.status = 'approved' 
            ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$article_id]);
    return $stmt->fetchAll();
}

function getTotalComments($article_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE article_id = ? AND status = 'approved'");
    $stmt->execute([$article_id]);
    return $stmt->fetchColumn();
}

// ============================================================
//  توابع دیتابیس - مقالات مرتبط
// ============================================================

function getRelatedArticles($article_id, $category_id, $limit = 5) {
    global $pdo;
    
    $limit = (int)$limit;
    if ($limit < 1) $limit = 1;
    
    $sql = "SELECT a.*, u.name as author_name 
            FROM articles a 
            LEFT JOIN users u ON a.author_id = u.id 
            WHERE a.id != ? AND a.category_id = ? AND a.status = 'published' 
            ORDER BY a.created_at DESC LIMIT $limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$article_id, $category_id]);
    return $stmt->fetchAll();
}

function getPageBySlug($slug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// ============================================================
//  توابع نمایشی
// ============================================================

function formatViews($views) {
    if ($views >= 1000) {
        return number_format($views / 1000, 1) . 'K';
    }
    return number_format($views);
}

function truncateText($text, $limit = 100) {
    if (mb_strlen($text) <= $limit) return $text;
    return mb_substr($text, 0, $limit) . '...';
}

function generatePagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) return '';
    
    $base_url = rtrim($base_url, '&');
    if (strpos($base_url, '?') === false) {
        $base_url .= '?';
    } elseif (substr($base_url, -1) != '?' && substr($base_url, -1) != '&') {
        $base_url .= '&';
    }
    
    $html = '<div class="pagination">';
    if ($current_page > 1) {
        $html .= '<a href="' . $base_url . 'page=' . ($current_page - 1) . '">‹</a>';
    } else {
        $html .= '<span class="disabled">‹</span>';
    }
    
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="active">' . $i . '</span>';
        } elseif ($i == 1 || $i == $total_pages || abs($i - $current_page) <= 2) {
            $html .= '<a href="' . $base_url . 'page=' . $i . '">' . $i . '</a>';
        } elseif ($i == $current_page - 3 || $i == $current_page + 3) {
            $html .= '<span>…</span>';
        }
    }
    
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $base_url . 'page=' . ($current_page + 1) . '">›</a>';
    } else {
        $html .= '<span class="disabled">›</span>';
    }
    $html .= '</div>';
    return $html;
}

// ============================================================
//  تابع آپلود فایل
// ============================================================

function uploadFile($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطا در آپلود فایل'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'نوع فایل مجاز نیست. انواع مجاز: ' . implode(', ', $allowed_types)];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'حجم فایل باید کمتر از ۵ مگابایت باشد'];
    }
    
    $filename = time() . '_' . uniqid() . '.' . $extension;
    $filepath = $target_dir . '/' . $filename;
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'خطا در ذخیره فایل'];
}

// ============================================================
//  تابع jdate (تاریخ شمسی)
// ============================================================

function jdate($format, $timestamp = null) {
    if ($timestamp === null) $timestamp = time();
    
    $jmonths = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    $jweekdays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
    
    $date = getdate($timestamp);
    $year = $date['year'];
    $month = $date['mon'];
    $day = $date['mday'];
    $hours = $date['hours'];
    $minutes = $date['minutes'];
    $seconds = $date['seconds'];
    $weekday = $date['wday'];
    
    $gy = $year;
    $gm = $month;
    $gd = $day;
    
    $days = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $jd = $days[$gm - 1] + $gd;
    if ($gm > 2 && ($gy % 4 == 0 && ($gy % 100 != 0 || $gy % 400 == 0))) $jd++;
    
    $jy = $gy - 621;
    $jdn = $jd + 286;
    $jdn = $jdn % 365;
    $jdn = $jdn > 0 ? $jdn : 365;
    $jdn = $jdn + 1;
    
    $jmonth = 1;
    $jday = 1;
    $jm = 0;
    for ($i = 1; $i <= 12; $i++) {
        $days_in_month = ($i <= 6) ? 31 : 30;
        if ($i == 12 && ($jy % 4 == 0 && ($jy % 100 != 0 || $jy % 400 == 0))) $days_in_month = 30;
        if ($jdn <= $days_in_month) {
            $jmonth = $i;
            $jday = $jdn;
            break;
        }
        $jdn -= $days_in_month;
    }
    
    $replacements = [
        'Y' => $jy,
        'm' => str_pad($jmonth, 2, '0', STR_PAD_LEFT),
        'd' => str_pad($jday, 2, '0', STR_PAD_LEFT),
        'F' => $jmonths[$jmonth],
        'n' => $jmonth,
        'j' => $jday,
        'H' => str_pad($hours, 2, '0', STR_PAD_LEFT),
        'i' => str_pad($minutes, 2, '0', STR_PAD_LEFT),
        's' => str_pad($seconds, 2, '0', STR_PAD_LEFT),
        'l' => $jweekdays[($weekday + 1) % 7],
        'w' => ($weekday + 1) % 7,
    ];
    
    $result = '';
    $len = strlen($format);
    for ($i = 0; $i < $len; $i++) {
        $char = $format[$i];
        if (isset($replacements[$char])) {
            $result .= $replacements[$char];
        } else {
            $result .= $char;
        }
    }
    
    return $result;
}

// ============================================================
//  توابع امنیتی برای کاربران
// ============================================================

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

if (!function_exists('checkAdmin')) {
    function checkAdmin() {
        if (!isLoggedIn() || !isAdmin()) {
            redirect('/admin/login.php');
        }
    }
}
?>