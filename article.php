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
    die("❌ مقاله با اسلاگ '$slug' یافت نشد! لطفاً از صحیح بودن لینک مطمئن شوید.");
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

<!-- ===== استایل‌های اختصاصی صفحه مقاله ===== -->
<style>
    /* ===== ARTICLE CONTENT ===== */
    .article-wrapper {
        max-width: 1400px;
        margin: 24px auto;
        padding: 0 16px;
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 24px;
    }
    .article-main {
        background: var(--bg-card);
        border-radius: var(--radius);
        padding: 32px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
    }
    .article-main .breadcrumb {
        font-size: 13px;
        color: var(--text-light);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .article-main .breadcrumb a { color: var(--gold); }
    .article-main .breadcrumb a:hover { text-decoration: underline; }
    .article-main .breadcrumb span { color: var(--text-light); }
    .article-main .category-tag {
        display: inline-block;
        background: var(--gold-gradient);
        color: #fff;
        padding: 4px 18px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 12px;
    }
    .article-main h1 {
        font-size: 32px;
        line-height: 1.4;
        margin-bottom: 12px;
        color: var(--text);
    }
    .article-main .meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        font-size: 14px;
        color: var(--text-light);
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 20px;
    }
    .article-main .meta i { margin-left: 4px; }
    .article-main .meta .author { color: var(--gold); font-weight: 600; }
    
    .article-main .featured-image {
        width: 100%;
        max-height: 500px;
        object-fit: cover;
        border-radius: var(--radius-sm);
        margin-bottom: 24px;
        border: 1px solid var(--border);
    }
    .article-main .article-excerpt {
        font-size: 18px;
        color: var(--text-light);
        border-right: 4px solid var(--gold);
        padding-right: 16px;
        margin-bottom: 24px;
        line-height: 1.8;
    }
    .article-main .article-content {
        font-size: 16px;
        line-height: 2.2;
        color: var(--text);
    }
    .article-main .article-content p { margin-bottom: 16px; }
    .article-main .article-content h2 {
        font-size: 24px;
        margin: 28px 0 12px;
        color: var(--text);
    }
    .article-main .article-content h3 {
        font-size: 20px;
        margin: 24px 0 10px;
        color: var(--text);
    }
    .article-main .article-content ul, 
    .article-main .article-content ol {
        padding-right: 24px;
        margin-bottom: 16px;
    }
    .article-main .article-content li { margin-bottom: 6px; }
    .article-main .article-content img {
        max-width: 100%;
        border-radius: var(--radius-sm);
        margin: 16px 0;
        border: 1px solid var(--border);
    }
    .article-main .article-content blockquote {
        background: var(--bg);
        padding: 16px 24px;
        border-right: 4px solid var(--gold);
        border-radius: var(--radius-sm);
        margin: 16px 0;
        font-style: italic;
        color: var(--text-light);
    }
    .article-main .article-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 16px 0;
    }
    .article-main .article-content table th,
    .article-main .article-content table td {
        border: 1px solid var(--border);
        padding: 10px 14px;
        text-align: right;
    }
    .article-main .article-content table th {
        background: var(--bg);
        font-weight: 600;
    }

    .article-main .tags {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 24px;
        padding-top: 16px;
        border-top: 1px solid var(--border);
    }
    .article-main .tags .tag {
        background: var(--bg);
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 12px;
        border: 1px solid var(--border);
        color: var(--text-light);
    }
    .article-main .tags .tag:hover {
        background: var(--gold);
        color: #fff;
        border-color: var(--gold);
    }

    .article-main .share-buttons {
        display: flex;
        gap: 10px;
        margin-top: 16px;
        flex-wrap: wrap;
    }
    .article-main .share-buttons button {
        padding: 8px 16px;
        border: none;
        border-radius: 30px;
        cursor: pointer;
        font-family: 'Vazirmatn', sans-serif;
        font-size: 13px;
        font-weight: 600;
        transition: all var(--transition);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .article-main .share-buttons .telegram { background: #0088CC; color: #fff; }
    .article-main .share-buttons .whatsapp { background: #25D366; color: #fff; }
    .article-main .share-buttons .twitter { background: #1DA1F2; color: #fff; }
    .article-main .share-buttons .copy { background: var(--bg); color: var(--text); border: 1px solid var(--border); }
    .article-main .share-buttons button:hover { transform: scale(1.05); }

    /* ===== SIDEBAR ===== */
    .article-sidebar {
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
        color: var(--gold);
    }
    .sidebar-widget .widget-title i { color: var(--gold); }
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
        color: var(--gold);
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

    /* ===== COMMENTS ===== */
    .comments-section {
        background: var(--bg-card);
        border-radius: var(--radius);
        padding: 32px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        margin-top: 24px;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
        padding-left: 16px;
        padding-right: 16px;
    }
    .comments-section .comments-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .comments-section .comments-title i { color: var(--gold); }
    .comments-section .comment {
        padding: 16px 0;
        border-bottom: 1px solid var(--border);
    }
    .comments-section .comment:last-child { border-bottom: none; }
    .comments-section .comment .comment-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 4px;
    }
    .comments-section .comment .comment-meta .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--gold-gradient);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        flex-shrink: 0;
    }
    .comments-section .comment .comment-meta .name {
        font-weight: 600;
        color: var(--text);
    }
    .comments-section .comment .comment-meta .date {
        font-size: 12px;
        color: var(--text-light);
    }
    .comments-section .comment .comment-text {
        font-size: 15px;
        line-height: 1.8;
        color: var(--text);
        margin-top: 4px;
        padding-right: 52px;
    }
    .comments-section .no-comments {
        text-align: center;
        padding: 30px 0;
        color: var(--text-light);
    }
    .comments-section .no-comments i {
        font-size: 48px;
        opacity: 0.3;
        display: block;
        margin-bottom: 12px;
    }

    .comment-form {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
    }
    .comment-form h4 {
        font-size: 18px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .comment-form h4 i { color: var(--gold); }
    .comment-form .form-group {
        margin-bottom: 14px;
    }
    .comment-form .form-group label {
        display: block;
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 4px;
        color: var(--text);
    }
    .comment-form .form-group input,
    .comment-form .form-group textarea {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--bg);
        color: var(--text);
        font-family: 'Vazirmatn', sans-serif;
        font-size: 13px;
        transition: all var(--transition);
    }
    .comment-form .form-group input:focus,
    .comment-form .form-group textarea:focus {
        border-color: var(--gold);
        outline: none;
        box-shadow: 0 0 30px rgba(201, 168, 76, 0.08);
    }
    .comment-form .form-group textarea {
        min-height: 120px;
        resize: vertical;
    }
    .comment-form .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .comment-form .btn-submit {
        background: var(--gold-gradient);
        color: #fff;
        border: none;
        padding: 10px 28px;
        border-radius: 30px;
        cursor: pointer;
        font-family: 'Vazirmatn', sans-serif;
        font-size: 14px;
        font-weight: 600;
        transition: all var(--transition);
    }
    .comment-form .btn-submit:hover {
        transform: scale(1.03);
        box-shadow: var(--shadow);
    }
    .comment-form .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* ===== RELATED ARTICLES ===== */
    .related-section {
        max-width: 1400px;
        margin: 24px auto;
        padding: 0 16px;
    }
    .related-section .related-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .related-section .related-title i { color: var(--gold); }
    .related-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }
    .related-item {
        background: var(--bg-card);
        border-radius: var(--radius-sm);
        overflow: hidden;
        border: 1px solid var(--border);
        cursor: pointer;
        transition: all var(--transition);
    }
    .related-item:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-hover);
        border-color: var(--gold);
    }
    .related-item img {
        width: 100%;
        height: 130px;
        object-fit: cover;
    }
    .related-item .info {
        padding: 12px 14px;
    }
    .related-item .info h4 {
        font-size: 14px;
        line-height: 1.4;
        color: var(--text);
    }
    .related-item .info .date {
        font-size: 11px;
        color: var(--text-light);
        margin-top: 4px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .article-wrapper {
            grid-template-columns: 1fr;
        }
        .article-sidebar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    }
    @media (max-width: 768px) {
        .article-main { padding: 20px; }
        .article-main h1 { font-size: 24px; }
        .article-sidebar { grid-template-columns: 1fr; }
        .comment-form .form-row { grid-template-columns: 1fr; }
        .related-grid { grid-template-columns: 1fr 1fr; }
        .comments-section { padding: 16px; }
    }
    @media (max-width: 480px) {
        .article-main { padding: 16px; }
        .article-main h1 { font-size: 20px; }
        .article-main .meta { font-size: 12px; gap: 10px; }
        .article-main .article-content { font-size: 14px; }
        .comments-section .comment .comment-text { padding-right: 0; }
        .related-grid { grid-template-columns: 1fr; }
        .article-sidebar { grid-template-columns: 1fr; }
    }
