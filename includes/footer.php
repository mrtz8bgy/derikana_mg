<?php
// footer.php
?>
<!-- ===== BACK TO TOP ===== -->
<button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0, behavior:'smooth'})">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="footer-inner">
        <div>
            <h4>📖 درباره مجله</h4>
            <a href="page/about">درباره ما</a>
            <a href="page/contact">تماس با ما</a>
            <a href="page/privacy">قوانین</a>
            <a href="page/terms">شرایط استفاده</a>
        </div>
        <div>
            <h4>🛎️ خدمات</h4>
            <a href="#">راهنمای نویسندگان</a>
            <a href="#">پاسخ به سوالات</a>
            <a href="#">گزارش خطا</a>
            <a href="#">پیشنهاد موضوع</a>
        </div>
        <div>
            <h4>📞 تماس با ما</h4>
            <a class="contact-phone" href="tel:<?php echo getSetting('footer_phone') ?: '09926008650'; ?>">
                <i class="fas fa-phone"></i> <?php echo getSetting('footer_phone') ?: '۰۹۹۲۶۰۰۸۶۵۰'; ?>
            </a>
            <a style="margin-top:6px;"><i class="fas fa-envelope"></i> <?php echo getSetting('footer_email') ?: 'info@derikana.com'; ?></a>
            <a><i class="fas fa-map-marker-alt"></i> <?php echo getSetting('footer_address') ?: 'تهران، ایران'; ?></a>
        </div>
        <div>
            <h4>🌐 شبکه‌های اجتماعی</h4>
            <?php
            $socials = getSetting('footer_social') ?: 'اینستاگرام, تلگرام, توییتر';
            foreach (explode(',', $socials) as $social): ?>
                <a><i class="fas fa-hashtag"></i> <?php echo trim($social); ?></a>
            <?php endforeach; ?>
            <?php if (getSetting('social_instagram')): ?>
                <a href="<?php echo getSetting('social_instagram'); ?>" target="_blank"><i class="fab fa-instagram"></i> اینستاگرام</a>
            <?php endif; ?>
            <?php if (getSetting('social_telegram')): ?>
                <a href="<?php echo getSetting('social_telegram'); ?>" target="_blank"><i class="fab fa-telegram"></i> تلگرام</a>
            <?php endif; ?>
            <?php if (getSetting('social_twitter')): ?>
                <a href="<?php echo getSetting('social_twitter'); ?>" target="_blank"><i class="fab fa-twitter"></i> توییتر</a>
            <?php endif; ?>
            <?php if (getSetting('social_youtube')): ?>
                <a href="<?php echo getSetting('social_youtube'); ?>" target="_blank"><i class="fab fa-youtube"></i> یوتیوب</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer-bottom">
        <?php echo getSetting('footer_copyright') ?: '© ۲۰۲۶ مجله آنلاین دریکانا - تمامی حقوق محفوظ است'; ?>
        <span style="display:block; margin-top:3px; opacity:0.5; font-size:11px;">
            <i class="fas fa-code"></i> طراحی و توسعه توسط تیم دریکانا
        </span>
    </div>
</footer>

