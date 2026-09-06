# مجله دریکانا — Derikana Magazine

مجله‌ی آنلاین فارسی (RTL) با PHP + MySQL؛ شامل مقالات، دسته‌بندی‌های تودرتو با مگا منو، بنر اسلایدر، نظرات، نویسندگان، جست‌وجو و پنل مدیریت.

## ✨ بازطراحی رابط کاربری (نسخه‌ی «Luxe Editorial»)

رابط کاربری بخش عمومی سایت با یک سیستم طراحی یکپارچه بازسازی شده است:

- **هویت بصری واحد** در همه‌ی صفحه‌ها (خانه، مقاله، دسته، جست‌وجو، ورود/ثبت‌نام، نویسنده) با پالت «مرکب و طلا» در پوسته‌ی تاریک و «سفید و برنج» در پوسته‌ی روشن.
- **توکن‌های طراحی** (رنگ، شعاع، سایه، حرکت) به‌صورت CSS Custom Properties در `assets/css/style.css`.
- **تایپوگرافی**: وزیرمتن برای متن فارسی + Playfair Display برای اعداد/نشان لاتین؛ سلسله‌مرتب اندازه با `clamp()`.
- **کامپوننت‌های مجله‌ای**: کارت lead تمام‌عرض، گرید کارت‌ها، ستون کناری ویجت‌دار، هدر صفحه (page-head) با چیپ‌های آمار، فوتر چهارستونه با خبرنامه.
- **دسترس‌پذیری**: skip-link، `aria`، focus-visible، احترام به `prefers-reduced-motion`، لینک‌های واقعی به‌جای `onclick`.
- **عملکرد**: حذف استایل‌های inline پراکنده و تجمیع در `style.css` + `pages.css`؛ اسکریپت مشترک در `assets/js/main.js`؛ bootstrap پوسته در `<head>` برای حذف پرش تم.

### ساختار فایل‌های فرانت

```
assets/css/style.css   → توکن‌ها + هدر/ناو/هرو/اسلایدر/کارت‌ها/سایدبار/فوتر/واکنش‌گرایی
assets/css/pages.css   → مقاله (نثر)، دسته، جست‌وجو، ورود/ثبت‌نام، نویسنده، نظرات
assets/js/main.js      → تم، اسلایدر، درایور موبایل، شمارنده‌ها، starfield، اشتراک، toast
includes/header.php    → هدر + مگا منو + درایور موبایل (HTML معتبر)
includes/footer.php    → فوتر + خبرنامه + اسکریپت‌ها
```

## 🚀 راه‌اندازی محلی

```bash
# 1) دیتابیس
mysql -e "CREATE DATABASE derikana_magazine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql derikana_magazine < config/derikana_magazine.sql

# 2) یکدست‌سازی charset (پیشنهادی — برای پشتیبانی ایموجی در نظرات/مقالات)
mysql derikana_magazine < dev/fix-charset.sql

# 3) داده‌ی نمونه برای پیش‌نمایش (اختیاری)
cp uploads/seed/*.jpg uploads/articles/ && cp uploads/seed/*.jpg uploads/banners/
mysql derikana_magazine < dev/seed-demo.sql

# 4) تنظیم اتصال در config/database.php و اجرا
php -S localhost:8080
```

> ورود ادمین نمونه مطابق dump اصلی پروژه است (پنل `admin/` دست‌نخورده باقی مانده است).

## 🗂 نکته‌ها

- صفحه‌ی دسته، مقالات **زیردسته‌ها** را هم تجمیعی نمایش می‌دهد.
- `author.php` اضافه شد (پیش‌تر پیوند نویسندگان به صفحه‌ی موجود نمی‌رسید).
- تصاویر نمونه‌ی دمو در `uploads/seed/` هستند و توسط seed به `uploads/articles|banners` کپی می‌شوند.
- پنل مدیریت (`admin/`) در این مرحله بازطراحی نشده است.
