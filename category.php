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
    die("❌ دسته‌بندی با اسلاگ '$slug' یافت نشد!");
}

// دریافت مقالات این دسته با صفحه‌بندی
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = (int)getSetting('posts_per_page') ?: 9;
if ($limit < 1) $limit = 9;

$offset = ($page - 1) * $limit;

// تعداد کل مقالات این دسته
$stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ? AND status = 'published'");
$stmt->execute([$category['id']]);
$total_articles = (int)$stmt->fetchColumn();
$total_pages = ceil($total_articles / $limit);

// دریافت مقالات - اصلاح شده با CAST
$stmt = $pdo->prepare("
    SELECT a.*, u.name as author_name, c.name as category_name 
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.category_id = ? AND a.status = 'published' 
    ORDER BY a.created_at DESC 
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
");
$stmt->execute([$category['id']]);
$articles = $stmt->fetchAll();

// دریافت مقالات ویژه (با بالاترین بازدید)
$stmt = $pdo->prepare("
    SELECT a.*, u.name as author_name 
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE a.category_id = ? AND a.status = 'published' 
    ORDER BY a.views DESC 
    LIMIT 4
");
$stmt->execute([$category['id']]);
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

<!-- ===== استایل‌های اختصاصی صفحه دسته‌بندی ===== -->
<style>
    /* ===== CATEGORY HEADER ===== */
    .category-header {
        max-width: 1400px;
        margin: 20px auto;
        padding: 0 16px;
    }
    .category-header .header-content {
        background: var(--bg-card);
        border-radius: var(--radius);
        padding: 40px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }
    .category-header .header-content::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        height: 4px;
        background: <?php echo $category['color'] ?: 'var(--gold-gradient)'; ?>;
    }
    .category-header .header-content .category-icon {
        font-size: 48px;
        color: <?php echo $category['color'] ?: 'var(--gold)'; ?>;
        margin-bottom: 10px;
    }
    .category-header .header-content h1 {
        font-size: 32px;
        color: var(--text);
    }
    .category-header .header-content p {
        color: var(--text-light);
        font-size: 16px;
        margin-top: 8px;
        max-width: 600px;
    }
    .category-header .header-content .stats {
        display: flex;
        gap: 20px;
        margin-top: 12px;
        font-size: 14px;
        color: var(--text-light);
        flex-wrap: wrap;
    }
    .category-header .header-content .stats span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .category-header .header-content .stats i {
        color: var(--gold);
    }
    .category-header .header-content .stats .color-dot {
        display: inline-block;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 1px solid var(--border);
    }

    /* ===== FEATURED ARTICLES ===== */
    .featured-section {
        max-width: 1400px;
        margin: 16px auto;
        padding: 0 16px;
    }
    .featured-section h2 {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 20px;
        margin-bottom: 16px;
        color: var(--text);
    }
    .featured-section h2 i { color: var(--gold); }
    .featured-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 18px;
    }
    .featured-card {
        background: var(--bg-card);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: all var(--transition);
        cursor: pointer;
        border: 1px solid var(--border);
        position: relative;
    }
    .featured-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-hover);
        border-color: var(--gold);
    }
    .featured-card .featured-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: var(--gold-gradient);
        color: #fff;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        z-index: 5;
    }
    .featured-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    .featured-card .content { padding: 16px 18px; }
    .featured-card .content .tag {
        display: inline-block;
        background: var(--badge-bg);
        color: var(--gold);
        padding: 2px 14px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        border: 1px solid var(--border);
    }
    .featured-card .content h3 {
        font-size: 18px;
        margin: 8px 0;
        color: var(--text);
    }
    .featured-card .content p {
        color: var(--text-light);
        font-size: 14px;
        line-height: 1.7;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .featured-card .content .meta {
        font-size: 12px;
        color: var(--text-light);
        margin-top: 8px;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .featured-card .content .meta i { color: var(--gold); }

    /* ===== ARTICLES GRID ===== */
    .articles-section {
        max-width: 1400px;
        margin: 20px auto;
        padding: 0 16px;
    }
    .articles-section .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .articles-section .section-header h2 {
        font-size: 22px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text);
    }
    .articles-section .section-header h2 i { color: var(--gold); }
    .articles-section .section-header .count {
        font-size: 14px;
        color: var(--text-light);
    }
    
    .articles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    .article-card {
        background: var(--bg-card);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: all var(--transition);
        border: 1px solid var(--border);
        cursor: pointer;
    }
    .article-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--shadow-hover);
        border-color: var(--gold);
    }
    .article-card .article-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        transition: all var(--transition);
        background: var(--bg);
    }
    .article-card:hover .article-image { transform: scale(1.03); }
    .article-card .card-content { padding: 16px 18px; }
    .article-card .card-content .tag {
        display: inline-block;
        background: var(--badge-bg);
        color: var(--gold);
        padding: 2px 14px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        border: 1px solid var(--border);
    }
    .article-card .card-content h3 {
        font-size: 16px;
        margin: 8px 0 4px;
        color: var(--text);
        line-height: 1.5;
    }
    .article-card .card-content p {
        font-size: 13px;
        color: var(--text-light);
        line-height: 1.7;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .article-card .card-content .meta {
        font-size: 11px;
        color: var(--text-light);
        margin-top: 8px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .article-card .card-content .meta i { color: var(--gold); }

    /* ===== PAGINATION ===== */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 24px;
        flex-wrap: wrap;
    }
    .pagination a, .pagination span {
        padding: 8px 16px;
        border: 2px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--bg-card);
        color: var(--text);
        cursor: pointer;
        transition: all var(--transition);
        font-size: 13px;
        font-weight: 500;
    }
    .pagination a:hover {
        background: var(--gold-gradient);
        color: #fff;
        border-color: var(--gold);
    }
    .pagination .active {
        background: var(--gold-gradient);
        color: #fff;
        border-color: var(--gold);
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-light);
        grid-column: 1 / -1;
    }
    .empty-state i {
        font-size: 64px;
        opacity: 0.3;
        margin-bottom: 16px;
    }
    .empty-state h3 {
        font-size: 22px;
        color: var(--text);
        margin-bottom: 8px;
    }
    .empty-state a {
        display: inline-block;
        margin-top: 12px;
        padding: 8px 24px;
        background: var(--gold-gradient);
        color: #fff;
        border-radius: 30px;
        font-weight: 600;
        transition: all var(--transition);
    }
    .empty-state a:hover {
        transform: scale(1.05);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .category-header .header-content { padding: 24px; }
        .category-header .header-content h1 { font-size: 24px; }
        .featured-grid { grid-template-columns: 1fr; }
        .articles-grid { grid-template-columns: 1fr; }
        .featured-card img { height: 160px; }
    }
    @media (max-width: 480px) {
        .category-header .header-content { padding: 16px; }
        .category-header .header-content h1 { font-size: 20px; }
        .category-header .header-content .stats { flex-direction: column; gap: 6px; }
        .articles-grid { grid-template-columns: 1fr; }
        .article-card .article-image { height: 150px; }
    }
