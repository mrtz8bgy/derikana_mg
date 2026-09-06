/*
 Navicat Premium Dump SQL

 Source Server         : localhost_3306
 Source Server Type    : MySQL
 Source Server Version : 100411 (10.4.11-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : derikana_magazine

 Target Server Type    : MySQL
 Target Server Version : 100411 (10.4.11-MariaDB)
 File Encoding         : 65001

 Date: 05/09/2026 00:46:16
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for article_requests
-- ----------------------------
DROP TABLE IF EXISTS `article_requests`;
CREATE TABLE `article_requests`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `category_id` int NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `user_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `user_email` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'pending',
  `admin_note` text CHARACTER SET utf8 COLLATE utf8_persian_ci NULL,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  `updated_at` datetime NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `category_id`(`category_id` ASC) USING BTREE,
  CONSTRAINT `article_requests_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_persian_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of article_requests
-- ----------------------------

-- ----------------------------
-- Table structure for articles
-- ----------------------------
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `content` longtext CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `excerpt` text CHARACTER SET utf8 COLLATE utf8_persian_ci NULL,
  `image` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `author_id` int NOT NULL,
  `category_id` int NULL DEFAULT NULL,
  `views` int NULL DEFAULT 0,
  `status` enum('published','draft','archived') CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'draft',
  `featured` tinyint(1) NULL DEFAULT 0,
  `tags` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `meta_description` text CHARACTER SET utf8 COLLATE utf8_persian_ci NULL,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  `updated_at` datetime NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `slug`(`slug` ASC) USING BTREE,
  INDEX `author_id`(`author_id` ASC) USING BTREE,
  INDEX `category_id`(`category_id` ASC) USING BTREE,
  CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_persian_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of articles
-- ----------------------------
INSERT INTO `articles` VALUES (1, 'تست مقاله', 'تست-مقاله', 'این یک مقاله تستی تکنولوژی مهم هست که برای افراد زیر 18 سال توصیه میشود ', 'تست مقاله تکنولوژیا', '1788017476_6a92fb4471aef.jpg', 1, 1, 20, 'published', 1, 'تکنولوژی ، هوش مصنوعی', 'تکنولوژی', 'تست مقاله های تکنولوژی', '2026-08-29 19:01:16', '2026-09-03 15:48:09');
INSERT INTO `articles` VALUES (2, 'test slg', 'test', 'test in maghaleh', 'saat slug', '1788021577_6a930b49a94c8.jpg', 1, 6, 1, 'draft', 0, 'تکنولوژی ، هوش مصنوعی', 'تکنولوژی', 'testet', '2026-08-29 20:09:37', '2026-08-29 20:27:05');
INSERT INTO `articles` VALUES (3, 'test slg', 'test-slg', 'test in maghaleh', 'saat slug', '1788021913_6a930c991ffe5.jpg', 1, 6, 0, 'draft', 0, 'تکنولوژی ، هوش مصنوعی', 'تکنولوژی', 'testet', '2026-08-29 20:15:13', '2026-08-29 20:15:13');

-- ----------------------------
-- Table structure for banners
-- ----------------------------
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `subtitle` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `position` enum('slider','banner','sidebar','header','footer') CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'slider',
  `sort_order` int NULL DEFAULT 0,
  `active` tinyint(1) NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_persian_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of banners
-- ----------------------------
INSERT INTO `banners` VALUES (1, 'بنر اصلی سایت', 'جدیدترین مقالات و دانستنی‌ها', '1788017802_6a92fc8a79095.jpg', '', 'slider', 1, 1, '2026-08-29 19:04:38');
INSERT INTO `banners` VALUES (2, 'تخفیف ویژه', '۵۰٪ تخفیف برای کاربران جدید', '1788045815_6a9369f785a9c.jpeg', '', 'slider', 2, 1, '2026-08-29 19:04:38');

-- ----------------------------
-- Table structure for categories
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `color` varchar(7) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT '#6C63FF',
  `parent_id` int NULL DEFAULT NULL,
  `icon` varchar(50) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_persian_ci NULL,
  `sort_order` int NULL DEFAULT 0,
  `active` tinyint(1) NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  `link_type` enum('internal','external') CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'internal',
  `external_link` varchar(500) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `link_target` tinyint(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `slug`(`slug` ASC) USING BTREE,
  INDEX `fk_categories_parent`(`parent_id` ASC) USING BTREE,
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8 COLLATE = utf8_persian_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of categories
-- ----------------------------
INSERT INTO `categories` VALUES (1, 'تکنولوژی', 'technology', '#6C63FF', NULL, 'fa-laptop', 'مقالات مربوط به دنیای تکنولوژی', 0, 1, '2026-08-29 18:01:24', 'internal', NULL, 0);
INSERT INTO `categories` VALUES (2, 'سبک زندگی', 'lifestyle', '#00BCD4', NULL, 'fa-leaf', 'سبک زندگی سالم و مدرن', 0, 1, '2026-08-29 18:01:24', 'internal', NULL, 0);
INSERT INTO `categories` VALUES (3, 'سلامت', 'health', '#4CAF50', NULL, 'fa-heartbeat', 'سلامت جسم و روان', 0, 1, '2026-08-29 18:01:24', 'internal', NULL, 0);
INSERT INTO `categories` VALUES (4, 'علم و دانش', 'science', '#9C27B0', NULL, 'fa-flask', 'اکتشافات علمی و دانش', 0, 1, '2026-08-29 18:01:24', 'internal', NULL, 0);
INSERT INTO `categories` VALUES (5, 'فرهنگ و هنر', 'culture', '#FF6B6B', NULL, 'fa-palette', 'فرهنگ، هنر و ادبیات', 0, 1, '2026-08-29 18:01:24', 'internal', NULL, 0);
INSERT INTO `categories` VALUES (6, 'اقتصاد', 'economy', '#F9A825', NULL, 'fa-chart-line', 'اقتصاد و بازار', 0, 1, '2026-08-29 18:01:24', 'internal', NULL, 0);
INSERT INTO `categories` VALUES (7, 'گردشگری', 'tourism', '#2196F3', NULL, 'fa-plane', 'سفر و گردشگری', 0, 1, '2026-08-29 18:01:24', 'internal', NULL, 0);
INSERT INTO `categories` VALUES (9, 'ریشه', 'root', '#6C63FF', NULL, NULL, NULL, 0, 1, '2026-08-30 01:31:34', 'internal', NULL, 0);
INSERT INTO `categories` VALUES (12, 'فروشگاه سایت', 'link-1788041282', '#6c63ff', NULL, 'fa-globe', '', 0, 0, '2026-08-30 01:38:02', 'external', 'https://www.derikana.com/shop/public', 0);
INSERT INTO `categories` VALUES (18, 'تست زیر دسته ها', 'تست-زیر-دسته-ها', '#c9a84c', 1, 'fa-mobile-alt', '', 1, 1, '2026-09-03 14:23:49', 'internal', '', 0);

-- ----------------------------
-- Table structure for comments
-- ----------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `user_id` int NULL DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'pending',
  `parent_id` int NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `article_id`(`article_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `parent_id`(`parent_id` ASC) USING BTREE,
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_persian_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of comments
-- ----------------------------

-- ----------------------------
-- Table structure for menus
-- ----------------------------
DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `position` enum('header','footer','sidebar') CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'header',
  `parent_id` int NULL DEFAULT NULL,
  `link_type` enum('page','category','external','custom') CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'custom',
  `link_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `page_id` int NULL DEFAULT NULL,
  `category_id` int NULL DEFAULT NULL,
  `sort_order` int NULL DEFAULT 0,
  `active` tinyint(1) NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `slug`(`slug` ASC) USING BTREE,
  INDEX `parent_id`(`parent_id` ASC) USING BTREE,
  CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_persian_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of menus
-- ----------------------------
INSERT INTO `menus` VALUES (1, 'خانه', 'home', 'header', NULL, 'custom', '/', NULL, NULL, 1, 1, '2026-08-29 18:01:24');
INSERT INTO `menus` VALUES (2, 'تکنولوژی', 'technology', 'header', NULL, 'category', '/category/technology', NULL, NULL, 2, 1, '2026-08-29 18:01:24');
INSERT INTO `menus` VALUES (3, 'سبک زندگی', 'lifestyle', 'header', NULL, 'category', '/category/lifestyle', NULL, NULL, 3, 1, '2026-08-29 18:01:24');
INSERT INTO `menus` VALUES (4, 'سلامت', 'health', 'header', NULL, 'category', '/category/health', NULL, NULL, 4, 1, '2026-08-29 18:01:24');
INSERT INTO `menus` VALUES (5, 'تماس با ما', 'contact', 'header', NULL, 'page', '/page/contact', NULL, NULL, 5, 1, '2026-08-29 18:01:24');

-- ----------------------------
-- Table structure for pages
-- ----------------------------
DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `content` longtext CHARACTER SET utf8 COLLATE utf8_persian_ci NULL,
  `parent_id` int NULL DEFAULT NULL,
  `template` varchar(50) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'default',
  `meta_title` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `meta_description` text CHARACTER SET utf8 COLLATE utf8_persian_ci NULL,
  `sort_order` int NULL DEFAULT 0,
  `active` tinyint(1) NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  `updated_at` datetime NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `slug`(`slug` ASC) USING BTREE,
  INDEX `parent_id`(`parent_id` ASC) USING BTREE,
  CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_persian_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of pages
-- ----------------------------

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8 COLLATE utf8_persian_ci NULL,
  `setting_group` varchar(50) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'general',
  `created_at` datetime NULL DEFAULT current_timestamp(),
  `updated_at` datetime NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `setting_key`(`setting_key` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8 COLLATE = utf8_persian_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of settings
-- ----------------------------
INSERT INTO `settings` VALUES (1, 'site_title', 'مجله آنوشا', 'general', '2026-08-29 18:01:24', '2026-08-29 18:01:24');
INSERT INTO `settings` VALUES (2, 'site_description', 'جدیدترین مقالات و دانستنی‌ها در حوزه تکنولوژی، سبک زندگی، سلامت و علم', 'general', '2026-08-29 18:01:24', '2026-08-29 18:01:24');
INSERT INTO `settings` VALUES (3, 'posts_per_page', '9', 'general', '2026-08-29 18:01:24', '2026-08-29 18:01:24');
INSERT INTO `settings` VALUES (4, 'site_language', 'fa', 'general', '2026-08-29 18:01:24', '2026-08-29 18:01:24');
INSERT INTO `settings` VALUES (5, 'footer_copyright', '© ۲۰۲۶ مجله آنلاین Anosha - تمامی حقوق محفوظ است', 'footer', '2026-08-29 18:01:24', '2026-08-29 18:01:24');
INSERT INTO `settings` VALUES (6, 'footer_phone', '۰۹۹۲۶۰۰۸۶۵۰', 'footer', '2026-08-29 18:01:24', '2026-08-29 18:01:24');
INSERT INTO `settings` VALUES (7, 'footer_email', 'info@anosha.com', 'footer', '2026-08-29 18:01:24', '2026-08-29 18:01:24');
INSERT INTO `settings` VALUES (8, 'footer_social', 'اینستاگرام, تلگرام, توییتر', 'footer', '2026-08-29 18:01:24', '2026-08-29 18:01:24');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
  `role` enum('admin','editor','user') CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'user',
  `avatar` varchar(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL,
  `registered_at` datetime NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT 'active',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username` ASC) USING BTREE,
  UNIQUE INDEX `email`(`email` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_persian_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'مدیر سیستم', 'admin', 'admin@anosha.com', '$2y$10$X65eAlUxkWCa/aJTvAYQUeJm6NSBqqxSTLZgJqmRR6OivSYc8ashW', 'admin', NULL, '2026-08-29 18:01:24', 'active');

SET FOREIGN_KEY_CHECKS = 1;
