    document.addEventListener('DOMContentLoaded', function() {
        function checkCookie(name) {
            return document.cookie.split(';').some((item) => item.trim().startsWith(name + '='));
        }

        setTimeout(function() {
            const cookieName = document.querySelector('div[data-site-cookie-name]').getAttribute('data-site-cookie-name') || 'cookie_for_consent';
            if (!checkCookie(cookieName)) {
                const siteNotice = document.querySelector('.js-site-notice');
                if (siteNotice) {
                    siteNotice.classList.add('site-notice--visible');
                }
            }
        }, 1000);
    });

    window.addEventListener('load', function () {
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'default', {
                'ad_storage': 'denied',
                'analytics_storage': 'denied'
            });

            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('js-site-notice-agree')) {
                    const categories = document.querySelectorAll('.js-cookie-category:checked');
                    const consents = {
                        'ad_storage': 'denied',
                        'analytics_storage': 'denied'
                    };

                    categories.forEach(function(category) {
                        if (category.value === 'marketing') {
                            consents.ad_storage = 'granted';
                        }
                        if (category.value === 'analytics') {
                            consents.analytics_storage = 'granted';
                        }
                    });

                    gtag('consent', 'update', consents);
                }
            });
        }

        window.botbleCookieConsent = (function () {
            const COOKIE_NAME = document.querySelector('div[data-site-cookie-name]').getAttribute('data-site-cookie-name') || 'cookie_for_consent';
            const COOKIE_DOMAIN = document.querySelector('div[data-site-cookie-domain]').getAttribute('data-site-cookie-domain') || window.location.hostname;
            const COOKIE_LIFETIME = parseInt(document.querySelector('div[data-site-cookie-lifetime]').getAttribute('data-site-cookie-lifetime') || '36000', 10);
            const SESSION_SECURE = document.querySelector('div[data-site-session-secure]').getAttribute('data-site-session-secure') || '';

            const cookieDialog = document.querySelector('.js-site-notice');
            const cookieCategories = document.querySelector('.site-notice__categories');
            const customizeButton = document.querySelector('.js-site-notice-customize');

            if (cookieDialog) {
                if (cookieCategories) {
                    cookieCategories.style.display = 'none';
                }

                if (!cookieExists(COOKIE_NAME)) {
                    setTimeout(function() {
                        cookieDialog.classList.add('site-notice--visible');
                    }, 800);
                }
            }

            function consentWithCookies() {
                const categories = {};
                document.querySelectorAll('.js-cookie-category:checked').forEach(function(checkbox) {
                    categories[checkbox.value] = true;
                });
                setCookie(COOKIE_NAME, JSON.stringify(categories), COOKIE_LIFETIME);
                hideCookieDialog();
            }

            function savePreferences() {
                consentWithCookies();

                if (cookieCategories) {
                    const slideUpAnimation = cookieCategories.animate(
                        [
                            { opacity: 1, height: cookieCategories.offsetHeight + 'px' },
                            { opacity: 0, height: 0 }
                        ],
                        { duration: 300, easing: 'ease-out' }
                    );

                    slideUpAnimation.onfinish = function() {
                        cookieCategories.style.display = 'none';
                    };
                }

                if (customizeButton) {
                    customizeButton.classList.remove('active');
                }
            }

            function rejectAllCookies() {
                if (cookieExists(COOKIE_NAME)) {
                    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                    document.cookie = COOKIE_NAME +
                        '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; domain=' +
                        COOKIE_DOMAIN +
                        '; path=/; SameSite=Lax' +
                        secure +
                        SESSION_SECURE;
                }

                if (typeof gtag !== 'undefined') {
                    gtag('consent', 'update', {
                        'ad_storage': 'denied',
                        'analytics_storage': 'denied'
                    });
                }

                hideCookieDialog();
            }

            function cookieExists(name) {
                const cookie = getCookie(name);
                return cookie !== null && cookie !== undefined;
            }

            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) {
                    return parts.pop().split(';').shift();
                }
                return null;
            }

            function hideCookieDialog() {
                if (cookieDialog) {
                    cookieDialog.classList.remove('site-notice--visible');
                    cookieDialog.style.display = 'none';
                }
            }

            function setCookie(name, value, expirationInDays) {
                const date = new Date();
                date.setTime(date.getTime() + expirationInDays * 24 * 60 * 60 * 1000);
                const secure = window.location.protocol === 'https:' ? ';Secure' : '';
                document.cookie =
                    name +
                    '=' +
                    value +
                    ';expires=' +
                    date.toUTCString() +
                    ';domain=' +
                    COOKIE_DOMAIN +
                    ';path=/' +
                    ';SameSite=Lax' +
                    secure +
                    SESSION_SECURE;
            }

            function toggleCustomizeView() {
                if (!cookieCategories) return;

                if (cookieCategories.style.display === 'none') {
                    cookieCategories.style.height = '0';
                    cookieCategories.style.opacity = '0';
                    cookieCategories.style.display = 'block';

                    const height = cookieCategories.scrollHeight;
                    const slideDownAnimation = cookieCategories.animate(
                        [
                            { opacity: 0, height: 0 },
                            { opacity: 1, height: height + 'px' }
                        ],
                        { duration: 300, easing: 'ease-in' }
                    );

                    slideDownAnimation.onfinish = function() {
                        cookieCategories.style.height = 'auto';
                        cookieCategories.style.opacity = '1';
                    };

                    if (customizeButton) {
                        customizeButton.classList.add('active');
                    }
                } else {
                    const slideUpAnimation = cookieCategories.animate(
                        [
                            { opacity: 1, height: cookieCategories.offsetHeight + 'px' },
                            { opacity: 0, height: 0 }
                        ],
                        { duration: 300, easing: 'ease-out' }
                    );

                    slideUpAnimation.onfinish = function() {
                        cookieCategories.style.display = 'none';
                    };

                    if (customizeButton) {
                        customizeButton.classList.remove('active');
                    }
                }
            }

            if (cookieExists(COOKIE_NAME)) {
                hideCookieDialog();
            }

            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('js-site-notice-agree')) {
                    consentWithCookies();
                } else if (event.target.classList.contains('js-site-notice-reject')) {
                    rejectAllCookies();
                } else if (event.target.classList.contains('js-site-notice-customize')) {
                    toggleCustomizeView();
                } else if (event.target.classList.contains('js-site-notice-save')) {
                    savePreferences();
                }
            });

            return {
                consentWithCookies: consentWithCookies,
                rejectAllCookies: rejectAllCookies,
                hideCookieDialog: hideCookieDialog,
                savePreferences: savePreferences,
            };
        })();
    });
