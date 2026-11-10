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
(function () {
    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function fetchWithTimeout(url, options, timeoutMs) {
        var controller = typeof AbortController === 'function' ? new AbortController() : null;
        var requestOptions = Object.assign({}, options || {});
        var timeoutId;

        if (controller) {
            requestOptions.signal = controller.signal;
        }

        // Defer fetch initiation so malformed URLs and other synchronous errors
        // follow the same retry/error path as rejected network requests.
        var request = Promise.resolve().then(function () {
            return fetch(url, requestOptions);
        });
        var timeout = new Promise(function (_, reject) {
            timeoutId = window.setTimeout(function () {
                if (controller) {
                    controller.abort();
                }

                reject(new Error('Request timed out'));
            }, timeoutMs);
        });

        return Promise.race([request, timeout]).finally(function () {
            window.clearTimeout(timeoutId);
        });
    }

    function initLoadMoreCategories() {
        var loadMoreBtn = document.getElementById('loadMoreCategories');
        var categoriesContainer = document.getElementById('categories-container');
        if (!loadMoreBtn) {
            return;
        }

        var endpoint = loadMoreBtn.getAttribute('data-endpoint');
        if (!endpoint || !categoriesContainer) {
            return;
        }

        loadMoreBtn.addEventListener('click', function () {
            if (loadMoreBtn.dataset.loading === 'true') {
                return;
            }

            var nextOffset = parseInt(loadMoreBtn.getAttribute('data-next-offset') || '0', 10);
            var limit = parseInt(loadMoreBtn.getAttribute('data-limit') || '5', 10);
            var requestUrl = new URL(endpoint, window.location.origin);
            requestUrl.searchParams.set('offset', String(Number.isNaN(nextOffset) ? 0 : nextOffset));
            requestUrl.searchParams.set('limit', String(Number.isNaN(limit) ? 5 : limit));

            loadMoreBtn.dataset.loading = 'true';
            loadMoreBtn.disabled = true;
            loadMoreBtn.setAttribute('aria-busy', 'true');
            loadMoreBtn.textContent = 'Loading...';

            fetchWithTimeout(requestUrl.toString(), {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }, 10000)
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Category request failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    var html = typeof data.html === 'string' ? data.html.trim() : '';
                    if (html) {
                        categoriesContainer.insertAdjacentHTML('beforeend', html);
                    }

                    var fallbackOffset = (Number.isNaN(nextOffset) ? 0 : nextOffset) + (Number.isNaN(limit) ? 5 : limit);
                    var parsedNextOffset = Number.parseInt(String(data.nextOffset), 10);
                    var newOffset = Number.isNaN(parsedNextOffset) ? fallbackOffset : parsedNextOffset;
                    loadMoreBtn.setAttribute('data-next-offset', String(newOffset));

                    if (!data.hasMore || !html) {
                        loadMoreBtn.style.display = 'none';
                        return;
                    }

                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Load More Categories';
                })
                .catch(function () {
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Retry Load More';
                })
                .finally(function () {
                    loadMoreBtn.dataset.loading = 'false';
                    loadMoreBtn.setAttribute('aria-busy', 'false');
                });
        });
    }

    function initTickerAnimation() {
        var tickerContents = document.querySelectorAll('.rss-ticker-content');
        if (!tickerContents.length) {
            return;
        }

        tickerContents.forEach(function (content) {
            if (content.dataset.tickerInitialized === 'true') {
                return;
            }

            content.dataset.tickerInitialized = 'true';
            var originalHtml = content.innerHTML;
            content.innerHTML = originalHtml + originalHtml;
        });

        tickerContents.forEach(function (content) {
            var headlines = content.querySelectorAll('.rss-headline');
            var totalWidth = Array.from(headlines).reduce(function (accumulator, headline) {
                return accumulator + headline.offsetWidth + 60;
            }, 0);
            var duration = Math.max(80, totalWidth / 30);
            content.style.animationDuration = duration + 's';
        });
    }

    function hydrateAsyncWidgets() {
        var placeholders = Array.from(document.querySelectorAll('.async-widget-placeholder[data-widget-url]'));
        if (!placeholders.length) {
            return;
        }

        var loadAll = function () {
            placeholders.forEach(function (placeholder) {
                if (placeholder.dataset.widgetLoaded === 'true') {
                    return;
                }

                placeholder.dataset.widgetLoaded = 'true';

                fetchWithTimeout(placeholder.dataset.widgetUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }, 8000)
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Widget request failed');
                        }

                        return response.text();
                    })
                    .then(function (html) {
                        var markup = html.trim();
                        if (!markup) {
                            throw new Error('Widget response was empty');
                        }

                        var template = document.createElement('template');
                        template.innerHTML = markup;
                        var replacement = template.content.firstElementChild;

                        if (!replacement) {
                            throw new Error('Widget response could not be rendered');
                        }

                        var widgetTitle = placeholder.querySelector('.async-widget-title');
                        if (widgetTitle) {
                            replacement.setAttribute('role', 'region');
                            replacement.setAttribute('aria-label', widgetTitle.textContent.trim());
                        }
                        replacement.setAttribute('aria-busy', 'false');
                        placeholder.replaceWith(replacement);
                    })
                    .catch(function () {
                        placeholder.classList.add('async-widget-error');
                        placeholder.setAttribute('aria-busy', 'false');

                        var badge = placeholder.querySelector('.async-widget-badge');
                        if (badge) {
                            badge.textContent = 'Offline';
                        }

                        var message = placeholder.querySelector('.async-widget-message');
                        if (message) {
                            message.textContent = 'This widget is temporarily unavailable.';
                        }
                    });
            });
        };

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(loadAll, { timeout: 1500 });
            return;
        }

        window.setTimeout(loadAll, 250);
    }

    function initNewsletterForm() {
        var form = document.querySelector('.homepage-newsletter-form');
        if (!form || typeof window.fetch !== 'function') {
            return;
        }

        var status = form.querySelector('.homepage-newsletter-status');
        var submit = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Joining...';
            }

            if (status) {
                status.classList.remove('is-error');
                status.textContent = '';
            }

            fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: new URLSearchParams(new FormData(form)).toString()
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            throw new Error(payload.message || 'Subscription failed.');
                        }

                        return payload;
                    });
                })
                .then(function (payload) {
                    if (status) {
                        status.textContent = payload.message || 'You are subscribed.';
                    }
                    form.reset();
                })
                .catch(function (error) {
                    if (status) {
                        status.classList.add('is-error');
                        status.textContent = error.message || 'Subscription failed. Please try again.';
                    }
                })
                .finally(function () {
                    if (submit) {
                        submit.disabled = false;
                        submit.innerHTML = 'Subscribe <span aria-hidden="true">→</span>';
                    }
                });
        });
    }

    onReady(function () {
        initLoadMoreCategories();
        initTickerAnimation();
        hydrateAsyncWidgets();
        initNewsletterForm();
    });
}());

