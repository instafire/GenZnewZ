/**
 * Language switcher dropdown — vanilla JS rewrite of the original jQuery
 * version (2026-09). Toggles the dropdown inside .language-wrapper and closes
 * it when clicking anywhere else. Note: currently unused on this site (only
 * one language is configured, no dropdown renders) — kept for completeness.
 */
(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.language-wrapper .dropdown .dropdown-toggle').forEach((toggle) => {
            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                const wrapper = toggle.closest('.language-wrapper');
                const menu = wrapper && wrapper.querySelector('.dropdown-menu');
                if (toggle.classList.contains('active')) {
                    if (menu) { menu.style.display = 'none'; }
                    toggle.classList.remove('active');
                } else {
                    if (menu) { menu.style.display = ''; }
                    toggle.classList.add('active');
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.language-wrapper')) {
                document.querySelectorAll('.language-wrapper').forEach((wrapper) => {
                    const menu = wrapper.querySelector('.dropdown-menu');
                    const toggle = wrapper.querySelector('.dropdown-toggle');
                    if (menu) { menu.style.display = 'none'; }
                    if (toggle) { toggle.classList.remove('active'); }
                });
            }
        });
    });
})();
