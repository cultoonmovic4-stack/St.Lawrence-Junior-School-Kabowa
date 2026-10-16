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

    // Clean URL Sanitizer for Production
    // Prevents internal directory paths like '/frontend/' from being exposed in browser address bar
    if (!IS_LOCAL && window.history && window.history.replaceState) {
        var currentPath = window.location.pathname;
        if (currentPath.includes('/frontend/index-redesign') || currentPath.endsWith('/index.html')) {
            window.history.replaceState(null, '', '/' + window.location.search + window.location.hash);
        } else if (currentPath.includes('/frontend/')) {
            var cleanPath = currentPath
                .replace('/frontend/', '/')
                .replace(/-redesign\.html/i, '')
                .replace(/\.html/i, '')
                .toLowerCase();
            window.history.replaceState(null, '', cleanPath + window.location.search + window.location.hash);
        }
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
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
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
