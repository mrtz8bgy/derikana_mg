<?php
// admin/login.php
// session_start(); // این خط را حذف کنید چون در auth.php وجود دارد
require_once __DIR__ . '/../includes/auth.php';

// اگر قبلاً وارد شده، به داشبورد بروید
if (isAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (loginUser($username, $password) && isAdmin()) {
        header('Location: index.php');
        exit;
    } else {
        $error = '❌ اطلاعات ورود نادرست است!';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ورود به پنل مدیریت</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* ===== استایل‌های صفحه ورود ===== */
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --primary-light: #A8A4FF;
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
        .login-box .error-msg {
            color: var(--danger);
            font-size: 13px;
            margin-top: 12px;
            padding: 10px 16px;
            background: rgba(255, 82, 82, 0.08);
            border-radius: var(--radius-sm);
            border-right: 3px solid var(--danger);
            text-align: right;
        }
        .login-box .error-msg i {
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
        .login-box .demo-info {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-light);
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .login-box .demo-info span {
            background: var(--bg);
            padding: 4px 14px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }
        .login-box .demo-info .label {
            background: var(--primary-gradient);
            color: #fff;
            border: none;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
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
            .login-box .demo-info {
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }
            .theme-toggle {
                top: 10px;
                left: 10px;
                padding: 6px 12px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <!-- دکمه تغییر تم -->
    <button class="theme-toggle" onclick="toggleTheme()">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">تیره</span>
    </button>

    <div class="login-box">
        <div class="logo">
            <span class="brand-en">Derikana</span>
            <span class="badge">مدیریت</span>
        </div>
        <p class="subtitle">👋 برای ورود به پنل مدیریت، اطلاعات خود را وارد کنید</p>
        
        <form method="POST" id="loginForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label>نام کاربری یا ایمیل</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder=" email@example.com" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" />
                </div>
            </div>
            
            <div class="form-group">
                <label>رمز عبور</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="********" required id="passwordInput" />
                    <i class="fas fa-eye" style="position:absolute; left:14px; right:auto; cursor:pointer; color:var(--text-light);" onclick="togglePassword()" id="togglePassword"></i>
                </div>
            </div>
            
            <div class="form-options">
                <label>
                    <input type="checkbox" name="remember" /> مرا به خاطر بسپار
                </label>
                <a href="#">رمز عبور را فراموش کرده‌اید؟</a>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-left"></i> ورود به پنل مدیریت
            </button>
            
            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </form>
        
        <a class="back-link" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/../">
            <i class="fas fa-arrow-right"></i> بازگشت به سایت
        </a>
        
     
    </div>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin_theme', newTheme);
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

        const savedTheme = localStorage.getItem('admin_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeUI(savedTheme);

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

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = this.querySelector('input[name="username"]').value.trim();
            const password = this.querySelector('input[name="password"]').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('لطفاً تمام فیلدها را پر کنید!');
                return false;
            }
        });
    </script>
</body>
</html>