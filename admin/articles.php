<?php
// admin/articles.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// حذف مقاله
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    if ($article && $article['image']) {
        @unlink('../uploads/articles/' . $article['image']);
    }
    $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
    $message = '✅ مقاله با موفقیت حذف شد!';
}

// افزودن/ویرایش مقاله
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = cleanInput($_POST['title'] ?? '');
    $slug_input = cleanInput($_POST['slug'] ?? '');
    
    // ساخت اسلاگ - اگر کاربر وارد نکرده بود، از عنوان بساز
    if (empty($slug_input)) {
        $slug = slugify($title);
    } else {
        $slug = slugify($slug_input);
    }
    
    // بررسی تکراری نبودن اسلاگ
    if ($id) {
        $check = $pdo->prepare("SELECT id FROM articles WHERE slug = ? AND id != ?");
        $check->execute([$slug, $id]);
    } else {
        $check = $pdo->prepare("SELECT id FROM articles WHERE slug = ?");
        $check->execute([$slug]);
    }
    
    if ($check->fetch()) {
        // اگر اسلاگ تکراری بود، یک عدد به آن اضافه کن
        $slug = $slug . '-' . time();
    }
    
    $content = $_POST['content'] ?? '';
    $excerpt = cleanInput($_POST['excerpt'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $tags = cleanInput($_POST['tags'] ?? '');
    $meta_title = cleanInput($_POST['meta_title'] ?? '');
    $meta_description = cleanInput($_POST['meta_description'] ?? '');
    
    if (empty($title) || empty($content)) {
        $error = '❌ لطفاً عنوان و متن مقاله را وارد کنید!';
    } else {
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], '../uploads/articles');
            if ($upload['success']) {
                $image = $upload['filename'];
                // حذف تصویر قدیمی
                if ($id) {
                    $stmt = $pdo->prepare("SELECT image FROM articles WHERE id = ?");
                    $stmt->execute([$id]);
                    $old = $stmt->fetch();
                    if ($old && $old['image']) {
                        @unlink('../uploads/articles/' . $old['image']);
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
                    $stmt = $pdo->prepare("UPDATE articles SET title=?, slug=?, content=?, excerpt=?, category_id=?, status=?, featured=?, tags=?, meta_title=?, meta_description=?, image=? WHERE id=?");
                    $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $status, $featured, $tags, $meta_title, $meta_description, $image, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE articles SET title=?, slug=?, content=?, excerpt=?, category_id=?, status=?, featured=?, tags=?, meta_title=?, meta_description=? WHERE id=?");
                    $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $status, $featured, $tags, $meta_title, $meta_description, $id]);
                }
                $message = '✅ مقاله با موفقیت ویرایش شد!';
            } else {
                // افزودن
                $author_id = $_SESSION['user_id'];
                $stmt = $pdo->prepare("INSERT INTO articles (title, slug, content, excerpt, category_id, author_id, status, featured, tags, meta_title, meta_description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $author_id, $status, $featured, $tags, $meta_title, $meta_description, $image]);
                $message = '✅ مقاله جدید با موفقیت اضافه شد!';
            }
        }
    }
}

