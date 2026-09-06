<?php
// admin/partials/sidebar.php — ناوبری مشترک پنل مدیریت
// نیازمند: $pdo ؛ صفحه فعال با $current_page مشخص می‌شود.
$current_page = isset($current_page) ? $current_page : basename($_SERVER['PHP_SELF']);
$__cnt = ['articles' => 0, 'categories' => 0, 'banners' => 0, 'requests' => 0, 'users' => 0, 'comments' => 0];
try {
    $__cnt['articles']   = (int) $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    $__cnt['categories'] = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $__cnt['banners']    = (int) $pdo->query("SELECT COUNT(*) FROM banners")->fetchColumn();
    $__cnt['requests']   = (int) $pdo->query("SELECT COUNT(*) FROM article_requests WHERE status = 'pending'")->fetchColumn();
    $__cnt['users']      = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $__cnt['comments']   = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
} catch (Throwable $e) {
    // جدول‌ها در دسترس نیستند؛ شمارنده‌ها صفر می‌مانند
}
$__nav = [
    ['section' => 'داشبورد', 'icon' => 'fa-chart-pie'],
    ['href' => 'index.php', 'icon' => 'fa-chart-pie', 'label' => 'نمای کلی'],
    ['divider' => true],
    ['section' => 'مدیریت محتوا', 'icon' => 'fa-newspaper'],
    ['href' => 'articles.php', 'icon' => 'fa-newspaper', 'label' => 'مقالات', 'count' => 'articles'],
    ['href' => 'categories.php', 'icon' => 'fa-tags', 'label' => 'دسته‌بندی‌ها', 'count' => 'categories'],
    ['href' => 'banners.php', 'icon' => 'fa-images', 'label' => 'بنرها', 'count' => 'banners'],
    ['href' => 'requests.php', 'icon' => 'fa-file-lines', 'label' => 'درخواست‌ها', 'count' => 'requests'],
    ['divider' => true],
    ['section' => 'کاربران', 'icon' => 'fa-users'],
    ['href' => 'users.php', 'icon' => 'fa-users', 'label' => 'کاربران', 'count' => 'users'],
    ['href' => 'comments.php', 'icon' => 'fa-comments', 'label' => 'نظرات', 'count' => 'comments'],
    ['divider' => true],
    ['section' => 'تنظیمات', 'icon' => 'fa-gear'],
    ['href' => 'settings.php', 'icon' => 'fa-gear', 'label' => 'تنظیمات'],
];
?>
<nav class="admin-sidebar" id="adminSidebar">
    <?php foreach ($__nav as $__item): ?>
        <?php if (!empty($__item['divider'])): ?>
            <hr class="menu-divider" />
        <?php elseif (isset($__item['section'])): ?>
            <div class="sidebar-title"><i class="fas <?php echo $__item['icon']; ?>"></i> <?php echo $__item['section']; ?></div>
        <?php else: ?>
            <a href="<?php echo $__item['href']; ?>" class="menu-item<?php echo $current_page === $__item['href'] ? ' active' : ''; ?>">
                <i class="fas <?php echo $__item['icon']; ?>"></i> <?php echo $__item['label']; ?>
                <?php if (isset($__item['count'])): ?><span class="count"><?php echo $__cnt[$__item['count']]; ?></span><?php endif; ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
