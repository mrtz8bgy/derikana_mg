<?php
// admin/users.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// ===== تغییر نقش کاربر =====
if (isset($_GET['role'])) {
    $id = (int)$_GET['role'];
    $new_role = $_GET['set'] ?? 'user';
    
    // جلوگیری از تغییر نقش خودش
    if ($id == $_SESSION['user_id']) {
        $error = '❌ نمی‌توانید نقش خودتان را تغییر دهید!';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $id]);
        $message = '✅ نقش کاربر با موفقیت تغییر کرد!';
    }
}

// ===== فعال/غیرفعال کردن کاربر =====
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    
    // جلوگیری از غیرفعال کردن خودش
    if ($id == $_SESSION['user_id']) {
        $error = '❌ نمی‌توانید خودتان را غیرفعال کنید!';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt->execute([$id]);
        $message = '✅ وضعیت کاربر با موفقیت تغییر کرد!';
    }
}

// ===== حذف کاربر =====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // جلوگیری از حذف خودش
    if ($id == $_SESSION['user_id']) {
        $error = '❌ نمی‌توانید خودتان را حذف کنید!';
    } else {
        // بررسی اینکه کاربر مقاله دارد یا خیر
        $check = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE author_id = ?");
        $check->execute([$id]);
        $article_count = $check->fetchColumn();
        
        if ($article_count > 0) {
            $error = "❌ این کاربر {$article_count} مقاله دارد! ابتدا مقالات را به کاربر دیگری منتقل کنید.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = '🗑️ کاربر با موفقیت حذف شد!';
        }
    }
}

// ===== ویرایش کاربر =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = (int)$_POST['id'];
    $name = cleanInput($_POST['name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $error = '❌ لطفاً نام و ایمیل را وارد کنید!';
    } elseif (!validateEmail($email)) {
        $error = '❌ ایمیل وارد شده معتبر نیست!';
    } else {
        if (!empty($password)) {
            // با تغییر رمز عبور
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $status, $hashed, $id]);
        } else {
            // بدون تغییر رمز عبور
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $status, $id]);
        }
        $message = '✅ اطلاعات کاربر با موفقیت به‌روزرسانی شد!';
    }
}

// ===== دریافت لیست کاربران =====
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();

// ===== دریافت کاربر برای ویرایش =====
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch();
}