<!-- ===== Scripts ===== -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // ================================================================
    // READING PROGRESS
    // ================================================================
    const progressBar = document.getElementById('readingProgress');
    const readingPercent = document.getElementById('readingPercent');
    window.addEventListener('scroll', () => {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = (scrollTop / docHeight) * 100;
        if (progressBar) progressBar.style.width = progress + '%';
        if (readingPercent) {
            if (progress > 5) {
                readingPercent.classList.add('visible');
                readingPercent.textContent = Math.round(progress) + '%';
            } else {
                readingPercent.classList.remove('visible');
            }
        }
    });

    // ================================================================
    // HEADER SHRINK
    // ================================================================
    const header = document.getElementById('mainHeader');
    window.addEventListener('scroll', () => {
        if (header) {
            if (window.scrollY > 80) {
                header.classList.add('shrink');
            } else {
                header.classList.remove('shrink');
            }
        }
    });

    // ================================================================
    // AOS
    // ================================================================
    AOS.init({
        once: false,
        mirror: true,
        duration: 600,
        easing: 'ease-out-cubic',
        offset: 30,
    });

    // ================================================================
    // SLIDER
    // ================================================================
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    let autoSlideInterval;

    function showSlide(index) {
        if (slides.length === 0) return;
        if (index < 0) index = slides.length - 1;
        if (index >= slides.length) index = 0;
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        if (slides[index]) slides[index].classList.add('active');
        if (dots[index]) dots[index].classList.add('active');
        currentSlide = index;
    }

    function changeSlide(dir) { showSlide(currentSlide + dir); resetAutoSlide(); }
    function goToSlide(index) { showSlide(index); resetAutoSlide(); }

    function resetAutoSlide() {
        clearInterval(autoSlideInterval);
        if (slides.length > 1) {
            autoSlideInterval = setInterval(() => changeSlide(1), 5000);
        }
    }

    if (slides.length > 1) {
        autoSlideInterval = setInterval(() => changeSlide(1), 5000);
    }
    document.querySelector('.slider-container')?.addEventListener('mouseenter', () => clearInterval(autoSlideInterval));
    document.querySelector('.slider-container')?.addEventListener('mouseleave', resetAutoSlide);

    // ================================================================
    // THEME TOGGLE
    // ================================================================
    function toggleTheme() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeUI(newTheme);
    }

    function updateThemeUI(theme) {
        const icon = document.getElementById('themeIcon');
        const label = document.getElementById('themeLabel');
        if (icon && label) {
            if (theme === 'dark') {
                icon.textContent = '☀️';
                label.textContent = 'روشن';
            } else {
                icon.textContent = '🌙';
                label.textContent = 'تیره';
            }
        }
    }

    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeUI(savedTheme);

    // ================================================================
    // MOBILE MENU
    // ================================================================
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        if (menu) {
            menu.classList.toggle('open');
            document.body.style.overflow = menu.classList.contains('open') ? 'hidden' : '';
        }
    }

    // ================================================================
    // COUNTERS
    // ================================================================
    const counters = document.querySelectorAll('.counter');
    let countersAnimated = false;

    function animateCounters() {
        if (countersAnimated) return;
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            if (target === 0) return;
            let current = 0;
            const increment = Math.ceil(target / 40);
            const stepTime = 30;
            const updateCounter = () => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target + '+';
                    return;
                }
                counter.textContent = current + '+';
                setTimeout(updateCounter, stepTime);
            };
            updateCounter();
        });
        countersAnimated = true;
    }

    // ================================================================
    // BACK TO TOP
    // ================================================================
    const backToTop = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
        if (backToTop) {
            if (window.scrollY > 400) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        }
        if (window.scrollY < 600 && !countersAnimated) {
            animateCounters();
        }
    });

    // ================================================================
    // THEME COLOR CHANGE
    // ================================================================
    function changeThemeColor(color, darkColor, el) {
        document.querySelectorAll('.color-option').forEach(c => c.classList.remove('active'));
        if (el) el.classList.add('active');
        document.documentElement.style.setProperty('--gold', color);
        document.documentElement.style.setProperty('--gold-dark', darkColor);
        document.documentElement.style.setProperty('--gold-gradient', 
            `linear-gradient(135deg, ${color} 0%, ${color} 40%, ${darkColor} 100%)`);
        localStorage.setItem('themeColor', color);
        localStorage.setItem('themeColorDark', darkColor);
    }

    // ================================================================
    // 🌟 STARFIELD BACKGROUND (پویا و زنده)
    // ================================================================
    (function createStarfield() {
        const canvas = document.createElement('canvas');
        canvas.id = 'starfield';
        document.body.prepend(canvas);

        const ctx = canvas.getContext('2d');
        let stars = [];
        let W, H;

        function resize() {
            W = canvas.width = window.innerWidth;
            H = canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        class Star {
            constructor() {
                this.reset();
            }
            reset() {
                this.x = Math.random() * W;
                this.y = Math.random() * H;
                this.size = Math.random() * 2.2 + 0.3;
                this.speed = Math.random() * 0.8 + 0.2;
                this.opacity = Math.random() * 0.8 + 0.2;
                this.twinkleSpeed = Math.random() * 0.02 + 0.005;
                this.phase = Math.random() * Math.PI * 2;
            }
            update() {
                this.y += this.speed;
                this.phase += this.twinkleSpeed;
                this.currentOpacity = this.opacity * (0.6 + 0.4 * Math.sin(this.phase));
                if (this.y > H) {
                    this.reset();
                    this.y = -2;
                    this.x = Math.random() * W;
                }
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${this.currentOpacity})`;
                ctx.fill();
                if (this.size > 1.5) {
                    ctx.shadowColor = 'rgba(201, 168, 76, 0.15)';
                    ctx.shadowBlur = 8;
                    ctx.fill();
                    ctx.shadowBlur = 0;
                }
            }
        }

        const starCount = Math.min(300, Math.floor((W * H) / 4000));
        for (let i = 0; i < starCount; i++) {
            stars.push(new Star());
        }

        let shootingStars = [];

        class ShootingStar {
            constructor() {
                this.reset();
                this.active = true;
            }
            reset() {
                this.x = Math.random() * W * 0.8;
                this.y = Math.random() * H * 0.5;
                this.length = Math.random() * 120 + 60;
                this.speed = Math.random() * 6 + 4;
                this.angle = Math.random() * Math.PI * 0.4 + Math.PI * 0.1;
                this.opacity = 1;
                this.life = Math.random() * 60 + 30;
                this.maxLife = this.life;
                this.vx = Math.cos(this.angle) * this.speed;
                this.vy = Math.sin(this.angle) * this.speed;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                this.life--;
                this.opacity = this.life / this.maxLife;
                if (this.life <= 0 || this.x > W || this.y > H) {
                    this.active = false;
                }
            }
            draw() {
                if (!this.active) return;
                const gradient = ctx.createLinearGradient(
                    this.x, this.y,
                    this.x - this.vx * 2, this.y - this.vy * 2
                );
                gradient.addColorStop(0, `rgba(255, 255, 255, ${this.opacity * 0.9})`);
                gradient.addColorStop(0.3, `rgba(255, 215, 150, ${this.opacity * 0.6})`);
                gradient.addColorStop(1, `rgba(255, 255, 255, 0)`);
                ctx.beginPath();
                ctx.moveTo(this.x, this.y);
                ctx.lineTo(this.x - this.vx * 2.5, this.y - this.vy * 2.5);
                ctx.strokeStyle = gradient;
                ctx.lineWidth = 2;
                ctx.shadowColor = 'rgba(255, 215, 150, 0.3)';
                ctx.shadowBlur = 20;
                ctx.stroke();
                ctx.shadowBlur = 0;
            }
        }

        function animate() {
            const theme = document.documentElement.getAttribute('data-theme');
            if (theme === 'dark') {
                canvas.style.display = 'block';
                ctx.clearRect(0, 0, W, H);
                
                stars.forEach(star => {
                    star.update();
                    star.draw();
                });

                if (Math.random() < 0.003) {
                    shootingStars.push(new ShootingStar());
                }
                shootingStars = shootingStars.filter(s => s.active);
                shootingStars.forEach(s => {
                    s.update();
                    s.draw();
                });

            } else {
                canvas.style.display = 'none';
            }
            requestAnimationFrame(animate);
        }

        animate();

        const observer = new MutationObserver(() => {
            const theme = document.documentElement.getAttribute('data-theme');
            canvas.style.display = (theme === 'dark') ? 'block' : 'none';
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    })();

    // ================================================================
    // CONSOLE
    // ================================================================
    console.log('📰 مجله دریکانا - طلا، جواهرات و اشیای قیمتی');
    console.log('🌟 افکت ستاره‌های پویا فعال است');
    console.log('📱 مگا منو با زیرمجموعه‌ها فعال شد');
</script>
</body>
</html>