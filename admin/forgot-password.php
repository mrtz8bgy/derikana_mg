<?php
// admin/forgot-password.php
session_start();
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');

    if (empty($email) || !validateEmail($email)) {
        $error = 'لطفاً یک ایمیل معتبر وارد کنید.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // در اینجا می‌توانید ایمیل بازیابی ارسال کنید؛
            // برای سادگی، پیام موفقیت نمایش داده می‌شود.
            $message = 'لینک بازیابی رمز عبور به ایمیل شما ارسال شد.';
        } else {
            $error = 'کاربری با این ایمیل یافت نشد.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>بازیابی رمز عبور | پنل مدیریت دریکانا</title>
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
            <span class="badge">بازیابی رمز</span>
        </div>
        <p class="subtitle">ایمیل حساب مدیریتی خود را وارد کنید تا لینک بازیابی رمز عبور برایتان ارسال شود.</p>

        <?php if ($message): ?>
            <div class="message success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="recover-email">ایمیل حساب</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <input type="email" id="recover-email" name="email" placeholder="email@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-paper-plane"></i> ارسال لینک بازیابی
            </button>
        </form>

        <a class="back-link" href="login.php">
            <i class="fas fa-arrow-right"></i> بازگشت به صفحه ورود
        </a>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