/**
 * web-vitals.js — Core Web Vitals → GA4 dataLayer capture.
 *
 * Measures LCP, CLS and INP with PerformanceObserver and pushes them to
 * window.dataLayer as `web_vitals` events. Reaches GA4 automatically when a
 * GA4 tag is present (gtag.js) or managed by Cloudflare Zaraz (which forwards
 * dataLayer events to configured GA4 destinations). If no GA4 tag exists the
 * pushes are harmless no-ops — the script stays dormant until then.
 *
 * Registered site-wide from config.php (footer container, ~2 KB, no deps).
 * View the data in GA4: Reports → Engagement → Events → web_vitals, or build
 * an Explorations report on the `metric_name` / `metric_value` custom dims.
 * Based on the standard patterns from web.dev/articles/vitals-ga4.
 */
(function () {
    'use strict';

    if (!('PerformanceObserver' in window)) {
        return;
    }

    window.dataLayer = window.dataLayer || [];

    // Canonical gtag command queueing — identical to the official snippet's
    // `function gtag(){dataLayer.push(arguments);}`. Plain gtag.js (no GTM)
    // only transmits Arguments-format commands; {event:...} objects and arrays
    // are GTM conventions it ignores. The object push below is kept for
    // Cloudflare Zaraz / GTM containers, which read that shape instead.
    function gv() {
        window.dataLayer.push(arguments);
    }

    var metric = { name: '', value: 0, id: '' };
    var reported = {};

    function metricId() {
        return 'v3-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
    }

    // One id per page visit stitches the LCP/CLS/INP pushes together in GA4.
    var pageMetricId = metricId();

    function push(name, value, rating) {
        // Only report once per metric, and only if GA4 (or Zaraz) is present.
        if (reported[name] || typeof value !== 'number' || value < 0) {
            return;
        }
        reported[name] = true;
        var rounded = Math.round(name === 'CLS' ? value * 1000 : value);
        var params = {
            web_vitals_metric_name: name,
            web_vitals_metric_value: rounded,
            web_vitals_metric_rating: rating,
            web_vitals_metric_id: pageMetricId,
        };
        // Format 1: gtag command (Arguments) — transmitted by plain gtag.js.
        gv('event', 'web_vitals', params);
        // Format 2: plain object — what GTM containers and Zaraz read as a
        // custom event. Ignored by plain gtag.js, harmless alongside it.
        window.dataLayer.push(Object.assign({ event: 'web_vitals' }, params));
    }

    function rating(name, value) {
        var thresholds = {
            LCP: [2500, 4000],
            CLS: [0.1, 0.25],
            INP: [200, 500],
        };
        var t = thresholds[name];
        if (!t) {
            return 'unknown';
        }
        return value <= t[0] ? 'good' : value <= t[1] ? 'needs-improvement' : 'poor';
    }

    // --- LCP: largest contentful paint, finalized on page hide -------------
    var lcpValue = 0;
    try {
        new PerformanceObserver(function (list) {
            var entries = list.getEntries();
            var last = entries[entries.length - 1];
            if (last) {
                lcpValue = last.startTime;
            }
        }).observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (e) { /* unsupported */ }

    // --- CLS: layout shifts, session-window capped --------------------------
    var clsValue = 0;
    try {
        new PerformanceObserver(function (list) {
            for (var i = 0; i < list.getEntries().length; i++) {
                var entry = list.getEntries()[i];
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
        }).observe({ type: 'layout-shift', buffered: true });
    } catch (e) { /* unsupported */ }

    // --- INP: worst interaction latency -------------------------------------
    var inpValue = 0;
    try {
        new PerformanceObserver(function (list) {
            for (var i = 0; i < list.getEntries().length; i++) {
                var entry = list.getEntries()[i];
                var duration = entry.duration || 0;
                if (duration > inpValue) {
                    inpValue = duration;
                }
            }
        }).observe({ type: 'event', durationThreshold: 16, buffered: true });
    } catch (e) { /* unsupported */ }

    // --- Flush on page hide (sendBeacon when GA4 provides a transport) ------
    function flush() {
        if (lcpValue > 0) {
            metric.name = 'LCP';
            metric.value = lcpValue;
            push('LCP', lcpValue, rating('LCP', lcpValue));
        }
        push('CLS', clsValue, rating('CLS', clsValue));
        if (inpValue > 0) {
            metric.name = 'INP';
            metric.value = inpValue;
            push('INP', inpValue, rating('INP', inpValue));
        }
    }

    window.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            flush();
        }
    });
    window.addEventListener('pagehide', flush);
})();
/**
 * Live search suggestions for the header search overlay.
 *
 * Typing queries /api/search/suggest (debounced) and renders a keyboard
 * navigable listbox. With the endpoint unavailable or the script absent, the
 * plain form still submits to the search page as before.
 */
