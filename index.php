<?php
// index.php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = (int)getSetting('posts_per_page') ?: 9;
$offset = ($page - 1) * $limit;

$total_articles = getTotalArticles();
$total_pages = ceil($total_articles / $limit);
$articles = getArticles($limit, $offset);

$featured_articles = getArticles(4, 0);
$banners = getBanners('slider');

// ===== دریافت فقط دسته‌بندی‌های فعال =====
$stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll();

$site_title = getSetting('site_title') ?: 'مجله دریکانا';
$site_description = getSetting('site_description') ?: 'جدیدترین مقالات و دانستنی‌ها درباره طلا، جواهرات و اشیای قیمتی';

$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($base_path == '') {
    $base_path = '';
}

$popular_articles = $pdo->query("SELECT id, title, slug, image, views, created_at, author_id FROM articles WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();
$recent_articles = $pdo->query("SELECT id, title, slug, image, created_at, author_id FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$top_authors = $pdo->query("SELECT u.id, u.name, COUNT(a.id) as article_count, u.avatar 
                            FROM users u 
                            LEFT JOIN articles a ON u.id = a.author_id AND a.status = 'published' 
                            GROUP BY u.id 
                            ORDER BY article_count DESC 
                            LIMIT 6")->fetchAll();

// Get today's date for Persian calendar widget
$today = jdate('l j F Y');
$today_qamari = jdate('l j F Y', null, true);

// ===== هدر رو فراخوانی میکنیم =====
include_once 'includes/header.php';
?>

<!-- ===== BANNER SLIDER ===== -->
<?php if (count($banners) > 0): ?>
<section class="banner-slider">
    <div class="slider-container" id="bannerSlider">
        <?php foreach ($banners as $index => $banner): ?>
            <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                <?php if ($banner['image']): ?>
                    <img src="uploads/banners/<?php echo $banner['image']; ?>" alt="<?php echo $banner['title']; ?>" loading="lazy" />
                <?php else: ?>
                    <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=1440&h=560&fit=crop" alt="<?php echo $banner['title']; ?>" loading="lazy" />
                <?php endif; ?>
                <div class="slide-overlay"></div>
                <?php if ($banner['title'] || $banner['subtitle']): ?>
                    <div class="slide-content">
                        <span class="banner-tag">✦ ویژه</span>
                        <h2><?php echo $banner['title']; ?></h2>
                        <?php if ($banner['subtitle']): ?><p><?php echo $banner['subtitle']; ?></p><?php endif; ?>
                        <?php if ($banner['link']): ?>
                            <a href="<?php echo $banner['link']; ?>" class="btn-link">مشاهده بیشتر <i class="fas fa-arrow-left"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="slider-nav prev" onclick="changeSlide(-1)">❮</button>
        <button class="slider-nav next" onclick="changeSlide(1)">❯</button>
        <div class="slider-dots">
            <?php foreach ($banners as $index => $banner): ?>
                <button class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== HERO ===== -->
<section class="hero">
    <div class="hero-content">
        <h1>📰 <?php echo $site_title; ?></h1>
        <div class="typewriter-text" id="typewriterText"><?php echo $site_description; ?></div>
        <div class="hero-stats">
            <div class="stat">
                <div class="number counter" data-target="<?php echo $total_articles; ?>">0</div>
                <div class="label">مقاله</div>
            </div>
            <div class="stat">
                <div class="number counter" data-target="<?php echo count($categories); ?>">0</div>
                <div class="label">دسته‌بندی</div>
            </div>
            <div class="stat">
                <div class="number counter" data-target="<?php echo count($top_authors); ?>">0</div>
                <div class="label">نویسنده</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== MAIN ===== -->
<div class="main-wrapper">

    <!-- ARTICLES -->
    <section class="articles-section">
        <div class="section-header">
            <h2><i class="fas fa-newspaper"></i> آخرین اخبار و مقالات</h2>
            <a href="search.php" class="view-all">مشاهده همه <i class="fas fa-arrow-left"></i></a>
        </div>
        <div class="articles-list">
            <?php if (count($articles) > 0): ?>
                <?php foreach ($articles as $index => $article): ?>
                    <div class="article-item" 
                         onclick="window.location.href='article.php?slug=<?php echo $article['slug']; ?>'"
                         data-aos="fade-up"
                         data-aos-delay="<?php echo ($index % 5) * 50; ?>"
                         data-aos-duration="600">
                        <div class="item-image">
                            <?php if ($article['image']): ?>
                                <img src="uploads/articles/<?php echo $article['image']; ?>" alt="<?php echo $article['title']; ?>" loading="lazy" />
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=400&h=250&fit=crop" alt="<?php echo $article['title']; ?>" loading="lazy" />
                            <?php endif; ?>
                        </div>
                        <div class="item-content">
                            <div class="meta">
                                <span class="category"><?php echo $article['category_name'] ?? 'بدون دسته'; ?></span>
                                <span><i class="fas fa-user"></i> <span class="author-name"><?php echo $article['author_name'] ?? 'نامشخص'; ?></span></span>
                                <span><i class="fas fa-calendar"></i> <?php echo getPersianDate(strtotime($article['created_at'])); ?></span>
                            </div>
                            <h3><?php echo $article['title']; ?></h3>
                            <p class="excerpt"><?php echo truncateText($article['excerpt'] ?? $article['content'], 120); ?></p>
                            <div class="footer">
                                <div class="meta">
                                    <span><i class="fas fa-eye"></i> <?php echo formatViews($article['views']); ?></span>
                                    <span><i class="fas fa-comment"></i> <?php echo getTotalComments($article['id']); ?></span>
                                </div>
                                <span class="read-time"><i class="fas fa-clock"></i> <?php echo ceil(strlen($article['content'] ?? '') / 1200) ?: 1; ?> دقیقه</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:var(--text-light);">
                    <i class="fas fa-newspaper" style="font-size:48px; display:block; margin-bottom:16px; opacity:0.15;"></i>
                    هیچ مقاله‌ای یافت نشد
                </div>
            <?php endif; ?>
        </div>
        <?php echo generatePagination($page, $total_pages, '?'); ?>
    </section>

    <!-- SIDEBAR -->
    <aside class="main-sidebar">
        <!-- Persian Calendar Widget -->
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-calendar-alt"></i> تقویم امروز</div>
            <div class="calendar-widget">
                <div class="today-date"><?php echo $today; ?></div>
                <div class="today-qamari"><?php echo $today_qamari; ?> (قمری)</div>
            </div>
        </div>

        <!-- Top Authors -->
        <?php if (count($top_authors) > 0): ?>
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-users"></i> نویسندگان برتر</div>
            <div class="authors-grid">
                <?php foreach (array_slice($top_authors, 0, 6) as $author): ?>
                    <div class="author-item" onclick="window.location.href='author.php?id=<?php echo $author['id']; ?>'">
                        <div class="avatar"><?php echo mb_substr($author['name'], 0, 1); ?></div>
                        <div class="name"><?php echo truncateText($author['name'], 12); ?></div>
                        <div class="count"><?php echo $author['article_count']; ?> مقاله</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Popular -->
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-fire"></i> محبوب‌ترین‌ها</div>
            <?php foreach ($popular_articles as $index => $p): ?>
                <div class="widget-item" onclick="window.location.href='article.php?slug=<?php echo $p['slug']; ?>'">
                    <span class="item-number"><?php echo $index + 1; ?></span>
                    <?php if ($p['image']): ?>
                        <img src="uploads/articles/<?php echo $p['image']; ?>" alt="<?php echo $p['title']; ?>" loading="lazy" />
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=100&h=100&fit=crop" alt="<?php echo $p['title']; ?>" loading="lazy" />
                    <?php endif; ?>
                    <div class="info">
                        <h4><?php echo truncateText($p['title'], 30); ?></h4>
                        <div class="views"><i class="fas fa-eye"></i> <?php echo number_format($p['views']); ?></div>
                        <div class="date"><i class="fas fa-calendar"></i> <?php echo getPersianDate(strtotime($p['created_at'])); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent -->
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-clock"></i> جدیدترین‌ها</div>
            <?php foreach ($recent_articles as $r): ?>
                <div class="widget-item" onclick="window.location.href='article.php?slug=<?php echo $r['slug']; ?>'">
                    <?php if ($r['image']): ?>
                        <img src="uploads/articles/<?php echo $r['image']; ?>" alt="<?php echo $r['title']; ?>" loading="lazy" />
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=100&h=100&fit=crop" alt="<?php echo $r['title']; ?>" loading="lazy" />
                    <?php endif; ?>
                    <div class="info">
                        <h4><?php echo truncateText($r['title'], 30); ?></h4>
                        <div class="date"><i class="fas fa-calendar"></i> <?php echo getPersianDate(strtotime($r['created_at'])); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Color Picker -->
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-palette"></i> انتخاب رنگ تم</div>
            <div class="color-picker-widget">
                <div class="color-option active" style="background: #C9A84C;" onclick="changeThemeColor('#C9A84C', '#A8892A', this)"></div>
                <div class="color-option" style="background: #E74C3C;" onclick="changeThemeColor('#E74C3C', '#C0392B', this)"></div>
                <div class="color-option" style="background: #3498DB;" onclick="changeThemeColor('#3498DB', '#2980B9', this)"></div>
                <div class="color-option" style="background: #2ECC71;" onclick="changeThemeColor('#2ECC71', '#27AE60', this)"></div>
                <div class="color-option" style="background: #9B59B6;" onclick="changeThemeColor('#9B59B6', '#8E44AD', this)"></div>
                <div class="color-option" style="background: #F39C12;" onclick="changeThemeColor('#F39C12', '#E67E22', this)"></div>
            </div>
        </div>
    </aside>
</div>

<?php
// ===== فوتر رو فراخوانی میکنیم =====
include_once 'includes/footer.php';
echo "test";
?>