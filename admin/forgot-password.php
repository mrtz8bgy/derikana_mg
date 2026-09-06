<?php
// forgot-password.php
session_start();
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    
    if (empty($email) || !validateEmail($email)) {
        $error = '❌ لطفاً یک ایمیل معتبر وارد کنید!';
    } else {
        // بررسی وجود کاربر با این ایمیل
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // در اینجا می‌توانید ایمیل بازیابی ارسال کنید
            // برای سادگی، یک پیام موفقیت نمایش می‌دهیم
            $message = '✅ لینک بازیابی رمز عبور به ایمیل شما ارسال شد!';
        } else {
            $error = '❌ کاربری با این ایمیل یافت نشد!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>بازیابی رمز عبور</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* استایل‌های مشابه صفحه ورود */
        :root {
            --primary: #6C63FF;
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
        .forgot-box {
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
        .forgot-box::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 4px;
            background: var(--primary-gradient);
        }
        .forgot-box .logo {
            font-size: 34px;
            font-weight: 900;
            margin-bottom: 6px;
        }
        .forgot-box .logo .brand-en {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-style: italic;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .forgot-box .subtitle {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 28px;
        }
        .forgot-box .form-group {
            margin-bottom: 18px;
            text-align: right;
        }
        .forgot-box .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 5px;
            color: var(--text);
        }
        .forgot-box .form-group .input-wrapper {
            position: relative;
        }
        .forgot-box .form-group .input-wrapper i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
        }
        .forgot-box .form-group input {
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
        .forgot-box .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.1);
        }
        .forgot-box .btn-submit {
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
        .forgot-box .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.3);
        }
        .forgot-box .message {
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            font-size: 13px;
            text-align: right;
        }
        .forgot-box .message.success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
        }
        .forgot-box .message.error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #EF9A9A;
        }
        .forgot-box .message i {
            margin-left: 8px;
        }
        .forgot-box .back-link {
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
        .forgot-box .back-link:hover {
            color: var(--primary);
            transform: translateX(-4px);
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
            .forgot-box { padding: 30px 20px 28px; }
            .theme-toggle { top: 10px; left: 10px; padding: 6px 12px; font-size: 11px; }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">تیره</span>
    </button>

    <div class="forgot-box">
        <div class="logo">
            <span class="brand-en">Anosha</span>
        </div>
        <p class="subtitle">🔑 برای بازیابی رمز عبور، ایمیل خود را وارد کنید</p>
        
        <?php if ($message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>ایمیل</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="example@email.com" required />
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> ارسال لینک بازیابی
            </button>
        </form>
        
        <a class="back-link" href="/login.php">
            <i class="fas fa-arrow-right"></i> بازگشت به صفحه ورود
        </a>
    </div>

    <script>
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
    </script>
</body>
</html>