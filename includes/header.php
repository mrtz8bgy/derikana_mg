<?php
// header.php — هدر یکپارچه‌ی دریکانا (منوی مگا + درایور موبایل)
if (!isset($categories)) {
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
$site_description = getSetting('site_description') ?: 'جدیدترین مقالات و دانستنی‌ها درباره طلا، جواهرات و اشیای قیمتی';
$current_cat = $_GET['slug'] ?? $_GET['category'] ?? null;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site_description); ?>" />
    <meta name="theme-color" content="#0B0D12" />

    <!-- ===== SEO ===== -->
    <meta property="og:title" content="<?php echo htmlspecialchars($site_title); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($site_description); ?>" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />

    <!-- favicon (gem monogram) -->
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23D6B36A' d='M6 3h12l4 6-10 12L2 9l4-6zm1.6 2L5 9h4.2l1.3-4H7.6zm5.9 0L12.2 9h3.9l-1.3-4h-1.3zm4 0L18.8 9H21l-2.6-4h-.9zM4.2 11l6.4 7.7L8.9 11H4.2zm6.6 0l1.2 8.6L13.2 11h-2.4zm4.3 0l-1.7 7.7L19.8 11h-4.7z'/%3E%3C/svg%3E" />

    <!-- ===== Fonts & Icons ===== -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />

    <!-- ===== Styles ===== -->
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/pages.css" />

    <!-- theme boot: no flash of wrong theme -->
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
            } catch (e) {}
        })();
    </script>
</head>
<body>

<a class="skip-link" href="#main">پرش به محتوای اصلی</a>
<canvas id="starfield" aria-hidden="true"></canvas>
<div id="readingProgress" aria-hidden="true"></div>
<div id="readingPercent" aria-hidden="true">0%</div>

