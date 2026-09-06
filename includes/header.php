<?php
// header.php - هدر با مگا منو برای دسته‌بندی‌ها
if (!isset($categories)) {
    // دریافت دسته‌بندی‌ها با زیرمجموعه‌ها
    function getCategoriesTreeNav($pdo, $parent_id = null, $level = 0) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE active = 1 AND parent_id " . ($parent_id === null ? "IS NULL" : "= ?") . " ORDER BY sort_order ASC, name ASC");
        if ($parent_id !== null) {
            $stmt->execute([$parent_id]);
        } else {
            $stmt->execute();
        }
        $result = [];
        while ($row = $stmt->fetch()) {
            $row['level'] = $level;
            $row['children'] = getCategoriesTreeNav($pdo, $row['id'], $level + 1);
            $result[] = $row;
        }
        return $result;
    }
    $categories_tree = getCategoriesTreeNav($pdo);
} else {
    // اگر قبلاً categories تعریف شده، تبدیل به درخت
    $categories_tree = [];
    foreach ($categories as $cat) {
        if ($cat['parent_id'] === null || $cat['parent_id'] == 0) {
            $cat['children'] = [];
            foreach ($categories as $sub) {
                if ($sub['parent_id'] == $cat['id']) {
                    $cat['children'][] = $sub;
                }
            }
            $categories_tree[] = $cat;
        }
    }
}

$site_title = getSetting('site_title') ?: 'مجله دریکانا';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo getSetting('site_description') ?: 'جدیدترین مقالات و دانستنی‌ها درباره طلا، جواهرات و اشیای قیمتی'; ?>" />
    
    <!-- ===== SEO ===== -->
    <meta property="og:title" content="<?php echo $site_title; ?>" />
    <meta property="og:description" content="<?php echo getSetting('site_description') ?: 'جدیدترین مقالات و دانستنی‌ها درباره طلا، جواهرات و اشیای قیمتی'; ?>" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    
    <!-- ===== Fonts & Icons ===== -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&family=Great+Vibes&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    
    <!-- ===== Main Style ===== -->
    <link rel="stylesheet" href="assets/css/style.css" />
    
    <style>
        /* استایل‌های اضافه مخصوص صفحه (در صورت نیاز) */
        .back-to-top {
            position: fixed;
            bottom: 24px;
            left: 24px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gold-gradient);
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 20px;
            box-shadow: var(--shadow-lg);
            transition: all var(--transition);
            opacity: 0;
            transform: translateY(20px);
            z-index: 999;
        }
        .back-to-top.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .back-to-top:hover {
            transform: scale(1.1);
        }
        #readingProgress {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: var(--gold-gradient);
            width: 0%;
            z-index: 9999;
            transition: width 0.1s linear;
        }
        #readingPercent {
            position: fixed;
            bottom: 80px;
            left: 24px;
            background: var(--bg-card);
            color: var(--gold);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            opacity: 0;
            transition: all var(--transition);
            z-index: 999;
            backdrop-filter: blur(8px);
        }
        #readingPercent.visible {
            opacity: 1;
        }
        .brand-fa {
            font-family: 'Great Vibes', cursive;
            font-weight: 700;
            font-size: 22px;
            color: var(--text-secondary);
            opacity: 0.85;
        }
    </style>
</head>
<body>

<!-- ===== READING PROGRESS BAR ===== -->
<div id="readingProgress"></div>
<div id="readingPercent">0%</div>

<!-- ===== HEADER ===== -->
<header class="header" id="mainHeader">
    <div class="header-inner">
        <div class="logo" onclick="window.location.href='index.php'">
            <span class="brand-en">rahmani</span>
            <span class="brand-fa">مجله دریکانا</span>
        </div>
        
        <div class="search-box">
            <form action="search.php" method="GET" style="display:flex; flex:1;">
                <input type="text" name="q" placeholder="جستجوی مقاله..." />
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()">
                <span id="themeIcon">🌙</span>
                <span id="themeLabel">تیره</span>
            </button>
            
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="admin/" class="header-btn primary-btn">
                        <i class="fas fa-user-shield"></i> مدیریت
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="header-btn">
                    <i class="fas fa-sign-out-alt"></i> خروج
                </a>
            <?php else: ?>
                <a href="login.php" class="header-btn">ورود</a>
                <a href="register.php" class="header-btn primary-btn">ثبت‌نام</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ===== MOBILE MENU ===== -->