(function () {
    'use strict';

    var RECENT_KEY = 'gzn_recent_searches';
    var RECENT_LIMIT = 5;
    var DEBOUNCE_MS = 180;
    var MIN_LENGTH = 2;

    function readRecent() {
        try {
            var raw = window.localStorage.getItem(RECENT_KEY);
            var parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.slice(0, RECENT_LIMIT) : [];
        } catch (error) {
            return [];
        }
    }

    function rememberSearch(term) {
        var trimmed = String(term || '').trim();

        if (trimmed.length < MIN_LENGTH) {
            return;
        }

        var list = readRecent().filter(function (item) {
            return item.toLowerCase() !== trimmed.toLowerCase();
        });

        list.unshift(trimmed);

        try {
            window.localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, RECENT_LIMIT)));
        } catch (error) {
            // Storage unavailable; suggestions still work for this session.
        }
    }

    function init() {
        var overlay = document.getElementById('searchOverlay');

        if (!overlay) {
            return;
        }

        var form = overlay.querySelector('form');
        var input = overlay.querySelector('.search-input');
        var panel = document.getElementById('searchSuggest');

        if (!form || !input || !panel) {
            return;
        }

        var endpoint = panel.getAttribute('data-suggest-endpoint') || '/api/search/suggest';
        var status = panel.querySelector('[data-suggest-status]');
        var listBox = panel.querySelector('[data-suggest-list]');
        var options = [];
        var activeIndex = -1;
        var controller = null;
        var debounceTimer = null;
        var requestSeq = 0;

        function announce(message) {
            if (status) {
                status.textContent = message || '';
            }
        }

        function clearOptions() {
            options = [];
            activeIndex = -1;
            listBox.innerHTML = '';
        }

        function setActive(index) {
            if (!options.length) {
                return;
            }

            if (options[activeIndex]) {
                options[activeIndex].classList.remove('is-active');
                options[activeIndex].setAttribute('aria-selected', 'false');
            }

            // Wrap around in both directions so held arrow keys keep moving.
            activeIndex = (index + options.length) % options.length;

            var option = options[activeIndex];
            option.classList.add('is-active');
            option.setAttribute('aria-selected', 'true');

            if (input) {
                input.setAttribute('aria-activedescendant', option.id);
            }
        }

        function makeOption(suggestion, index) {
            var option = document.createElement('a');
            option.className = 'search-suggest-option';
            option.id = 'search-suggest-option-' + index;
            option.href = suggestion.url;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');

            var badge = document.createElement('span');
            badge.className = 'search-suggest-badge search-suggest-badge--' + (suggestion.type || 'post');
            badge.textContent = suggestion.type === 'category' ? 'Topic' : 'Story';
            option.appendChild(badge);

            var title = document.createElement('span');
            title.className = 'search-suggest-title';
            title.textContent = suggestion.title;
            option.appendChild(title);

            if (suggestion.meta) {
                var meta = document.createElement('span');
                meta.className = 'search-suggest-meta';
                meta.textContent = suggestion.meta;
                option.appendChild(meta);
            }

            option.addEventListener('mouseenter', function () {
                setActive(index);
            });

            option.addEventListener('click', function () {
                rememberSearch(suggestion.title);
            });

            return option;
        }

        function makeSection(title) {
            var section = document.createElement('div');
            section.className = 'search-suggest-section';
            section.textContent = title;
            return section;
        }

        function renderSection(title, items, startIndex) {
            listBox.appendChild(makeSection(title));

            items.forEach(function (item, offset) {
                var option = makeOption(item, startIndex + offset);
                options.push(option);
                listBox.appendChild(option);
            });
        }

        function render(payload) {
            clearOptions();

            var suggestions = payload.suggestions || [];
            var trending = payload.trending || [];
            var term = (payload.query || '').trim();

            if (!term && !suggestions.length) {
                var recent = readRecent();

                if (recent.length) {
                    var recentSection = makeSection('Recent searches');
                    listBox.appendChild(recentSection);

                    var recentWrap = document.createElement('div');
                    recentWrap.className = 'search-suggest-recents';

                    recent.forEach(function (entry) {
                        var chip = document.createElement('button');
                        chip.type = 'button';
                        chip.className = 'search-suggest-chip';
                        chip.textContent = entry;
                        chip.addEventListener('click', function () {
                            input.value = entry;
                            submitSearch(entry);
                        });
                        recentWrap.appendChild(chip);
                    });

                    listBox.appendChild(recentWrap);
                }

                if (trending.length) {
                    renderSection('Trending topics', trending, options.length);
                }

                panel.hidden = !listBox.childNodes.length;

                return;
            }

            if (suggestions.length) {
                renderSection('Suggestions', suggestions, options.length);
            }

            // Always leave a way to run the full search, even with no matches.
            var searchAll = document.createElement('button');
            searchAll.type = 'button';
            searchAll.className = 'search-suggest-option search-suggest-all';
            searchAll.textContent = term ? 'Search all stories for “' + term + '”' : 'Browse all stories';
            searchAll.addEventListener('click', function () {
                submitSearch(input.value);
            });

            listBox.appendChild(searchAll);

            panel.hidden = false;

            if (suggestions.length) {
                announce(suggestions.length + ' suggestion' + (suggestions.length === 1 ? '' : 's') + ' available');
            } else {
                announce('No matching suggestions');
            }
        }

        function submitSearch(term) {
            rememberSearch(term);

            if (term && !input.value) {
                input.value = term;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }

        function fetchSuggestions(term) {
            if (controller) {
                controller.abort();
            }

            if (typeof AbortController === 'function') {
                controller = new AbortController();
            }

            var sequence = ++requestSeq;
            var url = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(term);

            var requestOptions = { headers: { Accept: 'application/json' } };

            if (controller) {
                requestOptions.signal = controller.signal;
            }

            fetch(url, requestOptions)
                .then(function (response) {
                    return response.ok ? response.json() : null;
                })
                .then(function (payload) {
                    // Ignore responses that arrive out of order.
                    if (!payload || sequence !== requestSeq) {
                        return;
                    }

                    render(payload);
                })
                .catch(function () {
                    // Aborts and network failures are expected; the form still
                    // works without suggestions.
                });
        }

        input.addEventListener('input', function () {
            var term = input.value.trim();

            window.clearTimeout(debounceTimer);

            if (term.length < MIN_LENGTH) {
                debounceTimer = window.setTimeout(function () {
                    fetchSuggestions('');
                }, DEBOUNCE_MS);

                return;
            }

            debounceTimer = window.setTimeout(function () {
                fetchSuggestions(term);
            }, DEBOUNCE_MS);
        });

        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', 'searchSuggestList');
        input.setAttribute('aria-expanded', 'false');

        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setActive(activeIndex + 1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(activeIndex - 1);
                return;
            }

            if (event.key === 'Enter' && activeIndex > -1 && options[activeIndex]) {
                event.preventDefault();
                rememberSearch(input.value);
                window.location.href = options[activeIndex].href;
                return;
            }

            if (event.key === 'Enter') {
                rememberSearch(input.value);
            }
        });

        form.addEventListener('submit', function () {
            rememberSearch(input.value);
        });

        // Warm the panel with trending topics as soon as the overlay opens.
        document.addEventListener('gzn:search-opened', function () {
            fetchSuggestions('');
        });

        panel.addEventListener('click', function (event) {
            if (event.target === panel) {
                input.focus();
            }
        });
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    }
}());
(function () {
    var root = document.getElementById('gzn-news-chat');
    if (!root) {
        return;
    }

    var endpoint = root.getAttribute('data-endpoint');
    var toggleButton = root.querySelector('[data-chat-toggle]');
    var closeButton = root.querySelector('[data-chat-close]');
    var panel = root.querySelector('[data-chat-panel]');
    var messagesEl = root.querySelector('[data-chat-messages]');
    var form = root.querySelector('[data-chat-form]');
    var input = root.querySelector('[data-chat-input]');
    var sendButton = root.querySelector('[data-chat-send]');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var state = {
        open: false,
        waiting: false,
        messages: []
    };
    var noticeSelector = '#cookie-consent-banner, .js-site-notice, .site-notice';
    var noticeObserver = null;
    var noticeResizeObserver = null;
    var noticeWatchAttempts = 0;

    function getVisibleNoticeHeight() {
        var notices = document.querySelectorAll(noticeSelector);
        if (!notices.length) {
            return 0;
        }

        return Array.prototype.reduce.call(notices, function (maxHeight, notice) {
            var styles = window.getComputedStyle(notice);
            if (styles.display === 'none' || styles.visibility === 'hidden' || Number(styles.opacity || 1) === 0) {
                return maxHeight;
            }

            var rect = notice.getBoundingClientRect();
            if (!rect || rect.height < 1) {
                return maxHeight;
            }

            if (rect.bottom < window.innerHeight - 2) {
                return maxHeight;
            }

            return Math.max(maxHeight, Math.ceil(rect.height + 12));
        }, 0);
    }

    function applyNoticeOffset() {
        var noticeHeight = getVisibleNoticeHeight();
        root.style.setProperty('--gzn-news-chat-notice-offset', noticeHeight + 'px');
    }

    function watchNoticeElement() {
        var notices = document.querySelectorAll(noticeSelector);

        if (!notices.length) {
            noticeWatchAttempts += 1;
            if (noticeWatchAttempts <= 20) {
                setTimeout(watchNoticeElement, 250);
            }

            return;
        }

        if (noticeObserver) {
            noticeObserver.disconnect();
        }

        noticeObserver = new MutationObserver(applyNoticeOffset);

        if (window.ResizeObserver) {
            if (noticeResizeObserver) {
                noticeResizeObserver.disconnect();
            }

            noticeResizeObserver = new ResizeObserver(applyNoticeOffset);
        }

        Array.prototype.forEach.call(notices, function (notice) {
            noticeObserver.observe(notice, {
                attributes: true,
                attributeFilter: ['class', 'style']
            });

            if (noticeResizeObserver) {
                noticeResizeObserver.observe(notice);
            }
        });
    }

    function scheduleNoticeOffsetRefresh() {
        applyNoticeOffset();
        setTimeout(applyNoticeOffset, 250);
        setTimeout(applyNoticeOffset, 1000);
    }

    function scrollMessagesToEnd() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function addMessage(role, text, className) {
        var message = document.createElement('div');
        message.className = 'gzn-news-chat__message ' + (className || ('gzn-news-chat__message--' + role));
        message.textContent = text;
        messagesEl.appendChild(message);
        scrollMessagesToEnd();
    }

    function ensureWelcomeMessage() {
        if (state.messages.length > 0) {
            return;
        }

        var welcome = 'Hi, I am the GenZ NewZ news assistant. Ask me about latest headlines, major stories, or where to find GenZ NewZ coverage on a topic.';
        state.messages.push({ role: 'assistant', content: welcome });
        addMessage('assistant', welcome);
    }

    function setOpen(isOpen) {
        state.open = isOpen;
        root.classList.toggle('gzn-news-chat--open', isOpen);
        panel.hidden = !isOpen;
        toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        if (isOpen) {
            ensureWelcomeMessage();
            setTimeout(function () {
                input.focus();
            }, 20);
        } else {
            input.blur();
        }
    }

    function setSending(isSending) {
        state.waiting = isSending;
        sendButton.disabled = isSending;
        input.disabled = isSending;
    }

    async function sendMessage(text) {
        if (!endpoint) {
            addMessage('assistant', 'Chat endpoint is not configured right now.', 'gzn-news-chat__message--status');
            return;
        }

        state.messages.push({ role: 'user', content: text });
        addMessage('user', text);
        setSending(true);
        addMessage('assistant', 'Thinking...', 'gzn-news-chat__message--status');

        try {
            var recentMessages = state.messages.slice(-10).map(function (item) {
                return {
                    role: item.role,
                    content: item.content
                };
            });

            var headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }

            var response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: headers,
                body: JSON.stringify({
                    messages: recentMessages
                })
            });

            var data = await response.json();

            messagesEl.lastChild && messagesEl.lastChild.remove();

            if (!response.ok || !data || data.success !== true || !data.message) {
                var errorText = (data && data.message) ? data.message : 'The assistant is temporarily unavailable.';
                addMessage('assistant', errorText, 'gzn-news-chat__message--status');
                return;
            }

            state.messages.push({ role: 'assistant', content: data.message });
            addMessage('assistant', data.message);
        } catch (error) {
            messagesEl.lastChild && messagesEl.lastChild.remove();
            addMessage('assistant', 'The assistant is temporarily unavailable. Please try again shortly.', 'gzn-news-chat__message--status');
        } finally {
            setSending(false);
            input.focus();
        }
    }

    toggleButton.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        setOpen(!state.open);
    });

    closeButton.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        setOpen(false);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (state.waiting) {
            return;
        }

        var text = input.value.trim();
        if (!text) {
            return;
        }

        input.value = '';
        sendMessage(text);
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    document.addEventListener('click', function (event) {
        if (!state.open) {
            return;
        }

        if (!root.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && state.open) {
            setOpen(false);
        }
    });

    window.addEventListener('resize', applyNoticeOffset, { passive: true });
    document.addEventListener('click', function () {
        setTimeout(applyNoticeOffset, 60);
    });

    watchNoticeElement();
    scheduleNoticeOffsetRefresh();
})();
