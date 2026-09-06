/* ================================================================
   DERIKANA ADMIN — shared behaviors
   ================================================================ */
(function () {
    'use strict';
    const root = document.documentElement;

    /* theme */
    window.toggleAdminTheme = function () {
        const next = root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        const to = next === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', to);
        try { localStorage.setItem('theme', to); } catch (e) {}
    };

    /* sidebar drawer (mobile) */
    window.toggleAdminSidebar = function () {
        const sb = document.querySelector('.admin-sidebar');
        if (!sb) return;
        const open = sb.classList.toggle('open');
        document.body.classList.toggle('sidebar-open', open);
    };
    function closeAdminSidebar() {
        const sb = document.querySelector('.admin-sidebar');
        if (sb) sb.classList.remove('open');
        document.body.classList.remove('sidebar-open');
    }
    document.addEventListener('click', (e) => {
        const sb = document.querySelector('.admin-sidebar');
        if (!sb || !sb.classList.contains('open')) return;
        if (!sb.contains(e.target) && !e.target.closest('.sidebar-toggle')) closeAdminSidebar();
    });

    /* make wide tables scrollable instead of breaking layout */
    document.querySelectorAll('.admin-table').forEach((t) => {
        if (t.parentElement.classList.contains('table-scroll')) return;
        const wrap = document.createElement('div');
        wrap.className = 'table-scroll';
        t.parentNode.insertBefore(wrap, t);
        wrap.appendChild(t);
    });

    /* close modals & drawer with Escape */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.show, .modal-overlay.active').forEach((m) => {
                m.classList.remove('show', 'active');
            });
            closeAdminSidebar();
        }
    });
})();
