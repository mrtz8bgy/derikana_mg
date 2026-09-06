<?php
// category.php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// دریافت اسلاگ دسته‌بندی
$slug = isset($_GET['slug']) ? cleanInput($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// دریافت اطلاعات دسته‌بندی
$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND active = 1");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit;
}

// شناسه‌ی این دسته + همه زیردسته‌ها (نمایش تجمیعی)
$cat_ids = [(int)$category['id']];
$collect = function ($parent) use (&$collect, $pdo, &$cat_ids) {
    $st = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ?");
    $st->execute([$parent]);
    foreach ($st->fetchAll() as $row) {
        $cat_ids[] = (int)$row['id'];
        $collect($row['id']);
    }
};
$collect((int)$category['id']);
$in_place = implode(',', array_fill(0, count($cat_ids), '?'));

// دریافت مقالات این دسته با صفحه‌بندی
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = (int)getSetting('posts_per_page') ?: 9;
if ($limit < 1) $limit = 9;

$offset = ($page - 1) * $limit;

// تعداد کل مقالات این دسته
$stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE category_id IN ($in_place) AND status = 'published'");
$stmt->execute($cat_ids);
$total_articles = (int)$stmt->fetchColumn();
$total_pages = ceil($total_articles / $limit);

// دریافت مقالات - اصلاح شده با CAST
$stmt = $pdo->prepare("
    SELECT a.*, u.name as author_name, c.name as category_name 
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.category_id IN ($in_place) AND a.status = 'published'
    ORDER BY a.created_at DESC
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
");
$stmt->execute($cat_ids);
$articles = $stmt->fetchAll();

// دریافت مقالات ویژه (با بالاترین بازدید)
$stmt = $pdo->prepare("
    SELECT a.*, u.name as author_name 
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE a.category_id IN ($in_place) AND a.status = 'published'
    ORDER BY a.views DESC
    LIMIT 4
");
$stmt->execute($cat_ids);
$featured_articles = $stmt->fetchAll();

// دریافت تمام دسته‌بندی‌ها برای منو
$stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll();

// متا تگ‌ها
$site_title = $category['name'] . ' | ' . (getSetting('site_title') ?: 'مجله دریکانا');
$site_description = $category['description'] ?: 'مقالات دسته‌بندی ' . $category['name'];

// ===== هدر رو فراخوانی میکنیم =====
include_once 'includes/header.php';
?>


<!-- ===== CATEGORY HEADER ===== -->
<section class="container">
    <div class="page-head">
        <?php if ($category['icon']): ?>
            <div class="head-icon"><i class="fas <?php echo htmlspecialchars($category['icon']); ?>" aria-hidden="true"></i></div>
        <?php endif; ?>
        <span class="kicker">دسته‌بندی مجله</span>
        <h1><?php echo htmlspecialchars($category['name']); ?></h1>
        <?php if ($category['description']): ?>
            <p class="head-desc"><?php echo htmlspecialchars($category['description']); ?></p>
        <?php endif; ?>
        <div class="head-stats">
            <span class="chip"><i class="fas fa-file-lines"></i> <?php echo number_format($total_articles); ?> مقاله</span>
            <span class="chip"><i class="fas fa-eye"></i> <?php echo number_format(array_sum(array_column($articles, 'views'))); ?> بازدید</span>
            <?php if (isset($category['link_type']) && $category['link_type'] == 'external' && !empty($category['external_link'])): ?>
                <a class="chip" href="<?php echo htmlspecialchars($category['external_link']); ?>" target="_blank" rel="noopener">
                    <i class="fas fa-arrow-up-right-from-square"></i> مشاهده پیوند خارجی
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== FEATURED ARTICLES ===== -->
<?php if (count($featured_articles) > 0): ?>
<section class="featured-section container">
    <div class="section-header">
        <div class="section-title">
            <span class="kicker">گزیده‌ی سردبیر</span>
            <h2><i class="fas fa-star"></i> مقالات ویژه <?php echo htmlspecialchars($category['name']); ?></h2>
        </div>
    </div>
    <div class="featured-grid">
        <?php foreach ($featured_articles as $article): ?>
            <a class="featured-card" href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                <span class="featured-badge"><i class="fas fa-star"></i> ویژه</span>
                <?php if ($article['image']): ?>
                    <img src="uploads/articles/<?php echo $article['image']; ?>" alt="<?php echo $article['title']; ?>" />
                <?php else: ?>
                    <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=600&h=350&fit=crop" alt="<?php echo $article['title']; ?>" />
                <?php endif; ?>
                <div class="content">
                    <span class="tag"><?php echo $category['name']; ?></span>
                    <h3><?php echo $article['title']; ?></h3>
                    <p><?php echo truncateText($article['excerpt'] ?? $article['content'], 80); ?></p>
                    <div class="meta">
                        <span><i class="fas fa-user"></i> <?php echo $article['author_name'] ?? 'نامشخص'; ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo getPersianDate(strtotime($article['created_at'])); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo formatViews($article['views']); ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== ARTICLES ===== -->
<section class="articles-section container">
    <div class="section-header">
        <div class="section-title">
            <span class="kicker">آرشیو دسته</span>
            <h2><i class="fas fa-newspaper"></i> تمام مقالات <?php echo htmlspecialchars($category['name']); ?></h2>
        </div>
        <span class="chip"><i class="fas fa-file-lines"></i> <?php echo number_format($total_articles); ?> مقاله</span>
    </div>

    <div class="articles-grid page-grid">
        <?php if (count($articles) > 0): ?>
            <?php foreach ($articles as $article): ?>
                <a class="article-card" href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                    <?php if ($article['image']): ?>
                        <img src="uploads/articles/<?php echo $article['image']; ?>" class="article-image" alt="<?php echo $article['title']; ?>" />
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=600&h=350&fit=crop" class="article-image" alt="<?php echo $article['title']; ?>" />
                    <?php endif; ?>
                    <div class="card-content">
                        <span class="tag"><?php echo $article['category_name'] ?? $category['name']; ?></span>
                        <h3><?php echo $article['title']; ?></h3>
                        <p><?php echo truncateText($article['excerpt'] ?? $article['content'], 100); ?></p>
                        <div class="meta">
                            <span><i class="fas fa-user"></i> <?php echo $article['author_name'] ?? 'نامشخص'; ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo getPersianDate(strtotime($article['created_at'])); ?></span>
                            <span><i class="fas fa-eye"></i> <?php echo formatViews($article['views']); ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column:1/-1;">
                <i class="fas fa-folder-open"></i>
                <h3>هیچ مقاله‌ای در این دسته وجود ندارد</h3>
                <p>به زودی مقالات جدیدی در این دسته منتشر خواهد شد.</p>
                <a href="index.php">بازگشت به صفحه اصلی</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="category.php?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-right"></i> قبلی</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="category.php?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="category.php?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>">بعدی <i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php
// ===== فوتر رو فراخوانی میکنیم =====
include_once 'includes/footer.php';
?>