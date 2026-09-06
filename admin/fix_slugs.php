<?php
// fix_slugs.php
require_once 'config/database.php';

echo "<h2>🔧 اصلاح اسلاگ مقالات</h2>";

// دریافت همه مقالات
$stmt = $pdo->query("SELECT id, title, slug FROM articles");
$articles = $stmt->fetchAll();

echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>عنوان</th><th>اسلاگ قدیمی</th><th>اسلاگ جدید</th><th>وضعیت</th></tr>";

$updated = 0;
foreach ($articles as $article) {
    $old_slug = $article['slug'];
    
    // اگر اسلاگ خالی بود یا null، از عنوان بساز
    if (empty($article['slug'])) {
        $new_slug = slugify($article['title']);
        
        // بررسی تکراری نبودن اسلاگ
        $check = $pdo->prepare("SELECT id FROM articles WHERE slug = ? AND id != ?");
        $check->execute([$new_slug, $article['id']]);
        if ($check->fetch()) {
            $new_slug = $new_slug . '-' . $article['id'];
        }
        
        // به‌روزرسانی
        $update = $pdo->prepare("UPDATE articles SET slug = ? WHERE id = ?");
        $update->execute([$new_slug, $article['id']]);
        
        echo "<tr>";
        echo "<td>{$article['id']}</td>";
        echo "<td>{$article['title']}</td>";
        echo "<td style='color:red;'><strong>خالی</strong></td>";
        echo "<td style='color:green;'><strong>{$new_slug}</strong></td>";
        echo "<td style='color:green;'>✅ اصلاح شد</td>";
        echo "</tr>";
        
        $updated++;
    } else {
        echo "<tr>";
        echo "<td>{$article['id']}</td>";
        echo "<td>{$article['title']}</td>";
        echo "<td>{$article['slug']}</td>";
        echo "<td style='color:blue;'>همان ({$article['slug']})</td>";
        echo "<td style='color:blue;'>✅ بدون تغییر</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<h3 style='color:green;'>✅ {$updated} مقاله اصلاح شد!</h3>";

// تابع slugify
function slugify($string) {
    $string = trim($string);
    $string = str_replace(' ', '-', $string);
    $string = preg_replace('/[^a-zA-Z0-9\-]/u', '', $string);
    $string = strtolower($string);
    return $string;
}

echo "<br><a href='index.php'>بازگشت به صفحه اصلی</a> | ";
echo "<a href='debug_articles.php'>مشاهده مجدد دیباگ</a>";
?>