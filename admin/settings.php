<?php
// admin/settings.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// ===== ذخیره تنظیمات =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        // تنظیمات عمومی
        'site_title' => cleanInput($_POST['site_title'] ?? ''),
        'site_description' => cleanInput($_POST['site_description'] ?? ''),
        'site_keywords' => cleanInput($_POST['site_keywords'] ?? ''),
        'posts_per_page' => (int)($_POST['posts_per_page'] ?? 9),
        'site_language' => cleanInput($_POST['site_language'] ?? 'fa'),
        'site_timezone' => cleanInput($_POST['site_timezone'] ?? 'Asia/Tehran'),
        
        // تنظیمات فوتر
        'footer_copyright' => cleanInput($_POST['footer_copyright'] ?? ''),
        'footer_phone' => cleanInput($_POST['footer_phone'] ?? ''),
        'footer_email' => cleanInput($_POST['footer_email'] ?? ''),
        'footer_address' => cleanInput($_POST['footer_address'] ?? ''),
        'footer_social' => cleanInput($_POST['footer_social'] ?? ''),
        
        // تنظیمات سئو
        'seo_meta_title' => cleanInput($_POST['seo_meta_title'] ?? ''),
        'seo_meta_description' => cleanInput($_POST['seo_meta_description'] ?? ''),
        'seo_meta_keywords' => cleanInput($_POST['seo_meta_keywords'] ?? ''),
        
        // تنظیمات ظاهری
        'theme_color' => cleanInput($_POST['theme_color'] ?? '#6C63FF'),
        'theme_mode' => cleanInput($_POST['theme_mode'] ?? 'light'),
        'logo_text' => cleanInput($_POST['logo_text'] ?? ''),
        
        // تنظیمات اجتماعی
        'social_instagram' => cleanInput($_POST['social_instagram'] ?? ''),
        'social_telegram' => cleanInput($_POST['social_telegram'] ?? ''),
        'social_twitter' => cleanInput($_POST['social_twitter'] ?? ''),
        'social_youtube' => cleanInput($_POST['social_youtube'] ?? ''),
        'social_linkedin' => cleanInput($_POST['social_linkedin'] ?? ''),
        'social_github' => cleanInput($_POST['social_github'] ?? ''),
        
        // تنظیمات ایمیل
        'email_admin' => cleanInput($_POST['email_admin'] ?? ''),
        'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
        'email_contact' => cleanInput($_POST['email_contact'] ?? ''),
        
        // تنظیمات امنیتی
        'allow_registration' => isset($_POST['allow_registration']) ? 1 : 0,
        'require_email_verification' => isset($_POST['require_email_verification']) ? 1 : 0,
        'captcha_enabled' => isset($_POST['captcha_enabled']) ? 1 : 0,
        
        // تنظیمات دیگر
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
        'maintenance_message' => cleanInput($_POST['maintenance_message'] ?? ''),
        'analytics_code' => $_POST['analytics_code'] ?? '',
        'custom_css' => $_POST['custom_css'] ?? '',
        'custom_js' => $_POST['custom_js'] ?? '',
    ];
    
    try {
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            
            // تعیین گروه تنظیمات
            $group = 'general';
            if (strpos($key, 'footer_') === 0) $group = 'footer';
            elseif (strpos($key, 'seo_') === 0) $group = 'seo';
            elseif (strpos($key, 'social_') === 0) $group = 'social';
            elseif (strpos($key, 'email_') === 0) $group = 'email';
            elseif (in_array($key, ['theme_color', 'theme_mode', 'logo_text'])) $group = 'appearance';
            elseif (in_array($key, ['allow_registration', 'require_email_verification', 'captcha_enabled'])) $group = 'security';
            elseif (in_array($key, ['maintenance_mode', 'maintenance_message'])) $group = 'maintenance';
            elseif (in_array($key, ['analytics_code', 'custom_css', 'custom_js'])) $group = 'advanced';
            
            $stmt->execute([$key, $value, $group, $value]);
        }
        $message = '✅ تنظیمات با موفقیت ذخیره شد!';
    } catch (PDOException $e) {
        $error = '❌ خطا در ذخیره تنظیمات: ' . $e->getMessage();
    }
}

