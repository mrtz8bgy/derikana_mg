/* ================================================================
   DERIKANA — shared behaviors
   ================================================================ */
(function () {
    'use strict';

    const doc = document;
    const root = doc.documentElement;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------- theme ---------------- */
    function currentTheme() { return root.getAttribute('data-theme') === 'light' ? 'light' : 'dark'; }

    window.toggleTheme = function () {
        const next = currentTheme() === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('theme', next); } catch (e) {}
        doc.dispatchEvent(new CustomEvent('dm:theme', { detail: next }));
    };

    /* ---------------- header scroll state ---------------- */
    const header = doc.getElementById('mainHeader');
    const backToTop = doc.getElementById('backToTop');
    const progressBar = doc.getElementById('readingProgress');
    const readPercent = doc.getElementById('readingPercent');

    function onScroll() {
        const y = window.scrollY;
        if (header) header.classList.toggle('scrolled', y > 24);
        if (backToTop) backToTop.classList.toggle('visible', y > 480);

        const docH = doc.documentElement.scrollHeight - window.innerHeight;
        const p = docH > 0 ? (y / docH) * 100 : 0;
        if (progressBar) progressBar.style.width = p + '%';
        if (readPercent) {
            readPercent.classList.toggle('visible', p > 6 && p < 98);
            readPercent.textContent = Math.round(p) + '%';
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* ---------------- mobile drawer ---------------- */
    window.toggleMobileMenu = function (force) {
        const menu = doc.getElementById('mobileMenu');
        if (!menu) return;
        const open = typeof force === 'boolean' ? force : !menu.classList.contains('open');
        menu.classList.toggle('open', open);
        doc.body.style.overflow = open ? 'hidden' : '';
        const btn = doc.querySelector('.mobile-menu-toggle');
        if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    doc.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') window.toggleMobileMenu(false);
    });

    /* ---------------- slider ---------------- */
    const slides = Array.from(doc.querySelectorAll('.slider-container .slide'));
    const dots = Array.from(doc.querySelectorAll('.slider-dots .dot'));
    let slideIndex = 0;
    let slideTimer = null;

    function showSlide(i) {
        if (!slides.length) return;
        slideIndex = (i + slides.length) % slides.length;
        slides.forEach((s, k) => s.classList.toggle('active', k === slideIndex));
        dots.forEach((d, k) => d.classList.toggle('active', k === slideIndex));
    }
    function startAuto() {
        stopAuto();
        if (slides.length > 1 && !reducedMotion) slideTimer = setInterval(() => showSlide(slideIndex + 1), 6000);
    }
    function stopAuto() { if (slideTimer) { clearInterval(slideTimer); slideTimer = null; } }

    window.changeSlide = function (dir) { showSlide(slideIndex + dir); startAuto(); };
    window.goToSlide = function (i) { showSlide(i); startAuto(); };

    const sliderEl = doc.querySelector('.slider-container');
    if (sliderEl) {
        sliderEl.addEventListener('mouseenter', stopAuto);
        sliderEl.addEventListener('mouseleave', startAuto);
        /* swipe */
        let x0 = null;
        sliderEl.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; }, { passive: true });
        sliderEl.addEventListener('touchend', (e) => {
            if (x0 === null) return;
            const dx = e.changedTouches[0].clientX - x0;
            if (Math.abs(dx) > 48) window.changeSlide(dx > 0 ? -1 : 1); /* rtl: swipe feels natural reversed */
            x0 = null;
        }, { passive: true });
        startAuto();
    }

    /* ---------------- animated counters ---------------- */
    const counters = Array.from(doc.querySelectorAll('.counter'));
    let countersDone = false;
    function runCounters() {
        if (countersDone || !counters.length) return;
        countersDone = true;
        counters.forEach((el) => {
            const target = parseInt(el.getAttribute('data-target') || '0', 10);
            if (!target) { el.textContent = '0'; return; }
            if (reducedMotion) { el.textContent = target + '+'; return; }
            const t0 = performance.now();
            const dur = 1200;
            (function tick(now) {
                const k = Math.min(1, (now - t0) / dur);
                const eased = 1 - Math.pow(1 - k, 3);
                el.textContent = Math.round(target * eased) + (k === 1 ? '+' : '');
                if (k < 1) requestAnimationFrame(tick);
            })(t0);
        });
    }
    if (counters.length) {
        const io = new IntersectionObserver((entries) => {
            if (entries.some((en) => en.isIntersecting)) { runCounters(); io.disconnect(); }
        }, { threshold: .4 });
        io.observe(counters[0]);
    }

    /* ---------------- theme accent picker ---------------- */
    window.changeThemeColor = function (color, darkColor, el) {
        doc.querySelectorAll('.color-option').forEach((c) => c.classList.remove('active'));
        if (el) el.classList.add('active');
        root.style.setProperty('--gold', color);
        root.style.setProperty('--gold-3', darkColor);
        root.style.setProperty('--gold-grad', `linear-gradient(135deg, ${color} 0%, ${color} 45%, ${darkColor} 100%)`);
        root.style.setProperty('--gold-soft', color + '1f');
        try {
            localStorage.setItem('themeColor', color);
            localStorage.setItem('themeColorDark', darkColor);
        } catch (e) {}
    };
    (function restoreAccent() {
        try {
            const c = localStorage.getItem('themeColor');
            const d = localStorage.getItem('themeColorDark');
            if (c && d) {
                const active = doc.querySelector('.color-option[data-color="' + c + '"]');
                window.changeThemeColor(c, d, active || null);
            }
        } catch (e) {}
    })();

    /* ---------------- share helpers (article) ---------------- */
    window.shareArticle = function (network) {
        const url = encodeURIComponent(location.href);
        const title = encodeURIComponent(doc.title);
        const targets = {
            telegram: 'https://t.me/share/url?url=' + url + '&text=' + title,
            whatsapp: 'https://wa.me/?text=' + title + '%20' + url,
            twitter: 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title
        };
        if (targets[network]) window.open(targets[network], '_blank', 'noopener,width=680,height=560');
    };
    window.copyLink = function () {
        const done = () => toast('لینک مقاله کپی شد');
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(location.href).then(done).catch(done);
        } else {
            const ta = doc.createElement('textarea');
            ta.value = location.href; doc.body.appendChild(ta); ta.select();
            try { doc.execCommand('copy'); } catch (e) {}
            doc.body.removeChild(ta); done();
        }
    };

    /* ---------------- toast ---------------- */
    let toastEl = null, toastTimer = null;
    function toast(msg) {
        if (!toastEl) {
            toastEl = doc.createElement('div');
            toastEl.className = 'dm-toast';
            toastEl.setAttribute('role', 'status');
            doc.body.appendChild(toastEl);
            const st = toastEl.style;
            st.cssText = 'position:fixed;bottom:26px;right:50%;transform:translate(50%,16px);z-index:2500;' +
                'background:var(--surface);color:var(--ink);border:1px solid var(--line-2);' +
                'box-shadow:var(--shadow-2);border-radius:999px;padding:10px 22px;font-size:13px;font-weight:700;' +
                'opacity:0;transition:all .3s cubic-bezier(.22,1,.36,1);pointer-events:none;';
        }
        toastEl.textContent = msg;
        requestAnimationFrame(() => {
            toastEl.style.opacity = '1';
            toastEl.style.transform = 'translate(50%,0)';
        });
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toastEl.style.opacity = '0';
            toastEl.style.transform = 'translate(50%,16px)';
        }, 2200);
    }
    window.dmToast = toast;

    /* ---------------- password toggle (auth pages) ---------------- */
    window.togglePassword = function (inputId) {
        const input = doc.getElementById(inputId);
        if (!input) return;
        const icon = input.parentElement.querySelector('.password-toggle');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        if (icon) {
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
        }
    };

    /* ---------------- password strength (register) ---------------- */
    (function () {
        const input = doc.getElementById('passwordInput');
        const bar = doc.getElementById('passwordStrength');
        const text = doc.getElementById('passwordStrengthText');
        if (!input || !bar || !text) return;
        input.addEventListener('input', () => {
            const v = input.value;
            let score = 0;
            if (v.length >= 6) score++;
            if (v.length >= 10) score++;
            if (/[A-Zآ-ی]/.test(v) && /[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9آ-ی]/.test(v)) score++;
            bar.className = 'bar';
            if (!v) { text.textContent = 'رمز عبور را وارد کنید'; bar.style.width = '0'; return; }
            if (score <= 1) { bar.classList.add('weak'); text.textContent = 'ضعیف'; }
            else if (score <= 2) { bar.classList.add('medium'); text.textContent = 'متوسط'; }
            else { bar.classList.add('strong'); text.textContent = 'قوی'; }
        });
    })();

    /* ---------------- newsletter (footer) ---------------- */
    doc.addEventListener('submit', (e) => {
        const form = e.target.closest('.footer-newsletter');
        if (form) {
            e.preventDefault();
            toast('عضویت شما در خبرنامه ثبت شد ✦');
            form.reset();
        }
    });

    /* ---------------- AOS ---------------- */
    if (window.AOS) {
        window.AOS.init({
            once: true,
            duration: 620,
            easing: 'ease-out-cubic',
            offset: 40,
            disable: reducedMotion
        });
    }

    /* ---------------- starfield (dark only, gentle) ---------------- */
    (function starfield() {
        if (reducedMotion) return;
        const canvas = doc.getElementById('starfield');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let stars = [], W = 0, H = 0, raf = null;

        function resize() {
            W = canvas.width = window.innerWidth;
            H = canvas.height = window.innerHeight;
            const count = Math.min(110, Math.floor((W * H) / 16000));
            stars = Array.from({ length: count }, () => ({
                x: Math.random() * W,
                y: Math.random() * H,
                r: Math.random() * 1.4 + .3,
                s: Math.random() * .22 + .05,
                o: Math.random() * .5 + .15,
                ph: Math.random() * Math.PI * 2,
                tw: Math.random() * .015 + .004
            }));
        }
        function frame() {
            if (currentTheme() === 'dark' && !doc.hidden) {
                ctx.clearRect(0, 0, W, H);
                for (const st of stars) {
                    st.y += st.s; st.ph += st.tw;
                    if (st.y > H + 2) { st.y = -2; st.x = Math.random() * W; }
                    const a = st.o * (.65 + .35 * Math.sin(st.ph));
                    ctx.beginPath();
                    ctx.arc(st.x, st.y, st.r, 0, 6.2832);
                    ctx.fillStyle = 'rgba(233,212,162,' + a.toFixed(3) + ')';
                    ctx.fill();
                }
            }
            raf = requestAnimationFrame(frame);
        }
        window.addEventListener('resize', resize);
        resize();
        frame();
        doc.addEventListener('visibilitychange', () => {
            if (doc.hidden && raf) { cancelAnimationFrame(raf); raf = null; }
            else if (!raf) frame();
        });
    })();
})();
