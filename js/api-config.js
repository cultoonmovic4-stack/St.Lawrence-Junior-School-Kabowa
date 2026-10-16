/**
 * St. Lawrence Junior School - Universal API Router & Config
 * Connects frontend seamlessly to local XAMPP in development and Railway in production.
 */
(function() {
    'use strict';

    const IS_LOCAL = window.location.hostname === 'localhost' || 
                     window.location.hostname === '127.0.0.1' || 
                     window.location.hostname.endsWith('.local');

    const REMOTE_BACKEND = 'https://api.stlawrencejuniorschoolkabowa.com/backend';

    window.API_BASE_URL = IS_LOCAL
        ? (window.location.pathname.includes('/frontend/') ? '../backend/api' : './backend/api')
        : `${REMOTE_BACKEND}/api`;

    window.BACKEND_BASE_URL = IS_LOCAL
        ? (window.location.pathname.includes('/frontend/') ? '../backend' : './backend')
        : REMOTE_BACKEND;

    // Automatic .html Extension Stripper & Canonical URL Normalizer for Production Clean URLs
    if (!IS_LOCAL) {
        var p = window.location.pathname;
        if (p.endsWith('.html') || /[A-Z]/.test(p) || p.startsWith('/frontend')) {
            var clean = p.replace(/\/frontend\//i, '/').replace(/\.html$/i, '').toLowerCase();
            if (clean === '/index' || clean.endsWith('/index')) clean = '/';
            if (clean === '/admission') clean = '/admissions';
            if (clean === '/school-anthem') clean = '/anthem';
            if (clean !== p) {
                window.location.replace(clean + window.location.search + window.location.hash);
            }
        }
    }

    // Local XAMPP subfolder prefixing for root clean URLs
    if (IS_LOCAL) {
        function getLocalPrefix() {
            var m = window.location.pathname.match(/^(\/[^\/]+\/[^\/]+)/);
            return (m && m[1] && !m[1].includes('.')) ? m[1] : '';
        }

        // Fast click interceptor so navigation never 404s even if clicked before DOMContentLoaded
        document.addEventListener('click', function(e) {
            var a = e.target.closest('a');
            if (!a) return;
            var href = a.getAttribute('href');
            if (href && href.startsWith('/') && !href.startsWith('//')) {
                var prefix = getLocalPrefix();
                if (prefix && !href.startsWith(prefix)) {
                    e.preventDefault();
                    window.location.href = (href === '/' ? prefix + '/' : prefix + href);
                }
            }
        }, true);

        // Pre-rewrite all static anchor hrefs on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            var prefix = getLocalPrefix();
            if (prefix) {
                document.querySelectorAll('a[href^="/"]:not([href^="//"])').forEach(function(a) {
                    var href = a.getAttribute('href');
                    if (href === '/') {
                        a.setAttribute('href', prefix + '/');
                    } else if (!href.startsWith(prefix)) {
                        a.setAttribute('href', prefix + href);
                    }
                });
            }
        });
    }

    // Automatically rewrite relative backend requests to Railway in production

    if (!IS_LOCAL) {
        const originalFetch = window.fetch;
        window.fetch = function(resource, init) {
            if (typeof resource === 'string') {
                if (resource.startsWith('../backend')) {
                    resource = resource.replace('../backend', REMOTE_BACKEND);
                } else if (resource.startsWith('./backend')) {
                    resource = resource.replace('./backend', REMOTE_BACKEND);
                } else if (resource.startsWith('/backend')) {
                    resource = resource.replace('/backend', REMOTE_BACKEND);
                }
            } else if (resource instanceof Request) {
                const url = resource.url;
                if (url.includes('/backend/')) {
                    const newUrl = url.replace(/https?:\/\/[^\/]+\/backend\//, `${REMOTE_BACKEND}/`);
                    return originalFetch(new Request(newUrl, resource), init);
                }
            }
            if (init && init.headers && typeof init.body === 'string') {
                if (init.headers instanceof Headers) {
                    if (init.headers.get('content-type') === 'application/json') {
                        init.headers.set('content-type', 'text/plain');
                    }
                } else if (typeof init.headers === 'object') {
                    for (const k of Object.keys(init.headers)) {
                        if (k.toLowerCase() === 'content-type' && init.headers[k] === 'application/json') {
                            init.headers[k] = 'text/plain';
                        }
                    }
                }
            }
            return originalFetch(resource, init);
        };

        // Automatically rewrite static <a> and <img> elements pointing to backend
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('a[href*="/backend/"], img[src*="/backend/"]').forEach(el => {
                if (el.tagName === 'A') {
                    const href = el.getAttribute('href');
                    if (href && (href.startsWith('../backend') || href.startsWith('./backend') || href.startsWith('/backend'))) {
                        el.href = href.replace(/^(\.\.\/|\.\/|\/)backend/, REMOTE_BACKEND);
                    }
                } else if (el.tagName === 'IMG') {
                    const src = el.getAttribute('src');
                    if (src && (src.startsWith('../backend') || src.startsWith('./backend') || src.startsWith('/backend'))) {
                        el.src = src.replace(/^(\.\.\/|\.\/|\/)backend/, REMOTE_BACKEND);
                    }
                }
            });
        });
    }
})();

// Legacy API Helper Functions (backward compatibility for existing code)
var API = window.API || {
    async get(endpoint) {
        try {
            const url = `${window.API_BASE_URL}${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;
            const response = await fetch(url);
            return await response.json();
        } catch (error) {
            console.error('API GET Error:', error);
            return { success: false, message: 'Connection error' };
        }
    },

    async post(endpoint, body) {
        try {
            const url = `${window.API_BASE_URL}${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;
            const isFormData = typeof FormData !== 'undefined' && body instanceof FormData;
            const headers = isFormData ? {} : { 'Content-Type': 'text/plain' };
            const response = await fetch(url, {
                method: 'POST',
                headers: headers,
                body: isFormData ? body : (typeof body === 'string' ? body : JSON.stringify(body))
            });
            return await response.json();
        } catch (error) {
            console.error('API POST Error:', error);
            return { success: false, message: 'Connection error' };
        }
    }
};
window.API = API;

function showLoading(elementId) {
    const el = document.getElementById(elementId);
    if (el) {
        el.innerHTML = '<div class="loading-container"><div class="spinner"></div><p>Loading...</p></div>';
    }
}

function showError(elementId, message) {
    const el = document.getElementById(elementId);
    if (el) {
        el.innerHTML = `<div class="error-container"><i class="fas fa-exclamation-circle"></i><p>${message}</p><button onclick="location.reload()" class="retry-btn">Retry</button></div>`;
    }
}

function showNoData(elementId, message) {
    const el = document.getElementById(elementId);
    if (el) {
        el.innerHTML = `<div class="no-data-container"><i class="fas fa-inbox"></i><p>${message}</p></div>`;
    }
}
