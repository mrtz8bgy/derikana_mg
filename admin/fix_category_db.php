<?php
// fix_category_db.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $messages = [];
    $success = true;
    
    // 1. پیدا کردن نام کلید خارجی
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'categories' 
        AND COLUMN_NAME = 'parent_id' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
        AND TABLE_SCHEMA = DATABASE()
    ");
    
    $foreign_key = $stmt->fetch();
    
    if ($foreign_key) {
        $fk_name = $foreign_key['CONSTRAINT_NAME'];
        $messages[] = "🔍 نام کلید خارجی پیدا شد: " . $fk_name;
        
        // حذف کلید خارجی
        try {
            $pdo->exec("ALTER TABLE categories DROP FOREIGN KEY `{$fk_name}`");
            $messages[] = "✅ کلید خارجی '{$fk_name}' با موفقیت حذف شد";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Cannot drop') !== false) {
                $messages[] = "ℹ️ کلید خارجی وجود ندارد یا قبلاً حذف شده است";
            } else {
                throw $e;
            }
        }
    } else {
        $messages[] = "ℹ️ هیچ کلید خارجی برای parent_id پیدا نشد";
    }
    
    // 2. تغییر parent_id به NULL
    try {
        // بررسی نوع ستون
        $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
        $col = $stmt->fetch();
        
        if ($col && strpos($col['Type'], 'NULL') === false) {
            $pdo->exec("ALTER TABLE categories MODIFY parent_id INT DEFAULT NULL");
            $messages[] = "✅ ستون parent_id با موفقیت به NULL تغییر کرد";
        } else {
            $messages[] = "ℹ️ ستون parent_id از قبل می‌تواند NULL باشد";
        }
    } catch (PDOException $e) {
        $messages[] = "⚠️ خطا در تغییر parent_id: " . $e->getMessage();
    }
    
    // 3. به‌روزرسانی رکوردها
    try {
        $pdo->exec("UPDATE categories SET parent_id = NULL WHERE parent_id = 0");
        $messages[] = "✅ رکوردهای با parent_id=0 به NULL تبدیل شدند";
    } catch (PDOException $e) {
        $messages[] = "⚠️ خطا در به‌روزرسانی: " . $e->getMessage();
    }
    
    // 4. افزودن کلید خارجی مجدد (اختیاری)
    try {
        // بررسی وجود کلید خارجی با نام جدید
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'categories' 
            AND COLUMN_NAME = 'parent_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND TABLE_SCHEMA = DATABASE()
        ");
        
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE categories ADD CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE");
            $messages[] = "✅ کلید خارجی با موفقیت افزوده شد";
        } else {
            $messages[] = "ℹ️ کلید خارجی از قبل وجود دارد";
        }
    } catch (PDOException $e) {
        $messages[] = "⚠️ خطا در افزودن کلید خارجی: " . $e->getMessage();
    }
    
    // 5. افزودن ستون‌های جدید
    $columns = [
        'link_type' => "ALTER TABLE categories ADD COLUMN link_type ENUM('internal','external') DEFAULT 'internal'",
        'external_link' => "ALTER TABLE categories ADD COLUMN external_link VARCHAR(500) DEFAULT NULL",
        'link_target' => "ALTER TABLE categories ADD COLUMN link_target TINYINT(1) DEFAULT 0",
        'active' => "ALTER TABLE categories ADD COLUMN active TINYINT(1) DEFAULT 1"
    ];
    
    foreach ($columns as $name => $sql) {
        try {
            // بررسی وجود ستون
            $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE '$name'");
            if ($stmt->fetch()) {
                $messages[] = "ℹ️ ستون $name قبلاً وجود دارد";
            } else {
                $pdo->exec($sql);
                $messages[] = "✅ ستون $name با موفقیت اضافه شد";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $messages[] = "ℹ️ ستون $name قبلاً وجود دارد";
            } else {
                $messages[] = "⚠️ خطا در افزودن ستون $name: " . $e->getMessage();
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => implode('<br>', $messages)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا: ' . $e->getMessage()
    ]);
}
?>