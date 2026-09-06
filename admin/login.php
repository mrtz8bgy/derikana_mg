<?php
// admin/login.php
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
        $error = 'اطلاعات ورود نادرست است.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ورود به پنل مدیریت | مجله دریکانا</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23D6B36A' d='M6 3h12l4 6-10 12L2 9l4-6zm1.6 2L5 9h4.2l1.3-4H7.6zm5.9 0L12.2 9h3.9l-1.3-4h-1.3zm4 0L18.8 9H21l-2.6-4h-.9zM4.2 11l6.4 7.7L8.9 11H4.2zm6.6 0l1.2 8.6L13.2 11h-2.4zm4.3 0l-1.7 7.7L19.8 11h-4.7z'/%3E%3C/svg%3E" />
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
            } catch (e) {}
        })();
    </script>
</head>
<body class="admin-auth-body">

    <button class="theme-toggle" onclick="toggleAdminTheme()">
        <i class="fas fa-moon i-moon" aria-hidden="true"></i>
        <i class="fas fa-sun i-sun" aria-hidden="true"></i>
        پوسته
    </button>

    <div class="login-box">
        <div class="logo">
            <span class="brand-en">Derikana</span>
            <span class="badge">پنل مدیریت</span>
        </div>
        <p class="subtitle">خوش آمدید؛ برای مدیریت مجله، اطلاعات حساب مدیریتی خود را وارد کنید.</p>

        <form method="POST" id="loginForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="login-username">نام کاربری یا ایمیل</label>
                <div class="input-wrapper">
                    <i class="fas fa-user" aria-hidden="true"></i>
                    <input type="text" id="login-username" name="username" placeholder="نام کاربری یا ایمیل" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" />
                </div>
            </div>

            <div class="form-group">
                <label for="passwordInput">رمز عبور</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    <input type="password" name="password" placeholder="••••••••" required id="passwordInput" />
                    <i class="fas fa-eye" style="position:absolute; inset-inline-end:15px; top:50%; transform:translateY(-50%); cursor:pointer; color:var(--ink-4);" onclick="togglePassword()" id="togglePassword" role="button" aria-label="نمایش رمز عبور"></i>
                </div>
            </div>

            <div class="form-options">
                <label>
                    <input type="checkbox" name="remember" /> مرا به خاطر بسپار
                </label>
                <a href="forgot-password.php">رمز عبور را فراموش کرده‌اید؟</a>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-left"></i> ورود به پنل مدیریت
            </button>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fas fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </form>

        <a class="back-link" href="../">
            <i class="fas fa-arrow-right"></i> بازگشت به سایت
        </a>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('togglePassword');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
        }
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = this.querySelector('.btn-login');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ورود…';
        });
    </script>
</body>
</html>
