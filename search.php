<?php
// search.php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// دریافت عبارت جستجو
$query = isset($_GET['q']) ? cleanInput($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = (int)getSetting('posts_per_page') ?: 9;
$offset = ($page - 1) * $limit;

$categories = getCategories();
$site_title = 'نتایج جستجو: ' . $query . ' | ' . (getSetting('site_title') ?: 'مجله آنوشا');
$site_description = 'نتایج جستجو برای عبارت "' . $query . '" در مجله آنوشا';

$results = [];
$total_results = 0;
$total_pages = 0;

if (!empty($query)) {
    $total_results = getTotalArticles(null, $query);
    $total_pages = ceil($total_results / $limit);
    $results = getArticles($limit, $offset, null, $query);
}

// دریافت مقالات محبوب برای سایدبار
$popular_articles = $pdo->query("SELECT id, title, slug, image, views, created_at FROM articles WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();

// دریافت مقالات اخیر برای سایدبار
$recent_articles = $pdo->query("SELECT id, title, slug, image, created_at FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>" />
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&family=Great+Vibes&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* ===== استایل‌های اصلی ===== */
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --primary-light: #A8A4FF;
            --primary-gradient: linear-gradient(135deg, #6C63FF 0%, #3F3D9E 100%);
            --bg: #f5f6fa;
            --bg-card: #ffffff;
            --text: #1a1a2e;
            --text-light: #6c6c8a;
            --border: #e2e6f0;
            --shadow: rgba(108, 99, 255, 0.10);
            --shadow-hover: rgba(108, 99, 255, 0.20);
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.12);
            --header-bg: rgba(255, 255, 255, 0.95);
            --footer-bg: #0a0a1a;
            --footer-text: #b0b0d0;
            --radius: 20px;
            --radius-sm: 12px;
            --transition: 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            --gold: #F9A825;
            --success: #00E676;
            --danger: #FF5252;
            --info: #448AFF;
            --orange: #FF9800;
        }
        [data-theme="dark"] {
            --bg: #0a0a1a;
            --bg-card: #16162e;
            --text: #e8e8f0;
            --text-light: #9090b0;
            --border: #2a2a4a;
            --header-bg: rgba(10, 10, 26, 0.95);
            --footer-bg: #05050f;
            --footer-text: #8080a0;
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.5);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        body { background: var(--bg); color: var(--text); transition: all var(--transition); min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        /* ===== HEADER ===== */
        .header {
            background: var(--header-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 10px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }
        .header-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .logo { font-size: 28px; font-weight: 900; display: flex; align-items: center; gap: 6px; }
        .logo .brand-en { font-family: 'Playfair Display', serif; font-weight: 900; font-style: italic; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 32px; }
        .logo .brand-fa { font-family: 'Great Vibes', cursive; font-weight: 700; color: var(--text); font-size: 24px; -webkit-text-fill-color: var(--text); opacity: 0.8; }
        
        .search-box { flex: 1; max-width: 400px; min-width: 150px; display: flex; background: var(--bg); border-radius: 30px; overflow: hidden; border: 2px solid var(--border); }
        .search-box input { flex: 1; border: none; padding: 10px 18px; background: transparent; font-size: 13px; outline: none; color: var(--text); }
        .search-box button { background: var(--primary-gradient); border: none; padding: 10px 20px; cursor: pointer; color: #fff; font-size: 14px; }
        .search-box button:hover { opacity: 0.85; }
        
        .header-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .header-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif;
            font-size: 13px;
            color: var(--text);
            padding: 6px 14px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 500;
            transition: all var(--transition);
        }
        .header-btn:hover { background: rgba(255,255,255,0.1); color: var(--primary); }
        .theme-toggle {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            padding: 6px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text);
            transition: all var(--transition);
            font-size: 12px;
        }
        .theme-toggle:hover { background: var(--primary); color: #fff; }

        /* ===== CATEGORIES ===== */
        .categories {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 10px 0;
            overflow-x: auto;
            position: sticky;
            top: 72px;
            z-index: 999;
        }
        .categories-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            gap: 6px 20px;
            padding: 0 20px;
            font-size: 14px;
            color: var(--text-light);
            overflow-x: auto;
        }
        .categories-inner a {
            padding: 4px 0;
            border-bottom: 3px solid transparent;
            transition: all var(--transition);
            white-space: nowrap;
            cursor: pointer;
            font-weight: 500;
            flex-shrink: 0;
        }
        .categories-inner a:hover { color: var(--primary); }
        .categories-inner a.active { color: var(--primary); border-bottom-color: var(--primary); }

        /* ===== SEARCH HEADER ===== */
        .search-header {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 16px;
        }
        .search-header .header-content {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 40px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            text-align: center;
        }
        .search-header .header-content h1 {
            font-size: 28px;
            color: var(--text);
        }
        .search-header .header-content h1 i {
            color: var(--primary);
        }
        .search-header .header-content .query {
            color: var(--primary);
            font-weight: 700;
        }
        .search-header .header-content .result-count {
            color: var(--text-light);
            font-size: 14px;
            margin-top: 8px;
        }
        .search-header .header-content .search-again {
            margin-top: 16px;
        }
        .search-header .header-content .search-again form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
            gap: 10px;
        }
        .search-header .header-content .search-again input {
            flex: 1;
            padding: 10px 18px;
            border: 2px solid var(--border);
            border-radius: 30px;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            font-family: 'Vazirmatn', sans-serif;
            transition: all var(--transition);
        }
        .search-header .header-content .search-again input:focus {
            border-color: var(--primary);
            outline: none;
        }
        .search-header .header-content .search-again button {
            padding: 10px 24px;
            border: none;
            border-radius: 30px;
            background: var(--primary-gradient);
            color: #fff;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif;
            font-size: 14px;
            font-weight: 600;
            transition: all var(--transition);
        }
        .search-header .header-content .search-again button:hover {
            transform: scale(1.05);
        }

        /* ===== SEARCH RESULTS ===== */
        .search-results {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 16px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
        }
        .results-main .result-item {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            margin-bottom: 16px;
            transition: all var(--transition);
            cursor: pointer;
        }
        .results-main .result-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-light);
        }
        .results-main .result-item .result-meta {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 6px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .results-main .result-item .result-meta .category {
            color: var(--primary);
            font-weight: 600;
        }
        .results-main .result-item .result-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
        }
        .results-main .result-item .result-title .highlight {
            background: rgba(108, 99, 255, 0.15);
            padding: 0 4px;
            border-radius: 4px;
        }
        .results-main .result-item .result-excerpt {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.8;
        }
        .results-main .result-item .result-excerpt .highlight {
            background: rgba(108, 99, 255, 0.15);
            padding: 0 4px;
            border-radius: 4px;
        }
        .results-main .result-item .result-footer {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12px;
            color: var(--text-light);
        }
        .results-main .result-item .result-footer a {
            color: var(--primary);
            font-weight: 600;
        }
        .results-main .result-item .result-footer a:hover {
            text-decoration: underline;
        }
        .results-main .result-item .result-footer .views {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        .empty-state i {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 22px;
            color: var(--text);
            margin-bottom: 8px;
        }
        .empty-state .suggestions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .empty-state .suggestions a {
            padding: 6px 18px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 13px;
            color: var(--text);
            transition: all var(--transition);
        }
        .empty-state .suggestions a:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* ===== SIDEBAR ===== */
        .results-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .sidebar-widget {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        .sidebar-widget .widget-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar-widget .widget-title i { color: var(--primary); }
        .sidebar-widget .widget-item {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: all var(--transition);
        }
        .sidebar-widget .widget-item:hover {
            transform: translateX(-4px);
            color: var(--primary);
        }
        .sidebar-widget .widget-item:last-child { border-bottom: none; }
        .sidebar-widget .widget-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            flex-shrink: 0;
        }
        .sidebar-widget .widget-item .info h4 {
            font-size: 14px;
            line-height: 1.4;
        }
        .sidebar-widget .widget-item .info .date {
            font-size: 11px;
            color: var(--text-light);
        }
        .sidebar-widget .widget-item .info .views {
            font-size: 11px;
            color: var(--text-light);
        }

        /* ===== PAGINATION ===== */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 8px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg-card);
            color: var(--text);
            cursor: pointer;
            transition: all var(--transition);
            font-size: 13px;
            font-weight: 500;
        }
        .pagination a:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .pagination .active {
            background: var(--primary-gradient);
            color: #fff;
            border-color: var(--primary);
        }
        .pagination .disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: var(--footer-bg);
            color: var(--footer-text);
            padding: 40px 20px 16px;
            margin-top: 40px;
        }
        .footer-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 24px;
        }
        .footer-inner h4 { color: #fff; margin-bottom: 12px; font-size: 16px; }
        .footer-inner a { display: block; margin: 6px 0; font-size: 13px; cursor: pointer; transition: all var(--transition); opacity: 0.7; }
        .footer-inner a:hover { color: var(--primary); opacity: 1; }
        .footer-inner .contact-phone {
            font-size: 18px;
            color: var(--primary);
            font-weight: 700;
            direction: ltr;
            display: inline-block;
            padding: 6px 16px;
            border-radius: 30px;
            border: 2px solid var(--primary);
            transition: all var(--transition);
            opacity: 1;
        }
        .footer-inner .contact-phone:hover { background: var(--primary); color: #fff; }
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            margin-top: 20px;
            font-size: 13px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .search-results {
                grid-template-columns: 1fr;
            }
            .results-sidebar {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
        }
        @media (max-width: 768px) {
            .header-inner { flex-direction: column; align-items: stretch; gap: 8px; }
            .search-box { max-width: 100%; }
            .categories { top: 68px; }
            .categories-inner { font-size: 12px; gap: 4px 12px; justify-content: flex-start; }
            .search-header .header-content { padding: 24px; }
            .search-header .header-content h1 { font-size: 22px; }
            .search-header .header-content .search-again form { flex-direction: column; }
            .results-sidebar { grid-template-columns: 1fr; }
            .results-main .result-item { padding: 16px; }
            .results-main .result-item .result-title { font-size: 17px; }
        }
        @media (max-width: 480px) {
            .search-header .header-content { padding: 16px; }
            .search-header .header-content h1 { font-size: 18px; }
            .search-results { padding: 0 10px; }
            .results-main .result-item { padding: 12px; }
            .results-main .result-item .result-title { font-size: 15px; }
            .results-main .result-item .result-excerpt { font-size: 13px; }
            .pagination a, .pagination span { padding: 6px 12px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header class="header">
        <div class="header-inner">
            <div class="logo">
                <span class="brand-en">Anosha</span>
                <span class="brand-fa">مجله آنوشا</span>
            </div>
            <div class="search-box">
                <form action="search.php" method="GET" style="display:flex; flex:1;">
                    <input type="text" name="q" placeholder="🔍 جستجوی مقاله..." value="<?php echo htmlspecialchars($query); ?>" />
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="header-actions">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <span id="themeIcon">🌙</span>
                    <span id="themeLabel">تیره</span>
                </button>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="admin/" class="header-btn" style="background:var(--primary-gradient); color:#fff; padding:6px 18px; border-radius:30px; font-weight:600;">
                            <i class="fas fa-user-shield"></i> پنل مدیریت
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="header-btn">
                        <i class="fas fa-sign-out-alt"></i> خروج
                    </a>
                <?php else: ?>
                    <a href="login.php" class="header-btn">ورود</a>
                    <a href="register.php" class="header-btn" style="background:var(--primary-gradient); color:#fff; padding:6px 18px; border-radius:30px; font-weight:600;">
                        ثبت‌نام
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- ===== CATEGORIES ===== -->
    <nav class="categories">
        <div class="categories-inner">
            <a href="index.php">🔥 همه</a>
            <?php foreach ($categories as $cat): ?>
                <a href="category.php?slug=<?php echo $cat['slug']; ?>">
                    <?php if ($cat['icon']): ?>
                        <i class="fas <?php echo $cat['icon']; ?>"></i>
                    <?php endif; ?>
                    <?php echo $cat['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <!-- ===== SEARCH HEADER ===== -->
    <section class="search-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-search"></i> 
                نتایج جستجو برای: <span class="query">"<?php echo htmlspecialchars($query); ?>"</span>
            </h1>
            <?php if (!empty($query)): ?>
                <div class="result-count">
                    <?php if ($total_results > 0): ?>
                        <i class="fas fa-file-alt"></i> <?php echo number_format($total_results); ?> مقاله یافت شد
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle"></i> هیچ مقاله‌ای یافت نشد
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="search-again">
                <form action="search.php" method="GET">
                    <input type="text" name="q" placeholder="جستجوی جدید..." value="<?php echo htmlspecialchars($query); ?>" />
                    <button type="submit"><i class="fas fa-search"></i> جستجو</button>
                </form>
            </div>
        </div>
    </section>

    <!-- ===== SEARCH RESULTS ===== -->
    <div class="search-results">
        <div class="results-main">
            <?php if (!empty($query)): ?>
                <?php if (count($results) > 0): ?>
                    <?php foreach ($results as $article): ?>
                        <div class="result-item" onclick="location.href='article.php?slug=<?php echo $article['slug']; ?>'">
                            <div class="result-meta">
                                <span class="category"><i class="fas fa-tag"></i> <?php echo $article['category_name'] ?? 'بدون دسته'; ?></span>
                                <span><i class="fas fa-user"></i> <?php echo $article['author_name'] ?? 'نامشخص'; ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo getPersianDate(strtotime($article['created_at'])); ?></span>
                            </div>
                            <div class="result-title">
                                <?php 
                                // هایلایت کلمه جستجو در عنوان
                                $title = $article['title'];
                                if (!empty($query)) {
                                    $title = preg_replace('/(' . preg_quote($query, '/') . ')/iu', '<span class="highlight">$1</span>', $title);
                                }
                                echo $title;
                                ?>
                            </div>
                            <div class="result-excerpt">
                                <?php 
                                // هایلایت کلمه جستجو در خلاصه
                                $excerpt = $article['excerpt'] ?? truncateText($article['content'], 150);
                                if (!empty($query)) {
                                    $excerpt = preg_replace('/(' . preg_quote($query, '/') . ')/iu', '<span class="highlight">$1</span>', $excerpt);
                                }
                                echo $excerpt;
                                ?>
                            </div>
                            <div class="result-footer">
                                <a href="article.php?slug=<?php echo $article['slug']; ?>">مشاهده مقاله <i class="fas fa-arrow-left"></i></a>
                                <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- صفحه‌بندی -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>">‹</a>
                            <?php else: ?>
                                <span class="disabled">‹</span>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php elseif ($i == 1 || $i == $total_pages || abs($i - $page) <= 2): ?>
                                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <span>…</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>">›</a>
                            <?php else: ?>
                                <span class="disabled">›</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>نتیجه‌ای یافت نشد!</h3>
                        <p>متأسفیم، هیچ مقاله‌ای با عبارت "<strong><?php echo htmlspecialchars($query); ?></strong>" یافت نشد.</p>
                        <div class="suggestions">
                            <span style="color:var(--text-light); font-size:13px;">پیشنهادات:</span>
                            <a href="index.php">مشاهده همه مقالات</a>
                            <?php foreach (array_slice($categories, 0, 3) as $cat): ?>
                                <a href="category.php?slug=<?php echo $cat['slug']; ?>"><?php echo $cat['name']; ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>عبارت جستجو را وارد کنید</h3>
                    <p>لطفاً یک عبارت برای جستجو در مقالات وارد کنید.</p>
                    <div class="suggestions">
                        <a href="index.php">مشاهده همه مقالات</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- SIDEBAR -->
        <div class="results-sidebar">
            <!-- Popular Articles -->
            <div class="sidebar-widget">
                <div class="widget-title"><i class="fas fa-fire"></i> محبوب‌ترین‌ها</div>
                <?php foreach ($popular_articles as $p): ?>
                    <div class="widget-item" onclick="location.href='article.php?slug=<?php echo $p['slug']; ?>'">
                        <?php if ($p['image']): ?>
                            <img src="uploads/articles/<?php echo $p['image']; ?>" alt="<?php echo $p['title']; ?>" />
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=100&h=100&fit=crop" alt="<?php echo $p['title']; ?>" />
                        <?php endif; ?>
                        <div class="info">
                            <h4><?php echo truncateText($p['title'], 30); ?></h4>
                            <div class="views"><i class="fas fa-eye"></i> <?php echo number_format($p['views']); ?></div>
                            <div class="date"><i class="fas fa-calendar"></i> <?php echo getPersianDate(strtotime($p['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Articles -->
            <div class="sidebar-widget">
                <div class="widget-title"><i class="fas fa-clock"></i> جدیدترین‌ها</div>
                <?php foreach ($recent_articles as $r): ?>
                    <div class="widget-item" onclick="location.href='article.php?slug=<?php echo $r['slug']; ?>'">
                        <?php if ($r['image']): ?>
                            <img src="uploads/articles/<?php echo $r['image']; ?>" alt="<?php echo $r['title']; ?>" />
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=100&h=100&fit=crop" alt="<?php echo $r['title']; ?>" />
                        <?php endif; ?>
                        <div class="info">
                            <h4><?php echo truncateText($r['title'], 30); ?></h4>
                            <div class="date"><i class="fas fa-calendar"></i> <?php echo getPersianDate(strtotime($r['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="footer-inner">
            <div>
                <h4>📖 درباره مجله</h4>
                <a href="page/about">درباره ما</a>
                <a href="page/contact">تماس با ما</a>
                <a href="page/privacy">قوانین</a>
            </div>
            <div>
                <h4>🛎️ خدمات</h4>
                <a href="#">راهنمای نویسندگان</a>
                <a href="#">پاسخ به سوالات</a>
            </div>
            <div>
                <h4>📞 تماس با ما</h4>
                <a class="contact-phone" href="tel:<?php echo getSetting('footer_phone') ?: '09926008650'; ?>">
                    <i class="fas fa-phone"></i> <?php echo getSetting('footer_phone') ?: '۰۹۹۲۶۰۰۸۶۵۰'; ?>
                </a>
                <a style="margin-top:6px;"><i class="fas fa-envelope"></i> <?php echo getSetting('footer_email') ?: 'info@anosha.com'; ?></a>
            </div>
            <div>
                <h4>🌐 شبکه‌های اجتماعی</h4>
                <?php
                $socials = getSetting('footer_social') ?: 'اینستاگرام, تلگرام, توییتر';
                foreach (explode(',', $socials) as $social):
                ?>
                    <a><i class="fas fa-hashtag"></i> <?php echo trim($social); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="footer-bottom">
            <?php echo getSetting('footer_copyright') ?: '© ۲۰۲۶ مجله آنلاین Anosha - تمامی حقوق محفوظ است'; ?>
        </div>
    </footer>

    <script>
        // ===== THEME TOGGLE =====
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeUI(newTheme);
        }

        function updateThemeUI(theme) {
            const icon = document.getElementById('themeIcon');
            const label = document.getElementById('themeLabel');
            if (theme === 'dark') {
                icon.textContent = '☀️';
                label.textContent = 'روشن';
            } else {
                icon.textContent = '🌙';
                label.textContent = 'تیره';
            }
        }

        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeUI(savedTheme);

        // ===== HIGHLIGHT SEARCH TERM IN RESULTS =====
        // (Already handled server-side with PHP)

        console.log('🔍 صفحه جستجو');
        console.log('📝 عبارت جستجو: <?php echo htmlspecialchars($query); ?>');
        console.log('📊 تعداد نتایج: <?php echo $total_results; ?>');
    </script>
</body>
</html>