// ===== دریافت تنظیمات =====
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_data = [];
while ($row = $stmt->fetch()) {
    $settings_data[$row['setting_key']] = $row['setting_value'];
}

// ===== تنظیمات پیش‌فرض =====
$defaults = [
    'site_title' => 'مجله آنوشا',
    'site_description' => 'جدیدترین مقالات و دانستنی‌ها در حوزه تکنولوژی، سبک زندگی، سلامت و علم',
    'site_keywords' => 'مجله, مقاله, تکنولوژی, سبک زندگی, سلامت, علم',
    'posts_per_page' => 9,
    'site_language' => 'fa',
    'site_timezone' => 'Asia/Tehran',
    'footer_copyright' => '© ۲۰۲۶ مجله آنلاین دریکانا - تمامی حقوق محفوظ است',
    'footer_phone' => '۰۹۹۲۶۰۰۸۶۵۰',
    'footer_email' => 'info@anosha.com',
    'footer_address' => 'تهران، ایران',
    'footer_social' => 'اینستاگرام, تلگرام, توییتر',
    'seo_meta_title' => '',
    'seo_meta_description' => '',
    'seo_meta_keywords' => '',
    'theme_color' => '#6C63FF',
    'theme_mode' => 'light',
    'logo_text' => 'دریکانا',
    'social_instagram' => '',
    'social_telegram' => '',
    'social_twitter' => '',
    'social_youtube' => '',
    'social_linkedin' => '',
    'social_github' => '',
    'email_admin' => 'admin@anosha.com',
    'email_notifications' => 1,
    'email_contact' => 'contact@anosha.com',
    'allow_registration' => 1,
    'require_email_verification' => 0,
    'captcha_enabled' => 0,
    'maintenance_mode' => 0,
    'maintenance_message' => 'سایت در حال بروزرسانی است. لطفاً بعداً مراجعه کنید.',
    'analytics_code' => '',
    'custom_css' => '',
    'custom_js' => '',
];

// ترکیب تنظیمات با پیش‌فرض‌ها
foreach ($defaults as $key => $value) {
    if (!isset($settings_data[$key])) {
        $settings_data[$key] = $value;
    }
}

