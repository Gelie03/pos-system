/**
 * theme.js - light / dark mode switch.
 * The choice is saved in the browser (localStorage), so it is remembered
 * next time. This file is loaded in the <head> so the right theme is applied
 * before the page is drawn (no white flash).
 */
(function () {
    var KEY = 'pos-theme';

    function saved() {
        try { return localStorage.getItem(KEY); } catch (e) { return null; }
    }
    function save(value) {
        try { localStorage.setItem(KEY, value); } catch (e) { /* ignore */ }
    }

    function apply(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        // Update every toggle button's icon and label
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            var icon = btn.querySelector('i');
            var isDark = theme === 'dark';
            if (icon) icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            btn.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            btn.setAttribute('aria-label', btn.getAttribute('title'));
        });
    }

    // Start with the saved theme (default: light)
    var current = saved() === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-bs-theme', current);

    document.addEventListener('DOMContentLoaded', function () {
        apply(current);
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                current = (current === 'dark') ? 'light' : 'dark';
                save(current);
                apply(current);
            });
        });
    });
})();