</style>

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
            <span><i class="fas fa-calendar"></i> <?php echo getPersianDateTime(strtotime($article['created_at'])); ?></span>
            <span><i class="fas fa-eye"></i> <?php echo number_format($article['views'] + 1); ?> بازدید</span>
            <span><i class="fas fa-comment"></i> <?php echo $total_comments; ?> نظر</span>
            <?php if ($article['updated_at'] && strtotime($article['updated_at']) > strtotime($article['created_at'])): ?>
                <span><i class="fas fa-edit"></i> آخرین ویرایش: <?php echo getPersianDateTime(strtotime($article['updated_at'])); ?></span>
            <?php endif; ?>
        </div>

        <!-- Featured Image -->
        <?php if ($article['image']): ?>
            <img src="uploads/articles/<?php echo $article['image']; ?>" class="featured-image" alt="<?php echo $article['title']; ?>" />
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

<!-- ===== RELATED ARTICLES ===== -->
<?php if (count($related) > 0): ?>
<section class="related-section">
    <div class="related-title">
        <i class="fas fa-lightbulb"></i> مقالات مرتبط
    </div>
    <div class="related-grid">
        <?php foreach ($related as $rel): ?>
            <div class="related-item" onclick="location.href='article.php?slug=<?php echo $rel['slug']; ?>'">
                <?php if ($rel['image']): ?>
                    <img src="uploads/articles/<?php echo $rel['image']; ?>" alt="<?php echo $rel['title']; ?>" />
                <?php else: ?>
                    <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=400&h=300&fit=crop" alt="<?php echo $rel['title']; ?>" />
                <?php endif; ?>
                <div class="info">
                    <h4><?php echo truncateText($rel['title'], 40); ?></h4>
                    <div class="date"><i class="fas fa-calendar"></i> <?php echo getPersianDate(strtotime($rel['created_at'])); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== COMMENTS ===== -->
<section class="comments-section" id="comments">
    <div class="comments-title">
        <i class="fas fa-comments"></i> نظرات (<?php echo $total_comments; ?>)
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