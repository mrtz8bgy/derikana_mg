<?php
// article.php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// دریافت اسلاگ مقاله
$slug = isset($_GET['slug']) ? cleanInput($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// دریافت اطلاعات مقاله
$article = getArticleBySlug($slug);

if (!$article) {
    header('Location: index.php');
    exit;
}

// افزایش بازدید
$pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$article['id']]);

// دریافت نظرات تایید شده
$comments = getComments($article['id']);
$total_comments = getTotalComments($article['id']);

// دریافت مقالات مرتبط (همان دسته‌بندی)
$related = getRelatedArticles($article['id'], $article['category_id']);

// دریافت تمام دسته‌بندی‌ها برای منو (برای هدر)
$stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll();

// دریافت بنرها
$banners = getBanners('banner');

// متا تگ‌ها
$site_title = $article['meta_title'] ?: $article['title'] . ' | ' . (getSetting('site_title') ?: 'مجله دریکانا');
$site_description = $article['meta_description'] ?: ($article['excerpt'] ?: truncateText($article['content'], 150));

// پردازش فرم نظر
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $text = cleanInput($_POST['text'] ?? '');
    
    if (!empty($text)) {
        if (isLoggedIn()) {
            $user_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("INSERT INTO comments (article_id, user_id, text, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$article['id'], $user_id, $text]);
        } else {
            $name = cleanInput($_POST['name'] ?? '');
            $email = cleanInput($_POST['email'] ?? '');
            $stmt = $pdo->prepare("INSERT INTO comments (article_id, name, email, text, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$article['id'], $name, $email, $text]);
        }
        
        header("Location: article.php?slug=" . $article['slug'] . "#comments");
        exit;
    }
}

// دریافت مقالات محبوب برای سایدبار
$popular_articles = $pdo->query("SELECT id, title, slug, image, views, created_at FROM articles WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();

// دریافت مقالات اخیر برای سایدبار
$recent_articles = $pdo->query("SELECT id, title, slug, image, created_at FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// ===== هدر رو فراخوانی میکنیم =====
include_once 'includes/header.php';
?>


<!-- ===== ARTICLE ===== -->
<div class="article-wrapper">
    <!-- MAIN CONTENT -->
    <div class="article-main">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">خانه</a>
            <span>›</span>
            <a href="category.php?slug=<?php echo $article['category_slug'] ?? ''; ?>">
                <?php echo $article['category_name'] ?? 'بدون دسته'; ?>
            </a>
            <span>›</span>
            <span><?php echo truncateText($article['title'], 30); ?></span>
        </div>

        <!-- Category Tag -->
        <span class="category-tag"><?php echo $article['category_name'] ?? 'بدون دسته'; ?></span>
        
        <!-- Title -->
        <h1><?php echo $article['title']; ?></h1>
        
        <!-- Meta -->
        <div class="meta">
            <span><i class="fas fa-user"></i> <span class="author"><?php echo $article['author_name'] ?? 'نامشخص'; ?></span></span>
            <span><i class="fas fa-calendar-days"></i> <?php echo getPersianDateTime(strtotime($article['created_at'])); ?></span>
            <span><i class="fas fa-eye"></i> <?php echo number_format($article['views'] + 1); ?> بازدید</span>
            <span><i class="fas fa-comment"></i> <?php echo $total_comments; ?> نظر</span>
            <?php if ($article['updated_at'] && strtotime($article['updated_at']) > strtotime($article['created_at'])): ?>
                <span><i class="fas fa-edit"></i> آخرین ویرایش: <?php echo getPersianDateTime(strtotime($article['updated_at'])); ?></span>
            <?php endif; ?>
        </div>

        <!-- Featured Image -->
        <?php if ($article['image']): ?>
            <img src="uploads/articles/<?php echo htmlspecialchars($article['image']); ?>" class="featured-image" alt="<?php echo htmlspecialchars($article['title']); ?>" />
        <?php endif; ?>

        <!-- Excerpt -->
        <?php if ($article['excerpt']): ?>
            <div class="article-excerpt">
                <?php echo $article['excerpt']; ?>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="article-content">
            <?php echo nl2br($article['content']); ?>
        </div>

        <!-- Tags -->
        <?php if ($article['tags']): ?>
            <div class="tags">
                <?php foreach (explode(',', $article['tags']) as $tag): ?>
                    <span class="tag">#<?php echo trim($tag); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Share Buttons -->
        <div class="share-buttons">
            <button class="telegram" onclick="shareArticle('telegram')">
                <i class="fab fa-telegram"></i> اشتراک در تلگرام
            </button>
            <button class="whatsapp" onclick="shareArticle('whatsapp')">
                <i class="fab fa-whatsapp"></i> اشتراک در واتساپ
            </button>
            <button class="twitter" onclick="shareArticle('twitter')">
                <i class="fab fa-twitter"></i> توییت
            </button>
            <button class="copy" onclick="copyLink()">
                <i class="fas fa-link"></i> کپی لینک
            </button>
        </div>
    </div>

    <!-- SIDEBAR -->
    <div class="article-sidebar">
        <!-- Popular Articles -->
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-fire"></i> محبوب‌ترین‌ها</div>
            <?php foreach ($popular_articles as $p): ?>
                <a class="widget-item" href="article.php?slug=<?php echo htmlspecialchars($p['slug']); ?>">
                    <?php if ($p['image']): ?>
                        <img src="uploads/articles/<?php echo $p['image']; ?>" alt="<?php echo $p['title']; ?>" />
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=100&h=100&fit=crop" alt="<?php echo $p['title']; ?>" />
                    <?php endif; ?>
                    <div class="info">
                        <h4><?php echo truncateText($p['title'], 30); ?></h4>
                        <div class="views"><i class="fas fa-eye"></i> <?php echo number_format($p['views']); ?></div>
                        <div class="date"><i class="fas fa-calendar-days"></i> <?php echo getPersianDate(strtotime($p['created_at'])); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Recent Articles -->
        <div class="sidebar-widget">
            <div class="widget-title"><i class="fas fa-clock"></i> جدیدترین‌ها</div>
            <?php foreach ($recent_articles as $r): ?>
                <a class="widget-item" href="article.php?slug=<?php echo htmlspecialchars($r['slug']); ?>">
                    <?php if ($r['image']): ?>
                        <img src="uploads/articles/<?php echo $r['image']; ?>" alt="<?php echo $r['title']; ?>" />
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=100&h=100&fit=crop" alt="<?php echo $r['title']; ?>" />
                    <?php endif; ?>
                    <div class="info">
                        <h4><?php echo truncateText($r['title'], 30); ?></h4>
                        <div class="date"><i class="fas fa-calendar-days"></i> <?php echo getPersianDate(strtotime($r['created_at'])); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ===== RELATED ARTICLES ===== -->
<?php if (count($related) > 0): ?>
<section class="related-section">
    <div class="related-title">
        <i class="fas fa-lightbulb"></i> مقالات مرتبط
    </div>
    <div class="related-grid">
        <?php foreach ($related as $rel): ?>
            <a class="related-item" href="article.php?slug=<?php echo htmlspecialchars($rel['slug']); ?>">
                <?php if ($rel['image']): ?>
                    <img src="uploads/articles/<?php echo $rel['image']; ?>" alt="<?php echo $rel['title']; ?>" />
                <?php else: ?>
                    <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=400&h=300&fit=crop" alt="<?php echo $rel['title']; ?>" />
                <?php endif; ?>
                <div class="info">
                    <h4><?php echo truncateText($rel['title'], 40); ?></h4>
                    <div class="date"><i class="fas fa-calendar-days"></i> <?php echo getPersianDate(strtotime($rel['created_at'])); ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== COMMENTS ===== -->
<section class="comments-section" id="comments">
    <div class="comments-title">
        <i class="fas fa-comments"></i> نظرات <span class="count-badge"><?php echo (int)$total_comments; ?></span>
    </div>

    <?php if (count($comments) > 0): ?>
        <?php foreach ($comments as $c): ?>
            <div class="comment">
                <div class="comment-meta">
                    <div class="avatar">
                        <?php echo mb_substr($c['user_name'] ?? $c['name'] ?? 'کاربر مهمان', 0, 1); ?>
                    </div>
                    <span class="name"><?php echo $c['user_name'] ?? $c['name'] ?? 'کاربر مهمان'; ?></span>
                    <span class="date"><?php echo getPersianDateTime(strtotime($c['created_at'])); ?></span>
                </div>
                <div class="comment-text">
                    <?php echo nl2br($c['text']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-comments">
            <i class="fas fa-comment-slash"></i>
            <p>هنوز نظری ثبت نشده است. اولین نفری باشید که نظر می‌دهید!</p>
        </div>
    <?php endif; ?>

    <!-- Comment Form -->
    <div class="comment-form">
        <h4><i class="fas fa-pen-fancy"></i> ارسال نظر</h4>
        <form method="POST" id="commentForm">
            <?php if (!isLoggedIn()): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>نام *</label>
                        <input type="text" name="name" required />
                    </div>
                    <div class="form-group">
                        <label>ایمیل</label>
                        <input type="email" name="email" />
                    </div>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label>متن نظر *</label>
                <textarea name="text" required></textarea>
            </div>
            <button type="submit" name="comment" class="btn-submit" id="commentBtn">
                <i class="fas fa-paper-plane"></i> ارسال نظر
            </button>
        </form>
    </div>
</section>

<?php
// ===== فوتر رو فراخوانی میکنیم =====
include_once 'includes/footer.php';
?>