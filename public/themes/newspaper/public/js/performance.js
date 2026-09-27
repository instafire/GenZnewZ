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

