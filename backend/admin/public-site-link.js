/**
 * Admin "Back to website" links.
 * The admin runs on the API server (api.stlawrencejuniorschoolkabowa.com), which keeps its own
 * snapshot of the site. Send visitors to the real public website instead.
 * On localhost the original relative links are left untouched.
 */
(function () {
    'use strict';

    var PUBLIC_SITE_URL = 'https://stlawrencejuniorschoolkabowa.com/';
    var host = window.location.hostname;
    var isLocal = host === 'localhost' || host === '127.0.0.1' || host.endsWith('.local');
    if (isLocal) return;

    function rewrite() {
        document.querySelectorAll('a.back-website-link, a.back-link').forEach(function (a) {
            var href = a.getAttribute('href') || '';
            if (/^(\.\.\/)+(index\.html|frontend\/)/.test(href)) {
                a.setAttribute('href', PUBLIC_SITE_URL);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', rewrite);
    } else {
        rewrite();
    }
})();
