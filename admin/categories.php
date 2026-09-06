<?php
// admin/categories.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// ===== ابتدا ستون‌های مورد نیاز را بررسی و اضافه کن =====
try {
    $check_col = $pdo->query("SHOW COLUMNS FROM categories LIKE 'active'");
    if ($check_col->rowCount() == 0) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN active TINYINT(1) DEFAULT 1");
    }
    $check_col = $pdo->query("SHOW COLUMNS FROM categories LIKE 'link_type'");
    if ($check_col->rowCount() == 0) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN link_type ENUM('internal','external') DEFAULT 'internal'");
    }
    $check_col = $pdo->query("SHOW COLUMNS FROM categories LIKE 'external_link'");
    if ($check_col->rowCount() == 0) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN external_link VARCHAR(500) DEFAULT NULL");
    }
    $check_col = $pdo->query("SHOW COLUMNS FROM categories LIKE 'link_target'");
    if ($check_col->rowCount() == 0) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN link_target TINYINT(1) DEFAULT 0");
    }
} catch (PDOException $e) {
    // خطا را نادیده بگیر
}

// ===== حذف دسته‌بندی =====
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // 1. بررسی اینکه دسته‌بندی مقاله دارد یا خیر
        $check = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
        $check->execute([$id]);
        $count = $check->fetchColumn();
        
        if ($count > 0) {
            $error = "❌ این دسته‌بندی دارای {$count} مقاله است! ابتدا مقالات را به دسته دیگری منتقل کنید.";
        } else {
            // 2. بررسی زیرمجموعه‌ها
            $check_sub = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $check_sub->execute([$id]);
            $sub_count = $check_sub->fetchColumn();
            
            if ($sub_count > 0) {
                $error = "❌ این دسته‌بندی دارای {$sub_count} زیرمجموعه است! ابتدا زیرمجموعه‌ها را حذف یا منتقل کنید.";
            } else {
                // 3. حذف دسته‌بندی
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $message = '✅ دسته‌بندی با موفقیت حذف شد!';
                } else {
                    $error = '❌ دسته‌بندی یافت نشد یا قبلاً حذف شده است!';
                }
            }
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            $error = '❌ این دسته‌بندی دارای وابستگی است! ابتدا مقالات و زیرمجموعه‌های آن را حذف کنید.';
        } else {
            $error = '❌ خطا در حذف دسته‌بندی: ' . $e->getMessage();
        }
    }
}

// ===== تغییر وضعیت فعال/غیرفعال =====
if (isset($_GET['toggle']) && !empty($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        $stmt = $pdo->prepare("UPDATE categories SET active = NOT active WHERE id = ?");
        $stmt->execute([$id]);
        
        $stmt2 = $pdo->prepare("SELECT active FROM categories WHERE id = ?");
        $stmt2->execute([$id]);
        $new_status = $stmt2->fetchColumn();
        
        $message = '✅ وضعیت دسته‌بندی به ' . ($new_status ? 'فعال' : 'غیرفعال') . ' تغییر کرد!';
    } catch (PDOException $e) {
        $error = '❌ خطا: ' . $e->getMessage();
    }
}

