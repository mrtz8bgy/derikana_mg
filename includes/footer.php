<?php
// footer.php — فوتر یکپارچه‌ی دریکانا
$footer_site_title = getSetting('site_title') ?: 'مجله دریکانا';
?>
</main>

<!-- ===== BACK TO TOP ===== -->
<button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0, behavior:'smooth'})" aria-label="بازگشت به بالا">
    <i class="fas fa-arrow-up" aria-hidden="true"></i>
</button>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container">

        <div class="footer-top">
            <div class="footer-brand">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M6 3h12l4 6-10 12L2 9l4-6zm1.6 2L5 9h4.2l1.3-4H7.6zm5.9 0L12.2 9h3.9l-1.3-4h-1.3zm4 0L18.8 9H21l-2.6-4h-.9zM4.2 11l6.4 7.7L8.9 11H4.2zm6.6 0l1.2 8.6L13.2 11h-2.4zm4.3 0l-1.7 7.7L19.8 11h-4.7z"/></svg>
                </span>
                <div>
                    <span class="brand-fa"><?php echo htmlspecialchars($footer_site_title); ?></span>
                    <p>روایت‌گر دانستنی‌ها و راهنماهای معتبر درباره طلا، جواهرات، سبک زندگی و سرمایه‌گذاری؛ با قلمی که به جزئیات احترام می‌گذارد.</p>
                </div>
            </div>
            <form class="footer-newsletter" aria-label="خبرنامه">
                <input type="email" name="email" placeholder="ایمیل شما برای عضویت در خبرنامه…" required aria-label="ایمیل" />
                <button type="submit" class="btn"><i class="fas fa-envelope-open-text"></i> عضویت</button>
            </form>
        </div>

        <div class="footer-grid">
            <div>
                <h4><i class="fas fa-book-open"></i> درباره مجله</h4>
                <a href="page/about"><i class="fas fa-angle-left"></i> درباره ما</a>
                <a href="page/contact"><i class="fas fa-angle-left"></i> تماس با ما</a>
                <a href="page/privacy"><i class="fas fa-angle-left"></i> حریم خصوصی</a>
                <a href="page/terms"><i class="fas fa-angle-left"></i> شرایط استفاده</a>
            </div>
            <div>
                <h4><i class="fas fa-concierge-bell"></i> خدمات</h4>
                <a href="#"><i class="fas fa-angle-left"></i> راهنمای نویسندگان</a>
                <a href="#"><i class="fas fa-angle-left"></i> پاسخ به سوالات</a>
                <a href="#"><i class="fas fa-angle-left"></i> گزارش خطا</a>
                <a href="#"><i class="fas fa-angle-left"></i> پیشنهاد موضوع</a>
            </div>
            <div>
                <h4><i class="fas fa-address-card"></i> تماس با ما</h4>
                <a class="contact-phone" href="tel:<?php echo preg_replace('/[^0-9+]/', '', getSetting('footer_phone') ?: '09926008650'); ?>">
                    <i class="fas fa-phone"></i> <?php echo getSetting('footer_phone') ?: '۰۹۹۲۶۰۰۸۶۵۰'; ?>
                </a>
                <a href="mailto:<?php echo getSetting('footer_email') ?: 'info@derikana.com'; ?>"><i class="fas fa-angle-left"></i> <?php echo getSetting('footer_email') ?: 'info@derikana.com'; ?></a>
                <a href="#"><i class="fas fa-angle-left"></i> <?php echo getSetting('footer_address') ?: 'تهران، ایران'; ?></a>
            </div>
            <div>
                <h4><i class="fas fa-share-nodes"></i> ما را دنبال کنید</h4>
                <div class="footer-social">
                    <?php if (getSetting('social_instagram')): ?>
                        <a href="<?php echo getSetting('social_instagram'); ?>" target="_blank" rel="noopener" aria-label="اینستاگرام"><i class="fab fa-instagram"></i></a>
                    <?php else: ?><a href="#" aria-label="اینستاگرام"><i class="fab fa-instagram"></i></a><?php endif; ?>
                    <?php if (getSetting('social_telegram')): ?>
                        <a href="<?php echo getSetting('social_telegram'); ?>" target="_blank" rel="noopener" aria-label="تلگرام"><i class="fab fa-telegram"></i></a>
                    <?php else: ?><a href="#" aria-label="تلگرام"><i class="fab fa-telegram"></i></a><?php endif; ?>
                    <?php if (getSetting('social_twitter')): ?>
                        <a href="<?php echo getSetting('social_twitter'); ?>" target="_blank" rel="noopener" aria-label="توییتر"><i class="fab fa-x-twitter"></i></a>
                    <?php else: ?><a href="#" aria-label="توییتر"><i class="fab fa-x-twitter"></i></a><?php endif; ?>
                    <?php if (getSetting('social_youtube')): ?>
                        <a href="<?php echo getSetting('social_youtube'); ?>" target="_blank" rel="noopener" aria-label="یوتیوب"><i class="fab fa-youtube"></i></a>
                    <?php else: ?><a href="#" aria-label="یوتیوب"><i class="fab fa-youtube"></i></a><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <span><?php echo getSetting('footer_copyright') ?: '© ۲۰۲۶ مجله دریکانا — تمامی حقوق محفوظ است'; ?></span>
            <span class="dev"><i class="fas fa-gem"></i> طراحی و توسعه توسط تیم دریکانا</span>
        </div>
    </div>
</footer>

<!-- ===== Scripts ===== -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
