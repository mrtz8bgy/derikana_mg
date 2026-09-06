<?php
// login.php — ورود به حساب کاربری
session_start();
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// اگر قبلاً وارد شده، به صفحه اصلی بروید
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    if (empty($username) || empty($password)) {
        $error = 'لطفاً تمام فیلدها را پر کنید.';
    } else {
        if (loginUser($username, $password)) {
            if ($remember) {
                setcookie('remember_username', $username, time() + (86400 * 30), '/');
            }
            header('Location: index.php');
            exit;
        } else {
            $error = 'نام کاربری یا رمز عبور نادرست است.';
        }
    }
}

$remember_username = isset($_COOKIE['remember_username']) ? $_COOKIE['remember_username'] : '';
$site_title = getSetting('site_title') ?: 'مجله دریکانا';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ورود | <?php echo htmlspecialchars($site_title); ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23D6B36A' d='M6 3h12l4 6-10 12L2 9l4-6zm1.6 2L5 9h4.2l1.3-4H7.6zm5.9 0L12.2 9h3.9l-1.3-4h-1.3zm4 0L18.8 9H21l-2.6-4h-.9zM4.2 11l6.4 7.7L8.9 11H4.2zm6.6 0l1.2 8.6L13.2 11h-2.4zm4.3 0l-1.7 7.7L19.8 11h-4.7z'/%3E%3C/svg%3E" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/pages.css" />
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
            } catch (e) {}
        })();
    </script>
</head>
<body class="auth-body">

<div class="auth-page">
    <!-- decorative aside -->
    <aside class="auth-aside">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 3h12l4 6-10 12L2 9l4-6zm1.6 2L5 9h4.2l1.3-4H7.6zm5.9 0L12.2 9h3.9l-1.3-4h-1.3zm4 0L18.8 9H21l-2.6-4h-.9zM4.2 11l6.4 7.7L8.9 11H4.2zm6.6 0l1.2 8.6L13.2 11h-2.4zm4.3 0l-1.7 7.7L19.8 11h-4.7z"/></svg>
            </span>
            <span class="brand-text">
                <span class="brand-fa"><?php echo htmlspecialchars($site_title); ?></span>
                <span class="brand-en">Derikana Magazine</span>
            </span>
        </a>
        <h2>به محفل <em>اهل قلم</em> و درخشش بازگردید</h2>
        <p>با حساب کاربری خود، نوشته‌ها را دنبال کنید، نظر بدهید و نخستین رویدادهای مجله را زودتر از همه ببینید.</p>
        <ul class="auth-features">
            <li><i class="fas fa-bookmark"></i> نشان‌کردن مقالات برای مطالعه بعدی</li>
            <li><i class="fas fa-comments"></i> گفت‌وگو با نویسندگان و خوانندگان</li>
            <li><i class="fas fa-bell"></i> خبرنامه‌ی هفتگی بدون نامه‌های اضافه</li>
        </ul>
    </aside>

    <!-- form -->
    <div class="auth-main">
        <button class="icon-btn theme-toggle theme-toggle-float" onclick="toggleTheme()" aria-label="تغییر پوسته">
            <i class="fas fa-moon i-moon" aria-hidden="true"></i>
            <i class="fas fa-sun i-sun" aria-hidden="true"></i>
        </button>

        <div class="login-box">
            <div class="logo">
                <span class="brand-en">Derikana</span>
                <span class="badge">ورود</span>
            </div>
            <p class="subtitle">خوش آمدید؛ برای ادامه وارد حساب کاربری خود شوید.</p>

            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm" class="auth-form" novalidate>
                <div class="form-group">
                    <label for="login-username">نام کاربری یا ایمیل</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user" aria-hidden="true"></i>
                        <input type="text" id="login-username" name="username" placeholder="نام کاربری یا ایمیل" required value="<?php echo $remember_username ? htmlspecialchars($remember_username) : (isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="passwordInput">رمز عبور</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input type="password" name="password" placeholder="••••••••" required id="passwordInput" />
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('passwordInput')" id="togglePassword" role="button" aria-label="نمایش رمز عبور"></i>
                    </div>
                </div>

                <div class="form-options">
                    <label>
                        <input type="checkbox" name="remember" <?php echo $remember_username ? 'checked' : ''; ?> />
                        مرا به خاطر بسپار
                    </label>
                    <a href="forgot-password.php">رمز عبور را فراموش کرده‌اید؟</a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-arrow-left"></i> ورود به مجله
                </button>
            </form>

            <div class="register-link">
                حساب کاربری ندارید؟ <a href="register.php">ثبت‌نام کنید</a>
            </div>

            <div class="social-login">
                <p>یا با حساب‌های اجتماعی وارد شوید</p>
                <div class="social-btns">
                    <button class="google" onclick="dmToast('اتصال به گوگل به‌زودی…')" aria-label="گوگل"><i class="fab fa-google"></i></button>
                    <button class="github" onclick="dmToast('اتصال به گیت‌هاب به‌زودی…')" aria-label="گیت‌هاب"><i class="fab fa-github"></i></button>
                    <button class="telegram" onclick="dmToast('اتصال به تلگرام به‌زودی…')" aria-label="تلگرام"><i class="fab fa-telegram"></i></button>
                </div>
            </div>

            <a class="back-link" href="./">
                <i class="fas fa-arrow-right"></i> بازگشت به سایت
            </a>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
    document.getElementById('loginForm').addEventListener('submit', function (e) {
        const username = this.querySelector('input[name="username"]').value.trim();
        const password = this.querySelector('input[name="password"]').value.trim();
        if (!username || !password) {
            e.preventDefault();
            dmToast('لطفاً تمام فیلدها را پر کنید');
            return false;
        }
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ورود…';
    });
</script>
</body>
</html>
