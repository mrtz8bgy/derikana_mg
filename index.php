<?php
// index.php — صفحه اصلی مجله دریکانا
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = (int)getSetting('posts_per_page') ?: 9;
$offset = ($page - 1) * $limit;

$total_articles = getTotalArticles();
$total_pages = ceil($total_articles / $limit);
$articles = getArticles($limit, $offset);

$banners = getBanners('slider');

$stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll();

$site_title = getSetting('site_title') ?: 'مجله دریکانا';
$site_description = getSetting('site_description') ?: 'جدیدترین مقالات و دانستنی‌ها درباره طلا، جواهرات و اشیای قیمتی';

$popular_articles = $pdo->query("SELECT id, title, slug, image, views, created_at, author_id FROM articles WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();
$recent_articles = $pdo->query("SELECT id, title, slug, image, created_at, author_id FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$top_authors = $pdo->query("SELECT u.id, u.name, COUNT(a.id) as article_count, u.avatar
                            FROM users u
                            LEFT JOIN articles a ON u.id = a.author_id AND a.status = 'published'
                            GROUP BY u.id
                            ORDER BY article_count DESC
                            LIMIT 6")->fetchAll();

$today = jdate('l j F Y');
$today_qamari = jdate('l j F Y', null, true);

include_once 'includes/header.php';

$img = function ($article, $w = 640, $h = 400) {
    if (!empty($article['image']) && file_exists(__DIR__ . '/uploads/articles/' . $article['image'])) {
        return 'uploads/articles/' . $article['image'];
    }
    return "https://images.unsplash.com/photo-1499750310107-5fef28a66643?w={$w}&h={$h}&fit=crop";
};
?>

<!-- ===== HERO MASTHEAD ===== -->
<section class="hero-band container">
    <div class="hero-card">
        <div>
            <span class="hero-kicker">مجله‌ی تخصصی طلا، جواهر و سبک زندگی</span>
            <h1><?php echo htmlspecialchars($site_title); ?>؛ <em>روایت‌گر درخشش</em> و دانستنی‌های اصیل</h1>
            <p class="hero-lead"><?php echo htmlspecialchars($site_description); ?></p>
        </div>
        <div class="hero-stats">
            <div class="stat">
                <div class="stat-value counter" data-target="<?php echo (int)$total_articles; ?>">0</div>
                <div class="stat-label">مقاله منتشرشده</div>
            </div>
            <div class="stat">
                <div class="stat-value counter" data-target="<?php echo count($categories); ?>">0</div>
                <div class="stat-label">دسته‌بندی</div>
            </div>
            <div class="stat">
                <div class="stat-value counter" data-target="<?php echo count($top_authors); ?>">0</div>
                <div class="stat-label">نویسنده</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== BANNER SLIDER ===== -->
<?php if (count($banners) > 0): ?>
<section class="banner-slider container" aria-label="بنرهای ویژه">
    <div class="slider-container" id="bannerSlider">
        <?php foreach ($banners as $index => $banner): ?>
            <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                <?php if ($banner['image'] && file_exists(__DIR__ . '/uploads/banners/' . $banner['image'])): ?>
                    <img src="uploads/banners/<?php echo htmlspecialchars($banner['image']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>" />
                <?php else: ?>
                    <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=1440&h=560&fit=crop" alt="<?php echo htmlspecialchars($banner['title']); ?>" />
                <?php endif; ?>
                <div class="slide-overlay"></div>
                <?php if ($banner['title'] || $banner['subtitle']): ?>
                    <div class="slide-content">
                        <span class="banner-tag"><i class="fas fa-gem" aria-hidden="true"></i> ویژه</span>
                        <h2><?php echo htmlspecialchars($banner['title']); ?></h2>
                        <?php if ($banner['subtitle']): ?><p><?php echo htmlspecialchars($banner['subtitle']); ?></p><?php endif; ?>
                        <?php if ($banner['link']): ?>
                            <a href="<?php echo htmlspecialchars($banner['link']); ?>" class="btn btn-link">مشاهده بیشتر <i class="fas fa-arrow-left"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="slider-nav prev" onclick="changeSlide(-1)" aria-label="بنر قبلی"><i class="fas fa-chevron-right"></i></button>
        <button class="slider-nav next" onclick="changeSlide(1)" aria-label="بنر بعدی"><i class="fas fa-chevron-left"></i></button>
        <div class="slider-dots">
            <?php foreach ($banners as $index => $banner): ?>
                <button class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)" aria-label="بنر <?php echo $index + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== MAIN ===== -->
<div class="main-wrapper container">

    <!-- ARTICLES -->
    <section class="articles-section" aria-label="آخرین مقالات">
        <div class="section-header">
            <div class="section-title">
                <span class="kicker">تازه‌های مجله</span>
                <h2><i class="fas fa-feather-pointed"></i> آخرین اخبار و مقالات</h2>
            </div>
            <a href="search.php" class="view-all">مشاهده همه <i class="fas fa-arrow-left"></i></a>
        </div>

        <div class="articles-grid">
            <?php if (count($articles) > 0): ?>
                <?php foreach ($articles as $index => $article): ?>
                    <?php $is_lead = ($index === 0 && $page === 1); ?>
                    <article class="post-card <?php echo $is_lead ? 'lead' : ''; ?>" data-aos="fade-up" data-aos-delay="<?php echo ($index % 4) * 60; ?>">
                        <a class="post-media" href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" tabindex="-1" aria-hidden="true">
                            <img src="<?php echo $img($article); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" loading="<?php echo $index < 2 ? 'eager' : 'lazy'; ?>" />
                            <span class="post-cat"><?php echo htmlspecialchars($article['category_name'] ?? 'بدون دسته'); ?></span>
                            <?php if ($is_lead): ?><span class="lead-flag"><i class="fas fa-star"></i> پرونده ویژه</span><?php endif; ?>
                        </a>
                        <div class="post-body">
                            <div class="post-meta">
                                <span class="author"><i class="fas fa-user-pen"></i><?php echo htmlspecialchars($article['author_name'] ?? 'نامشخص'); ?></span>
                                <span><i class="fas fa-calendar-days"></i><?php echo getPersianDate(strtotime($article['created_at'])); ?></span>
                            </div>
                            <h3 class="post-title">
                                <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>"><?php echo htmlspecialchars($article['title']); ?></a>
                            </h3>
                            <p class="post-excerpt"><?php echo htmlspecialchars(truncateText($article['excerpt'] ?? $article['content'], 150)); ?></p>
                            <div class="post-foot">
                                <span>
                                    <i class="fas fa-eye"></i><?php echo formatViews($article['views']); ?>
                                    <span style="display:inline-block; width:10px;"></span>
                                    <i class="fas fa-comment"></i><?php echo getTotalComments($article['id']); ?>
                                </span>
                                <a class="read-more" href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                                    ادامه مطلب <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column:1/-1;">
                    <i class="fas fa-feather" aria-hidden="true"></i>
                    <h3>هنوز مقاله‌ای منتشر نشده است</h3>
                    <p>به‌زودی نخستین نوشته‌های مجله در این صفحه منتشر می‌شود.</p>
                    <a href="index.php">بازگشت به صفحه اصلی</a>
                </div>
            <?php endif; ?>
        </div>

        <?php echo generatePagination($page, $total_pages, '?'); ?>
    </section>

    <!-- SIDEBAR -->
    <aside class="main-sidebar" aria-label="ستون کناری">

        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-calendar-days"></i> تقویم امروز</div>
            <div class="calendar-widget">
                <div class="today-date"><?php echo $today; ?></div>
                <div class="today-qamari"><i class="fas fa-moon"></i> <?php echo $today_qamari; ?> (قمری)</div>
            </div>
        </div>

        <?php if (count($top_authors) > 0): ?>
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-users"></i> نویسندگان برتر</div>
            <div class="authors-grid">
                <?php foreach (array_slice($top_authors, 0, 6) as $author): ?>
                    <a class="author-item" href="author.php?id=<?php echo (int)$author['id']; ?>">
                        <span class="avatar"><?php echo htmlspecialchars(mb_substr($author['name'], 0, 1)); ?></span>
                        <span class="name"><?php echo htmlspecialchars(truncateText($author['name'], 14)); ?></span>
                        <span class="count"><?php echo (int)$author['article_count']; ?> مقاله</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-fire-flame-curved"></i> محبوب‌ترین‌ها</div>
            <?php foreach ($popular_articles as $index => $p): ?>
                <a class="widget-item" href="article.php?slug=<?php echo htmlspecialchars($p['slug']); ?>">
                    <span class="item-number"><?php echo $index + 1; ?></span>
                    <img src="<?php echo $img($p, 120, 120); ?>" alt="" loading="lazy" />
                    <div class="info">
                        <h4><?php echo htmlspecialchars(truncateText($p['title'], 34)); ?></h4>
                        <div class="views"><i class="fas fa-eye"></i> <?php echo number_format($p['views']); ?></div>
                        <div class="date"><i class="fas fa-calendar-days"></i> <?php echo getPersianDate(strtotime($p['created_at'])); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-clock"></i> جدیدترین‌ها</div>
            <?php foreach ($recent_articles as $r): ?>
                <a class="widget-item" href="article.php?slug=<?php echo htmlspecialchars($r['slug']); ?>">
                    <img src="<?php echo $img($r, 120, 120); ?>" alt="" loading="lazy" />
                    <div class="info">
                        <h4><?php echo htmlspecialchars(truncateText($r['title'], 34)); ?></h4>
                        <div class="date"><i class="fas fa-calendar-days"></i> <?php echo getPersianDate(strtotime($r['created_at'])); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-palette"></i> رنگ accent</div>
            <div class="color-picker-widget">
                <div class="color-option active" data-color="#D6B36A" style="background:#D6B36A;" onclick="changeThemeColor('#D6B36A','#A9853E', this)" title="طلایی"></div>
                <div class="color-option" data-color="#C0564A" style="background:#C0564A;" onclick="changeThemeColor('#C0564A','#8E3A31', this)" title="شرابی"></div>
                <div class="color-option" data-color="#4E8D7C" style="background:#4E8D7C;" onclick="changeThemeColor('#4E8D7C','#35655A', this)" title="مالاکیت"></div>
                <div class="color-option" data-color="#5B7DB1" style="background:#5B7DB1;" onclick="changeThemeColor('#5B7DB1','#3E5B84', this)" title="لاجوردی"></div>
                <div class="color-option" data-color="#8A6BAE" style="background:#8A6BAE;" onclick="changeThemeColor('#8A6BAE','#63497F', this)" title="بنفش"></div>
                <div class="color-option" data-color="#B0713F" style="background:#B0713F;" onclick="changeThemeColor('#B0713F','#7E4E28', this)" title="مسی"></div>
            </div>
        </div>
    </aside>
</div>

<?php
include_once 'includes/footer.php';
