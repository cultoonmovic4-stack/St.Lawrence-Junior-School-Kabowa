/**
 * Mobile Edge Display Fixes
 * St. Lawrence Junior School - Kabowa
 * 
 * Handles dynamic viewport, safe areas, and edge display issues
 * for modern mobile devices (Samsung S8/S9, iPhone X+, etc.)
 */

(function() {
    'use strict';

    // ========== DYNAMIC VIEWPORT HEIGHT FIX ==========
    function setDynamicViewportHeight() {
        // Get the actual viewport height
        const vh = window.innerHeight * 0.01;
        const dvh = window.visualViewport ? window.visualViewport.height * 0.01 : vh;
        
        // Set CSS custom properties
        document.documentElement.style.setProperty('--vh', `${vh}px`);
        document.documentElement.style.setProperty('--dvh', `${dvh}px`);
        
        // Update hero section height for mobile
        const hero = document.querySelector('.hero');
        if (hero && window.innerWidth <= 768) {
            hero.style.minHeight = `calc(${vh * 100}px - var(--header-height) - var(--safe-area-top))`;
        }
    }

    // ========== SAFE AREA DETECTION ==========
    function detectSafeAreas() {
        // Check if device supports safe areas
        const supportsEnv = CSS.supports('padding-top: env(safe-area-inset-top)');
        
        if (supportsEnv) {
            document.documentElement.classList.add('supports-safe-areas');
        }
        
        // Detect notch/dynamic island
        if (window.screen && window.screen.height > 800 && window.innerWidth >= 375) {
            document.documentElement.classList.add('has-notch');
        }
        
        // Detect curved edges (Samsung Galaxy S series)
        const userAgent = navigator.userAgent.toLowerCase();
        if (userAgent.includes('samsung') || userAgent.includes('sm-')) {
            document.documentElement.classList.add('has-curved-edges');
        }
    }

    // ========== PREVENT ZOOM ON INPUT FOCUS ==========
    function preventZoomOnInput() {
        // Only on mobile devices
        if (window.innerWidth <= 768) {
            const inputs = document.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                // Ensure font-size is at least 16px to prevent zoom
                const computedStyle = window.getComputedStyle(input);
                const fontSize = parseFloat(computedStyle.fontSize);
                
                if (fontSize < 16) {
                    input.style.fontSize = '16px';
                }
            });
        }
    }

    // ========== HANDLE KEYBOARD APPEARANCE ==========
    function handleVirtualKeyboard() {
        if (!window.visualViewport) return;
        
        let initialViewportHeight = window.visualViewport.height;
        
        function viewportHandler() {
            const currentViewportHeight = window.visualViewport.height;
            const heightDifference = initialViewportHeight - currentViewportHeight;
            
            // If viewport height decreased significantly, keyboard is likely open
            if (heightDifference > 150) {
                document.documentElement.classList.add('keyboard-open');
                
                // Adjust floating elements
                const chatButton = document.querySelector('.chat-button');
                const backToTop = document.querySelector('.back-to-top');
                
                if (chatButton) {
                    chatButton.style.bottom = '20px';
                }
                if (backToTop) {
                    backToTop.style.bottom = '80px';
                }
            } else {
                document.documentElement.classList.remove('keyboard-open');
                
                // Reset floating elements
                const chatButton = document.querySelector('.chat-button');
                const backToTop = document.querySelector('.back-to-top');
                
                if (chatButton) {
                    chatButton.style.bottom = '';
                }
                if (backToTop) {
                    backToTop.style.bottom = '';
                }
            }
        }
        
        window.visualViewport.addEventListener('resize', viewportHandler);
    }

    // ========== FIX SHAKING ANIMATIONS ==========
    function fixShakingAnimations() {
        // Disable problematic CSS animations on mobile
        if (window.innerWidth <= 768) {
            const style = document.createElement('style');
            style.id = 'mobile-edge-shake-fix';
            style.textContent = `
                /* Scoped stabilization only - avoid global * overrides */
                
                /* Stable floating elements */
                .chat-button, .back-to-top, .hamburger {
                    -webkit-backface-visibility: hidden !important;
                    backface-visibility: hidden !important;
                    will-change: opacity !important;
                }
                
                /* Disable AOS animations on mobile */
                .aos-animate {
                    transform: none !important;
                    opacity: 1 !important;
                }
            `;
            if (!document.getElementById(style.id)) {
                document.head.appendChild(style);
            }
        }
    }

    // ========== STABILIZE FLOATING ELEMENTS ==========
    function stabilizeFloatingElements() {
        const chatButton = document.querySelector('.chat-button');
        const backToTop = document.querySelector('.back-to-top');
        const hamburger = document.querySelector('.hamburger');
        
        [chatButton, backToTop, hamburger].forEach(element => {
            if (element) {
                // Keep non-invasive hints only
                element.style.backfaceVisibility = 'hidden';
                element.style.webkitBackfaceVisibility = 'hidden';
                element.style.willChange = 'opacity';
                
                element.style.transition = 'opacity 0.2s ease';
            }
        });
    }

    // ========== FIX IMAGE SHAKING ==========
    function fixImageShaking() {
        if (window.innerWidth > 768) return;
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            // Preserve natural transforms/animations; enforce safe sizing only.
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
        });
    }

    // ========== DISABLE PROBLEMATIC ANIMATIONS ==========
    function disableProblematicAnimations() {
        // Handled by scoped CSS in mobile-animation-fixes.css.
    }
    function fixHorizontalScroll() {
        // Prevent horizontal scroll on body
        document.body.style.overflowX = 'hidden';
        document.documentElement.style.overflowX = 'hidden';
        
        // Check key overflow culprits without scanning every element.
        function checkForOverflow() {
            const elements = document.querySelectorAll('img, video, iframe, .container, .row, table');
            elements.forEach(el => {
                if (el.scrollWidth > document.documentElement.clientWidth) {
                    el.classList.add('overflow-x-issue');
                    
                    if (el.tagName === 'IMG' || el.tagName === 'VIDEO') {
                        el.style.maxWidth = '100%';
                        el.style.height = 'auto';
                    }
                    
                    if (el.classList.contains('container') || el.classList.contains('row')) {
                        el.style.maxWidth = '100%';
                        el.style.overflowX = 'hidden';
                    }
                }
            });
        }
        
        // Run check after DOM is loaded and on resize
        setTimeout(checkForOverflow, 100);
        window.addEventListener('resize', checkForOverflow);
    }

    // ========== OPTIMIZE TOUCH TARGETS ==========
    function optimizeTouchTargets() {
        if (window.innerWidth <= 768) {
            const touchElements = document.querySelectorAll('button, a, input, .hamburger, .chat-button, .back-to-top');
            
            touchElements.forEach(el => {
                const rect = el.getBoundingClientRect();
                
                // Ensure minimum touch target size of 44px
                if (rect.width < 44 || rect.height < 44) {
                    el.style.minWidth = '44px';
                    el.style.minHeight = '44px';
                    el.style.display = 'inline-flex';
                    el.style.alignItems = 'center';
                    el.style.justifyContent = 'center';
                }
            });
        }
    }

    // ========== HANDLE ORIENTATION CHANGES ==========
    function handleOrientationChange() {
        function orientationHandler() {
            // Small delay to ensure viewport has updated
            setTimeout(() => {
                setDynamicViewportHeight();
                optimizeTouchTargets();
                
                // Adjust layout for landscape on small screens
                if (window.innerHeight < 500 && window.orientation !== undefined) {
                    document.documentElement.classList.add('landscape-mobile');
                } else {
                    document.documentElement.classList.remove('landscape-mobile');
                }
            }, 100);
        }
        
        window.addEventListener('orientationchange', orientationHandler);
        window.addEventListener('resize', orientationHandler);
    }

    // ========== IMPROVE SCROLL PERFORMANCE ==========
    function improveScrollPerformance() {
        // Add passive event listeners for better scroll performance
        let ticking = false;
        
        function updateScrollPosition() {
            // Update scroll-based animations or effects here
            ticking = false;
        }
        
        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateScrollPosition);
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', requestTick, { passive: true });
        
        // Reduce motion for users who prefer it
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('reduce-motion');
        }
    }

    // ========== INITIALIZE ALL FIXES ==========
    function initMobileEdgeFixes() {
        // Run immediately
        setDynamicViewportHeight();
        detectSafeAreas();
        fixShakingAnimations();
        fixHorizontalScroll();
        
        // Run after DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                preventZoomOnInput();
                optimizeTouchTargets();
                handleVirtualKeyboard();
                handleOrientationChange();
                improveScrollPerformance();
                stabilizeFloatingElements();
                fixImageShaking();
                disableProblematicAnimations();
            });
        } else {
            preventZoomOnInput();
            optimizeTouchTargets();
            handleVirtualKeyboard();
            handleOrientationChange();
            improveScrollPerformance();
            stabilizeFloatingElements();
            fixImageShaking();
            disableProblematicAnimations();
        }
        
        // Update on window resize
        window.addEventListener('resize', () => {
            setDynamicViewportHeight();
            preventZoomOnInput();
            optimizeTouchTargets();
            stabilizeFloatingElements();
            fixImageShaking();
        }, { passive: true });
        
        // Update on visual viewport changes (for virtual keyboard)
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', setDynamicViewportHeight, { passive: true });
        }
    }

    // ========== AUTO-INITIALIZE ==========
    initMobileEdgeFixes();

    // Export for manual initialization if needed
    window.MobileEdgeFixes = {
        init: initMobileEdgeFixes,
        setDynamicViewportHeight,
        detectSafeAreas,
        preventZoomOnInput,
        optimizeTouchTargets,
        fixHorizontalScroll,
        fixShakingAnimations,
        stabilizeFloatingElements,
        fixImageShaking,
        disableProblematicAnimations
    };

})();