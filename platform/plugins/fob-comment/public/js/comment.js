/**
 * fob-comment frontend handler — vanilla JS rewrite of the original jQuery
 * version (2026-09). Same behaviors, no jQuery dependency:
 *   - restore name/email/website/cookie-consent from fob-comment-* cookies
 *   - load the comment list (GET window.fobComment.listUrl)
 *   - submit the form (POST FormData), toast via window.Theme, reload list
 *   - reply-in-place with cancel link, pagination interception
 */
(function () {
    'use strict';

    var replying = false;
    var formSectionBackup = '';

    function setCookie(name, value, days) {
        var expires = '';
        if (days != null) {
            var d = new Date();
            d.setDate(d.getDate() + days);
            expires = '; expires=' + d.toUTCString();
        }
        document.cookie = 'fob-comment-' + name + '=' + encodeURIComponent(value) + expires + '; path=/';
    }

    function getCookie(name) {
        var m = document.cookie.match(new RegExp('(^| )fob-comment-' + name + '=([^;]*)(;|$)'));
        return m ? decodeURIComponent(m[2]) : null;
    }

    function delCookie(name) {
        document.cookie = 'fob-comment-' + name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
    }

    function restoreCookies(form) {
        Array.prototype.forEach.call(form.querySelectorAll('input'), function (input) {
            var saved = getCookie(input.name);
            if (!saved) { return; }
            if (input.name === 'cookie_consent') {
                input.checked = true;
            } else if (!input.value) {
                input.value = saved;
            }
        });
    }

    function loadList(url) {
        fetch(url || (window.fobComment && window.fobComment.listUrl), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.error) {
                    if (window.Theme && window.Theme.showError) { window.Theme.showError(res.message); }
                    return;
                }
                var section = document.querySelector('.fob-comment-list-section');
                if (!section) { return; }
                if (!res.data.comments || res.data.comments.total < 1) {
                    section.style.display = 'none';
                    return;
                }
                section.style.display = '';
                var title = document.querySelector('.fob-comment-list-title');
                var wrapper = document.querySelector('.fob-comment-list-wrapper');
                if (title) { title.textContent = res.data.title; }
                if (wrapper) { wrapper.innerHTML = res.data.html; }
            })
            .catch(function () { /* list stays as server-rendered */ });
    }

    function scrollToFirst() {
        var section = document.querySelector('.fob-comment-list-section');
        if (section && section.scrollIntoView) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('.fob-comment-form');
        if (!form) { return; }
        event.preventDefault();
        event.stopPropagation();

        var data = new FormData(form);
        var consentBox = form.querySelector('input[type="checkbox"][name="cookie_consent"]');
        var consent = consentBox && consentBox.checked;

        fetch(form.action, {
            method: 'POST',
            body: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (window.Theme) {
                    if (res.error) {
                        if (window.Theme.showError) { window.Theme.showError(res.message); }
                    } else if (window.Theme.showSuccess) {
                        window.Theme.showSuccess(res.message);
                    }
                }
                if (!res.error) {
                    if (consent) {
                        setCookie('name', data.get('name'), 365);
                        setCookie('email', data.get('email'), 365);
                        setCookie('website', data.get('website'), 365);
                        setCookie('cookie_consent', 1, 365);
                        var content = form.querySelector('textarea[name="content"]');
                        if (content) { content.value = ''; }
                    } else {
                        form.reset();
                        delCookie('name');
                        delCookie('email');
                        delCookie('website');
                        delCookie('cookie_consent');
                    }
                    loadList();
                }
            })
            .catch(function (err) {
                if (window.Theme && window.Theme.handleError) {
                    window.Theme.handleError(err);
                }
            });
    });

    document.addEventListener('click', function (event) {
        // Pagination links
        var pagerLink = event.target.closest('.fob-comment-pagination a');
        if (pagerLink) {
            event.preventDefault();
            if (pagerLink.href) {
                loadList(pagerLink.href);
                scrollToFirst();
            }
            return;
        }

        // Reply: move the form under the comment being replied to
        var replyBtn = event.target.closest('.fob-comment-item-reply');
        if (replyBtn) {
            event.preventDefault();
            var current = document.querySelector('.fob-comment-form-section');
            if (current && !replying) {
                formSectionBackup = current.outerHTML;
                current.remove();
            }
            var section = document.querySelector('.fob-comment-form-section');
            if (section) {
                section.remove();
            }
            var item = replyBtn.closest('.fob-comment-item');
            if (item) {
                item.insertAdjacentHTML('afterend', formSectionBackup);
            }
            var moved = document.querySelector('.fob-comment-form-section');
            if (moved) {
                var titleSpan = moved.querySelector('.fob-comment-form-title span');
                if (titleSpan && replyBtn.dataset.replyTo) {
                    titleSpan.textContent = replyBtn.dataset.replyTo;
                }
                var title = moved.querySelector('.fob-comment-form-title');
                var oldCancel = moved.querySelector('.cancel-comment-reply-link');
                if (oldCancel) { oldCancel.remove(); }
                if (title && replyBtn.dataset.cancelReply) {
                    var link = document.createElement('a');
                    link.href = '#';
                    link.className = 'cancel-comment-reply-link';
                    link.rel = 'nofollow';
                    link.textContent = replyBtn.dataset.cancelReply;
                    title.appendChild(link);
                }
                var form = moved.querySelector('form');
                if (form && replyBtn.href) { form.action = replyBtn.href; }
            }
            replying = true;
            return;
        }

        // Cancel reply: restore the original form position
        var cancelLink = event.target.closest('.cancel-comment-reply-link');
        if (cancelLink) {
            event.preventDefault();
            replying = false;
            var open = document.querySelector('.fob-comment-form-section');
            if (open) { open.remove(); }
            var list = document.querySelector('.fob-comment-list-section');
            if (list && formSectionBackup) {
                list.insertAdjacentHTML('afterend', formSectionBackup);
            }
            restoreCookies(document.querySelector('.fob-comment-form'));
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('.fob-comment-form');
        if (form) { restoreCookies(form); }
        loadList();
    });
}());