// ===== افزودن/ویرایش دسته‌بندی =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = cleanInput($_POST['name'] ?? '');
    
    $link_type = $_POST['link_type'] ?? 'internal';
    $external_link = cleanInput($_POST['external_link'] ?? '');
    $slug_input = cleanInput($_POST['slug'] ?? '');
    
    if ($link_type === 'internal') {
        if (!empty($slug_input)) {
            $slug = slugify_persian($slug_input);
        } else {
            $slug = slugify_persian($name);
        }
        if ($id) {
            $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
        } else {
            $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $check->execute([$slug]);
        }
        if ($check->fetch()) {
            $slug = $slug . '-' . time();
        }
    } else {
        if (!empty($slug_input)) {
            $slug = slugify_persian($slug_input);
            if ($id) {
                $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
                $check->execute([$slug, $id]);
            } else {
                $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                $check->execute([$slug]);
            }
            if ($check->fetch()) {
                $slug = $slug . '-' . time();
            }
        } else {
            $slug = null;
        }
    }
    
    $color = cleanInput($_POST['color'] ?? '#C9A84C');
    $icon = cleanInput($_POST['icon'] ?? '');
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    if ($parent_id == 0) {
        $parent_id = null;
    }
    $description = cleanInput($_POST['description'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    $link_target = isset($_POST['link_target']) ? 1 : 0;
    
    if (empty($name)) {
        $error = '❌ لطفاً نام دسته‌بندی را وارد کنید!';
    } elseif ($link_type === 'external' && empty($external_link)) {
        $error = '❌ برای لینک خارجی، آدرس را وارد کنید!';
    } elseif ($link_type === 'external' && !filter_var($external_link, FILTER_VALIDATE_URL)) {
        $error = '❌ آدرس لینک خارجی معتبر نیست! (مثال: https://example.com)';
    } else {
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE categories SET 
                    name=?, slug=?, color=?, icon=?, parent_id=?, 
                    description=?, sort_order=?, active=?, 
                    link_type=?, external_link=?, link_target=? 
                    WHERE id=?");
                $stmt->execute([
                    $name, $slug, $color, $icon, $parent_id,
                    $description, $sort_order, $active,
                    $link_type, $external_link, $link_target,
                    $id
                ]);
                $message = '✅ دسته‌بندی با موفقیت ویرایش شد!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories 
                    (name, slug, color, icon, parent_id, description, sort_order, active, link_type, external_link, link_target) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $name, $slug, $color, $icon, $parent_id,
                    $description, $sort_order, $active,
                    $link_type, $external_link, $link_target
                ]);
                $message = '✅ دسته‌بندی جدید با موفقیت اضافه شد!';
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Cannot add or update a child row') !== false) {
                $error = '❌ خطا در ارتباط با دسته والد! لطفاً یک دسته والد معتبر انتخاب کنید.';
            } else {
                $error = '❌ خطا: ' . $e->getMessage();
            }
        }
    }
}

// ===== دریافت لیست دسته‌بندی‌ها با تابع بازگشتی =====
function getCategoriesTree($pdo, $parent_id = null, $level = 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id " . ($parent_id === null ? "IS NULL" : "= ?") . " ORDER BY sort_order ASC, name ASC");
    if ($parent_id !== null) {
        $stmt->execute([$parent_id]);
    } else {
        $stmt->execute();
    }
    $result = [];
    while ($row = $stmt->fetch()) {
        $row['level'] = $level;
        $result[] = $row;
        // دریافت زیرمجموعه‌ها
        $children = getCategoriesTree($pdo, $row['id'], $level + 1);
        $result = array_merge($result, $children);
    }
    return $result;
}

$categories = getCategoriesTree($pdo);

// ===== دریافت دسته‌بندی برای ویرایش =====
$edit_category = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_category = $stmt->fetch();
}

