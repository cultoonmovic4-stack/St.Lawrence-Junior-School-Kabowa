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

            // Reset mobile dropdown states
            document.querySelectorAll('.nav-dropdown').forEach(function(dropdown) {
                dropdown.classList.remove('open', 'dropdown-active');
                const menu = dropdown.querySelector('.dropdown-menu');
                if (menu) {
                    menu.classList.remove('mobile-open');
                    menu.style.display = '';
                }
                const trigger = dropdown.querySelector('.nav-link') || dropdown.querySelector('a');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            });
        }

        // ── Close when a regular nav link is clicked (excluding dropdown toggles) ──
        navMenu.querySelectorAll('a').forEach(function(link) {
            const isDropdownToggle = link.classList.contains('dropdown-toggle') ||
                (link.parentElement && link.parentElement.classList.contains('nav-dropdown') && link.nextElementSibling && link.nextElementSibling.classList.contains('dropdown-menu'));
            
            if (!isDropdownToggle) {
                link.addEventListener('click', close);
            }
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
            if (window.innerWidth > 992) {
                close();
                document.querySelectorAll('.nav-dropdown').forEach(function(dropdown) {
                    const menu = dropdown.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.classList.remove('mobile-open');
                        menu.style.display = '';
                        menu.style.opacity = '';
                        menu.style.visibility = '';
                        menu.style.transform = '';
                    }
                });
            }
            syncNavParent();
        });

        // ── Unified Dropdown Navigation Handler (Desktop & Mobile) ───────────
        const allDropdowns = document.querySelectorAll('.nav-dropdown');

        function isMobileView() {
            return window.innerWidth <= 991 || navMenu.classList.contains('active');
        }

        function closeDesktopDropdown(dropdown) {
            if (!dropdown) return;
            dropdown.classList.remove('dropdown-active', 'open');
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu) {
                menu.style.opacity = '0';
                menu.style.visibility = 'hidden';
                menu.style.transform = 'translateY(-10px)';
            }
            const trigger = dropdown.querySelector('.nav-link') || dropdown.querySelector('a');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        }

        function openDesktopDropdown(dropdown) {
            if (!dropdown) return;
            dropdown.classList.add('dropdown-active', 'open');
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu) {
                menu.style.opacity = '1';
                menu.style.visibility = 'visible';
                menu.style.transform = 'translateY(0)';
            }
            const trigger = dropdown.querySelector('.nav-link') || dropdown.querySelector('a');
            if (trigger) trigger.setAttribute('aria-expanded', 'true');
        }

        function closeAllDesktopDropdowns(exceptDropdown) {
            allDropdowns.forEach(function(dropdown) {
                if (dropdown !== exceptDropdown) closeDesktopDropdown(dropdown);
            });
        }

        allDropdowns.forEach(function(dropdown) {
            const trigger = dropdown.querySelector('.nav-link') || dropdown.querySelector('a');
            const menu = dropdown.querySelector('.dropdown-menu');
            if (!trigger) return;

            trigger.setAttribute('role', 'button');
            trigger.setAttribute('aria-haspopup', 'true');
            if (!trigger.hasAttribute('aria-expanded')) {
                trigger.setAttribute('aria-expanded', 'false');
            }

            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (isMobileView()) {
                    // Mobile dropdown toggle
                    const isOpen = dropdown.classList.contains('open') || 
                                   dropdown.classList.contains('dropdown-active') || 
                                   (menu && menu.classList.contains('mobile-open'));

                    // Close any other open dropdowns
                    allDropdowns.forEach(function(other) {
                        if (other !== dropdown) {
                            other.classList.remove('open', 'dropdown-active');
                            const otherMenu = other.querySelector('.dropdown-menu');
                            if (otherMenu) {
                                otherMenu.classList.remove('mobile-open');
                                otherMenu.style.display = 'none';
                            }
                            const otherTrigger = other.querySelector('.nav-link') || other.querySelector('a');
                            if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
                        }
                    });

                    if (isOpen) {
                        dropdown.classList.remove('open', 'dropdown-active');
                        if (menu) {
                            menu.classList.remove('mobile-open');
                            menu.style.display = 'none';
                        }
                        trigger.setAttribute('aria-expanded', 'false');
                    } else {
                        dropdown.classList.add('open', 'dropdown-active');
                        if (menu) {
                            menu.classList.add('mobile-open');
                            menu.style.display = 'block';
                        }
                        trigger.setAttribute('aria-expanded', 'true');
                    }
                } else {
                    // Desktop click toggle
                    const isOpen = dropdown.classList.contains('dropdown-active');
                    if (isOpen) {
                        closeDesktopDropdown(dropdown);
                    } else {
                        closeAllDesktopDropdowns(dropdown);
                        openDesktopDropdown(dropdown);
                    }
                }
            });
        });

        // Close dropdowns on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-dropdown')) {
                allDropdowns.forEach(function(dropdown) {
                    closeDesktopDropdown(dropdown);
                    if (isMobileView()) {
                        dropdown.classList.remove('open');
                        const menu = dropdown.querySelector('.dropdown-menu');
                        if (menu) {
                            menu.classList.remove('mobile-open');
                            menu.style.display = 'none';
                        }
                    }
                });
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