// ===== پاک کردن کش =====
if (isset($_GET['clear_cache'])) {
    // حذف فایل‌های کش (در صورت وجود)
    $cache_dir = __DIR__ . '/../cache/';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $message = '✅ کش با موفقیت پاک شد!';
    } else {
        $message = '⚠️ پوشه کش وجود ندارد!';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>تنظیمات سایت</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <script>(function(){try{var t=localStorage.getItem("theme");document.documentElement.setAttribute("data-theme",t||"dark");}catch(e){}})();</script>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <button class="sidebar-toggle" onclick="toggleAdminSidebar()" aria-label="منو"><i class="fas fa-bars"></i></button>
        <div class="logo">
            <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="gmark" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse"><stop stop-color="#EAD6A6"/><stop offset=".45" stop-color="#D6B36A"/><stop offset="1" stop-color="#A9853E"/></linearGradient></defs><path d="M14 8h20l8 10-18 22L6 18 14 8z" fill="url(#gmark)"/><path d="M6 18h36M14 8l4 10 6-10 6 10 4-10M18 18l6 22 6-22" stroke="#0B0D12" stroke-opacity=".38" stroke-width="1.6"/></svg></span>
            <span class="brand-en">دریکانا</span>
            <span class="badge">تنظیمات</span>
        </div>
        <button class="theme-btn" onclick="toggleAdminTheme()" aria-label="تغییر پوسته"><i class="fas fa-sun i-sun"></i><i class="fas fa-moon i-moon"></i></button>
        <div class="user-info">
            <span style="font-weight:500;"><?php echo $_SESSION['user_name']; ?></span>
            <div class="avatar"><?php echo mb_substr($_SESSION['user_name'], 0, 1); ?></div>
            <button class="back-btn" onclick="window.location.href='index.php'">داشبورد</button>
            <button class="back-btn danger" onclick="window.location.href='logout.php'">خروج</button>
        </div>
    </header>

    <!-- Content -->
    <!-- ===== ADMIN LAYOUT ===== -->
    <div class="admin-layout">
        <?php $current_page = 'settings.php'; require __DIR__ . '/partials/sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="admin-content">
        <div class="page-title"><i class="fas fa-cog"></i> تنظیمات عمومی سایت</div>

        <?php if ($message): ?>
            <?php $msg_type = strpos($message, '✅') !== false ? 'success' : (strpos($message, '❌') !== false ? 'error' : (strpos($message, '⚠️') !== false ? 'warning' : 'info')); $msg_icon = ['success' => 'fa-circle-check', 'error' => 'fa-circle-xmark', 'warning' => 'fa-triangle-exclamation', 'info' => 'fa-circle-info'][$msg_type]; ?>
            <div class="message <?php echo $msg_type; ?>"><i class="fas <?php echo $msg_icon; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><i class="fas fa-circle-xmark"></i> <?php echo preg_replace('/[\x{2705}\x{274C}\x{26A0}\x{FE0F}\x{23F3}\x{26D4}]\s*/u', '', $error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <!-- ===== تنظیمات عمومی ===== -->
            <div class="admin-form">
                <div class="section-title"><i class="fas fa-globe"></i> تنظیمات عمومی</div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>عنوان سایت</label>
                        <input type="text" name="site_title" value="<?php echo htmlspecialchars($settings_data['site_title'] ?? ''); ?>" />
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>توضیحات سایت</label>
                        <textarea name="site_description" rows="2"><?php echo htmlspecialchars($settings_data['site_description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>کلمات کلیدی (با کاما جدا کنید)</label>
                        <input type="text" name="site_keywords" value="<?php echo htmlspecialchars($settings_data['site_keywords'] ?? ''); ?>" placeholder="مجله, مقاله, تکنولوژی" />
                    </div>
                </div>
                <div class="form-row three">
                    <div class="form-group">
                        <label>تعداد مقالات در صفحه</label>
                        <select name="posts_per_page">
                            <option value="6" <?php echo ($settings_data['posts_per_page'] ?? 9) == 6 ? 'selected' : ''; ?>>۶</option>
                            <option value="9" <?php echo ($settings_data['posts_per_page'] ?? 9) == 9 ? 'selected' : ''; ?>>۹</option>
                            <option value="12" <?php echo ($settings_data['posts_per_page'] ?? 9) == 12 ? 'selected' : ''; ?>>۱۲</option>
                            <option value="15" <?php echo ($settings_data['posts_per_page'] ?? 9) == 15 ? 'selected' : ''; ?>>۱۵</option>
                            <option value="20" <?php echo ($settings_data['posts_per_page'] ?? 9) == 20 ? 'selected' : ''; ?>>۲۰</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>زبان سایت</label>
                        <select name="site_language">
                            <option value="fa" <?php echo ($settings_data['site_language'] ?? 'fa') == 'fa' ? 'selected' : ''; ?>>فارسی</option>
                            <option value="en" <?php echo ($settings_data['site_language'] ?? 'fa') == 'en' ? 'selected' : ''; ?>>انگلیسی</option>
                            <option value="ar" <?php echo ($settings_data['site_language'] ?? 'fa') == 'ar' ? 'selected' : ''; ?>>عربی</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>منطقه زمانی</label>
                        <select name="site_timezone">
                            <option value="Asia/Tehran" <?php echo ($settings_data['site_timezone'] ?? 'Asia/Tehran') == 'Asia/Tehran' ? 'selected' : ''; ?>>تهران</option>
                            <option value="Asia/Dubai" <?php echo ($settings_data['site_timezone'] ?? 'Asia/Tehran') == 'Asia/Dubai' ? 'selected' : ''; ?>>دبی</option>
                            <option value="UTC" <?php echo ($settings_data['site_timezone'] ?? 'Asia/Tehran') == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                            <option value="America/New_York" <?php echo ($settings_data['site_timezone'] ?? 'Asia/Tehran') == 'America/New_York' ? 'selected' : ''; ?>>نیویورک</option>
                            <option value="Europe/London" <?php echo ($settings_data['site_timezone'] ?? 'Asia/Tehran') == 'Europe/London' ? 'selected' : ''; ?>>لندن</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ===== تنظیمات فوتر ===== -->
            <div class="admin-form">
                <div class="section-title"><i class="fas fa-copyright"></i> تنظیمات فوتر</div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>متن کپی‌رایت</label>
                        <input type="text" name="footer_copyright" value="<?php echo htmlspecialchars($settings_data['footer_copyright'] ?? ''); ?>" />
                    </div>
                </div>
                <div class="form-row three">
                    <div class="form-group">
                        <label>شماره تماس</label>
                        <input type="text" name="footer_phone" value="<?php echo htmlspecialchars($settings_data['footer_phone'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label>ایمیل</label>
                        <input type="email" name="footer_email" value="<?php echo htmlspecialchars($settings_data['footer_email'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label>آدرس</label>
                        <input type="text" name="footer_address" value="<?php echo htmlspecialchars($settings_data['footer_address'] ?? ''); ?>" />
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>شبکه‌های اجتماعی (با کاما جدا کنید)</label>
                        <input type="text" name="footer_social" value="<?php echo htmlspecialchars($settings_data['footer_social'] ?? ''); ?>" placeholder="اینستاگرام, تلگرام, توییتر" />
                    </div>
                </div>
            </div>

            <!-- ===== تنظیمات ظاهری ===== -->
            <div class="admin-form">
                <div class="section-title"><i class="fas fa-palette"></i> تنظیمات ظاهری</div>
                
                <div class="form-row three">
                    <div class="form-group">
                        <label>رنگ اصلی سایت</label>
                        <input type="color" name="theme_color" value="<?php echo htmlspecialchars($settings_data['theme_color'] ?? '#6C63FF'); ?>" />
                        <div style="margin-top:4px;">
                            <span class="color-preview-box" style="background:<?php echo htmlspecialchars($settings_data['theme_color'] ?? '#6C63FF'); ?>;"></span>
                            <span style="font-size:12px; color:var(--text-light);"><?php echo htmlspecialchars($settings_data['theme_color'] ?? '#6C63FF'); ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>حالت نمایش پیش‌فرض</label>
                        <select name="theme_mode">
                            <option value="light" <?php echo ($settings_data['theme_mode'] ?? 'light') == 'light' ? 'selected' : ''; ?>>☀️ روشن</option>
                            <option value="dark" <?php echo ($settings_data['theme_mode'] ?? 'light') == 'dark' ? 'selected' : ''; ?>>🌙 تیره</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>متن لوگو</label>
                        <input type="text" name="logo_text" value="<?php echo htmlspecialchars($settings_data['logo_text'] ?? 'دریکانا'); ?>" />
                    </div>
                </div>
            </div>

            <!-- ===== تنظیمات سئو ===== -->
            <div class="admin-form">
                <div class="section-title"><i class="fas fa-search"></i> تنظیمات سئو</div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>عنوان متا (Meta Title)</label>
                        <input type="text" name="seo_meta_title" value="<?php echo htmlspecialchars($settings_data['seo_meta_title'] ?? ''); ?>" placeholder="عنوان پیش‌فرض برای صفحات" />
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>توضیحات متا (Meta Description)</label>
                        <textarea name="seo_meta_description" rows="2"><?php echo htmlspecialchars($settings_data['seo_meta_description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>کلمات کلیدی متا (Meta Keywords)</label>
                        <input type="text" name="seo_meta_keywords" value="<?php echo htmlspecialchars($settings_data['seo_meta_keywords'] ?? ''); ?>" placeholder="کلمات کلیدی با کاما جدا کنید" />
                    </div>
                </div>
            </div>

            <!-- ===== تنظیمات شبکه‌های اجتماعی ===== -->
            <div class="admin-form">
                <div class="section-title"><i class="fas fa-share-alt"></i> شبکه‌های اجتماعی</div>
                
                <div class="form-row three">
                    <div class="form-group">
                        <label><i class="fab fa-instagram" style="color:#E4405F;"></i> اینستاگرام</label>
                        <input type="text" name="social_instagram" value="<?php echo htmlspecialchars($settings_data['social_instagram'] ?? ''); ?>" placeholder="https://instagram.com/..." />
                    </div>
                    <div class="form-group">
                        <label><i class="fab fa-telegram" style="color:#0088CC;"></i> تلگرام</label>
                        <input type="text" name="social_telegram" value="<?php echo htmlspecialchars($settings_data['social_telegram'] ?? ''); ?>" placeholder="https://t.me/..." />
                    </div>
                    <div class="form-group">
                        <label><i class="fab fa-twitter" style="color:#1DA1F2;"></i> توییتر</label>
                        <input type="text" name="social_twitter" value="<?php echo htmlspecialchars($settings_data['social_twitter'] ?? ''); ?>" placeholder="https://twitter.com/..." />
                    </div>
                </div>
                <div class="form-row three">
                    <div class="form-group">
                        <label><i class="fab fa-youtube" style="color:#FF0000;"></i> یوتیوب</label>
                        <input type="text" name="social_youtube" value="<?php echo htmlspecialchars($settings_data['social_youtube'] ?? ''); ?>" placeholder="https://youtube.com/..." />
                    </div>
                    <div class="form-group">
                        <label><i class="fab fa-linkedin" style="color:#0077B5;"></i> لینکدین</label>
                        <input type="text" name="social_linkedin" value="<?php echo htmlspecialchars($settings_data['social_linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/..." />
                    </div>
                    <div class="form-group">
                        <label><i class="fab fa-github" style="color:#333;"></i> گیت‌هاب</label>
                        <input type="text" name="social_github" value="<?php echo htmlspecialchars($settings_data['social_github'] ?? ''); ?>" placeholder="https://github.com/..." />
                    </div>
                </div>
            </div>

            <!-- ===== تنظیمات ایمیل ===== -->
            <div class="admin-form">
                <div class="section-title"><i class="fas fa-envelope"></i> تنظیمات ایمیل</div>
                
                <div class="form-row three">
                    <div class="form-group">
                        <label>ایمیل ادمین</label>
                        <input type="email" name="email_admin" value="<?php echo htmlspecialchars($settings_data['email_admin'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label>ایمیل تماس</label>
                        <input type="email" name="email_contact" value="<?php echo htmlspecialchars($settings_data['email_contact'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="email_notifications" value="1" <?php echo ($settings_data['email_notifications'] ?? 1) ? 'checked' : ''; ?> />
                            <label>فعال کردن اعلان‌های ایمیل</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== تنظیمات امنیتی ===== -->
            <div class="admin-form">
                <div class="section-title"><i class="fas fa-shield-alt"></i> تنظیمات امنیتی</div>
                
                <div class="form-row three">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="allow_registration" value="1" <?php echo ($settings_data['allow_registration'] ?? 1) ? 'checked' : ''; ?> />
                            <label>فعال کردن ثبت‌نام کاربران</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="require_email_verification" value="1" <?php echo ($settings_data['require_email_verification'] ?? 0) ? 'checked' : ''; ?> />
                            <label>تایید ایمیل برای ثبت‌نام</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="captcha_enabled" value="1" <?php echo ($settings_data['captcha_enabled'] ?? 0) ? 'checked' : ''; ?> />
                            <label>فعال کردن کپچا</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== تنظیمات نگهداری ===== -->
            <div class="admin-form">
                <div class="section-title"><i class="fas fa-tools"></i> تنظیمات نگهداری</div>
                
                <div class="form-row three">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="maintenance_mode" value="1" <?php echo ($settings_data['maintenance_mode'] ?? 0) ? 'checked' : ''; ?> />
                            <label>حالت نگهداری (سایت غیرفعال)</label>
                        </div>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>پیام حالت نگهداری</label>
                        <input type="text" name="maintenance_message" value="<?php echo htmlspecialchars($settings_data['maintenance_message'] ?? ''); ?>" />
                    </div>
                </div>
            </div>

            <!-- ===== تنظیمات پیشرفته ===== -->
            <div class="admin-form">
                <div class="section-title"><i class="fas fa-code"></i> تنظیمات پیشرفته</div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>کد گوگل آنالیتیکس</label>
                        <textarea name="analytics_code" rows="3"><?php echo htmlspecialchars($settings_data['analytics_code'] ?? ''); ?></textarea>
                        <span style="font-size:11px; color:var(--text-light);">کد رهگیری گوگل آنالیتیکس را در اینجا قرار دهید</span>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>CSS سفارشی</label>
                        <textarea name="custom_css" rows="4"><?php echo htmlspecialchars($settings_data['custom_css'] ?? ''); ?></textarea>
                        <span style="font-size:11px; color:var(--text-light);">کدهای CSS سفارشی برای تغییر ظاهر سایت</span>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>JavaScript سفارشی</label>
                        <textarea name="custom_js" rows="4"><?php echo htmlspecialchars($settings_data['custom_js'] ?? ''); ?></textarea>
                        <span style="font-size:11px; color:var(--text-light);">کدهای JavaScript سفارشی برای اضافه کردن قابلیت‌ها</span>
                    </div>
                </div>
            </div>

            <!-- ===== دکمه‌های ارسال ===== -->
            <div class="admin-form">
                <div class="form-actions">
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> ذخیره تمام تنظیمات</button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='index.php'">بازگشت به داشبورد</button>
                </div>
            </div>
        </form>

        <!-- ===== ابزارهای مدیریتی ===== -->
        <div class="admin-form">
            <div class="section-title"><i class="fas fa-tools"></i> ابزارهای مدیریتی</div>
            
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="?clear_cache=1" class="btn-action info" onclick="return confirm('آیا از پاک کردن کش مطمئن هستید؟')">
                    <i class="fas fa-broom"></i> پاک کردن کش
                </a>
                <a href="../fix_slugs.php" class="btn-action info" target="_blank">
                    <i class="fas fa-link"></i> اصلاح اسلاگ‌ها
                </a>
                <a href="../debug_articles.php" class="btn-action info" target="_blank">
                    <i class="fas fa-bug"></i> دیباگ مقالات
                </a>
            </div>
        </div>

        <!-- ===== اطلاعات سیستم ===== -->
        <div class="admin-form">
            <div class="section-title"><i class="fas fa-info-circle"></i> اطلاعات سیستم</div>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; font-size:13px; color:var(--text-light);">
                <div><strong>PHP Version:</strong> <?php echo phpversion(); ?></div>
                <div><strong>MySQL Version:</strong> <?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></div>
                <div><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                <div><strong>Upload Max Size:</strong> <?php echo ini_get('upload_max_filesize'); ?></div>
                <div><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></div>
                <div><strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?>s</div>
            </div>
        </div>
    </div>

    <script>
        // ===== پیش‌نمایش رنگ =====
        document.querySelector('input[name="theme_color"]').addEventListener('input', function() {
            const color = this.value;
            const preview = document.querySelector('.color-preview-box');
            if (preview) {
                preview.style.background = color;
            }
            const colorText = document.querySelector('.color-preview-box + span');
            if (colorText) {
                colorText.textContent = color;
            }
        });

        console.log('<i class="fas fa-gear" aria-hidden="true"></i> تنظیمات سایت');
        console.log('<i class="fas fa-chart-pie" aria-hidden="true"></i> تعداد تنظیمات: <?php echo count($settings_data); ?>');
    </script>
    </div><!-- /admin-layout -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>