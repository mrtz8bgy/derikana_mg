<?php
// author.php — آرشیو نوشته‌های یک نویسنده
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT id, name, username, email, avatar, registered_at FROM users WHERE id = ?");
$stmt->execute([$id]);
$author = $stmt->fetch();

if (!$author) {
    header('Location: index.php');
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = (int)getSetting('posts_per_page') ?: 9;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE author_id = ? AND status = 'published'");
$stmt->execute([$id]);
$total_articles = (int)$stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_articles / $limit));

$stmt = $pdo->prepare("SELECT a.*, c.name AS category_name, c.slug AS category_slug
                       FROM articles a LEFT JOIN categories c ON a.category_id = c.id
                       WHERE a.author_id = ? AND a.status = 'published'
                       ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute([$id]);
$articles = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT SUM(views) FROM articles WHERE author_id = ? AND status = 'published'");
$stmt->execute([$id]);
$total_views = (int)$stmt->fetchColumn();

$categories = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll();

$site_title = 'نوشته‌های ' . $author['name'] . ' | ' . (getSetting('site_title') ?: 'مجله دریکانا');
$site_description = 'آرشیو کامل مقالات ' . $author['name'] . ' در مجله دریکانا';

include_once 'includes/header.php';

$img = function ($article) {
    if (!empty($article['image']) && file_exists(__DIR__ . '/uploads/articles/' . $article['image'])) {
        return 'uploads/articles/' . $article['image'];
    }
    return 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=640&h=400&fit=crop';
};
?>

<!-- ===== AUTHOR HEAD ===== -->
<section class="container">
    <div class="page-head author-head">
        <div class="head-icon author-avatar"><?php echo htmlspecialchars(mb_substr($author['name'], 0, 1)); ?></div>
        <span class="kicker">قلم‌های مجله</span>
        <h1><?php echo htmlspecialchars($author['name']); ?></h1>
        <p class="head-desc">عضو مجله از <?php echo getPersianDate(strtotime($author['registered_at'])); ?> — نویسنده‌ای که به جزئیات اهمیت می‌دهد.</p>
        <div class="head-stats">
            <span class="chip"><i class="fas fa-file-lines"></i> <?php echo number_format($total_articles); ?> مقاله</span>
            <span class="chip"><i class="fas fa-eye"></i> <?php echo number_format($total_views); ?> بازدید</span>
        </div>
    </div>
</section>

<!-- ===== ARTICLES ===== -->
<section class="articles-section container">
    <div class="section-header">
        <div class="section-title">
            <span class="kicker">آرشیو نویسنده</span>
            <h2><i class="fas fa-feather-pointed"></i> نوشته‌های <?php echo htmlspecialchars($author['name']); ?></h2>
        </div>
    </div>

    <div class="articles-grid page-grid">
        <?php if (count($articles) > 0): ?>
            <?php foreach ($articles as $article): ?>
                <a class="article-card" href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                    <img src="<?php echo $img($article); ?>" class="article-image" alt="<?php echo htmlspecialchars($article['title']); ?>" loading="lazy" />
                    <div class="card-content">
                        <span class="tag"><?php echo htmlspecialchars($article['category_name'] ?? 'بدون دسته'); ?></span>
                        <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                        <p><?php echo htmlspecialchars(truncateText($article['excerpt'] ?? $article['content'], 100)); ?></p>
                        <div class="meta">
                            <span><i class="fas fa-calendar-days"></i> <?php echo getPersianDate(strtotime($article['created_at'])); ?></span>
                            <span><i class="fas fa-eye"></i> <?php echo formatViews($article['views']); ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column:1/-1;">
                <i class="fas fa-feather" aria-hidden="true"></i>
                <h3>هنوز مقاله‌ای منتشر نشده است</h3>
                <p>این نویسنده هنوز مقاله‌ای منتشر نکرده است.</p>
                <a href="index.php">بازگشت به صفحه اصلی</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="author.php?id=<?php echo $id; ?>&page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-right"></i> قبلی</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="author.php?id=<?php echo $id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="author.php?id=<?php echo $id; ?>&page=<?php echo $page + 1; ?>">بعدی <i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php include_once 'includes/footer.php'; ?>
