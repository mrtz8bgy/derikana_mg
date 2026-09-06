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
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت مقالات</title>
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
            <span class="badge">مدیریت مقالات</span>
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
        <?php $current_page = 'articles.php'; require __DIR__ . '/partials/sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="admin-content">
        <div class="page-title"><i class="fas fa-newspaper"></i> مدیریت مقالات</div>

        <?php if ($message): ?>
            <div class="message success"><i class="fas fa-circle-check"></i> <?php echo preg_replace('/[\x{2705}\x{274C}\x{26A0}\x{FE0F}\x{23F3}\x{26D4}]\s*/u', '', $message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><i class="fas fa-circle-xmark"></i> <?php echo preg_replace('/[\x{2705}\x{274C}\x{26A0}\x{FE0F}\x{23F3}\x{26D4}]\s*/u', '', $error); ?></div>
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
                <h3><i class="fas fa-file-lines" aria-hidden="true"></i> لیست مقالات</h3>
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
                                        <span style="font-size:11px; color:var(--danger);"><i class="fas fa-circle-xmark" aria-hidden="true"></i> خالی</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $a['category_name'] ?? 'بدون دسته'; ?></td>
                                <td><?php echo $a['author_name'] ?? 'نامشخص'; ?></td>
                                <td><i class="fas fa-eye" aria-hidden="true"></i> <?php echo number_format($a['views']); ?></td>
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
                <strong><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> هشدار:</strong> 
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
    </div><!-- /admin-layout -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>