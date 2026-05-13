/**
 * Hamburger Menu - Clean Implementation
 * St. Lawrence Junior School - Kabowa
 */

(function() {
    'use strict';

    function init() {
        const hamburger = document.getElementById('hamburger');
        const navMenu   = document.getElementById('navMenu');

        if (!hamburger || !navMenu) return;

        function acquireMenuScrollLock() {
            if (!window.__scrollLockState) {
                window.__scrollLockState = { count: 0, ownerMap: {} };
            }

            const state = window.__scrollLockState;
            if (state.ownerMap.menu) return;

            if (state.count === 0) {
                document.body.style.overflow = 'hidden';
            }

            state.ownerMap.menu = true;
            state.count += 1;
        }

        function releaseMenuScrollLock() {
            const state = window.__scrollLockState;
            if (!state || !state.ownerMap.menu) return;

            delete state.ownerMap.menu;
            state.count = Math.max(0, state.count - 1);

            if (state.count === 0) {
                document.body.style.overflow = '';
            }
        }

        // ── Move navMenu to body on mobile to escape backdrop-filter stacking context ──
        // backdrop-filter on .header breaks position:fixed on children, but on
        // desktop the nav must remain inside .navbar to render inline.
        const originalNavParent = navMenu.parentElement;

        function syncNavParent() {
            const isMobile = window.matchMedia('(max-width: 992px)').matches;
            if (isMobile && navMenu.parentElement !== document.body) {
                document.body.appendChild(navMenu);
            } else if (!isMobile && originalNavParent && navMenu.parentElement !== originalNavParent) {
                originalNavParent.appendChild(navMenu);
            }
        }

        syncNavParent();

        // ── Toggle open / close ──────────────────────────────────────
        let lastToggleTime = 0;

        // Stop tap/click from falling through to links underneath.
        function swallowEvent(e) {
            if (!e) return;
            if (typeof e.preventDefault === 'function') e.preventDefault();
            if (typeof e.stopPropagation === 'function') e.stopPropagation();
        }

        function toggleMenu(e) {
            const now = Date.now();
            if (now - lastToggleTime < 350) return; // prevent double-fire
            lastToggleTime = now;

            swallowEvent(e);

            const isOpen = navMenu.classList.contains('active');
            isOpen ? close() : open();
        }

        // Use ONE primary event path for reliability.
        // Capture phase prevents underlying links from triggering navigation.
        hamburger.addEventListener('touchstart', swallowEvent, true);
        hamburger.addEventListener('pointerdown', swallowEvent, true);
        hamburger.addEventListener('click', swallowEvent, true);

        if (window.PointerEvent) {
            hamburger.addEventListener('pointerup', toggleMenu, true);
        } else {
            hamburger.addEventListener('touchend', toggleMenu, true);
            hamburger.addEventListener('click', toggleMenu, true);
        }

        function open() {
            hamburger.classList.add('active');
            navMenu.classList.add('active');
            acquireMenuScrollLock();
        }

        function close() {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
            releaseMenuScrollLock();
        }

        // ── Close when a nav link is clicked ────────────────────────
        navMenu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', close);
        });

        // ── Close on outside click ───────────────────────────────────
        document.addEventListener('click', function(e) {
            if (navMenu.classList.contains('active') &&
                !hamburger.contains(e.target) &&
                !navMenu.contains(e.target)) {
                close();
            }
        });

        // ── Close on Escape ──────────────────────────────────────────
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') close();
        });

        // ── Close when resized to desktop ────────────────────────────
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) close();
            syncNavParent();
        });

        // ── Academics expandable submenu ─────────────────────────────
        navMenu.querySelectorAll('.mobile-expandable').forEach(function(item) {
            const trigger = item.querySelector('.nav-link-mobile');
            if (!trigger) return;

            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const isOpen = item.classList.contains('active');

                // Close siblings
                navMenu.querySelectorAll('.mobile-expandable').forEach(function(other) {
                    if (other !== item) other.classList.remove('active');
                });

                item.classList.toggle('active');
            });
        });

        // ── Desktop Academics dropdown via click ─────────────────────
        const desktopDropdowns = document.querySelectorAll('.nav-dropdown');

        function closeDesktopDropdown(dropdown) {
            if (!dropdown) return;
            dropdown.classList.remove('dropdown-active');
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu) {
                menu.style.opacity = '0';
                menu.style.visibility = 'hidden';
                menu.style.transform = 'translateY(-10px)';
            }
        }

        function openDesktopDropdown(dropdown) {
            if (!dropdown) return;
            dropdown.classList.add('dropdown-active');
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu) {
                menu.style.opacity = '1';
                menu.style.visibility = 'visible';
                menu.style.transform = 'translateY(0)';
            }
        }

        function closeAllDesktopDropdowns(exceptDropdown) {
            desktopDropdowns.forEach(function(dropdown) {
                if (dropdown !== exceptDropdown) closeDesktopDropdown(dropdown);
            });
        }

        desktopDropdowns.forEach(function(dropdown) {
            const trigger = dropdown.querySelector('.nav-link');
            if (!trigger) return;

            trigger.addEventListener('click', function(e) {
                // Only desktop/tablet top-nav click should toggle dropdown.
                if (window.innerWidth <= 992) return;

                const isOpen = dropdown.classList.contains('dropdown-active');
                // First click opens dropdown; second click navigates to Academics page.
                if (!isOpen) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeAllDesktopDropdowns(dropdown);
                    openDesktopDropdown(dropdown);
                    return;
                }

                // When already open, force navigation to href to avoid other
                // listeners/styles canceling the default action.
                const href = trigger.getAttribute('href');
                if (href && href !== '#') {
                    e.preventDefault();
                    e.stopPropagation();
                    window.location.href = href;
                }
            });
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-dropdown')) {
                closeAllDesktopDropdowns(null);
            }
        });
    }

    // Run after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