// ===== آمار =====
$total_users = count($users);
$admin_count = array_filter($users, function($u) { return $u['role'] == 'admin'; });
$active_count = array_filter($users, function($u) { return $u['status'] == 'active'; });
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت کاربران</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* ===== استایل‌های ادمین ===== */
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --primary-gradient: linear-gradient(135deg, #6C63FF 0%, #3F3D9E 100%);
            --bg: #f0f2f8;
            --bg-card: #ffffff;
            --text: #1a1a2e;
            --text-light: #6c6c8a;
            --border: #e2e6f0;
            --shadow: rgba(108, 99, 255, 0.12);
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.12);
            --radius: 20px;
            --radius-sm: 12px;
            --transition: 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            --danger: #FF5252;
            --success: #00E676;
            --gold: #F9A825;
            --glass-bg: rgba(255,255,255,0.08);
            --orange: #FF9800;
            --info: #448AFF;
        }
        [data-theme="dark"] {
            --bg: #0a0a1a;
            --bg-card: #16162e;
            --text: #e8e8f0;
            --text-light: #9090b0;
            --border: #2a2a4a;
            --glass-bg: rgba(255,255,255,0.05);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        body { background: var(--bg); color: var(--text); transition: all var(--transition); min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        /* Admin Header */
        .admin-header {
            background: var(--bg-card);
            border-bottom: 2px solid var(--border);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
            flex-wrap: wrap;
            gap: 12px;
        }
        .admin-header .logo { font-size: 24px; font-weight: 900; display: flex; align-items: center; gap: 8px; }
        .admin-header .logo .brand-en { font-family: 'Playfair Display', serif; font-weight: 900; font-style: italic; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .admin-header .logo .badge { font-size: 12px; background: var(--primary-gradient); color: #fff; padding: 2px 12px; border-radius: 20px; -webkit-text-fill-color: #fff; }
        .admin-header .user-info { display: flex; align-items: center; gap: 12px; font-size: 14px; }
        .admin-header .user-info .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-gradient); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; }
        .admin-header .back-btn { background: var(--glass-bg); border: 1px solid var(--border); padding: 6px 16px; border-radius: 30px; cursor: pointer; color: var(--text); transition: all var(--transition); font-family: 'Vazirmatn', sans-serif; font-size: 13px; }
        .admin-header .back-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        .admin-content { max-width: 1400px; margin: 0 auto; padding: 24px; }
        .admin-content .page-title { font-size: 22px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .admin-content .page-title i { color: var(--primary); }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: all var(--transition);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .stat-card .stat-icon { font-size: 28px; margin-bottom: 6px; }
        .stat-card .stat-number { font-size: 28px; font-weight: 700; color: var(--text); }
        .stat-card .stat-label { font-size: 13px; color: var(--text-light); }
        .stat-card.total { border-right: 4px solid var(--primary); }
        .stat-card.admins { border-right: 4px solid var(--gold); }
        .stat-card.active { border-right: 4px solid var(--success); }
        .stat-card.inactive { border-right: 4px solid var(--danger); }

        /* Form */
        .admin-form {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        .admin-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px; }
        .admin-form .form-row.full { grid-template-columns: 1fr; }
        .admin-form .form-row.three { grid-template-columns: 1fr 1fr 1fr; }
        .admin-form .form-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px; color: var(--text); }
        .admin-form .form-group input, .admin-form .form-group select {
            width: 100%; padding: 10px 14px; border: 2px solid var(--border); border-radius: var(--radius-sm);
            background: var(--bg); color: var(--text); font-family: 'Vazirmatn', sans-serif; font-size: 13px;
            transition: all var(--transition);
        }
        .admin-form .form-group input:focus, .admin-form .form-group select:focus {
            border-color: var(--primary); outline: none;
        }
        .admin-form .form-actions { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
        .admin-form .form-actions button {
            padding: 10px 28px; border: none; border-radius: 30px; cursor: pointer;
            font-family: 'Vazirmatn', sans-serif; font-size: 14px; font-weight: 600;
            transition: all var(--transition);
        }
        .admin-form .form-actions .btn-submit { background: var(--primary-gradient); color: #fff; }
        .admin-form .form-actions .btn-submit:hover { transform: scale(1.03); }
        .admin-form .form-actions .btn-cancel { background: var(--glass-bg); color: var(--text); border: 1px solid var(--border); }
        .admin-form .form-actions .btn-cancel:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

        .btn-primary-sm {
            background: var(--primary-gradient); color: #fff; border: none;
            padding: 8px 20px; border-radius: 30px; cursor: pointer;
            font-family: 'Vazirmatn', sans-serif; font-size: 13px; font-weight: 600;
            transition: all var(--transition);
            margin-bottom: 16px;
        }
        .btn-primary-sm:hover { opacity: 0.85; transform: scale(1.05); }

        /* Table */
        .admin-table-container {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .admin-table-container .table-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .admin-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .admin-table th { text-align: right; padding: 12px 16px; background: var(--bg); color: var(--text-light); font-weight: 600; border-bottom: 2px solid var(--border); font-size: 12px; }
        .admin-table td { padding: 12px 16px; border-bottom: 1px solid var(--border); color: var(--text); vertical-align: middle; }
        .admin-table tr:hover td { background: var(--glass-bg); }

        .status-badge {
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.active { background: #E8F5E9; color: #2E7D32; }
        .status-badge.inactive { background: #FFEBEE; color: #C62828; }
        .status-badge.admin { background: #FFF3E0; color: #E65100; }
        .status-badge.editor { background: #E3F2FD; color: #0D47A1; }
        .status-badge.user { background: #F3E5F5; color: #6A1B9A; }
        [data-theme="dark"] .status-badge.active { background: #1B5E20; color: #A5D6A7; }
        [data-theme="dark"] .status-badge.inactive { background: #B71C1C; color: #EF9A9A; }
        [data-theme="dark"] .status-badge.admin { background: #E65100; color: #FFE0B2; }
        [data-theme="dark"] .status-badge.editor { background: #0D47A1; color: #90CAF9; }
        [data-theme="dark"] .status-badge.user { background: #4A148C; color: #CE93D8; }

        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .action-btns button {
            border: none;
            padding: 4px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif;
            font-size: 11px;
            transition: all var(--transition);
            font-weight: 500;
        }
        .btn-edit { background: var(--glass-bg); color: var(--text); border: 1px solid var(--border) !important; }
        .btn-edit:hover { background: var(--primary); color: #fff; border-color: var(--primary) !important; }
        .btn-delete { background: #FFEBEE; color: #C62828; }
        .btn-delete:hover { background: #C62828; color: #fff; }
        .btn-toggle { background: #FFF3E0; color: #E65100; }
        .btn-toggle:hover { background: #E65100; color: #fff; }
        .btn-toggle.active { background: #E8F5E9; color: #2E7D32; }
        .btn-toggle.active:hover { background: #2E7D32; color: #fff; }
        .btn-role { background: #E3F2FD; color: #0D47A1; }
        .btn-role:hover { background: #0D47A1; color: #fff; }

        .message { padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 16px; }
        .message.success { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .message.error { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }

        .avatar-small {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .admin-form .form-row { grid-template-columns: 1fr; }
            .admin-form .form-row.three { grid-template-columns: 1fr; }
            .admin-table { font-size: 12px; }
            .admin-table th, .admin-table td { padding: 8px 10px; }
            .admin-header { padding: 10px 16px; }
            .admin-header .logo { font-size: 18px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .admin-table th, .admin-table td { display: block; width: 100%; }
            .admin-table thead { display: none; }
            .admin-table tr { display: block; border-bottom: 2px solid var(--border); padding: 8px 0; }
            .admin-table td { display: flex; justify-content: space-between; padding: 4px 8px; border: none; }
            .admin-table td::before { content: attr(data-label); font-weight: 600; color: var(--text-light); }
            .action-btns { justify-content: flex-end; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="logo">
            <span class="brand-en">Anosha</span>
            <span class="badge">مدیریت کاربران</span>
        </div>
        <div class="user-info">
            <span style="font-weight:500;"><?php echo $_SESSION['user_name']; ?></span>
            <div class="avatar"><?php echo mb_substr($_SESSION['user_name'], 0, 1); ?></div>
            <button class="back-btn" onclick="window.location.href='index.php'">داشبورد</button>
            <button class="back-btn" onclick="window.location.href='logout.php'" style="background:var(--danger); color:#fff; border-color:var(--danger);">خروج</button>
        </div>
    </header>

    <!-- Content -->
    <div class="admin-content">
        <div class="page-title"><i class="fas fa-users"></i> مدیریت کاربران</div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- آمار -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">مجموع کاربران</div>
            </div>
            <div class="stat-card admins">
                <div class="stat-icon">🛡️</div>
                <div class="stat-number"><?php echo count($admin_count); ?></div>
                <div class="stat-label">مدیران</div>
            </div>
            <div class="stat-card active">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo count($active_count); ?></div>
                <div class="stat-label">کاربران فعال</div>
            </div>
            <div class="stat-card inactive">
                <div class="stat-icon">⛔</div>
                <div class="stat-number"><?php echo $total_users - count($active_count); ?></div>
                <div class="stat-label">کاربران غیرفعال</div>
            </div>
        </div>

        <!-- فرم ویرایش کاربر -->
        <?php if ($edit_user): ?>
        <div class="admin-form" id="userForm">
            <h3 style="margin-bottom:16px;">
                <i class="fas fa-edit" style="color:var(--primary);"></i>
                ویرایش کاربر: <?php echo $edit_user['name']; ?>
            </h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>" />
                
                <div class="form-row">
                    <div class="form-group">
                        <label>نام کامل</label>
                        <input type="text" name="name" value="<?php echo $edit_user['name']; ?>" required />
                    </div>
                    <div class="form-group">
                        <label>ایمیل</label>
                        <input type="email" name="email" value="<?php echo $edit_user['email']; ?>" required />
                    </div>
                </div>
                
                <div class="form-row three">
                    <div class="form-group">
                        <label>نقش</label>
                        <select name="role">
                            <option value="user" <?php echo $edit_user['role'] == 'user' ? 'selected' : ''; ?>>👤 کاربر</option>
                            <option value="editor" <?php echo $edit_user['role'] == 'editor' ? 'selected' : ''; ?>>✍️ نویسنده</option>
                            <option value="admin" <?php echo $edit_user['role'] == 'admin' ? 'selected' : ''; ?>>🛡️ مدیر</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>وضعیت</label>
                        <select name="status">
                            <option value="active" <?php echo $edit_user['status'] == 'active' ? 'selected' : ''; ?>>✅ فعال</option>
                            <option value="inactive" <?php echo $edit_user['status'] == 'inactive' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>رمز عبور جدید (اختیاری)</label>
                        <input type="password" name="password" placeholder="برای تغییر وارد کنید..." />
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="edit_user" class="btn-submit"><i class="fas fa-save"></i> ذخیره تغییرات</button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='users.php'">لغو</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- لیست کاربران -->
        <div class="admin-table-container">
            <div class="table-header">
                <h3>👥 لیست کاربران</h3>
                <span style="font-size:13px; color:var(--text-light);"><?php echo $total_users; ?> کاربر</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>کاربر</th>
                        <th>نام کاربری</th>
                        <th>ایمیل</th>
                        <th>نقش</th>
                        <th>وضعیت</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $index => $u): 
                            $is_self = ($u['id'] == $_SESSION['user_id']);
                            $role_badge = [
                                'admin' => 'مدیر',
                                'editor' => 'نویسنده',
                                'user' => 'کاربر'
                            ];
                            $role_class = $u['role'];
                            
                            // بررسی وجود ستون registered_at یا registered
                            $register_date = $u['registered_at'] ?? $u['registered'] ?? null;
                            $date_display = $register_date ? getPersianDate(strtotime($register_date)) : '---';
                        ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div class="avatar-small"><?php echo mb_substr($u['name'], 0, 1); ?></div>
                                        <span style="font-weight:500;"><?php echo $u['name']; ?></span>
                                        <?php if ($is_self): ?>
                                            <span style="font-size:10px; background:var(--primary); color:#fff; padding:0 8px; border-radius:10px;">شما</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo $u['username']; ?></td>
                                <td><?php echo $u['email']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $role_class; ?>">
                                        <?php echo $role_badge[$u['role']] ?? $u['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $u['status']; ?>">
                                        <?php echo $u['status'] == 'active' ? '✅ فعال' : '⛔ غیرفعال'; ?>
                                    </span>
                                </td>
                                <td><?php echo $date_display; ?></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-edit" onclick="window.location.href='users.php?edit=<?php echo $u['id']; ?>'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if (!$is_self): ?>
                                            <!-- تغییر نقش -->
                                            <button class="btn-role" onclick="changeRole(<?php echo $u['id']; ?>, '<?php echo $u['role']; ?>')">
                                                <i class="fas fa-user-tag"></i>
                                            </button>
                                            
                                            <!-- تغییر وضعیت -->
                                            <button class="btn-toggle <?php echo $u['status'] == 'active' ? 'active' : ''; ?>" onclick="window.location.href='users.php?toggle=<?php echo $u['id']; ?>'">
                                                <?php echo $u['status'] == 'active' ? 'غیرفعال' : 'فعال'; ?>
                                            </button>
                                            
                                            <!-- حذف -->
                                            <button class="btn-delete" onclick="if(confirm('آیا از حذف کاربر "<?php echo $u['name']; ?>" مطمئن هستید؟')) window.location.href='users.php?delete=<?php echo $u['id']; ?>'">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--text-light);">(خودتان)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:30px; color:var(--text-light);">
                                <i class="fas fa-users" style="font-size:48px; display:block; margin-bottom:10px; opacity:0.3;"></i>
                                هیچ کاربری وجود ندارد
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- راهنما -->
        <div style="background:var(--bg-card); border-radius:var(--radius); padding:20px; border:1px solid var(--border);">
            <h4 style="margin-bottom:10px;"><i class="fas fa-info-circle" style="color:var(--primary);"></i> راهنمای کاربران</h4>
            <ul style="color:var(--text-light); font-size:13px; line-height:2; padding-right:20px;">
                <li>📌 <strong>نقش‌ها</strong>: 
                    <span class="status-badge admin" style="font-size:11px;">مدیر</span> دسترسی کامل | 
                    <span class="status-badge editor" style="font-size:11px;">نویسنده</span> می‌تواند مقاله بنویسد | 
                    <span class="status-badge user" style="font-size:11px;">کاربر</span> فقط می‌تواند نظر دهد
                </li>
                <li>📌 <strong>وضعیت</strong>: کاربران فعال می‌توانند وارد سایت شوند</li>
                <li>📌 <strong>تغییر نقش</strong>: با کلیک روی دکمه <i class="fas fa-user-tag"></i> می‌توانید نقش کاربر را تغییر دهید</li>
                <li>📌 <strong>امنیت</strong>: نمی‌توانید خودتان را حذف یا غیرفعال کنید</li>
                <li>📌 <strong>تعداد کاربران</strong>: <?php echo $total_users; ?> کاربر در سیستم ثبت شده است</li>
            </ul>
        </div>
    </div>

    <!-- ===== MODAL: تغییر نقش ===== -->
    <div class="modal-overlay" id="roleModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('roleModal')">&times;</button>
            <h2 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-user-tag" style="color:var(--primary);"></i>
                تغییر نقش کاربر
            </h2>
            <p style="margin-bottom:12px; color:var(--text-light);">نقش جدید را برای کاربر انتخاب کنید:</p>
            <div id="roleOptions" style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">
                <button onclick="setRole('user')" class="role-option" style="padding:10px 16px; border:2px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; background:var(--bg); text-align:right; font-family:'Vazirmatn', sans-serif; font-size:14px; transition:all var(--transition);">
                    👤 کاربر عادی
                </button>
                <button onclick="setRole('editor')" class="role-option" style="padding:10px 16px; border:2px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; background:var(--bg); text-align:right; font-family:'Vazirmatn', sans-serif; font-size:14px; transition:all var(--transition);">
                    ✍️ نویسنده
                </button>
                <button onclick="setRole('admin')" class="role-option" style="padding:10px 16px; border:2px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; background:var(--bg); text-align:right; font-family:'Vazirmatn', sans-serif; font-size:14px; transition:all var(--transition);">
                    🛡️ مدیر
                </button>
            </div>
            <input type="hidden" id="roleUserId" />
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn-cancel" onclick="closeModal('roleModal')" style="padding:10px 28px; border:none; border-radius:30px; cursor:pointer; background:var(--glass-bg); color:var(--text); border:1px solid var(--border); font-family:'Vazirmatn', sans-serif; font-size:14px; font-weight:600;">
                    لغو
                </button>
            </div>
        </div>
    </div>

    <script>
        // ===== تغییر نقش =====
        function changeRole(id, currentRole) {
            document.getElementById('roleUserId').value = id;
            
            // هایلایت نقش فعلی
            document.querySelectorAll('.role-option').forEach(btn => {
                btn.style.borderColor = 'var(--border)';
                btn.style.background = 'var(--bg)';
            });
            
            const roleMap = {
                'admin': '🛡️ مدیر',
                'editor': '✍️ نویسنده',
                'user': '👤 کاربر عادی'
            };
            
            document.querySelectorAll('.role-option').forEach(btn => {
                if (btn.textContent.trim() === roleMap[currentRole]) {
                    btn.style.borderColor = 'var(--primary)';
                    btn.style.background = 'rgba(108, 99, 255, 0.1)';
                }
            });
            
            openModal('roleModal');
        }

        function setRole(role) {
            const id = document.getElementById('roleUserId').value;
            if (id) {
                window.location.href = 'users.php?role=' + id + '&set=' + role;
            }
        }

        // ===== توابع مودال =====
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }

        // بستن مودال با کلیک روی overlay
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        console.log('👥 مدیریت کاربران');
        console.log('📊 تعداد کل: <?php echo $total_users; ?>');
        console.log('🛡️ مدیران: <?php echo count($admin_count); ?>');
        console.log('✅ فعال: <?php echo count($active_count); ?>');
        console.log('⛔ غیرفعال: <?php echo $total_users - count($active_count); ?>');
    </script>
</body>
</html>