<!-- ===== HEADER ===== -->
<header class="site-header" id="mainHeader">
    <div class="header-inner container">

        <a class="brand" href="index.php" aria-label="<?php echo htmlspecialchars($site_title); ?>">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 3h12l4 6-10 12L2 9l4-6zm1.6 2L5 9h4.2l1.3-4H7.6zm5.9 0L12.2 9h3.9l-1.3-4h-1.3zm4 0L18.8 9H21l-2.6-4h-.9zM4.2 11l6.4 7.7L8.9 11H4.2zm6.6 0l1.2 8.6L13.2 11h-2.4zm4.3 0l-1.7 7.7L19.8 11h-4.7z"/></svg>
            </span>
            <span class="brand-text">
                <span class="brand-fa"><?php echo htmlspecialchars($site_title); ?></span>
                <span class="brand-en">Derikana Magazine</span>
            </span>
        </a>

        <form class="search-box" action="search.php" method="GET" role="search">
            <i class="fas fa-magnifying-glass search-icon" aria-hidden="true"></i>
            <input type="search" name="q" placeholder="جست‌وجو در مقالات…" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" aria-label="جست‌وجو" />
            <button type="submit"><i class="fas fa-arrow-left" aria-hidden="true"></i><span>جست‌وجو</span></button>
        </form>

        <div class="header-actions">
            <button class="icon-btn theme-toggle" onclick="toggleTheme()" aria-label="تغییر پوسته روشن/تاریک" title="تغییر پوسته">
                <i class="fas fa-moon i-moon" aria-hidden="true"></i>
                <i class="fas fa-sun i-sun" aria-hidden="true"></i>
            </button>

            <button class="icon-btn mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="منوی دسته‌بندی‌ها" aria-expanded="false">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>

            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="admin/" class="btn-soft"><i class="fas fa-user-shield"></i><span>مدیریت</span></a>
                <?php endif; ?>
                <a href="logout.php" class="btn-ghost"><i class="fas fa-arrow-right-from-bracket"></i><span>خروج</span></a>
            <?php else: ?>
                <a href="login.php" class="btn-ghost"><i class="fas fa-user"></i><span>ورود</span></a>
                <a href="register.php" class="btn"><i class="fas fa-user-plus"></i><span>ثبت‌نام</span></a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== CATEGORY NAV + MEGA MENU ===== -->
    <nav class="cat-nav" aria-label="دسته‌بندی‌ها">
        <ul class="cat-list container">
            <li>
                <a href="index.php" class="<?php echo $current_cat ? '' : 'active'; ?>">
                    <i class="fas fa-fire-flame-curved" aria-hidden="true"></i> تازه‌ها
                </a>
            </li>
            <?php
            function renderMegaMenu($items) {
                foreach ($items as $cat):
                    $has_children = !empty($cat['children']);
                    $link_target = (isset($cat['link_target']) && $cat['link_target'] == 1) ? ' target="_blank" rel="noopener"' : '';
                    $link_type = $cat['link_type'] ?? 'internal';
                    $is_active = ($current_cat && $current_cat == $cat['slug']) ? 'active' : '';
                    $icon = !empty($cat['icon']) ? '<i class="fas ' . htmlspecialchars($cat['icon']) . '" aria-hidden="true"></i>' : '';
                    $href = ($link_type == 'external' && !empty($cat['external_link'])) ? $cat['external_link'] : 'category.php?slug=' . $cat['slug'];

                    if ($has_children): ?>
                        <li class="has-sub">
                            <a href="<?php echo htmlspecialchars($href); ?>"<?php echo $link_target; ?> class="<?php echo $is_active; ?>" aria-haspopup="true">
                                <?php echo $icon; ?> <?php echo htmlspecialchars($cat['name']); ?>
                                <i class="fas fa-chevron-down caret" aria-hidden="true"></i>
                            </a>
                            <div class="mega">
                                <?php foreach ($cat['children'] as $child):
                                    $child_target = (isset($child['link_target']) && $child['link_target'] == 1) ? ' target="_blank" rel="noopener"' : '';
                                    $child_href = (($child['link_type'] ?? 'internal') == 'external' && !empty($child['external_link'])) ? $child['external_link'] : 'category.php?slug=' . $child['slug'];
                                    $child_icon = !empty($child['icon']) ? '<i class="fas ' . htmlspecialchars($child['icon']) . '" aria-hidden="true"></i>' : '<i class="fas fa-angle-left" aria-hidden="true"></i>';
                                    ?>
                                    <a href="<?php echo htmlspecialchars($child_href); ?>"<?php echo $child_target; ?>>
                                        <?php echo $child_icon; ?> <?php echo htmlspecialchars($child['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($href); ?>"<?php echo $link_target; ?> class="<?php echo $is_active; ?>">
                                <?php echo $icon; ?> <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        </li>
                    <?php endif;
                endforeach;
            }
            renderMegaMenu($categories_tree);
            ?>
        </ul>
    </nav>
</header>

<!-- ===== MOBILE DRAWER ===== -->
<div class="mobile-menu" id="mobileMenu" role="dialog" aria-modal="true" aria-label="منوی دسته‌بندی‌ها">
    <div class="drawer-head">
        <span class="drawer-title"><i class="fas fa-gem" aria-hidden="true"></i> دسته‌بندی‌ها</span>
        <button class="icon-btn" onclick="toggleMobileMenu(false)" aria-label="بستن منو">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <div class="mobile-categories">
        <a href="index.php" class="<?php echo $current_cat ? '' : 'active'; ?>">
            <i class="fas fa-fire-flame-curved" aria-hidden="true"></i> تازه‌ها
        </a>
        <?php
        function renderMobileCategories($items) {
            foreach ($items as $cat):
                $link_target = (isset($cat['link_target']) && $cat['link_target'] == 1) ? ' target="_blank" rel="noopener"' : '';
                $href = (($cat['link_type'] ?? 'internal') == 'external' && !empty($cat['external_link'])) ? $cat['external_link'] : 'category.php?slug=' . $cat['slug'];
                $icon = !empty($cat['icon']) ? '<i class="fas ' . htmlspecialchars($cat['icon']) . '" aria-hidden="true"></i>' : '<i class="fas fa-tag" aria-hidden="true"></i>';
                $is_active = ($current_cat && $current_cat == $cat['slug']) ? 'active' : '';
                ?>
                <a href="<?php echo htmlspecialchars($href); ?>"<?php echo $link_target; ?> class="<?php echo $is_active; ?>">
                    <?php echo $icon; ?> <?php echo htmlspecialchars($cat['name']); ?>
                </a>
                <?php if (!empty($cat['children'])): ?>
                    <div class="sub">
                        <?php foreach ($cat['children'] as $child):
                            $child_href = (($child['link_type'] ?? 'internal') == 'external' && !empty($child['external_link'])) ? $child['external_link'] : 'category.php?slug=' . $child['slug'];
                            ?>
                            <a href="<?php echo htmlspecialchars($child_href); ?>"><?php echo htmlspecialchars($child['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif;
            endforeach;
        }
        renderMobileCategories($categories_tree);
        ?>
    </div>
    <div class="mobile-actions">
        <?php if (isLoggedIn()): ?>
            <?php if (isAdmin()): ?>
                <a href="admin/" class="btn-soft" style="justify-content:center;"><i class="fas fa-user-shield"></i> پنل مدیریت</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-ghost" style="justify-content:center;"><i class="fas fa-arrow-right-from-bracket"></i> خروج</a>
        <?php else: ?>
            <a href="login.php" class="btn-ghost" style="justify-content:center;"><i class="fas fa-user"></i> ورود</a>
            <a href="register.php" class="btn" style="justify-content:center;">ثبت‌نام</a>
        <?php endif; ?>
    </div>
</div>

<main id="main">
