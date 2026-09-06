-- ============================================================
--  fix-charset.sql — یکدست‌سازی charset به utf8mb4
--  (بدون این، ذخیره ایموجی و برخی کاراکترها در نظرات/مقالات خطا می‌دهد)
--  اجرا:  mysql derikana_magazine < dev/fix-charset.sql
-- ============================================================
ALTER DATABASE derikana_magazine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE article_requests CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE articles         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE banners          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE categories       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE comments         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE menus            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE pages            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE settings         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SELECT table_name, table_collation
FROM information_schema.tables
WHERE table_schema = 'derikana_magazine';
