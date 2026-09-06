<?php
// add_category_columns.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // بررسی و افزودن ستون‌ها
    $columns = [
        'link_type' => "ALTER TABLE categories ADD COLUMN link_type ENUM('internal','external') DEFAULT 'internal'",
        'external_link' => "ALTER TABLE categories ADD COLUMN external_link VARCHAR(500) DEFAULT NULL",
        'link_target' => "ALTER TABLE categories ADD COLUMN link_target TINYINT(1) DEFAULT 0",
        'active' => "ALTER TABLE categories ADD COLUMN active TINYINT(1) DEFAULT 1"
    ];
    
    $added = [];
    foreach ($columns as $name => $sql) {
        try {
            $pdo->exec($sql);
            $added[] = $name;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                // ستون قبلاً وجود دارد
            } else {
                throw $e;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => count($added) > 0 ? 'ستون‌های ' . implode(', ', $added) . ' با موفقیت اضافه شدند!' : 'همه ستون‌ها قبلاً وجود دارند!'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>