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
