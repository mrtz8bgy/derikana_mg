<?php
// admin/banners.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// ===== حذف بنر =====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if ($banner && $banner['image']) {
        @unlink('../uploads/banners/' . $banner['image']);
    }
    $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
    $message = '✅ بنر با موفقیت حذف شد!';
}

// ===== تغییر وضعیت فعال/غیرفعال =====
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE banners SET active = NOT active WHERE id = ?");
    $stmt->execute([$id]);
    $message = '✅ وضعیت بنر تغییر کرد!';
}

// ===== افزودن/ویرایش بنر =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = cleanInput($_POST['title'] ?? '');
    $subtitle = cleanInput($_POST['subtitle'] ?? '');
    $link = cleanInput($_POST['link'] ?? '');
    $position = $_POST['position'] ?? 'slider';
    $active = isset($_POST['active']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    
    if (empty($title)) {
        $error = '❌ لطفاً عنوان بنر را وارد کنید!';
    } else {
        $image = '';
        // آپلود تصویر جدید
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], '../uploads/banners');
            if ($upload['success']) {
                $image = $upload['filename'];
                // حذف تصویر قدیمی
                if ($id) {
                    $stmt = $pdo->prepare("SELECT image FROM banners WHERE id = ?");
                    $stmt->execute([$id]);
                    $old = $stmt->fetch();
                    if ($old && $old['image']) {
                        @unlink('../uploads/banners/' . $old['image']);
                    }
                }
            } else {
                $error = $upload['message'];
            }
        }
        
        if (empty($error)) {
            if ($id) {
                // ویرایش
                if ($image) {
                    $stmt = $pdo->prepare("UPDATE banners SET title=?, subtitle=?, link=?, position=?, active=?, sort_order=?, image=? WHERE id=?");
                    $stmt->execute([$title, $subtitle, $link, $position, $active, $sort_order, $image, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE banners SET title=?, subtitle=?, link=?, position=?, active=?, sort_order=? WHERE id=?");
                    $stmt->execute([$title, $subtitle, $link, $position, $active, $sort_order, $id]);
                }
                $message = '✅ بنر با موفقیت ویرایش شد!';
            } else {
                // افزودن
                if (empty($image)) {
                    $error = '❌ لطفاً تصویر بنر را انتخاب کنید!';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO banners (title, subtitle, link, position, active, sort_order, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $subtitle, $link, $position, $active, $sort_order, $image]);
                    $message = '✅ بنر جدید با موفقیت اضافه شد!';
                }
            }
        }
    }
}

// ===== دریافت لیست بنرها =====
$banners = $pdo->query("SELECT * FROM banners ORDER BY position, sort_order")->fetchAll();

// ===== دریافت بنر برای ویرایش =====
$edit_banner = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_banner = $stmt->fetch();
}

