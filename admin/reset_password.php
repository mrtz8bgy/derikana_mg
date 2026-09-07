<?php
// reset_password.php — ابزار بازیابی اضطراری رمز عبور
// ⚠️ این فایل فقط برای شرایط اضطراری است؛ پس از استفاده حذفش کنید.
require_once __DIR__ . '/../config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (!empty($username) && !empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed, $username]);

        if ($stmt->rowCount() > 0) {
            $message = "✅ رمز عبور برای کاربر '$username' با موفقیت تغییر کرد!";
        } else {
            $message = "❌ کاربر '$username' یافت نشد!";
        }
    } else {
        $message = "❌ لطفاً همه فیلدها را پر کنید!";
    }
}

// لیست کاربران
$users = $pdo->query("SELECT id, username, name, email, role FROM users")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>تنظیم مجدد رمز عبور | دریکانا</title>
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

    <div class="login-box" style="max-width: 640px;">
        <div class="logo">
            <span class="brand-en">Derikana</span>
            <span class="badge">ابزار اضطراری</span>
        </div>
        <p class="subtitle"><i class="fas fa-key" style="color:var(--gold);"></i> تنظیم مجدد رمز عبور — از این ابزار فقط در شرایط اضطراری استفاده کنید.</p>

        <?php if ($message): ?>
            <?php $msg_type = strpos($message, '✅') !== false ? 'success' : 'error'; ?>
            <div class="message <?php echo $msg_type; ?>">
                <?php echo preg_replace('/[✅❌]\s*/u', '', htmlspecialchars($message)); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-form" style="box-shadow:none; border-style:dashed; margin-bottom:18px;">
            <div class="form-row">
                <div class="form-group">
                    <label>نام کاربری</label>
                    <input type="text" name="username" placeholder="مثال: admin" required />
                </div>
                <div class="form-group">
                    <label>رمز عبور جدید</label>
                    <input type="text" name="new_password" placeholder="رمز جدید را وارد کنید" required />
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit"><i class="fas fa-rotate"></i> تغییر رمز عبور</button>
            </div>
        </form>

        <div class="section-title"><i class="fas fa-users"></i> لیست کاربران</div>
        <div class="admin-table-container" style="margin-bottom:18px;">
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>نام کاربری</th>
                            <th>نام</th>
                            <th>ایمیل</th>
                            <th>نقش</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo (int)$user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="status-badge admin">مدیر</span>
                                    <?php else: ?>
                                        <span class="status-badge user"><?php echo htmlspecialchars($user['role']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="message warning">
            <i class="fas fa-triangle-exclamation"></i>
            هشدار امنیتی: این فایل را بعد از استفاده حذف کنید تا از دسترسی غیرمجاز جلوگیری شود.
        </div>

        <a class="back-link" href="login.php">
            <i class="fas fa-arrow-right"></i> بازگشت به صفحه ورود
        </a>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
