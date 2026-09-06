<?php
// register.php — ساخت حساب کاربری
session_start();
require_once 'includes/auth.php';
require_once 'includes/functions.php';

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

    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = 'لطفاً تمام فیلدها را پر کنید.';
    } elseif (!validateEmail($email)) {
        $error = 'ایمیل وارد شده معتبر نیست.';
    } elseif (strlen($password) < 6) {
        $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    } elseif ($password !== $password_confirm) {
        $error = 'رمز عبور و تکرار آن مطابقت ندارند.';
    } else {
        $result = registerUser($name, $username, $email, $password);
        if ($result['success']) {
            $success = 'ثبت‌نام با موفقیت انجام شد؛ حالا می‌توانید وارد شوید.';
            $_POST = [];
        } else {
            $error = $result['message'];
        }
    }
}

$site_title = getSetting('site_title') ?: 'مجله دریکانا';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ثبت‌نام | <?php echo htmlspecialchars($site_title); ?></title>
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
        <h2>عضویت در مجله؛ <em>آغاز یک همراهی</em> روشن</h2>
        <p>در چند ثانیه حساب خود را بسازید و به محفل خوانندگانی بپیوندید که جزئیات برایشان مهم است.</p>
        <ul class="auth-features">
            <li><i class="fas fa-feather-pointed"></i> امکان ثبت نظر و گفت‌وگو</li>
            <li><i class="fas fa-star"></i> دسترسی به پرونده‌های ویژه اعضا</li>
            <li><i class="fas fa-shield-halved"></i> اطلاعات شما نزد ما امانت است</li>
        </ul>
    </aside>

    <div class="auth-main">
        <button class="icon-btn theme-toggle theme-toggle-float" onclick="toggleTheme()" aria-label="تغییر پوسته">
            <i class="fas fa-moon i-moon" aria-hidden="true"></i>
            <i class="fas fa-sun i-sun" aria-hidden="true"></i>
        </button>

        <div class="register-box">
            <div class="logo">
                <span class="brand-en">Derikana</span>
                <span class="badge">ثبت‌نام</span>
            </div>
            <p class="subtitle">عضو شوید و از جدیدترین مقالات و پرونده‌های ویژه لذت ببرید.</p>

            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success">
                    <i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
                    <a href="login.php" style="margin-inline-start:auto; font-weight:800; color:inherit; text-decoration:underline;">ورود</a>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm" class="auth-form" novalidate>
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="reg-name">نام کامل</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <input type="text" id="reg-name" name="name" placeholder="نام و نام خانوادگی" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reg-username">نام کاربری</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user-tag" aria-hidden="true"></i>
                            <input type="text" id="reg-username" name="username" placeholder="نام کاربری یکتا" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg-email">ایمیل</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <input type="email" id="reg-email" name="email" placeholder="example@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="passwordInput">رمز عبور</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input type="password" name="password" placeholder="حداقل ۶ کاراکتر" required id="passwordInput" />
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('passwordInput')" id="togglePassword" role="button" aria-label="نمایش رمز عبور"></i>
                    </div>
                    <div class="password-strength">
                        <div class="bar" id="passwordStrength"></div>
                    </div>
                    <div class="password-strength-text" id="passwordStrengthText">رمز عبور را وارد کنید</div>
                </div>

                <div class="form-group">
                    <label for="passwordConfirm">تکرار رمز عبور</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input type="password" name="password_confirm" placeholder="تکرار رمز عبور" required id="passwordConfirm" />
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('passwordConfirm')" id="togglePasswordConfirm" role="button" aria-label="نمایش تکرار رمز"></i>
                    </div>
                </div>

                <div class="form-group terms-group">
                    <label>
                        <input type="checkbox" name="terms" required />
                        <a href="page/terms">قوانین و مقررات</a> را می‌پذیرم
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
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
    (function () {
        const confirmInput = document.getElementById('passwordConfirm');
        confirmInput.addEventListener('input', function () {
            const pwd = document.getElementById('passwordInput').value;
            this.classList.toggle('match', this.value.length > 0 && pwd === this.value);
            this.classList.toggle('mismatch', this.value.length > 0 && pwd !== this.value);
        });

        document.getElementById('registerForm').addEventListener('submit', function (e) {
            const password = document.getElementById('passwordInput').value;
            const confirm = confirmInput.value;
            const terms = document.querySelector('input[name="terms"]');

            if (password !== confirm) { e.preventDefault(); dmToast('رمز عبور و تکرار آن مطابقت ندارند'); return false; }
            if (password.length < 6) { e.preventDefault(); dmToast('رمز عبور باید حداقل ۶ کاراکتر باشد'); return false; }
            if (!terms.checked) { e.preventDefault(); dmToast('پذیرش قوانین و مقررات الزامی است'); return false; }

            const btn = document.getElementById('registerBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ثبت‌نام…';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> ثبت‌نام';
            }, 5000);
        });
    })();
</script>
</body>
</html>