// دریافت مقالات
$articles = $pdo->query("SELECT a.*, u.name as author_name, c.name as category_name 
                         FROM articles a 
                         LEFT JOIN users u ON a.author_id = u.id 
                         LEFT JOIN categories c ON a.category_id = c.id 
                         ORDER BY a.created_at DESC")->fetchAll();

// دریافت دسته‌بندی‌ها برای فرم
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// مقاله برای ویرایش
$edit_article = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_article = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت مقالات</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* استایل‌های مشابه admin/index.php */
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
        .status-badge.published { background: #E8F5E9; color: #2E7D32; }
        .status-badge.draft { background: #FFF3E0; color: #E65100; }
        .status-badge.archived { background: #EEEEEE; color: #616161; }
        [data-theme="dark"] .status-badge.published { background: #1B5E20; color: #A5D6A7; }
        [data-theme="dark"] .status-badge.draft { background: #E65100; color: #FFE0B2; }
        [data-theme="dark"] .status-badge.archived { background: #424242; color: #BDBDBD; }

        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .action-btns button { border: none; padding: 4px 12px; border-radius: 20px; cursor: pointer; font-family: 'Vazirmatn', sans-serif; font-size: 11px; transition: all var(--transition); font-weight: 500; }
        .btn-edit { background: var(--glass-bg); color: var(--text); border: 1px solid var(--border) !important; }
        .btn-edit:hover { background: var(--primary); color: #fff; border-color: var(--primary) !important; }
        .btn-delete { background: #FFEBEE; color: #C62828; }
        .btn-delete:hover { background: #C62828; color: #fff; }
        .btn-view { background: var(--glass-bg); color: var(--primary); border: 1px solid var(--primary) !important; }
        .btn-view:hover { background: var(--primary); color: #fff; }

        .message { padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 16px; }
        .message.success { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .message.error { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }

        /* Slug preview */
        .slug-preview {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 4px;
            padding: 4px 12px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            direction: ltr;
            display: inline-block;
        }
        .slug-preview strong {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .admin-form .form-row { grid-template-columns: 1fr; }
            .admin-table { font-size: 12px; }
            .admin-table th, .admin-table td { padding: 8px 10px; }
            .admin-header { padding: 10px 16px; }
            .admin-header .logo { font-size: 18px; }
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
            <span class="brand-en">Derikana</span>
            <span class="badge">مدیریت مقالات</span>
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
        <div class="page-title"><i class="fas fa-newspaper"></i> مدیریت مقالات</div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- فرم افزودن/ویرایش مقاله -->
        <button class="btn-primary-sm" onclick="toggleForm()">
            <i class="fas fa-plus"></i> <?php echo $edit_article ? 'ویرایش مقاله' : 'مقاله جدید'; ?>
        </button>

        <div class="admin-form" id="articleForm" style="<?php echo $edit_article ? 'display:block;' : 'display:none;'; ?>">
            <h3 style="margin-bottom:16px;">
                <i class="fas fa-edit" style="color:var(--primary);"></i>
                <?php echo $edit_article ? 'ویرایش مقاله' : 'افزودن مقاله جدید'; ?>
            </h3>
            <form method="POST" enctype="multipart/form-data" id="articleFormSubmit">
                <input type="hidden" name="id" value="<?php echo $edit_article['id'] ?? ''; ?>" />
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>عنوان مقاله *</label>
                        <input type="text" name="title" id="articleTitle" value="<?php echo $edit_article['title'] ?? ''; ?>" required onkeyup="generateSlug()" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>اسلاگ (آدرس)</label>
                        <input type="text" name="slug" id="articleSlug" value="<?php echo $edit_article['slug'] ?? ''; ?>" placeholder="به صورت خودکار پر می‌شود" onkeyup="validateSlug()" />
                        <div class="slug-preview" id="slugPreview">
                            <i class="fas fa-link"></i> 
                            <span id="slugPreviewText"><?php echo $edit_article['slug'] ?? 'آدرس خودکار'; ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>دسته‌بندی</label>
                        <select name="category_id">
                            <option value="">بدون دسته</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_article && $edit_article['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>وضعیت</label>
                        <select name="status">
                            <option value="draft" <?php echo ($edit_article && $edit_article['status'] == 'draft') ? 'selected' : ''; ?>>پیش‌نویس</option>
                            <option value="published" <?php echo ($edit_article && $edit_article['status'] == 'published') ? 'selected' : ''; ?>>منتشر شده</option>
                            <option value="archived" <?php echo ($edit_article && $edit_article['status'] == 'archived') ? 'selected' : ''; ?>>بایگانی</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="featured" value="1" <?php echo ($edit_article && $edit_article['featured']) ? 'checked' : ''; ?> />
                            مقاله ویژه
                        </label>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>تصویر شاخص</label>
                        <input type="file" name="image" accept="image/*" id="articleImage" />
                        <?php if ($edit_article && $edit_article['image']): ?>
                            <div style="margin-top:6px;">
                                <img src="/uploads/articles/<?php echo $edit_article['image']; ?>" style="max-width:150px; max-height:100px; border-radius:8px;" />
                                <span style="font-size:11px; color:var(--text-light);">تصویر فعلی</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>خلاصه مقاله</label>
                        <textarea name="excerpt" rows="2"><?php echo $edit_article['excerpt'] ?? ''; ?></textarea>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>متن مقاله *</label>
                        <textarea name="content" rows="8" required><?php echo $edit_article['content'] ?? ''; ?></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>تگ‌ها (با کاما جدا کنید)</label>
                        <input type="text" name="tags" value="<?php echo $edit_article['tags'] ?? ''; ?>" placeholder="تکنولوژی, موبایل, هوش مصنوعی" />
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>عنوان سئو (meta title)</label>
                        <input type="text" name="meta_title" value="<?php echo $edit_article['meta_title'] ?? ''; ?>" />
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>توضیحات سئو (meta description)</label>
                        <textarea name="meta_description" rows="2"><?php echo $edit_article['meta_description'] ?? ''; ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> ذخیره</button>
                    <button type="button" class="btn-cancel" onclick="toggleForm()">لغو</button>
                </div>
            </form>
        </div>

        <!-- لیست مقالات -->
        <div class="admin-table-container">
            <div class="table-header">
                <h3>📄 لیست مقالات</h3>
                <span style="font-size:13px; color:var(--text-light);"><?php echo count($articles); ?> مقاله</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>عنوان</th>
                        <th>اسلاگ</th>
                        <th>دسته</th>
                        <th>نویسنده</th>
                        <th>بازدید</th>
                        <th>تاریخ</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($articles) > 0): ?>
                        <?php foreach ($articles as $index => $a): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td style="font-weight:500;"><?php echo $a['title']; ?></td>
                                <td>
                                    <?php if ($a['slug']): ?>
                                        <span style="font-size:11px; color:var(--primary);"><?php echo $a['slug']; ?></span>
                                    <?php else: ?>
                                        <span style="font-size:11px; color:var(--danger);">❌ خالی</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $a['category_name'] ?? 'بدون دسته'; ?></td>
                                <td><?php echo $a['author_name'] ?? 'نامشخص'; ?></td>
                                <td>👁️ <?php echo number_format($a['views']); ?></td>
                                <td><?php echo getPersianDate(strtotime($a['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $a['status']; ?>">
                                        <?php echo $a['status'] == 'published' ? 'منتشر شده' : ($a['status'] == 'draft' ? 'پیش‌نویس' : 'بایگانی'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <?php if ($a['slug']): ?>
                                            <button class="btn-view" onclick="window.location.href='../article.php?slug=<?php echo $a['slug']; ?>'"><i class="fas fa-eye"></i></button>
                                        <?php else: ?>
                                            <button class="btn-view" disabled style="opacity:0.5;"><i class="fas fa-eye"></i></button>
                                        <?php endif; ?>
                                        <button class="btn-edit" onclick="window.location.href='articles.php?edit=<?php echo $a['id']; ?>'"><i class="fas fa-edit"></i></button>
                                        <button class="btn-delete" onclick="if(confirm('آیا از حذف این مقاله مطمئن هستید؟')) window.location.href='articles.php?delete=<?php echo $a['id']; ?>'"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:24px; color:var(--text-light);">
                                هیچ مقاله‌ای وجود ندارد
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- دکمه اصلاح اسلاگ‌ها -->
        <?php 
        // بررسی وجود مقالات بدون اسلاگ
        $empty_slugs = $pdo->query("SELECT COUNT(*) FROM articles WHERE slug IS NULL OR slug = ''")->fetchColumn();
        if ($empty_slugs > 0): 
        ?>
        <div style="background: #FFF3E0; padding: 15px 20px; border-radius: var(--radius-sm); border-right: 4px solid #FF9800; margin-top: 16px;">
            <p style="color: #E65100;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>⚠️ هشدار:</strong> 
                <?php echo $empty_slugs; ?> مقاله اسلاگ ندارند! برای اصلاح، 
                <a href="fix_slugs.php" style="color: var(--primary); font-weight: 600; text-decoration: underline;">اینجا کلیک کنید</a>.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // ===== توابع اسلاگ =====
        function generateSlug() {
            const title = document.getElementById('articleTitle').value;
            const slugInput = document.getElementById('articleSlug');
            const previewText = document.getElementById('slugPreviewText');
            
            // اگر کاربر هنوز اسلاگ را دستی وارد نکرده بود
            if (!slugInput.dataset.userEdited) {
                const slug = title
                    .trim()
                    .toLowerCase()
                    .replace(/[^a-zA-Z0-9\u0600-\u06FF\s]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                
                slugInput.value = slug;
                previewText.textContent = slug || 'آدرس خودکار';
            }
        }
        
        function validateSlug() {
            const slugInput = document.getElementById('articleSlug');
            const previewText = document.getElementById('slugPreviewText');
            
            // کاربر در حال ویرایش دستی است
            slugInput.dataset.userEdited = 'true';
            
            const slug = slugInput.value
                .trim()
                .toLowerCase()
                .replace(/[^a-zA-Z0-9\-]/g, '')
                .replace(/-+/g, '-');
            
            previewText.textContent = slug || 'آدرس خودکار';
            
            // اگر اسلاگ خالی بود، هشدار بده
            if (slug === '') {
                previewText.textContent = '⚠️ اسلاگ نمی‌تواند خالی باشد';
                previewText.style.color = 'var(--danger)';
            } else {
                previewText.style.color = 'var(--primary)';
            }
        }
        
        // ===== نمایش/مخفی کردن فرم =====
        function toggleForm() {
            const form = document.getElementById('articleForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'block') {
                form.scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        // ===== اعتبارسنجی فرم قبل از ارسال =====
        document.getElementById('articleFormSubmit').addEventListener('submit', function(e) {
            const title = document.getElementById('articleTitle').value.trim();
            const slug = document.getElementById('articleSlug').value.trim();
            
            if (!title) {
                e.preventDefault();
                alert('❌ لطفاً عنوان مقاله را وارد کنید!');
                return false;
            }
            
            // اگر اسلاگ خالی بود، از عنوان بساز
            if (!slug) {
                const newSlug = title
                    .trim()
                    .toLowerCase()
                    .replace(/[^a-zA-Z0-9\u0600-\u06FF\s]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                document.getElementById('articleSlug').value = newSlug;
            }
        });
    </script>
</body>
</html>