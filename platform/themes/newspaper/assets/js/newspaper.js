(function () {
    'use strict';

    window.dataLayer = window.dataLayer || [];

    function setInert(element, isInert) {
        if (!element) return;
        if (isInert) {
            element.setAttribute('inert', '');
        } else {
            element.removeAttribute('inert');
        }
        element.setAttribute('aria-hidden', isInert ? 'true' : 'false');
    }

    function restoreFocus(selector) {
        var trigger = document.querySelector(selector);
        if (trigger) trigger.focus();
    }

    window.openMobileMenu = function () {
        var menu = document.getElementById('mobileMenu');
        var overlay = document.querySelector('.mobile-menu-overlay');
        if (!menu || !overlay) return;
        menu.classList.add('active');
        overlay.classList.add('active');
        setInert(menu, false);
        setInert(overlay, false);
        document.body.style.overflow = 'hidden';
        var closeButton = menu.querySelector('.mobile-menu-close');
        if (closeButton) closeButton.focus();
    };

    window.closeMobileMenu = function () {
        var menu = document.getElementById('mobileMenu');
        var overlay = document.querySelector('.mobile-menu-overlay');
        if (menu) { menu.classList.remove('active'); setInert(menu, true); }
        if (overlay) { overlay.classList.remove('active'); setInert(overlay, true); }
        if (!document.querySelector('.search-overlay.active')) document.body.style.overflow = '';
        restoreFocus('[aria-controls="mobileMenu"]');
    };

    window.openSearch = function () {
        var overlay = document.getElementById('searchOverlay');
        var input = overlay && overlay.querySelector('.search-input');
        if (!overlay) return;
        overlay.classList.add('active');
        setInert(overlay, false);
        document.body.style.overflow = 'hidden';
        if (input) window.setTimeout(function () { input.focus(); }, 0);
        // search-suggest.js warms its trending-topics panel on this event.
        document.dispatchEvent(new Event('gzn:search-opened'));
    };

    window.closeSearch = function () {
        var overlay = document.getElementById('searchOverlay');
        if (overlay) { overlay.classList.remove('active'); setInert(overlay, true); }
        if (!document.querySelector('.mobile-menu.active')) document.body.style.overflow = '';
        restoreFocus('[aria-controls="searchOverlay"]');
    };

    // An explicit choice always wins. With no stored choice we follow the OS
    // preference: previously this only ever turned dark mode on for readers who
    // had clicked the toggle, so `prefers-color-scheme: dark` was ignored.
    function preferredDarkMode() {
        var stored = window.localStorage ? localStorage.getItem('darkMode') : null;
        if (stored === 'enabled') return true;
        if (stored === 'disabled') return false;
        return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    }

    function applyDarkMode(enabled) {
        var toggle = document.getElementById('darkModeToggle');
        var icon = toggle && toggle.querySelector('.dark-mode-icon');
        document.body.classList.toggle('dark-mode', enabled);
        if (toggle) toggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        if (icon) icon.textContent = enabled ? '☀️' : '🌙';
    }

    function initDarkMode() {
        var toggle = document.getElementById('darkModeToggle');
        if (!toggle) return;

        applyDarkMode(preferredDarkMode());

        toggle.addEventListener('click', function () {
            var isDarkMode = !document.body.classList.contains('dark-mode');
            applyDarkMode(isDarkMode);
            if (window.localStorage) localStorage.setItem('darkMode', isDarkMode ? 'enabled' : 'disabled');
            window.dataLayer.push({ event: 'dark_mode_toggle', dark_mode_enabled: isDarkMode });
        });

        // Keep following the OS, but only until the reader makes their own choice.
        if (window.matchMedia) {
            var query = window.matchMedia('(prefers-color-scheme: dark)');
            var onSchemeChange = function (event) {
                var stored = window.localStorage ? localStorage.getItem('darkMode') : null;
                if (stored !== 'enabled' && stored !== 'disabled') applyDarkMode(event.matches);
            };
            if (query.addEventListener) query.addEventListener('change', onSchemeChange);
            else if (query.addListener) query.addListener(onSchemeChange);
        }
    }

    function init() {
        initDarkMode();
        var searchOverlay = document.querySelector('.search-overlay');
        if (searchOverlay) {
            searchOverlay.addEventListener('click', function (event) {
                if (event.target === searchOverlay) window.closeSearch();
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                window.closeMobileMenu();
                window.closeSearch();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
}());
