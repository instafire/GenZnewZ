(function () {
    function buildAdminPrefix() {
        var segments = window.location.pathname.split('/').filter(Boolean);
        return segments.length ? '/' + segments[0] : '/admin';
    }

    function addQuickActions() {
        var actionHost = document.querySelector('.page-header .btn-list');
        if (!actionHost || actionHost.querySelector('.admin-nyt-quick-actions')) {
            return;
        }

        var adminPrefix = buildAdminPrefix();
        var profileLink = document.querySelector('a[href*="/system/users/profile/"]');

        var links = [
            { href: adminPrefix, label: 'Dashboard' },
            { href: adminPrefix + '/blog/posts/create', label: 'New Article' },
            { href: adminPrefix + '/media', label: 'Media' },
        ];

        if (profileLink && profileLink.getAttribute('href')) {
            links.push({ href: profileLink.getAttribute('href'), label: 'Profile' });
        }

        var wrap = document.createElement('div');
        wrap.className = 'admin-nyt-quick-actions';

        links.forEach(function (link) {
            var anchor = document.createElement('a');
            anchor.className = 'btn btn-outline-primary btn-sm';
            anchor.href = link.href;
            anchor.textContent = link.label;
            wrap.appendChild(anchor);
        });

        actionHost.prepend(wrap);
    }

    function enableStickyFormActions() {
        document.querySelectorAll('.form-actions, .card-body .btn-list').forEach(function (node) {
            if (node.closest('.admin-nyt-quick-actions') || node.classList.contains('admin-nyt-sticky-actions')) {
                return;
            }

            node.classList.add('admin-nyt-sticky-actions');
        });
    }

    function init() {
        addQuickActions();
        enableStickyFormActions();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