// ===== موقعیت‌های بنر =====
$positions = [
    'slider' => 'اسلایدر',
    'banner' => 'بنر وسط',
    'sidebar' => 'سایدبار',
    'header' => 'هدر',
    'footer' => 'فوتر'
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت بنرها</title>
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
        .admin-form .form-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px; color: var(--text); }
        .admin-form .form-group input, .admin-form .form-group select, .admin-form .form-group textarea {
            width: 100%; padding: 10px 14px; border: 2px solid var(--border); border-radius: var(--radius-sm);
            background: var(--bg); color: var(--text); font-family: 'Vazirmatn', sans-serif; font-size: 13px;
            transition: all var(--transition);
        }
        .admin-form .form-group input:focus, .admin-form .form-group select:focus, .admin-form .form-group textarea:focus {
            border-color: var(--primary); outline: none;
        }
        .admin-form .form-group textarea { min-height: 80px; resize: vertical; }
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
        .admin-form .form-group input[type="file"] { padding: 8px; }

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

        .status-badge { padding: 2px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge.active { background: #E8F5E9; color: #2E7D32; }
        .status-badge.inactive { background: #FFEBEE; color: #C62828; }
        [data-theme="dark"] .status-badge.active { background: #1B5E20; color: #A5D6A7; }
        [data-theme="dark"] .status-badge.inactive { background: #B71C1C; color: #EF9A9A; }

        .position-badge {
            padding: 2px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
            background: #E3F2FD; color: #0D47A1;
        }
        [data-theme="dark"] .position-badge { background: #0D47A1; color: #90CAF9; }

        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .action-btns button { border: none; padding: 4px 12px; border-radius: 20px; cursor: pointer; font-family: 'Vazirmatn', sans-serif; font-size: 11px; transition: all var(--transition); font-weight: 500; }
        .btn-edit { background: var(--glass-bg); color: var(--text); border: 1px solid var(--border) !important; }
        .btn-edit:hover { background: var(--primary); color: #fff; border-color: var(--primary) !important; }
        .btn-delete { background: #FFEBEE; color: #C62828; }
        .btn-delete:hover { background: #C62828; color: #fff; }
        .btn-toggle { background: #FFF3E0; color: #E65100; }
        .btn-toggle:hover { background: #E65100; color: #fff; }
        .btn-toggle.active { background: #E8F5E9; color: #2E7D32; }
        .btn-toggle.active:hover { background: #2E7D32; color: #fff; }

        .message { padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 16px; }
        .message.success { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .message.error { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }

        .banner-preview { width: 80px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); }

        @media (max-width: 768px) {
            .admin-form .form-row { grid-template-columns: 1fr; }
            .admin-table { font-size: 12px; }
            .admin-table th, .admin-table td { padding: 8px 10px; }
            .admin-header { padding: 10px 16px; }
            .admin-header .logo { font-size: 18px; }
            .banner-preview { width: 50px; height: 35px; }
        }
        @media (max-width: 480px) {
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
            <span class="badge">مدیریت بنرها</span>
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
        <div class="page-title"><i class="fas fa-images"></i> مدیریت بنرها</div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- فرم افزودن/ویرایش بنر -->
        <button class="btn-primary-sm" onclick="toggleForm()">
            <i class="fas fa-plus"></i> <?php echo $edit_banner ? 'ویرایش بنر' : 'بنر جدید'; ?>
        </button>

        <div class="admin-form" id="bannerForm" style="<?php echo $edit_banner ? 'display:block;' : 'display:none;'; ?>">
            <h3 style="margin-bottom:16px;">
                <i class="fas fa-edit" style="color:var(--primary);"></i>
                <?php echo $edit_banner ? 'ویرایش بنر' : 'افزودن بنر جدید'; ?>
            </h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_banner['id'] ?? ''; ?>" />
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>عنوان بنر *</label>
                        <input type="text" name="title" value="<?php echo $edit_banner['title'] ?? ''; ?>" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>زیرمتن</label>
                        <input type="text" name="subtitle" value="<?php echo $edit_banner['subtitle'] ?? ''; ?>" />
                    </div>
                    <div class="form-group">
                        <label>لینک (اختیاری)</label>
                        <input type="text" name="link" value="<?php echo $edit_banner['link'] ?? ''; ?>" placeholder="https://example.com" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>موقعیت نمایش</label>
                        <select name="position">
                            <?php foreach ($positions as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($edit_banner && $edit_banner['position'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ترتیب نمایش</label>
                        <input type="number" name="sort_order" value="<?php echo $edit_banner['sort_order'] ?? 0; ?>" min="0" />
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>تصویر بنر *</label>
                        <input type="file" name="image" accept="image/*" <?php echo $edit_banner ? '' : 'required'; ?> />
                        <?php if ($edit_banner && $edit_banner['image']): ?>
                            <div style="margin-top:8px;">
                                <img src="/uploads/banners/<?php echo $edit_banner['image']; ?>" style="max-width:200px; max-height:120px; border-radius:8px; border:1px solid var(--border);" />
                                <span style="font-size:11px; color:var(--text-light); display:block; margin-top:4px;">تصویر فعلی</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="active" value="1" <?php echo ($edit_banner && $edit_banner['active']) ? 'checked' : 'checked'; ?> />
                            فعال
                        </label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="save_banner" class="btn-submit"><i class="fas fa-save"></i> ذخیره</button>
                    <button type="button" class="btn-cancel" onclick="toggleForm()">لغو</button>
                </div>
            </form>
        </div>

        <!-- لیست بنرها -->
        <div class="admin-table-container">
            <div class="table-header">
                <h3>🖼️ لیست بنرها</h3>
                <span style="font-size:13px; color:var(--text-light);"><?php echo count($banners); ?> بنر</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>تصویر</th>
                        <th>عنوان</th>
                        <th>موقعیت</th>
                        <th>ترتیب</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($banners) > 0): ?>
                        <?php foreach ($banners as $index => $b): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <?php if ($b['image']): ?>
                                        <img src="/uploads/banners/<?php echo $b['image']; ?>" class="banner-preview" alt="<?php echo $b['title']; ?>" />
                                    <?php else: ?>
                                        <span style="color:var(--text-light); font-size:11px;">بدون تصویر</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:500;"><?php echo $b['title']; ?></td>
                                <td><span class="position-badge"><?php echo $positions[$b['position']] ?? $b['position']; ?></span></td>
                                <td><?php echo $b['sort_order']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $b['active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $b['active'] ? 'فعال' : 'غیرفعال'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-edit" onclick="window.location.href='banners.php?edit=<?php echo $b['id']; ?>'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-toggle <?php echo $b['active'] ? 'active' : ''; ?>" onclick="window.location.href='banners.php?toggle=<?php echo $b['id']; ?>'">
                                            <?php echo $b['active'] ? 'غیرفعال' : 'فعال'; ?>
                                        </button>
                                        <button class="btn-delete" onclick="if(confirm('آیا از حذف این بنر مطمئن هستید؟')) window.location.href='banners.php?delete=<?php echo $b['id']; ?>'">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:30px; color:var(--text-light);">
                                <i class="fas fa-images" style="font-size:48px; display:block; margin-bottom:10px; opacity:0.3;"></i>
                                هیچ بنری وجود ندارد. اولین بنر را اضافه کنید!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- راهنما -->
        <div style="background:var(--bg-card); border-radius:var(--radius); padding:20px; border:1px solid var(--border);">
            <h4 style="margin-bottom:10px;"><i class="fas fa-info-circle" style="color:var(--primary);"></i> راهنمای بنرها</h4>
            <ul style="color:var(--text-light); font-size:13px; line-height:2; padding-right:20px;">
                <li>📌 <strong>اسلایدر</strong>: بنرهای بزرگ در بالای صفحه اصلی</li>
                <li>📌 <strong>بنر وسط</strong>: بنرهای تبلیغاتی در وسط صفحات</li>
                <li>📌 <strong>سایدبار</strong>: بنرهای کناری در صفحات</li>
                <li>📌 <strong>هدر</strong>: بنرهای بالای صفحه</li>
                <li>📌 <strong>فوتر</strong>: بنرهای پایین صفحه</li>
                <li>📁 تصاویر در پوشه <code>/uploads/banners/</code> ذخیره می‌شوند</li>
                <li>📏 اندازه پیشنهادی: اسلایدر (1200x400)، بنر (800x300)، سایدبار (300x250)</li>
            </ul>
        </div>
    </div>

    <script>
        function toggleForm() {
            const form = document.getElementById('bannerForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'block') {
                form.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // اگر خطایی وجود داشت، فرم را باز نگه دار
        <?php if ($error): ?>
            document.getElementById('bannerForm').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>