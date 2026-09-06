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
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت بنرها</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <script>(function(){try{var t=localStorage.getItem("theme");document.documentElement.setAttribute("data-theme",t||"dark");}catch(e){}})();</script>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <button class="sidebar-toggle" onclick="toggleAdminSidebar()" aria-label="منو"><i class="fas fa-bars"></i></button>
        <div class="logo">
            <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="gmark" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse"><stop stop-color="#EAD6A6"/><stop offset=".45" stop-color="#D6B36A"/><stop offset="1" stop-color="#A9853E"/></linearGradient></defs><path d="M14 8h20l8 10-18 22L6 18 14 8z" fill="url(#gmark)"/><path d="M6 18h36M14 8l4 10 6-10 6 10 4-10M18 18l6 22 6-22" stroke="#0B0D12" stroke-opacity=".38" stroke-width="1.6"/></svg></span>
            <span class="brand-en">دریکانا</span>
            <span class="badge">مدیریت بنرها</span>
        </div>
        <button class="theme-btn" onclick="toggleAdminTheme()" aria-label="تغییر پوسته"><i class="fas fa-sun i-sun"></i><i class="fas fa-moon i-moon"></i></button>
        <div class="user-info">
            <span style="font-weight:500;"><?php echo $_SESSION['user_name']; ?></span>
            <div class="avatar"><?php echo mb_substr($_SESSION['user_name'], 0, 1); ?></div>
            <button class="back-btn" onclick="window.location.href='index.php'">داشبورد</button>
            <button class="back-btn danger" onclick="window.location.href='logout.php'">خروج</button>
        </div>
    </header>

    <!-- Content -->
    <!-- ===== ADMIN LAYOUT ===== -->
    <div class="admin-layout">
        <?php $current_page = 'banners.php'; require __DIR__ . '/partials/sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="admin-content">
        <div class="page-title"><i class="fas fa-images"></i> مدیریت بنرها</div>

        <?php if ($message): ?>
            <div class="message success"><i class="fas fa-circle-check"></i> <?php echo preg_replace('/[\x{2705}\x{274C}\x{26A0}\x{FE0F}\x{23F3}\x{26D4}]\s*/u', '', $message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><i class="fas fa-circle-xmark"></i> <?php echo preg_replace('/[\x{2705}\x{274C}\x{26A0}\x{FE0F}\x{23F3}\x{26D4}]\s*/u', '', $error); ?></div>
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
                <h3><i class="fas fa-image" aria-hidden="true"></i> لیست بنرها</h3>
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
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>اسلایدر</strong>: بنرهای بزرگ در بالای صفحه اصلی</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>بنر وسط</strong>: بنرهای تبلیغاتی در وسط صفحات</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>سایدبار</strong>: بنرهای کناری در صفحات</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>هدر</strong>: بنرهای بالای صفحه</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>فوتر</strong>: بنرهای پایین صفحه</li>
                <li><i class="fas fa-folder-open" aria-hidden="true"></i> تصاویر در پوشه <code>/uploads/banners/</code> ذخیره می‌شوند</li>
                <li><i class="fas fa-ruler" aria-hidden="true"></i> اندازه پیشنهادی: اسلایدر (1200x400)، بنر (800x300)، سایدبار (300x250)</li>
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
    </div><!-- /admin-layout -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>