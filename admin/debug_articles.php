<?php
// debug_articles.php
require_once 'includes/functions.php';

echo "<h2>🔍 دیباگ مقالات</h2>";

// نمایش همه مقالات
$stmt = $pdo->query("SELECT id, title, slug, status FROM articles");
$articles = $stmt->fetchAll();

echo "<h3>📋 لیست مقالات در دیتابیس:</h3>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>عنوان</th><th>اسلاگ</th><th>وضعیت</th><th>لینک</th></tr>";

foreach ($articles as $a) {
    echo "<tr>";
    echo "<td>{$a['id']}</td>";
    echo "<td>{$a['title']}</td>";
    echo "<td><strong>" . ($a['slug'] ?: '<span style="color:red;">❌ خالی</span>') . "</strong></td>";
    echo "<td>{$a['status']}</td>";
    echo "<td>";
    if ($a['slug']) {
        echo "<a href='article.php?slug={$a['slug']}' target='_blank'>article.php?slug={$a['slug']}</a>";
    } else {
        echo "<span style='color:red;'>⚠️ نیاز به اصلاح</span>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// آمار
$total = count($articles);
$with_slug = count(array_filter($articles, function($a) { return !empty($a['slug']); }));
$empty_slug = $total - $with_slug;

echo "<p>📊 مجموع: {$total} مقاله | ";
echo "✅ دارای اسلاگ: {$with_slug} | ";
echo "❌ بدون اسلاگ: {$empty_slug}</p>";

if ($empty_slug > 0) {
    echo "<p style='color:red;'>⚠️ {$empty_slug} مقاله اسلاگ ندارند! لطفاً فایل fix_slugs.php را اجرا کنید.</p>";
    echo "<a href='fix_slugs.php' style='display:inline-block; padding:10px 20px; background:#6C63FF; color:#fff; border-radius:8px; text-decoration:none;'>🔧 اصلاح اسلاگ‌ها</a>";
}

// تست یک اسلاگ خاص
if (isset($_GET['test_slug'])) {
    $test_slug = $_GET['test_slug'];
    echo "<h3>🔍 تست اسلاگ: $test_slug</h3>";
    $article = getArticleBySlug($test_slug);
    
    if ($article) {
        echo "<p style='color:green;'>✅ مقاله پیدا شد: " . $article['title'] . "</p>";
        echo "<p>لینک مقاله: <a href='article.php?slug={$test_slug}'>article.php?slug={$test_slug}</a></p>";
    } else {
        echo "<p style='color:red;'>❌ مقاله با اسلاگ '$test_slug' پیدا نشد!</p>";
    }
}
?>