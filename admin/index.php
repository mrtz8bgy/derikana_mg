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
    '✅ سیستم راه‌اندازی شد',
    "📰 {$total_articles} مقاله در سایت موجود است",
    "👤 {$total_users} کاربر ثبت‌نام کرده‌اند"
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>پنل مدیریت Anosha</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* ===== استایل‌های مشابه فایل ادمین ===== */
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --primary-light: #A8A4FF;
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
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        /* ===== ADMIN HEADER ===== */
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
        .admin-header .logo {
            font-size: 24px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-header .logo .brand-en {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-style: italic;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .admin-header .logo .badge {
            font-size: 12px;
            background: var(--primary-gradient);
            color: #fff;
            padding: 2px 12px;
            border-radius: 20px;
            -webkit-text-fill-color: #fff;
        }
        .admin-header .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        .admin-header .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        .admin-header .back-btn {
            background: var(--glass-bg);
            border: 1px solid var(--border);
            padding: 6px 16px;
            border-radius: 30px;
            cursor: pointer;
            color: var(--text);
            transition: all var(--transition);
            font-family: 'Vazirmatn', sans-serif;
            font-size: 13px;
        }
        .admin-header .back-btn:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* ===== ADMIN LAYOUT ===== */
        .admin-layout {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        .admin-sidebar {
            width: 260px;
            background: var(--bg-card);
            border-left: 1px solid var(--border);
            padding: 20px 0;
            flex-shrink: 0;
            overflow-y: auto;
            height: calc(100vh - 80px);
            position: sticky;
            top: 80px;
        }
        .admin-sidebar .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            cursor: pointer;
            transition: all var(--transition);
            color: var(--text-light);
            font-size: 14px;
            font-weight: 500;
            border-right: 3px solid transparent;
        }
        .admin-sidebar .menu-item:hover {
            background: var(--glass-bg);
            color: var(--text);
        }
        .admin-sidebar .menu-item.active {
            color: var(--primary);
            border-right-color: var(--primary);
            background: var(--glass-bg);
        }
        .admin-sidebar .menu-item i { width: 22px; font-size: 16px; }
        .admin-sidebar .menu-item .count {
            margin-right: auto;
            background: var(--border);
            padding: 0 10px;
            border-radius: 20px;
            font-size: 11px;
            color: var(--text-light);
        }
        .admin-sidebar .menu-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 12px 20px;
        }
        .admin-sidebar .sidebar-title {
            padding: 8px 24px;
            font-size: 11px;
            color: var(--text-light);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-content {
            flex: 1;
            padding: 24px;
            overflow-x: auto;
        }
        .admin-content .page-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .admin-content .page-title i { color: var(--primary); }

        /* ===== STATS CARDS ===== */
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

        /* ===== TABLES ===== */
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
        .admin-table-container .table-header h3 { font-size: 16px; font-weight: 700; }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .admin-table th {
            text-align: right;
            padding: 12px 16px;
            background: var(--bg);
            color: var(--text-light);
            font-weight: 600;
            border-bottom: 2px solid var(--border);
            font-size: 12px;
        }
        .admin-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
            vertical-align: middle;
        }
        .admin-table tr:hover td { background: var(--glass-bg); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .admin-layout { flex-direction: column; }
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: static;
                border-left: none;
                border-bottom: 1px solid var(--border);
                display: flex;
                flex-wrap: wrap;
                padding: 10px;
                gap: 4px;
            }
            .admin-sidebar .menu-item {
                padding: 8px 14px;
                font-size: 12px;
                border-right: none;
                border-bottom: 3px solid transparent;
                flex: 1 0 auto;
                min-width: 80px;
            }
            .admin-sidebar .menu-item.active {
                border-bottom-color: var(--primary);
                border-right-color: transparent;
            }
            .admin-sidebar .sidebar-title,
            .admin-sidebar .menu-divider { display: none; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- ===== ADMIN HEADER ===== -->
    <header class="admin-header">
        <div class="logo">
            <span class="brand-en">Derikana</span>
            <span class="badge">پنل مدیریت</span>
        </div>
        <div class="user-info">
            <span style="font-weight:500;"><?php echo $user['name']; ?></span>
            <div class="avatar"><?php echo mb_substr($user['name'], 0, 1); ?></div>
            <button class="back-btn" onclick="window.location.href='/'">بازگشت به سایت</button>
            <button class="back-btn" onclick="window.location.href='logout.php'" style="background:var(--danger); color:#fff; border-color:var(--danger);">خروج</button>
        </div>
    </header>

    <!-- ===== ADMIN LAYOUT ===== -->
    <div class="admin-layout">
        <!-- SIDEBAR -->
        <nav class="admin-sidebar">
            <div class="sidebar-title">📊 داشبورد</div>
            <a href="index.php" class="menu-item active">
                <i class="fas fa-chart-pie"></i> نمای کلی
            </a>
            <hr class="menu-divider" />
            <div class="sidebar-title">📝 مدیریت محتوا</div>
            <a href="articles.php" class="menu-item">
                <i class="fas fa-newspaper"></i> مقالات
                <span class="count"><?php echo $total_articles; ?></span>
            </a>
            <a href="categories.php" class="menu-item">
                <i class="fas fa-tags"></i> دسته‌بندی‌ها
                <span class="count"><?php echo $total_categories; ?></span>
            </a>
            <a href="banners.php" class="menu-item">
                <i class="fas fa-images"></i> بنرها
                <span class="count"><?php echo $total_banners; ?></span>
            </a>
            <a href="requests.php" class="menu-item">
                <i class="fas fa-file-alt"></i> درخواست‌ها
                <span class="count"><?php echo $total_requests; ?></span>
            </a>
            <a href="menus.php" class="menu-item">
                <i class="fas fa-bars"></i> منوها
            </a>
            <a href="pages.php" class="menu-item">
                <i class="fas fa-file"></i> صفحات
            </a>
            <hr class="menu-divider" />
            <div class="sidebar-title">👥 کاربران</div>
            <a href="users.php" class="menu-item">
                <i class="fas fa-users"></i> کاربران
                <span class="count"><?php echo $total_users; ?></span>
            </a>
            <a href="comments.php" class="menu-item">
                <i class="fas fa-comments"></i> نظرات
                <span class="count"><?php echo $total_comments; ?></span>
            </a>
            <hr class="menu-divider" />
            <div class="sidebar-title">⚙️ تنظیمات</div>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i> تنظیمات
            </a>
        </nav>

        <!-- CONTENT -->
        <main class="admin-content">
            <div class="page-title"><i class="fas fa-chart-pie"></i> نمای کلی</div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📰</div>
                    <div class="stat-number"><?php echo $total_articles; ?></div>
                    <div class="stat-label">مجموع مقالات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏷️</div>
                    <div class="stat-number"><?php echo $total_categories; ?></div>
                    <div class="stat-label">دسته‌بندی‌ها</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🖼️</div>
                    <div class="stat-number"><?php echo $total_banners; ?></div>
                    <div class="stat-label">بنرها</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">کاربران</div>
                </div>
                <div class="stat-card" style="border-right:4px solid var(--gold);">
                    <div class="stat-icon">💬</div>
                    <div class="stat-number"><?php echo $total_comments; ?></div>
                    <div class="stat-label">نظرات در انتظار</div>
                </div>
                <div class="stat-card" style="border-right:4px solid var(--info);">
                    <div class="stat-icon">📋</div>
                    <div class="stat-number"><?php echo $total_requests; ?></div>
                    <div class="stat-label">درخواست‌های جدید</div>
                </div>
            </div>

            <!-- پر بازدیدترین مقالات -->
            <div class="admin-table-container">
                <div class="table-header"><h3>🔥 پر بازدیدترین مقالات</h3></div>
                <div style="padding:12px 16px;">
                    <?php if (count($popular) > 0): ?>
                        <?php foreach ($popular as $p): ?>
                            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border); font-size:13px;">
                                <span><?php echo $p['title']; ?></span>
                                <span style="color:var(--primary); font-weight:700;">👁️ <?php echo number_format($p['views']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--text-light); text-align:center; padding:16px;">هیچ مقاله‌ای وجود ندارد</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- آخرین فعالیت‌ها -->
            <div class="admin-table-container">
                <div class="table-header"><h3>📝 آخرین فعالیت‌ها</h3></div>
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
</body>
</html>