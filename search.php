<?php
// search.php — جست‌وجو در مقالات
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$query = isset($_GET['q']) ? cleanInput($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = (int)getSetting('posts_per_page') ?: 9;
$offset = ($page - 1) * $limit;

$categories = getCategories();
$site_title = 'نتایج جستجو: ' . $query . ' | ' . (getSetting('site_title') ?: 'مجله دریکانا');
$site_description = 'نتایج جستجو برای عبارت "' . $query . '"';

$results = [];
$total_results = 0;
$total_pages = 0;

if (!empty($query)) {
    $total_results = getTotalArticles(null, $query);
    $total_pages = ceil($total_results / $limit);
    $results = getArticles($limit, $offset, null, $query);
}

$popular_articles = $pdo->query("SELECT id, title, slug, image, views, created_at FROM articles WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();
$recent_articles = $pdo->query("SELECT id, title, slug, image, created_at FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();

include_once 'includes/header.php';

$img = function ($article) {
    if (!empty($article['image']) && file_exists(__DIR__ . '/uploads/articles/' . $article['image'])) {
        return 'uploads/articles/' . $article['image'];
    }
    return 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=120&h=120&fit=crop';
};

$highlight = function ($text) use ($query) {
    $safe = htmlspecialchars($text);
    if ($query === '') return $safe;
    return preg_replace('/(' . preg_quote(htmlspecialchars($query), '/') . ')/iu', '<span class="highlight">$1</span>', $safe);
};
?>

<!-- ===== PAGE HEAD ===== -->
<section class="container">
    <div class="page-head">
        <div class="head-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></div>
        <span class="kicker">جست‌وجو در آرشیو مجله</span>
        <h1><?php echo $query !== '' ? 'نتایج برای: «' . htmlspecialchars($query) . '»' : 'در مجله چه می‌جویید؟'; ?></h1>
        <?php if ($query !== ''): ?>
            <p class="head-desc">
                <?php if ($total_results > 0): ?>
                    <?php echo number_format($total_results); ?> مقاله مطابق جست‌وجوی شما پیدا شد.
                <?php else: ?>
                    مقاله‌ای مطابق این عبارت پیدا نشد؛ شاید با عبارت کوتاه‌تر یا دسته‌بندی‌ها بهتر به نتیجه برسید.
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <div class="search-again">
            <form action="search.php" method="GET" role="search">
                <input type="search" name="q" placeholder="عبارت جدید…" value="<?php echo htmlspecialchars($query); ?>" aria-label="عبارت جست‌وجو" />
                <button type="submit" class="btn"><i class="fas fa-magnifying-glass"></i> جست‌وجو</button>
            </form>
        </div>
    </div>
</section>

<!-- ===== RESULTS ===== -->
<div class="search-results container">
    <div class="results-main">
        <?php if ($query !== ''): ?>
            <?php if (count($results) > 0): ?>
                <?php foreach ($results as $article): ?>
                    <a class="result-item" href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                        <div class="result-meta">
                            <span class="category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($article['category_name'] ?? 'بدون دسته'); ?></span>
                            <span><i class="fas fa-user-pen"></i> <?php echo htmlspecialchars($article['author_name'] ?? 'نامشخص'); ?></span>
                            <span><i class="fas fa-calendar-days"></i> <?php echo getPersianDate(strtotime($article['created_at'])); ?></span>
                        </div>
                        <div class="result-title"><?php echo $highlight($article['title']); ?></div>
                        <div class="result-excerpt"><?php echo $highlight($article['excerpt'] ?? truncateText($article['content'], 150)); ?></div>
                        <div class="result-footer">
                            <span>مشاهده مقاله <i class="fas fa-arrow-left"></i></span>
                            <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>" aria-label="صفحه قبل"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php elseif ($i == 1 || $i == $total_pages || abs($i - $page) <= 2): ?>
                                <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="disabled">…</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>" aria-label="صفحه بعد"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-magnifying-glass-minus" aria-hidden="true"></i>
                    <h3>نتیجه‌ای یافت نشد</h3>
                    <p>هیچ مقاله‌ای با عبارت «<?php echo htmlspecialchars($query); ?>» پیدا نشد. این پیشنهادها را امتحان کنید:</p>
                    <div class="suggestions">
                        <a href="index.php">مشاهده همه مقالات</a>
                        <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
                            <a href="category.php?slug=<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-keyboard" aria-hidden="true"></i>
                <h3>عبارت جست‌وجو را وارد کنید</h3>
                <p>در میان عنوان، متن و خلاصه‌ی همه‌ی مقالات مجله جست‌وجو کنید.</p>
                <div class="suggestions">
                    <a href="index.php">مشاهده همه مقالات</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- SIDEBAR -->
    <aside class="results-sidebar" aria-label="ستون کناری">
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-fire-flame-curved"></i> محبوب‌ترین‌ها</div>
            <?php foreach ($popular_articles as $index => $p): ?>
                <a class="widget-item" href="article.php?slug=<?php echo htmlspecialchars($p['slug']); ?>">
                    <span class="item-number"><?php echo $index + 1; ?></span>
                    <img src="<?php echo $img($p); ?>" alt="" loading="lazy" />
                    <div class="info">
                        <h4><?php echo htmlspecialchars(truncateText($p['title'], 32)); ?></h4>
                        <div class="views"><i class="fas fa-eye"></i> <?php echo number_format($p['views']); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-clock"></i> جدیدترین‌ها</div>
            <?php foreach ($recent_articles as $r): ?>
                <a class="widget-item" href="article.php?slug=<?php echo htmlspecialchars($r['slug']); ?>">
                    <img src="<?php echo $img($r); ?>" alt="" loading="lazy" />
                    <div class="info">
                        <h4><?php echo htmlspecialchars(truncateText($r['title'], 32)); ?></h4>
                        <div class="date"><i class="fas fa-calendar-days"></i> <?php echo getPersianDate(strtotime($r['created_at'])); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>
</div>

<?php include_once 'includes/footer.php'; ?>