</style>

<!-- ===== CATEGORY HEADER ===== -->
<section class="category-header">
    <div class="header-content">
        <?php if ($category['icon']): ?>
            <div class="category-icon">
                <i class="fas <?php echo $category['icon']; ?>"></i>
            </div>
        <?php endif; ?>
        <h1><?php echo $category['name']; ?></h1>
        <?php if ($category['description']): ?>
            <p><?php echo $category['description']; ?></p>
        <?php endif; ?>
        <div class="stats">
            <span><i class="fas fa-file-alt"></i> <?php echo $total_articles; ?> مقاله</span>
            <span><i class="fas fa-eye"></i> <?php echo number_format(array_sum(array_column($articles, 'views'))); ?> بازدید</span>
            <?php if ($category['color']): ?>
                <span>
                    <i class="fas fa-palette"></i> 
                    <span class="color-dot" style="background:<?php echo $category['color']; ?>;"></span>
                    <?php echo $category['color']; ?>
                </span>
            <?php endif; ?>
            <?php if (isset($category['link_type']) && $category['link_type'] == 'external' && !empty($category['external_link'])): ?>
                <span>
                    <i class="fas fa-external-link-alt"></i>
                    <a href="<?php echo $category['external_link']; ?>" target="_blank" style="color:var(--gold); text-decoration:underline;">
                        لینک خارجی
                    </a>
                </span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== FEATURED ARTICLES ===== -->
<?php if (count($featured_articles) > 0): ?>
<section class="featured-section">
    <h2><i class="fas fa-star"></i> مقالات ویژه <?php echo $category['name']; ?></h2>
    <div class="featured-grid">
        <?php foreach ($featured_articles as $article): ?>
            <div class="featured-card" onclick="location.href='article.php?slug=<?php echo $article['slug']; ?>'">
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
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== ARTICLES ===== -->
<section class="articles-section">
    <div class="section-header">
        <h2><i class="fas fa-newspaper"></i> تمام مقالات <?php echo $category['name']; ?></h2>
        <span class="count">📄 <?php echo $total_articles; ?> مقاله</span>
    </div>
    
    <div class="articles-grid">
        <?php if (count($articles) > 0): ?>
            <?php foreach ($articles as $article): ?>
                <div class="article-card" onclick="location.href='article.php?slug=<?php echo $article['slug']; ?>'">
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
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
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
                <a href="category.php?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>">‹ قبلی</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="category.php?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="category.php?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>">بعدی ›</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php
// ===== فوتر رو فراخوانی میکنیم =====
include_once 'includes/footer.php';
?>