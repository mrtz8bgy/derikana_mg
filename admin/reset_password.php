<?php
// reset_password.php
require_once 'config/database.php';

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
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>تنظیم مجدد رمز عبور</title>
    <style>
        body { font-family: 'Vazirmatn', sans-serif; background: #f0f2f8; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #6C63FF; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; }
        input { width: 100%; padding: 10px; border: 2px solid #e2e6f0; border-radius: 8px; font-size: 14px; }
        button { background: #6C63FF; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 16px; }
        button:hover { background: #5A52D5; }
        .message { padding: 12px; border-radius: 8px; margin-bottom: 15px; }
        .success { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .error { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #e2e6f0; text-align: right; }
        th { background: #f0f2f8; }
        .warning { color: #FF9800; font-weight: 600; }
        .admin-badge { background: #6C63FF; color: #fff; padding: 2px 10px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 تنظیم مجدد رمز عبور</h1>
        <p style="color: #666;">از این ابزار برای تغییر رمز عبور کاربران استفاده کنید.</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>نام کاربری</label>
                <input type="text" name="username" placeholder="مثال: admin" required />
            </div>
            <div class="form-group">
                <label>رمز عبور جدید</label>
                <input type="text" name="new_password" placeholder="رمز جدید را وارد کنید" required />
            </div>
            <button type="submit">🔄 تغییر رمز عبور</button>
        </form>
        
        <hr style="margin: 30px 0;" />
        
        <h3>📋 لیست کاربران</h3>
        <table>
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
                        <td><?php echo $user['id']; ?></td>
                        <td><strong><?php echo $user['username']; ?></strong></td>
                        <td><?php echo $user['name']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="admin-badge">مدیر</span>
                            <?php else: ?>
                                <?php echo $user['role']; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background: #FFF3E0; border-radius: 8px; border-right: 4px solid #FF9800;">
            <p class="warning">⚠️ هشدار امنیتی:</p>
            <p style="font-size: 13px; color: #666;">این فایل را بعد از استفاده حذف کنید تا از دسترسی غیرمجاز جلوگیری شود.</p>
            <p style="font-size: 13px; color: #666;">نام کاربری پیش‌فرض: <strong>admin</strong> | رمز عبور: <strong>admin123</strong></p>
        </div>
    </div>
</body>
</html>