// ===== آیکون‌های پیش‌فرض =====
$icons = [
    'fa-laptop' => '💻 لپ‌تاپ',
    'fa-mobile-alt' => '📱 موبایل',
    'fa-shield-alt' => '🛡️ امنیت',
    'fa-gamepad' => '🎮 گیمینگ',
    'fa-leaf' => '🌿 سبک زندگی',
    'fa-heartbeat' => '💚 سلامت',
    'fa-flask' => '🔬 علم',
    'fa-palette' => '🎨 هنر',
    'fa-chart-line' => '📊 اقتصاد',
    'fa-plane' => '✈️ گردشگری',
    'fa-code' => '💻 برنامه‌نویسی',
    'fa-camera' => '📷 عکاسی',
    'fa-music' => '🎵 موسیقی',
    'fa-book' => '📚 کتاب',
    'fa-film' => '🎬 فیلم',
    'fa-futbol' => '⚽ ورزش',
    'fa-utensils' => '🍽️ غذا',
    'fa-heart' => '❤️ عشق',
    'fa-star' => '⭐ ویژه',
    'fa-fire' => '🔥 داغ',
    'fa-lightbulb' => '💡 ایده',
    'fa-rocket' => '🚀 پیشرفت',
    'fa-globe' => '🌍 جهان',
    'fa-brain' => '🧠 ذهن',
    'fa-gem' => '💎 جواهر',
    'fa-crown' => '👑 سلطنتی',
    'fa-ring' => '💍 انگشتر',
    'fa-watch' => '⌚ ساعت',
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت دسته‌بندی‌ها - دریکانا</title>
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
            <span class="brand-en">Derikana</span>
            <span class="badge">مدیریت دسته‌بندی‌ها</span>
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
        <?php $current_page = 'categories.php'; require __DIR__ . '/partials/sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="admin-content">
        <div class="page-title"><i class="fas fa-tags"></i> مدیریت دسته‌بندی‌ها</div>

        <?php if ($message): ?>
            <div class="message success"><i class="fas fa-circle-check"></i> <?php echo preg_replace('/[\x{2705}\x{274C}\x{26A0}\x{FE0F}\x{23F3}\x{26D4}]\s*/u', '', $message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><i class="fas fa-circle-xmark"></i> <?php echo preg_replace('/[\x{2705}\x{274C}\x{26A0}\x{FE0F}\x{23F3}\x{26D4}]\s*/u', '', $error); ?></div>
        <?php endif; ?>

        <!-- فرم افزودن/ویرایش دسته‌بندی -->
        <button class="btn-primary-sm" onclick="toggleForm()">
            <i class="fas fa-plus"></i> <?php echo $edit_category ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید'; ?>
        </button>

        <div class="admin-form" id="categoryForm" style="<?php echo $edit_category ? 'display:block;' : 'display:none;'; ?>">
            <h3 style="margin-bottom:16px;">
                <i class="fas fa-edit" style="color:var(--primary);"></i>
                <?php echo $edit_category ? 'ویرایش دسته‌بندی' : 'افزودن دسته‌بندی جدید'; ?>
            </h3>
            <form method="POST" id="categoryFormSubmit">
                <input type="hidden" name="id" value="<?php echo isset($edit_category['id']) ? $edit_category['id'] : ''; ?>" />
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>نام دسته‌بندی *</label>
                        <input type="text" name="name" id="categoryName" value="<?php echo isset($edit_category['name']) ? $edit_category['name'] : ''; ?>" required onkeyup="generateSlug()" />
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label>نوع لینک</label>
                        <div class="link-type-group">
                            <label>
                                <input type="radio" name="link_type" value="internal" <?php echo (!isset($edit_category['link_type']) || $edit_category['link_type'] == 'internal') ? 'checked' : ''; ?> onchange="toggleLinkType()" />
                                <i class="fas fa-file-lines" aria-hidden="true"></i> لینک داخلی (دسته‌بندی)
                            </label>
                            <label>
                                <input type="radio" name="link_type" value="external" <?php echo (isset($edit_category['link_type']) && $edit_category['link_type'] == 'external') ? 'checked' : ''; ?> onchange="toggleLinkType()" />
                                <i class="fas fa-link" aria-hidden="true"></i> لینک خارجی
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row full external-link-group <?php echo (isset($edit_category['link_type']) && $edit_category['link_type'] == 'external') ? 'show' : ''; ?>" id="externalLinkGroup">
                    <div class="form-group">
                        <label>آدرس لینک خارجی *</label>
                        <input type="url" name="external_link" id="externalLink" value="<?php echo isset($edit_category['external_link']) ? $edit_category['external_link'] : ''; ?>" placeholder="https://example.com" />
                        <div style="font-size:12px; color:var(--text-light); margin-top:4px;">
                            <i class="fas fa-info-circle"></i> آدرس کامل لینک را وارد کنید (مثال: https://example.com)
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>اسلاگ (آدرس) - فقط برای لینک داخلی</label>
                        <input type="text" name="slug" id="categorySlug" value="<?php echo isset($edit_category['slug']) ? $edit_category['slug'] : ''; ?>" placeholder="اختیاری - به صورت خودکار پر می‌شود" onkeyup="validateSlug()" />
                        <div class="slug-preview" id="slugPreview">
                            <i class="fas fa-link"></i> 
                            <span id="slugPreviewText"><?php echo isset($edit_category['slug']) && $edit_category['slug'] ? $edit_category['slug'] : 'آدرس خودکار برای لینک داخلی'; ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>رنگ</label>
                        <input type="color" name="color" value="<?php echo isset($edit_category['color']) ? $edit_category['color'] : '#C9A84C'; ?>" />
                    </div>
                </div>
                
                <div class="form-row three">
                    <div class="form-group">
                        <label>آیکون</label>
                        <input type="text" name="icon" id="iconInput" value="<?php echo isset($edit_category['icon']) ? $edit_category['icon'] : ''; ?>" placeholder="مثال: fa-gem" />
                        <div style="font-size:12px; color:var(--text-light); margin-top:4px;">
                            آیکون انتخاب شده: <span id="selectedIconPreview" class="icon-preview"><?php echo (isset($edit_category['icon']) && $edit_category['icon']) ? '<i class="fas ' . $edit_category['icon'] . '"></i>' : '❌'; ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>دسته والد</label>
                        <select name="parent_id">
                            <option value="0">بدون والد (دسته اصلی)</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($edit_category && $cat['id'] == $edit_category['id']) continue; ?>
                                <?php if ($edit_category && $cat['id'] == $edit_category['parent_id']) continue; ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_category && isset($edit_category['parent_id']) && $edit_category['parent_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo str_repeat('— ', $cat['level'] ?? 0) . (isset($cat['name']) ? $cat['name'] : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ترتیب نمایش</label>
                        <input type="number" name="sort_order" value="<?php echo isset($edit_category['sort_order']) ? $edit_category['sort_order'] : 0; ?>" min="0" />
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>توضیحات</label>
                        <textarea name="description" rows="2"><?php echo isset($edit_category['description']) ? $edit_category['description'] : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="link_target" value="1" <?php echo (isset($edit_category['link_target']) && $edit_category['link_target'] == 1) ? 'checked' : ''; ?> id="linkTarget" />
                            <label for="linkTarget">باز شدن در صفحه جدید (برای لینک خارجی)</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="active" value="1" <?php echo (isset($edit_category['active']) && $edit_category['active'] == 1) ? 'checked' : 'checked'; ?> id="activeCheck" />
                            <label for="activeCheck">فعال</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>انتخاب آیکون از لیست</label>
                    <div class="icon-grid" id="iconGrid">
                        <?php foreach ($icons as $icon_class => $icon_label): ?>
                            <div class="icon-item <?php echo (isset($edit_category['icon']) && $edit_category['icon'] == $icon_class) ? 'active' : ''; ?>" 
                                 onclick="selectIcon('<?php echo $icon_class; ?>')" 
                                 title="<?php echo $icon_label; ?>">
                                <i class="fas <?php echo $icon_class; ?>"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="save_category" class="btn-submit"><i class="fas fa-save"></i> ذخیره</button>
                    <button type="button" class="btn-cancel" onclick="toggleForm()">لغو</button>
                </div>
            </form>
        </div>

        <!-- لیست دسته‌بندی‌ها -->
        <div class="admin-table-container">
            <div class="table-header">
                <h3><i class="fas fa-tags" aria-hidden="true"></i> لیست دسته‌بندی‌ها</h3>
                <span style="font-size:13px; color:var(--text-light);"><?php echo count($categories); ?> دسته‌بندی</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>آیکون</th>
                        <th>نام</th>
                        <th>نوع</th>
                        <th>اسلاگ/لینک</th>
                        <th>رنگ</th>
                        <th>مقالات</th>
                        <th>ترتیب</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($categories) > 0): ?>
                        <?php 
                        $counter = 0;
                        foreach ($categories as $cat):
                            $counter++;
                            $article_count = 0;
                            $link_type = isset($cat['link_type']) ? $cat['link_type'] : 'internal';
                            if ($link_type == 'internal') {
                                $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ? AND status = 'published'");
                                $count_stmt->execute([$cat['id']]);
                                $article_count = $count_stmt->fetchColumn();
                            }
                            
                            $is_active = isset($cat['active']) ? $cat['active'] : 1;
                            $link_type_label = $link_type == 'internal' ? 'داخلی' : 'خارجی';
                            $link_type_class = $link_type == 'internal' ? 'internal' : 'external';
                            $level = isset($cat['level']) ? $cat['level'] : 0;
                            $indent = str_repeat('— ', $level);
                        ?>
                            <tr>
                                <td><?php echo $counter; ?></td>
                                <td>
                                    <?php if (isset($cat['icon']) && $cat['icon']): ?>
                                        <i class="fas <?php echo $cat['icon']; ?>" style="font-size:20px; color:<?php echo isset($cat['color']) ? $cat['color'] : '#C9A84C'; ?>;"></i>
                                    <?php else: ?>
                                        <span style="color:var(--text-light);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:500;">
                                    <span style="color:var(--text-light); font-size:12px;"><?php echo $indent; ?></span>
                                    <?php echo isset($cat['name']) ? $cat['name'] : ''; ?>
                                    <?php if ($level > 0): ?>
                                        <span style="font-size:11px; color:var(--text-light);">(زیرمجموعه)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="link-badge <?php echo $link_type_class; ?>">
                                        <?php echo $link_type_label; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($link_type == 'external'): ?>
                                        <span style="font-size:11px; color:var(--info);">
                                            <i class="fas fa-external-link-alt"></i> 
                                            <a href="<?php echo isset($cat['external_link']) ? $cat['external_link'] : '#'; ?>" target="_blank" style="color:var(--info); text-decoration:underline;">
                                                <?php echo isset($cat['external_link']) ? substr($cat['external_link'], 0, 30) . (strlen($cat['external_link']) > 30 ? '...' : '') : ''; ?>
                                            </a>
                                        </span>
                                    <?php else: ?>
                                        <?php if (isset($cat['slug']) && $cat['slug']): ?>
                                            <span style="font-size:11px; color:var(--primary);"><?php echo $cat['slug']; ?></span>
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--danger);"><i class="fas fa-circle-xmark" aria-hidden="true"></i> خالی</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="color-preview" style="background:<?php echo isset($cat['color']) ? $cat['color'] : '#C9A84C'; ?>;"></span>
                                </td>
                                <td>
                                    <?php if ($link_type == 'internal'): ?>
                                        <span style="font-weight:600; color:var(--primary);"><?php echo $article_count; ?></span>
                                        <?php if ($article_count > 0): ?>
                                            <span style="font-size:11px; color:var(--text-light);">مقاله</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="font-size:11px; color:var(--text-light);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($cat['sort_order']) ? $cat['sort_order'] : 0; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $is_active ? 'فعال' : 'غیرفعال'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-edit" onclick="window.location.href='categories.php?edit=<?php echo $cat['id']; ?>'" title="ویرایش">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-toggle <?php echo $is_active ? 'active' : ''; ?>" onclick="window.location.href='categories.php?toggle=<?php echo $cat['id']; ?>'" title="<?php echo $is_active ? 'غیرفعال' : 'فعال'; ?>">
                                            <?php echo $is_active ? 'غیرفعال' : 'فعال'; ?>
                                        </button>
                                        <button class="btn-delete" onclick="confirmDelete(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', <?php echo $article_count; ?>)" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align:center; padding:30px; color:var(--text-light);">
                                <i class="fas fa-tags" style="font-size:48px; display:block; margin-bottom:10px; opacity:0.3;"></i>
                                هیچ دسته‌بندی وجود ندارد. اولین دسته‌بندی را اضافه کنید!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ===== راهنما ===== -->
        <div style="background:var(--bg-card); border-radius:var(--radius); padding:20px; border:1px solid var(--border); margin-top:16px;">
            <h4 style="margin-bottom:10px;"><i class="fas fa-info-circle" style="color:var(--primary);"></i> راهنمای دسته‌بندی‌ها</h4>
            <ul style="color:var(--text-light); font-size:13px; line-height:2; padding-right:20px;">
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>دسته‌بندی داخلی</strong>: مقالات در این دسته‌بندی قرار می‌گیرند و آدرس آن از اسلاگ ساخته می‌شود</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>لینک خارجی</strong>: با کلیک روی این دسته‌بندی، کاربر به لینک خارجی هدایت می‌شود</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>زیرمجموعه</strong>: با انتخاب دسته والد، زیرمجموعه ایجاد می‌شود (سلسله مراتب نامحدود)</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>اسلاگ</strong>: آدرس دسته‌بندی در URL - فقط برای لینک‌های داخلی ضروری است</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>حذف</strong>: دسته‌بندی‌هایی که مقاله یا زیرمجموعه دارند قابل حذف نیستند</li>
            </ul>
        </div>
    </div>

    <!-- ===== Modal Confirm Delete ===== -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h3><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> تأیید حذف</h3>
            <p id="deleteMessage">آیا از حذف این دسته‌بندی مطمئن هستید؟</p>
            <div class="modal-actions">
                <button class="btn-confirm" onclick="executeDelete()">بله، حذف کن</button>
                <button class="btn-cancel-modal" onclick="closeModal()">انصراف</button>
            </div>
        </div>
    </div>

    <script>
        // ===== متغیرهای حذف =====
        let deleteId = null;
        let deleteName = '';

        function confirmDelete(id, name, articleCount) {
            if (articleCount > 0) {
                alert('❌ این دسته‌بندی دارای ' + articleCount + ' مقاله است! ابتدا مقالات را به دسته دیگری منتقل کنید.');
                return;
            }
            deleteId = id;
            deleteName = name;
            document.getElementById('deleteMessage').textContent = 'آیا از حذف دسته‌بندی "' + name + '" مطمئن هستید؟ این عملیات غیرقابل بازگشت است.';
            document.getElementById('deleteModal').classList.add('show');
        }

        function executeDelete() {
            if (deleteId) {
                window.location.href = 'categories.php?delete=' + deleteId;
            }
        }

        function closeModal() {
            document.getElementById('deleteModal').classList.remove('show');
            deleteId = null;
        }

        // بستن مودال با کلیک روی پس‌زمینه
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // ===== توابع اسلاگ =====
        function generateSlug() {
            const linkType = document.querySelector('input[name="link_type"]:checked').value;
            if (linkType === 'internal') {
                const name = document.getElementById('categoryName').value;
                const slugInput = document.getElementById('categorySlug');
                const previewText = document.getElementById('slugPreviewText');
                if (!slugInput.dataset.userEdited) {
                    const slug = name.trim().replace(/\s+/g, '-').replace(/[^a-zA-Z0-9\-آ-ی]/g, '').replace(/-+/g, '-');
                    slugInput.value = slug;
                    previewText.textContent = slug || 'آدرس خودکار';
                }
            }
        }
        
        function validateSlug() {
            const slugInput = document.getElementById('categorySlug');
            const previewText = document.getElementById('slugPreviewText');
            const linkType = document.querySelector('input[name="link_type"]:checked').value;
            slugInput.dataset.userEdited = 'true';
            const slug = slugInput.value.trim().replace(/\s+/g, '-').replace(/[^a-zA-Z0-9\-آ-ی]/g, '').replace(/-+/g, '-');
            if (linkType === 'internal') {
                previewText.textContent = slug || 'آدرس خودکار';
                previewText.style.color = slug === '' ? 'var(--danger)' : 'var(--primary)';
            } else {
                previewText.textContent = 'لینک خارجی - نیازی به اسلاگ نیست';
                previewText.style.color = 'var(--text-light)';
            }
        }

        function toggleLinkType() {
            const externalRadio = document.querySelector('input[name="link_type"][value="external"]');
            const externalGroup = document.getElementById('externalLinkGroup');
            const slugInput = document.getElementById('categorySlug');
            const slugPreview = document.getElementById('slugPreviewText');
            if (externalRadio.checked) {
                externalGroup.classList.add('show');
                slugInput.placeholder = 'اختیاری - برای لینک خارجی';
                slugPreview.textContent = 'لینک خارجی - نیازی به اسلاگ نیست';
                slugPreview.style.color = 'var(--text-light)';
                slugInput.dataset.userEdited = 'true';
            } else {
                externalGroup.classList.remove('show');
                slugInput.placeholder = 'به صورت خودکار پر می‌شود';
                generateSlug();
            }
        }

        function selectIcon(iconClass) {
            document.getElementById('iconInput').value = iconClass;
            document.getElementById('selectedIconPreview').innerHTML = '<i class="fas ' + iconClass + '"></i>';
            document.querySelectorAll('.icon-item').forEach(el => {
                el.classList.remove('active');
                if (el.querySelector('i').className === 'fas ' + iconClass) {
                    el.classList.add('active');
                }
            });
        }

        function toggleForm() {
            const form = document.getElementById('categoryForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'block') {
                form.scrollIntoView({ behavior: 'smooth' });
                setTimeout(toggleLinkType, 100);
            }
        }

        document.getElementById('categoryFormSubmit').addEventListener('submit', function(e) {
            const name = document.getElementById('categoryName').value.trim();
            if (!name) {
                e.preventDefault();
                alert('❌ لطفاً نام دسته‌بندی را وارد کنید!');
                return false;
            }
            const externalRadio = document.querySelector('input[name="link_type"][value="external"]');
            if (externalRadio.checked) {
                const externalLink = document.getElementById('externalLink').value.trim();
                if (!externalLink) {
                    e.preventDefault();
                    alert('❌ برای لینک خارجی، آدرس را وارد کنید!');
                    return false;
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            toggleLinkType();
        });
    </script>
    </div><!-- /admin-layout -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>