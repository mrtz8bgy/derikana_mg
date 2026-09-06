<?php
// login.php
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
        $error = '❌ لطفاً تمام فیلدها را پر کنید!';
    } else {
        if (loginUser($username, $password)) {
            // اگر مرا به خاطر بسپار فعال بود
            if ($remember) {
                setcookie('remember_username', $username, time() + (86400 * 30), '/');
            }
            header('Location: index.php');
            exit;
        } else {
            $error = '❌ نام کاربری یا رمز عبور نادرست است!';
        }
    }
}

// اگر کوکی مرا به خاطر بسپار وجود داشت
$remember_username = isset($_COOKIE['remember_username']) ? $_COOKIE['remember_username'] : '';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ورود به مجله دریکانا</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --primary-gradient: linear-gradient(135deg, #6C63FF 0%, #3F3D9E 100%);
            --bg: #f0f2f8;
            --bg-card: #ffffff;
            --text: #1a1a2e;
            --text-light: #6c6c8a;
            --border: #e2e6f0;
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.12);
            --radius: 20px;
            --radius-sm: 12px;
            --transition: 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            --danger: #FF5252;
            --success: #00E676;
        }
        [data-theme="dark"] {
            --bg: #0a0a1a;
            --bg-card: #16162e;
            --text: #e8e8f0;
            --text-light: #9090b0;
            --border: #2a2a4a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        body {
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: all var(--transition);
        }
        .login-box {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 45px 40px 40px;
            max-width: 440px;
            width: 100%;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            text-align: center;
            transition: all var(--transition);
            position: relative;
            overflow: hidden;
        }
        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 4px;
            background: var(--primary-gradient);
        }
        .login-box .logo {
            font-size: 34px;
            font-weight: 900;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .login-box .logo .brand-en {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-style: italic;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .login-box .logo .badge {
            font-size: 13px;
            background: var(--primary-gradient);
            color: #fff;
            padding: 2px 16px;
            border-radius: 20px;
            -webkit-text-fill-color: #fff;
            display: inline-block;
            font-family: 'Vazirmatn', sans-serif;
        }
        .login-box .subtitle {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 28px;
        }
        .login-box .form-group {
            margin-bottom: 18px;
            text-align: right;
        }
        .login-box .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 5px;
            color: var(--text);
        }
        .login-box .form-group .input-wrapper {
            position: relative;
        }
        .login-box .form-group .input-wrapper i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
        }
        .login-box .form-group input {
            width: 100%;
            padding: 12px 44px 12px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            transition: all var(--transition);
            font-family: 'Vazirmatn', sans-serif;
        }
        .login-box .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.1);
        }
        .login-box .form-group input::placeholder {
            color: var(--text-light);
            opacity: 0.7;
        }
        .login-box .form-group .password-toggle {
            position: absolute;
            left: 14px;
            right: auto;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            font-size: 16px;
        }
        .login-box .form-group .password-toggle:hover {
            color: var(--primary);
        }
        .login-box .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            font-size: 13px;
            color: var(--text-light);
        }
        .login-box .form-options label {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .login-box .form-options label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        .login-box .form-options a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition);
        }
        .login-box .form-options a:hover {
            text-decoration: underline;
        }
        .login-box .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 30px;
            background: var(--primary-gradient);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
            font-family: 'Vazirmatn', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .login-box .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.3);
        }
        .login-box .btn-login:active {
            transform: scale(0.98);
        }
        .login-box .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .login-box .message {
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            font-size: 13px;
            text-align: right;
        }
        .login-box .message.error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #EF9A9A;
        }
        .login-box .message i {
            margin-left: 8px;
        }
        .login-box .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 18px;
            color: var(--text-light);
            font-size: 13px;
            text-decoration: none;
            transition: all var(--transition);
            cursor: pointer;
        }
        .login-box .back-link:hover {
            color: var(--primary);
            transform: translateX(-4px);
        }
        .login-box .register-link {
            margin-top: 16px;
            color: var(--text-light);
            font-size: 13px;
        }
        .login-box .register-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all var(--transition);
        }
        .login-box .register-link a:hover {
            text-decoration: underline;
        }
        .login-box .social-login {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        .login-box .social-login p {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 12px;
        }
        .login-box .social-login .social-btns {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .login-box .social-login .social-btns button {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--bg);
            color: var(--text);
            cursor: pointer;
            transition: all var(--transition);
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box .social-login .social-btns button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        .login-box .social-login .social-btns button.google:hover {
            border-color: #DB4437;
            color: #DB4437;
        }
        .login-box .social-login .social-btns button.github:hover {
            border-color: #333;
            color: #333;
        }
        .login-box .social-login .social-btns button.telegram:hover {
            border-color: #0088CC;
            color: #0088CC;
        }
        .theme-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text);
            transition: all var(--transition);
            font-size: 13px;
            font-family: 'Vazirmatn', sans-serif;
            box-shadow: var(--shadow-lg);
            z-index: 100;
        }
        .theme-toggle:hover {
            transform: scale(1.05);
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 30px 20px 28px;
            }
            .login-box .logo {
                font-size: 26px;
            }
            .login-box .form-options {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            .theme-toggle {
                top: 10px;
                left: 10px;
                padding: 6px 12px;
                font-size: 11px;
            }
            .login-box .social-login .social-btns button {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">تیره</span>
    </button>

    <div class="login-box">
        <div class="logo">
            <span class="brand-en">ِDerikana</span>
            <span class="badge">ورود</span>
        </div>
        <p class="subtitle">👋 خوش آمدید! برای دسترسی به مقالات وارد شوید</p>
        
        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm" novalidate>
            <div class="form-group">
                <label>نام کاربری یا ایمیل</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="نام کاربری یا ایمیل" required value="<?php echo $remember_username ? htmlspecialchars($remember_username) : (isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''); ?>" />
                </div>
            </div>
            
            <div class="form-group">
                <label>رمز عبور</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="********" required id="passwordInput" />
                    <i class="fas fa-eye password-toggle" onclick="togglePassword()" id="togglePassword"></i>
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
                <i class="fas fa-arrow-left"></i> ورود
            </button>
        </form>
        
        <div class="register-link">
            حساب کاربری ندارید؟ <a href="register.php">ثبت‌نام کنید</a>
        </div>
        
        <div class="social-login">
            <p>یا با حساب‌های اجتماعی وارد شوید</p>
            <div class="social-btns">
                <button class="google" onclick="alert('در حال اتصال به گوگل...')">
                    <i class="fab fa-google"></i>
                </button>
                <button class="github" onclick="alert('در حال اتصال به گیت‌هاب...')">
                    <i class="fab fa-github"></i>
                </button>
                <button class="telegram" onclick="alert('در حال اتصال به تلگرام...')">
                    <i class="fab fa-telegram"></i>
                </button>
            </div>
        </div>
        
        <a class="back-link" href="./">
            <i class="fas fa-arrow-right"></i> بازگشت به سایت
        </a>
    </div>

    <script>
        // ===== THEME TOGGLE =====
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeUI(newTheme);
        }

        function updateThemeUI(theme) {
            const icon = document.getElementById('themeIcon');
            const label = document.getElementById('themeLabel');
            if (theme === 'dark') {
                icon.textContent = '☀️';
                label.textContent = 'روشن';
            } else {
                icon.textContent = '🌙';
                label.textContent = 'تیره';
            }
        }

        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeUI(savedTheme);

        // ===== PASSWORD TOGGLE =====
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('togglePassword');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // ===== ENTER KEY SUPPORT =====
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const form = document.getElementById('loginForm');
                if (document.activeElement && document.activeElement.closest('.login-box')) {
                    form.submit();
                }
            }
        });

        // ===== PREVENT DUPLICATE SUBMIT =====
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = this.querySelector('input[name="username"]').value.trim();
            const password = this.querySelector('input[name="password"]').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('❌ لطفاً تمام فیلدها را پر کنید!');
                return false;
            }
            
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ورود...';
        });
    </script>
</body>
</html>