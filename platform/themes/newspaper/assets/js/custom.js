$(document).ready(function () {
    // Collapse Main NAv.
    $('.collap-main-nav, .close-nav').on('click', function () {
        $('.main-nav').toggleClass('main-nav-active');
    });

    // Toggle Icon.
    $(window).scroll(function () {
        if ($(this).scrollTop() > 200) {
            $('.icon-back-top').addClass('icon-back-top-active');
        } else {
            $('.icon-back-top').removeClass('icon-back-top-active');
        }
    });

    // Back Top.
    $('.icon-back-top').click(function () {
        $('body,html').animate({scrollTop: 0}, 'slow');
    });

    $('.form-popup').fancybox({
        maxWidth: 800,
        maxHeight: 600,
        fitToView: false,
        width: '70%',
        height: '70%',
        autoSize: false,
        closeClick: false,
        openEffect: 'none',
        closeEffect: 'none'
    });

    let superSearch = $('.super-search');
    let buttonSearch = $('.search-btn');

    buttonSearch.on('click', event => {
        event.preventDefault();
        if (buttonSearch.hasClass('active')) {
            superSearch.removeClass('active');
            buttonSearch.removeClass('active');
            $('body').removeClass('overflow');
            $('.quick-search > .form-control').focus();
        } else {
            superSearch.addClass('active');
            $('body').addClass('overflow');
            buttonSearch.addClass('active');
        }
    });

    // Initialize behavior tracking for personalized recommendations
    if (window.BehaviorTracker) {
        window.BehaviorTracker.init();
    }
});

/**
 * Behavior Tracker - Lightweight client for tracking post views and clicks
 * to power personalized recommendations. Respects Do Not Track.
 */
(function () {
    'use strict';

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function generateVisitorId() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'v_' + Math.random().toString(36).substring(2) + '_' + Date.now().toString(36);
    }

    function getVisitorId() {
        let visitorId = null;
        try {
            visitorId = localStorage.getItem('gz_visitor_id');
        } catch (e) {
            // localStorage may be unavailable in private mode
        }

        if (!visitorId) {
            visitorId = generateVisitorId();
            try {
                localStorage.setItem('gz_visitor_id', visitorId);
            } catch (e) {
                // Ignore storage errors
            }
        }

        // Mirror to a cookie so the server can read it for personalized rendering
        var isSecure = window.location.protocol === 'https:';
        document.cookie = 'gz_visitor_id=' + encodeURIComponent(visitorId)
            + '; path=/'
            + '; max-age=31536000'
            + '; SameSite=Lax'
            + (isSecure ? '; Secure' : '');

        return visitorId;
    }

    function isDoNotTrack() {
        return navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.globalPrivacyControl === true;
    }

    function track(postId, action, source) {
        if (isDoNotTrack()) {
            return;
        }

        if (!postId) {
            return;
        }

        if (typeof window !== 'undefined' && window.location && window.location.protocol === 'https:' && typeof isSecureContext !== 'undefined' && !isSecureContext) {
            // Avoid tracking in insecure contexts when the site is served over HTTPS
            return;
        }

        const payload = {
            visitor_id: getVisitorId(),
            post_id: parseInt(postId, 10),
            action: action,
            source: source || null,
        };

        const url = '/api/tracking/event';
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        const csrfToken = getCsrfToken();
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
            try {
                navigator.sendBeacon(url, JSON.stringify(payload));
                return;
            } catch (e) {
                // Fall back to fetch
            }
        }

        fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(payload),
            keepalive: true,
        }).catch(function () {
            // Silently ignore network errors to avoid disrupting the user
        });
    }

    function handleClick(event) {
        const trackable = event.target.closest('[data-track-post]');
        if (!trackable) {
            return;
        }

        const postId = trackable.dataset.trackPost;
        const source = trackable.dataset.trackSource || 'unknown';
        track(postId, 'click', source);
    }

    window.BehaviorTracker = {
        track: track,
        getVisitorId: getVisitorId,
        init: function () {
            if (isDoNotTrack()) {
                return;
            }

            // Track page view if on an article page with a post-id meta tag
            const articleMeta = document.querySelector('meta[name="post-id"]');
            if (articleMeta && articleMeta.content) {
                track(articleMeta.content, 'view', 'article_page');
            }

        // Track homepage widget impressions only when they enter the viewport
        const homepageMeta = document.querySelector('meta[name="behavior-homepage"]');
        if (homepageMeta && homepageMeta.dataset.postIds && 'IntersectionObserver' in window) {
            try {
                const postIds = JSON.parse(homepageMeta.dataset.postIds);
                if (Array.isArray(postIds)) {
                    const observedIds = new Set();
                    const observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                const id = entry.target.dataset.trackPost;
                                if (id && !observedIds.has(id)) {
                                    observedIds.add(id);
                                    track(id, 'view', 'homepage_widget');
                                }
                            }
                        });
                    }, { rootMargin: '0px', threshold: 0.5 });


                    document.querySelectorAll('[data-track-source="homepage_recommended_sidebar"]').forEach(function (el) {
                        observer.observe(el);
                    });
                }
            } catch (e) {
                // Ignore malformed data
            }
        }

        document.addEventListener('click', handleClick);
    }
    };
})();



