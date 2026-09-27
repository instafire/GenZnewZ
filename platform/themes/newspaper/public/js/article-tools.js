/**
 * Article reading tools: progress bar, auto outline, sharing and save-for-later.
 *
 * Everything is progressive enhancement — with this script absent the article
 * still reads correctly, it just loses the progress bar, outline, copy-link
 * feedback and the saved-stories list.
 */
(function () {
    'use strict';

    var SAVED_KEY = 'gzn_saved_articles';
    var SAVED_LIMIT = 60;
    var MIN_OUTLINE_HEADINGS = 3;

    function onReady(callback) {
        if (document.readyState !== 'loading') {
            callback();
        } else {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        }
    }

    function readSaved() {
        try {
            var raw = window.localStorage.getItem(SAVED_KEY);
            var parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            // Private browsing and disabled storage both throw; degrade to an
            // empty list rather than breaking the rest of the page.
            return [];
        }
    }

    function writeSaved(list) {
        try {
            window.localStorage.setItem(SAVED_KEY, JSON.stringify(list.slice(0, SAVED_LIMIT)));
            return true;
        } catch (error) {
            return false;
        }
    }

    function isSaved(url) {
        return readSaved().some(function (item) {
            return item && item.url === url;
        });
    }

    /* ---------------------------------------------------------------------
     * Reading progress
     * ------------------------------------------------------------------- */

    function initReadingProgress() {
        var bar = document.getElementById('readingProgressBar');
        var wrapper = document.getElementById('readingProgress');
        var article = document.querySelector('.article-body');

        if (!bar || !wrapper || !article) {
            return;
        }

        wrapper.setAttribute('aria-hidden', 'false');

        var ticking = false;

        function update() {
            ticking = false;

            var rect = article.getBoundingClientRect();
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var articleTop = rect.top + scrollTop;
            var articleHeight = article.offsetHeight;
            var viewportHeight = window.innerHeight;

            // Progress completes as the end of the body reaches the bottom of
            // the viewport, which is when a reader has actually finished it.
            var travelled = scrollTop + viewportHeight - articleTop;
            var total = articleHeight;

            if (total <= 0) {
                return;
            }

            var ratio = travelled / total;

            if (ratio < 0) {
                ratio = 0;
            }

            if (ratio > 1) {
                ratio = 1;
            }

            var percent = Math.round(ratio * 100);
            bar.style.width = percent + '%';
            wrapper.setAttribute('aria-valuenow', String(percent));
        }

        function requestUpdate() {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(update);
        }

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        update();
    }

    /* ---------------------------------------------------------------------
     * Auto outline
     * ------------------------------------------------------------------- */

    function slugify(text) {
        return text
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .slice(0, 60) || 'section';
    }

    function initOutline() {
        var nav = document.getElementById('articleOutline');
        var list = document.getElementById('articleOutlineList');
        var article = document.querySelector('.article-body');

        if (!nav || !list || !article) {
            return;
        }

        var headings = Array.prototype.slice
            .call(article.querySelectorAll('h2, h3'))
            .filter(function (heading) {
                return heading.textContent.trim().length > 0;
            });

        // A two-heading story does not need a table of contents; showing one
        // adds noise above the fold.
        if (headings.length < MIN_OUTLINE_HEADINGS) {
            return;
        }

        var usedIds = {};

        headings.forEach(function (heading, index) {
            var id = heading.id;

            if (!id) {
                id = slugify(heading.textContent);
                var base = id;
                var suffix = 2;

                while (usedIds[id] || document.getElementById(id)) {
                    id = base + '-' + suffix;
                    suffix += 1;
                }

                heading.id = id;
            }

            usedIds[id] = true;

            var item = document.createElement('li');
            item.className = 'article-outline-item' + (heading.tagName === 'H3' ? ' article-outline-item--sub' : '');

            var link = document.createElement('a');
            link.href = '#' + id;
            link.className = 'article-outline-link';
            link.textContent = heading.textContent.trim();
            link.setAttribute('data-outline-index', String(index));

            item.appendChild(link);
            list.appendChild(item);
        });

        nav.hidden = false;

        // Track which section the reader is in using the headings themselves,
        // so the highlight matches what is on screen.
        var links = Array.prototype.slice.call(list.querySelectorAll('.article-outline-link'));
        var current = -1;

        if (!('IntersectionObserver' in window)) {
            return;
        }

        function setActive(index) {
            if (index === current) {
                return;
            }

            if (links[current]) {
                links[current].classList.remove('is-active');
                links[current].removeAttribute('aria-current');
            }

            current = index;

            if (links[current]) {
                links[current].classList.add('is-active');
                links[current].setAttribute('aria-current', 'true');
            }
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    var index = headings.indexOf(entry.target);
                    setActive(index);
                });
            },
            { rootMargin: '-15% 0px -70% 0px', threshold: 0 }
        );

        headings.forEach(function (heading) {
            observer.observe(heading);
        });
    }

    /* ---------------------------------------------------------------------
     * Sharing
     * ------------------------------------------------------------------- */

    function initShare() {
        var nativeButton = document.querySelector('[data-share-native]');
        var container = document.querySelector('[data-article-tools]');

        if (!container) {
            return;
        }

        if (nativeButton && typeof navigator.share === 'function') {
            nativeButton.hidden = false;

            // The native button supersedes the explicit "Share" heading; keeping
            // both would read as "Share SHARE".
            var label = document.querySelector('.article-share-label');

            if (label) {
                label.hidden = true;
                label.style.display = 'none';
            }

            nativeButton.addEventListener('click', function () {
                var title = document.title;

                navigator
                    .share({ title: title, url: window.location.href })
                    .catch(function () {
                        // A cancelled share sheet rejects; that is not an error
                        // worth surfacing.
                    });
            });
        }

        var copyButton = document.querySelector('[data-copy-link]');

        if (!copyButton) {
            return;
        }

        copyButton.addEventListener('click', function () {
            var label = copyButton.querySelector('[data-copy-label]');
            var original = label ? label.textContent : '';

            var announce = function (message) {
                if (!label) {
                    return;
                }

                label.textContent = message;
                window.setTimeout(function () {
                    label.textContent = original;
                }, 2000);
            };

            var url = window.location.href;

            var fallbackCopy = function () {
                var field = document.createElement('textarea');
                field.value = url;
                field.setAttribute('readonly', 'readonly');
                field.style.position = 'fixed';
                field.style.opacity = '0';
                document.body.appendChild(field);
                field.select();

                var copied = false;

                try {
                    copied = document.execCommand('copy');
                } catch (error) {
                    copied = false;
                }

                document.body.removeChild(field);
                announce(copied ? 'Copied' : 'Press Ctrl+C');
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function () {
                    announce('Copied');
                }, fallbackCopy);

                return;
            }

            fallbackCopy();
        });
    }

    /* ---------------------------------------------------------------------
     * Save for later
     * ------------------------------------------------------------------- */

    function updateSaveButton(button) {
        var url = button.getAttribute('data-article-url');
        var label = button.querySelector('[data-save-label]');
        var saved = isSaved(url);

        button.setAttribute('aria-pressed', saved ? 'true' : 'false');
        button.classList.toggle('is-saved', saved);

        if (label) {
            label.textContent = saved ? 'Saved' : 'Save';
        }
    }

    function initSaveButton() {
        var button = document.querySelector('[data-save-article]');

        if (!button) {
            return;
        }

        updateSaveButton(button);

        button.addEventListener('click', function () {
            var url = button.getAttribute('data-article-url');
            var list = readSaved().filter(function (item) {
                return item && item.url !== url;
            });

            if (!isSaved(url)) {
                list.unshift({
                    url: url,
                    title: button.getAttribute('data-article-title') || document.title,
                    image: button.getAttribute('data-article-image') || '',
                    savedAt: Date.now(),
                });
            }

            if (!writeSaved(list)) {
                return;
            }

            updateSaveButton(button);
            document.dispatchEvent(new CustomEvent('gzn:saved-changed'));
        });
    }

    /* ---------------------------------------------------------------------
     * Saved stories drawer (rendered wherever the header mounts it)
     * ------------------------------------------------------------------- */

    function initSavedDrawer() {
        var drawer = document.getElementById('savedDrawer');

        if (!drawer) {
            return;
        }

        var list = drawer.querySelector('[data-saved-list]');
        var empty = drawer.querySelector('[data-saved-empty]');
        var countBadges = document.querySelectorAll('[data-saved-count]');

        function render() {
            var items = readSaved();

            countBadges.forEach(function (badge) {
                badge.textContent = String(items.length);
                badge.hidden = items.length === 0;
            });

            if (!list) {
                return;
            }

            list.innerHTML = '';

            if (empty) {
                empty.hidden = items.length > 0;
            }

            items.forEach(function (item) {
                var entry = document.createElement('li');
                entry.className = 'saved-item';

                var link = document.createElement('a');
                link.className = 'saved-item-link';
                link.href = item.url;

                if (item.image) {
                    var img = document.createElement('img');
                    img.className = 'saved-item-image';
                    img.src = item.image;
                    img.alt = '';
                    img.loading = 'lazy';
                    img.width = 64;
                    img.height = 48;
                    link.appendChild(img);
                }

                var title = document.createElement('span');
                title.className = 'saved-item-title';
                title.textContent = item.title;
                link.appendChild(title);

                entry.appendChild(link);

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'saved-item-remove';
                remove.setAttribute('aria-label', 'Remove "' + item.title + '" from saved stories');
                remove.textContent = '×';
                remove.addEventListener('click', function () {
                    writeSaved(
                        readSaved().filter(function (candidate) {
                            return candidate && candidate.url !== item.url;
                        })
                    );
                    render();
                });

                entry.appendChild(remove);
                list.appendChild(entry);
            });
        }

        var toggle = document.querySelector('[data-saved-toggle]');

        if (toggle) {
            toggle.addEventListener('click', function () {
                var open = drawer.hasAttribute('hidden');

                if (open) {
                    drawer.removeAttribute('hidden');
                } else {
                    drawer.setAttribute('hidden', 'hidden');
                }

                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        var close = drawer.querySelector('[data-saved-close]');

        if (close) {
            close.addEventListener('click', function () {
                drawer.setAttribute('hidden', 'hidden');

                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        render();

        document.addEventListener('gzn:saved-changed', render);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !drawer.hasAttribute('hidden')) {
                drawer.setAttribute('hidden', 'hidden');
            }
        });
    }

    onReady(function () {
        initReadingProgress();
        initOutline();
        initShare();
        initSaveButton();
        initSavedDrawer();
    });
}());
