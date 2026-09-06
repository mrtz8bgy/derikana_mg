<?php
// register.php
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
    $name = cleanInput($_POST['name'] ?? '');
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // اعتبارسنجی
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = '❌ لطفاً تمام فیلدها را پر کنید!';
    } elseif (!validateEmail($email)) {
        $error = '❌ ایمیل وارد شده معتبر نیست!';
    } elseif (strlen($password) < 6) {
        $error = '❌ رمز عبور باید حداقل ۶ کاراکتر باشد!';
    } elseif ($password !== $password_confirm) {
        $error = '❌ رمز عبور و تکرار آن مطابقت ندارند!';
    } else {
        $result = registerUser($name, $username, $email, $password);
        if ($result['success']) {
            $success = '✅ ثبت‌نام با موفقیت انجام شد! حالا می‌توانید وارد شوید.';
            // پاک کردن فرم
            $_POST = [];
        } else {
            $error = '❌ ' . $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ثبت‌نام در مجله دریکانا</title>
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
        .register-box {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 45px 40px 40px;
            max-width: 480px;
            width: 100%;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            text-align: center;
            transition: all var(--transition);
            position: relative;
            overflow: hidden;
        }
        .register-box::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 4px;
            background: var(--primary-gradient);
        }
        .register-box .logo {
            font-size: 34px;
            font-weight: 900;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .register-box .logo .brand-en {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-style: italic;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .register-box .logo .badge {
            font-size: 13px;
            background: var(--primary-gradient);
            color: #fff;
            padding: 2px 16px;
            border-radius: 20px;
            -webkit-text-fill-color: #fff;
            display: inline-block;
            font-family: 'Vazirmatn', sans-serif;
        }
        .register-box .subtitle {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 28px;
        }
        .register-box .form-group {
            margin-bottom: 18px;
            text-align: right;
        }
        .register-box .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 5px;
            color: var(--text);
        }
        .register-box .form-group .input-wrapper {
            position: relative;
        }
        .register-box .form-group .input-wrapper i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
        }
        .register-box .form-group input {
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
        .register-box .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.1);
        }
        .register-box .form-group input::placeholder {
            color: var(--text-light);
            opacity: 0.7;
        }
        .register-box .form-group .password-toggle {
            position: absolute;
            left: 14px;
            right: auto;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            font-size: 16px;
        }
        .register-box .form-group .password-toggle:hover {
            color: var(--primary);
        }
        .register-box .btn-register {
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
        .register-box .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.3);
        }
        .register-box .btn-register:active {
            transform: scale(0.98);
        }
        .register-box .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .register-box .message {
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            font-size: 13px;
            text-align: right;
        }
        .register-box .message.success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
        }
        .register-box .message.error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #EF9A9A;
        }
        .register-box .message i {
            margin-left: 8px;
        }
        .register-box .back-link {
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
        .register-box .back-link:hover {
            color: var(--primary);
            transform: translateX(-4px);
        }
        .register-box .login-link {
            margin-top: 16px;
            color: var(--text-light);
            font-size: 13px;
        }
        .register-box .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all var(--transition);
        }
        .register-box .login-link a:hover {
            text-decoration: underline;
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

        .password-strength {
            margin-top: 6px;
            height: 4px;
            border-radius: 4px;
            background: var(--border);
            overflow: hidden;
            transition: all 0.3s;
        }
        .password-strength .bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 4px;
        }
        .password-strength .bar.weak { width: 33%; background: var(--danger); }
        .password-strength .bar.medium { width: 66%; background: #FF9800; }
        .password-strength .bar.strong { width: 100%; background: var(--success); }
        .password-strength-text {
            font-size: 11px;
            margin-top: 2px;
            color: var(--text-light);
        }

        @media (max-width: 480px) {
            .register-box {
                padding: 30px 20px 28px;
            }
            .register-box .logo {
                font-size: 26px;
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
    <button class="theme-toggle" onclick="toggleTheme()">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">تیره</span>
    </button>

    <div class="register-box">
        <div class="logo">
            <span class="brand-en">ِDerikana</span>
            <span class="badge">ثبت‌نام</span>
        </div>
        <p class="subtitle">📝 عضو شوید و از جدیدترین مقالات لذت ببرید</p>
        
        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="registerForm" novalidate>
            <div class="form-group">
                <label>نام کامل</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" placeholder="نام و نام خانوادگی" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" />
                </div>
            </div>
            
            <div class="form-group">
                <label>نام کاربری</label>
                <div class="input-wrapper">
                    <i class="fas fa-user-tag"></i>
                    <input type="text" name="username" placeholder="نام کاربری یکتا" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" />
                </div>
            </div>
            
            <div class="form-group">
                <label>ایمیل</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="example@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                </div>
            </div>
            
            <div class="form-group">
                <label>رمز عبور</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="حداقل ۶ کاراکتر" required id="passwordInput" />
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('passwordInput')" id="togglePassword"></i>
                </div>
                <div class="password-strength">
                    <div class="bar" id="passwordStrength"></div>
                </div>
                <div class="password-strength-text" id="passwordStrengthText">رمز عبور را وارد کنید</div>
            </div>
            
            <div class="form-group">
                <label>تکرار رمز عبور</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password_confirm" placeholder="تکرار رمز عبور" required id="passwordConfirm" />
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('passwordConfirm')" id="togglePasswordConfirm"></i>
                </div>
            </div>
            
            <div class="form-group" style="text-align:center;">
                <label style="display:inline; font-weight:400; font-size:12px;">
                    <input type="checkbox" name="terms" required />
                    <a href="page/terms" style="color:var(--primary);">قوانین و مقررات</a> را می‌پذیرم
                </label>
            </div>
            
            <button type="submit" class="btn-register" id="registerBtn">
                <i class="fas fa-user-plus"></i> ثبت‌نام
            </button>
        </form>
        
        <div class="login-link">
            قبلاً عضو شده‌اید؟ <a href="login.php">وارد شوید</a>
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
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = inputId === 'passwordInput' ? 
                document.getElementById('togglePassword') : 
                document.getElementById('togglePasswordConfirm');
            
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

        // ===== PASSWORD STRENGTH =====
        document.getElementById('passwordInput').addEventListener('input', function() {
            const password = this.value;
            const bar = document.getElementById('passwordStrength');
            const text = document.getElementById('passwordStrengthText');
            
            if (password.length === 0) {
                bar.className = 'bar';
                text.textContent = 'رمز عبور را وارد کنید';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength <= 1) {
                bar.className = 'bar weak';
                text.textContent = 'ضعیف';
                text.style.color = 'var(--danger)';
            } else if (strength <= 3) {
                bar.className = 'bar medium';
                text.textContent = 'متوسط';
                text.style.color = '#FF9800';
            } else {
                bar.className = 'bar strong';
                text.textContent = 'قوی';
                text.style.color = 'var(--success)';
            }
        });

        // ===== PASSWORD MATCH =====
        document.getElementById('passwordConfirm').addEventListener('input', function() {
            const password = document.getElementById('passwordInput').value;
            const confirm = this.value;
            
            if (confirm.length === 0) return;
            
            if (password === confirm) {
                this.style.borderColor = 'var(--success)';
            } else {
                this.style.borderColor = 'var(--danger)';
            }
        });

        // ===== FORM VALIDATION =====
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('passwordInput').value;
            const confirm = document.getElementById('passwordConfirm').value;
            const terms = document.querySelector('input[name="terms"]');
            
            if (password !== confirm) {
                e.preventDefault();
                alert('❌ رمز عبور و تکرار آن مطابقت ندارند!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('❌ رمز عبور باید حداقل ۶ کاراکتر باشد!');
                return false;
            }
            
            if (!terms.checked) {
                e.preventDefault();
                alert('❌ برای ثبت‌نام باید قوانین و مقررات را بپذیرید!');
                return false;
            }
        });

        // ===== PREVENT DUPLICATE SUBMIT =====
        document.getElementById('registerForm').addEventListener('submit', function() {
            const btn = document.getElementById('registerBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ثبت‌نام...';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> ثبت‌نام';
            }, 5000);
        });
    </script>
</body>
</html>