<div class="mobile-menu" id="mobileMenu">
    <button class="close-menu" onclick="toggleMobileMenu()">✕</button>
    <h3 style="color: var(--gold); margin-bottom: 16px;">📚 دسته‌بندی‌ها</h3>
    <div class="mobile-categories">
        <a href="index.php" <?php echo !isset($_GET['category']) ? 'class="active"' : ''; ?>>🔥 همه</a>
        <?php 
        function renderMobileCategories($items, $pdo) {
            foreach ($items as $cat):
                $link_target = (isset($cat['link_target']) && $cat['link_target'] == 1) ? 'target="_blank"' : '';
                if (isset($cat['link_type']) && $cat['link_type'] == 'external' && !empty($cat['external_link'])): ?>
                    <a href="<?php echo $cat['external_link']; ?>" <?php echo $link_target; ?>>
                        <i class="fas <?php echo $cat['icon'] ?? 'fa-tag'; ?>"></i>
                        <?php echo $cat['name']; ?>
                    </a>
                <?php else: ?>
                    <a href="category.php?slug=<?php echo $cat['slug']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['slug']) ? 'class="active"' : ''; ?>>
                        <i class="fas <?php echo $cat['icon'] ?? 'fa-tag'; ?>"></i>
                        <?php echo $cat['name']; ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($cat['children'])): ?>
                    <div style="padding-right:20px; border-right:2px solid var(--border); margin-right:8px;">
                        <?php renderMobileCategories($cat['children'], $pdo); ?>
                    </div>
                <?php endif; ?>
            <?php endforeach;
        }
        renderMobileCategories($categories_tree, $pdo);
        ?>
    </div>
    <div class="mobile-actions">
        <?php if (isLoggedIn()): ?>
            <a href="logout.php" class="header-btn" style="justify-content:center;">خروج</a>
        <?php else: ?>
            <a href="login.php" class="header-btn" style="justify-content:center;">ورود</a>
            <a href="register.php" class="header-btn primary-btn" style="justify-content:center;">ثبت‌نام</a>
        <?php endif; ?>
    </div>
</div>

<!-- ===== CATEGORIES NAV - MEGA MENU ===== -->
<nav class="categories-nav" id="categoriesNav">
    <div class="categories-inner">
        <a href="index.php" <?php echo !isset($_GET['category']) ? 'class="active"' : ''; ?>>🔥 همه</a>
        <?php 
        function renderMegaMenu($items, $pdo, $level = 0) {
            foreach ($items as $cat):
                $has_children = !empty($cat['children']);
                $link_target = (isset($cat['link_target']) && $cat['link_target'] == 1) ? 'target="_blank"' : '';
                $link_type = isset($cat['link_type']) ? $cat['link_type'] : 'internal';
                $is_active = (isset($_GET['category']) && $_GET['category'] == $cat['slug']) ? 'active' : '';
                $icon = isset($cat['icon']) ? '<i class="fas ' . $cat['icon'] . '"></i>' : '';
                
                if ($has_children): ?>
                    <a href="#" class="has-mega <?php echo $is_active; ?>">
                        <?php echo $icon; ?> <?php echo $cat['name']; ?>
                        <span class="arrow"><i class="fas fa-chevron-down"></i></span>
                        <div class="mega-dropdown">
                            <?php foreach ($cat['children'] as $child): 
                                $child_target = (isset($child['link_target']) && $child['link_target'] == 1) ? 'target="_blank"' : '';
                                $child_type = isset($child['link_type']) ? $child['link_type'] : 'internal';
                                $child_icon = isset($child['icon']) ? '<i class="fas ' . $child['icon'] . '"></i>' : '';
                                if ($child_type == 'external' && !empty($child['external_link'])): ?>
                                    <a href="<?php echo $child['external_link']; ?>" <?php echo $child_target; ?> class="mega-item">
                                        <?php echo $child_icon; ?> <?php echo $child['name']; ?>
                                    </a>
                                <?php else: ?>
                                    <a href="category.php?slug=<?php echo $child['slug']; ?>" class="mega-item">
                                        <?php echo $child_icon; ?> <?php echo $child['name']; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </a>
                <?php elseif ($link_type == 'external' && !empty($cat['external_link'])): ?>
                    <a href="<?php echo $cat['external_link']; ?>" <?php echo $link_target; ?>>
                        <?php echo $icon; ?> <?php echo $cat['name']; ?>
                    </a>
                <?php else: ?>
                    <a href="category.php?slug=<?php echo $cat['slug']; ?>" class="<?php echo $is_active; ?>">
                        <?php echo $icon; ?> <?php echo $cat['name']; ?>
                    </a>
                <?php endif;
            endforeach;
        }
        renderMegaMenu($categories_tree, $pdo);
        ?>
    </div>
</nav>