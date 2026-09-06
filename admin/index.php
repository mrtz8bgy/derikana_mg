<?php
// admin/index.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// بررسی دسترسی
if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();

// ... ادامه کدهای داشبورد ...

requireAdmin();

$user = getCurrentUser();

// آمار
$total_articles = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_banners = $pdo->query("SELECT COUNT(*) FROM banners")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_comments = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
$total_requests = $pdo->query("SELECT COUNT(*) FROM article_requests WHERE status = 'pending'")->fetchColumn();

// مقالات پر بازدید
$popular = $pdo->query("SELECT title, views FROM articles WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();

// آخرین فعالیت‌ها (ساده)
$activities = [
    'سیستم با موفقیت راه‌اندازی شد',
    "{$total_articles} مقاله در سایت منتشر شده است",
    "{$total_users} کاربر در مجله ثبت‌نام کرده‌اند"
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>پنل مدیریت دریکانا</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <script>(function(){try{var t=localStorage.getItem("theme");document.documentElement.setAttribute("data-theme",t||"dark");}catch(e){}})();</script>
</head>
<body>
    <!-- ===== ADMIN HEADER ===== -->
    <header class="admin-header">
        <button class="sidebar-toggle" onclick="toggleAdminSidebar()" aria-label="منو"><i class="fas fa-bars"></i></button>
        <div class="logo">
            <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="gmark" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse"><stop stop-color="#EAD6A6"/><stop offset=".45" stop-color="#D6B36A"/><stop offset="1" stop-color="#A9853E"/></linearGradient></defs><path d="M14 8h20l8 10-18 22L6 18 14 8z" fill="url(#gmark)"/><path d="M6 18h36M14 8l4 10 6-10 6 10 4-10M18 18l6 22 6-22" stroke="#0B0D12" stroke-opacity=".38" stroke-width="1.6"/></svg></span>
            <span class="brand-en">Derikana</span>
            <span class="badge">پنل مدیریت</span>
        </div>
        <button class="theme-btn" onclick="toggleAdminTheme()" aria-label="تغییر پوسته"><i class="fas fa-sun i-sun"></i><i class="fas fa-moon i-moon"></i></button>
        <div class="user-info">
            <span style="font-weight:500;"><?php echo $user['name']; ?></span>
            <div class="avatar"><?php echo mb_substr($user['name'], 0, 1); ?></div>
            <button class="back-btn" onclick="window.location.href='/'">بازگشت به سایت</button>
            <button class="back-btn danger" onclick="window.location.href='logout.php'">خروج</button>
        </div>
    </header>

    <!-- ===== ADMIN LAYOUT ===== -->
    <div class="admin-layout">
        <!-- SIDEBAR -->
        <?php $current_page = 'index.php'; require __DIR__ . '/partials/sidebar.php'; ?>

        <!-- CONTENT -->
        <main class="admin-content">
            <div class="page-title"><i class="fas fa-chart-pie"></i> نمای کلی</div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-newspaper" aria-hidden="true"></i></div>
                    <div class="stat-number"><?php echo $total_articles; ?></div>
                    <div class="stat-label">مجموع مقالات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tags" aria-hidden="true"></i></div>
                    <div class="stat-number"><?php echo $total_categories; ?></div>
                    <div class="stat-label">دسته‌بندی‌ها</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-image" aria-hidden="true"></i></div>
                    <div class="stat-number"><?php echo $total_banners; ?></div>
                    <div class="stat-label">بنرها</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user" aria-hidden="true"></i></div>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">کاربران</div>
                </div>
                <div class="stat-card" style="border-right:4px solid var(--gold);">
                    <div class="stat-icon"><i class="fas fa-comments" aria-hidden="true"></i></div>
                    <div class="stat-number"><?php echo $total_comments; ?></div>
                    <div class="stat-label">نظرات در انتظار</div>
                </div>
                <div class="stat-card" style="border-right:4px solid var(--info);">
                    <div class="stat-icon"><i class="fas fa-file-lines" aria-hidden="true"></i></div>
                    <div class="stat-number"><?php echo $total_requests; ?></div>
                    <div class="stat-label">درخواست‌های جدید</div>
                </div>
            </div>

            <!-- پر بازدیدترین مقالات -->
            <div class="admin-table-container">
                <div class="table-header"><h3><i class="fas fa-fire" aria-hidden="true"></i> پر بازدیدترین مقالات</h3></div>
                <div style="padding:12px 16px;">
                    <?php if (count($popular) > 0): ?>
                        <?php foreach ($popular as $p): ?>
                            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border); font-size:13px;">
                                <span><?php echo $p['title']; ?></span>
                                <span style="color:var(--primary); font-weight:700;"><i class="fas fa-eye" aria-hidden="true"></i> <?php echo number_format($p['views']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--text-light); text-align:center; padding:16px;">هیچ مقاله‌ای وجود ندارد</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- آخرین فعالیت‌ها -->
            <div class="admin-table-container">
                <div class="table-header"><h3><i class="fas fa-newspaper" aria-hidden="true"></i> آخرین فعالیت‌ها</h3></div>
                <div style="padding:12px 16px;">
                    <?php foreach ($activities as $act): ?>
                        <div style="padding:4px 0; border-bottom:1px solid var(--border); font-size:13px; color:var(--text-light);">
                            <?php echo $act; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/admin.js"></script>
</body>
</html>