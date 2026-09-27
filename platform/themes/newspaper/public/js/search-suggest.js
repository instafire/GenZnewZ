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
