/**
 * Modern Keyboard Navigation Enhancement
 * Provides smooth keyboard navigation without obtrusive skip links
 */

(function() {
    'use strict';

    // Track if user is using keyboard
    let isKeyboardUser = false;

    // Detect keyboard usage
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            isKeyboardUser = true;
            document.body.classList.add('keyboard-user');
        }
    });

    // Detect mouse usage
    document.addEventListener('mousedown', function() {
        isKeyboardUser = false;
        document.body.classList.remove('keyboard-user');
    });

    // Keyboard shortcuts for power users
    document.addEventListener('keydown', function(e) {
        // Only if not typing in input field
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }

        // Ctrl/Cmd + K: Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('[name="q"], [type="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // G then H: Go to homepage
        if (lastKey === 'g' && e.key === 'h') {
            window.location.href = '/';
        }

        // G then L: Go to live events
        if (lastKey === 'g' && e.key === 'l') {
            window.location.href = '/live-world-events';
        }

        // ? : Show keyboard shortcuts help
        if (e.key === '?' && !e.shiftKey) {
            showKeyboardShortcutsHelp();
        }

        lastKey = e.key;
        setTimeout(() => lastKey = null, 1000);
    });

    let lastKey = null;

    // Smooth scroll to main content on first tab
    let tabCount = 0;
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab' && tabCount === 0) {
            tabCount++;
            const mainContent = document.getElementById('main-content');
            if (mainContent && !isElementInViewport(mainContent)) {
                // Show a subtle indicator
                showNavigationHint();
            }
        }
    });

    // Check if element is in viewport
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    // Show subtle navigation hint for keyboard users
    function showNavigationHint() {
        const hint = document.createElement('div');
        hint.className = 'keyboard-nav-hint';
        hint.innerHTML = `
            <div class="hint-content">
                <span class="hint-icon">⌨️</span>
                <span class="hint-text">Press <kbd>Tab</kbd> to navigate · <kbd>?</kbd> for shortcuts</span>
            </div>
        `;
        document.body.appendChild(hint);

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .keyboard-nav-hint {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #ffffff;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                animation: slideInRight 0.3s ease, fadeOut 0.3s ease 3s forwards;
                font-family: 'Inter', sans-serif;
                font-size: 0.9rem;
            }

            .hint-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .hint-icon {
                font-size: 1.2rem;
            }

            kbd {
                background: rgba(255, 255, 255, 0.2);
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 4px;
                padding: 2px 6px;
                font-family: 'Monaco', 'Courier New', monospace;
                font-size: 0.85rem;
                font-weight: 600;
            }

            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes fadeOut {
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
        `;
        document.head.appendChild(style);

        // Remove after animation
        setTimeout(() => hint.remove(), 3500);
    }

    // Show keyboard shortcuts overlay
    function showKeyboardShortcutsHelp() {
        // Check if already showing
        if (document.getElementById('keyboard-shortcuts-modal')) {
            return;
        }

        const modal = document.createElement('div');
        modal.id = 'keyboard-shortcuts-modal';
        modal.innerHTML = `
            <div class="shortcuts-overlay" onclick="this.parentElement.remove()">
                <div class="shortcuts-panel" onclick="event.stopPropagation()">
                    <div class="shortcuts-header">
                        <h3>⌨️ Keyboard Shortcuts</h3>
                        <button class="close-btn" onclick="this.closest('#keyboard-shortcuts-modal').remove()" aria-label="Close">×</button>
                    </div>
                    <div class="shortcuts-list">
                        <div class="shortcut-item">
                            <kbd>Tab</kbd>
                            <span>Navigate through links</span>
                        </div>
                        <div class="shortcut-item">
                            <kbd>Ctrl</kbd> + <kbd>K</kbd>
                            <span>Focus search</span>
                        </div>
                        <div class="shortcut-item">
                            <kbd>G</kbd> then <kbd>H</kbd>
                            <span>Go to homepage</span>
                        </div>
                        <div class="shortcut-item">
                            <kbd>G</kbd> then <kbd>L</kbd>
                            <span>Go to live events</span>
                        </div>
                        <div class="shortcut-item">
                            <kbd>?</kbd>
                            <span>Show this help</span>
                        </div>
                        <div class="shortcut-item">
                            <kbd>Esc</kbd>
                            <span>Close dialogs</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .shortcuts-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.2s ease;
            }

            .shortcuts-panel {
                background: #ffffff;
                border-radius: 12px;
                padding: 30px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: scaleIn 0.3s ease;
            }

            body.dark-mode .shortcuts-panel {
                background: #2a2a2a;
                color: #f0f0f0;
            }

            .shortcuts-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #e2e2e2;
            }

            body.dark-mode .shortcuts-header {
                border-bottom-color: #444;
            }

            .shortcuts-header h3 {
                margin: 0;
                font-family: 'Space Grotesk', sans-serif;
                font-size: 1.5rem;
                color: #121212;
            }

            body.dark-mode .shortcuts-header h3 {
                color: #f0f0f0;
            }

            .close-btn {
                background: none;
                border: none;
                font-size: 2rem;
                color: #666;
                cursor: pointer;
                line-height: 1;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
                transition: all 0.2s;
            }

            .close-btn:hover {
                background: #f0f0f0;
                color: #121212;
            }

            body.dark-mode .close-btn:hover {
                background: #444;
                color: #f0f0f0;
            }

            .shortcuts-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .shortcut-item {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 10px;
                border-radius: 6px;
                transition: background 0.2s;
            }

            .shortcut-item:hover {
                background: #f8f9fa;
            }

            body.dark-mode .shortcut-item:hover {
                background: #333;
            }

            .shortcut-item kbd {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #ffffff;
                border: none;
                border-radius: 6px;
                padding: 6px 12px;
                font-family: 'Monaco', 'Courier New', monospace;
                font-size: 0.85rem;
                font-weight: 600;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                min-width: 40px;
                text-align: center;
            }

            .shortcut-item span {
                flex: 1;
                color: #666;
                font-size: 0.95rem;
            }

            body.dark-mode .shortcut-item span {
                color: #aaa;
            }

            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @keyframes scaleIn {
                from {
                    opacity: 0;
                    transform: scale(0.9);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(modal);

        // Close on Esc key
        const closeOnEsc = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', closeOnEsc);
            }
        };
        document.addEventListener('keydown', closeOnEsc);
    }

    // Enhanced focus management - smooth scroll to focused elements
    document.addEventListener('focus', function(e) {
        if (isKeyboardUser && e.target.matches('a, button, input, textarea')) {
            // Smooth scroll focused element into view if needed
            if (!isElementInViewport(e.target)) {
                e.target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }
    }, true);

})();
