/**
 * ST. LAWRENCE JUNIOR SCHOOL - REDESIGN
 * JavaScript for Interactive Features
 */

// ========== INITIALIZE AOS ANIMATION ==========
// Mobile-optimized AOS settings
const isMobile = window.innerWidth <= 768;

AOS.init({
    duration: isMobile ? 300 : 800,
    easing: 'ease-in-out',
    once: true,
    offset: isMobile ? 50 : 100,
    disable: function() {
        // Disable AOS on mobile devices for better performance
        return window.innerWidth < 768;
    },
    // Reduce animations on mobile
    useClassNames: isMobile,
    initClassName: false,
    animatedClassName: isMobile ? 'aos-animate-mobile' : 'aos-animate'
});

// ========== HEADER SCROLL EFFECT ==========
const header = document.getElementById('header');
let lastScroll = 0;

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 100) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
    
    // Add class to body when scrolling starts for floating elements
    if (currentScroll > 0) {
        document.body.classList.add('scrolled');
        document.documentElement.classList.add('scrolled');
    } else {
        document.body.classList.remove('scrolled');
        document.documentElement.classList.remove('scrolled');
    }
    
    lastScroll = currentScroll;
});

// ========== MOBILE MENU TOGGLE ==========
// DISABLED - Using inline script for new mobile menu design
/*
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('navMenu');
const navLinks = document.querySelectorAll('.nav-link');

hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
    document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
});

// Close menu when clicking on a link
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
        document.body.style.overflow = '';
    });
});

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
        document.body.style.overflow = '';
    }
});
*/

// ========== ACTIVE NAV LINK ON SCROLL ==========
const sections = document.querySelectorAll('section[id]');

function highlightNavLink() {
    const scrollY = window.pageYOffset;
    const navLinks = document.querySelectorAll('.nav-link');

    if (!navLinks.length) return;
    
    sections.forEach(section => {
        const sectionHeight = section.offsetHeight;
        const sectionTop = section.offsetTop - 100;
        const sectionId = section.getAttribute('id');
        const navLink = document.querySelector(`.nav-link[href="#${sectionId}"]`);
        
        if (navLink && scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
            navLinks.forEach(link => link.classList.remove('active'));
            navLink.classList.add('active');
        }
    });
}

window.addEventListener('scroll', highlightNavLink);

// ========== COUNTER ANIMATION ==========
// Universal counter animation for all stat numbers across all pages
function initCounterAnimations() {
    // All counter selectors used across the site
    const counterSelectors = [
        '.stat-number',
        '.stat-number-story',
        '.stat-number-motto',
        '.stat-number-intro',
        '.why-stat-number'
    ];
    
    // Find all counters
    const allCounters = [];
    counterSelectors.forEach(selector => {
        document.querySelectorAll(selector).forEach(counter => {
            allCounters.push(counter);
        });
    });
    
    if (allCounters.length === 0) return;
    
    // Track which counters have been animated
    const animatedCounters = new Set();
    
    // Animation function for a single counter
    function animateCounter(counter) {
        if (animatedCounters.has(counter)) return;
        animatedCounters.add(counter);
        
        const text = counter.textContent.trim();
        let target, suffix = '', prefix = '';
        
        // Parse the counter value and format
        if (text.includes('%')) {
            target = parseFloat(text.replace('%', ''));
            suffix = '%';
        } else if (text.includes('+')) {
            target = parseFloat(text.replace('+', ''));
            suffix = '+';
        } else if (text.includes(':')) {
            // Handle ratio format like "1:15"
            const parts = text.split(':');
            if (parts.length === 2) {
                // Animate the second number
                target = parseFloat(parts[1]);
                prefix = parts[0] + ':';
            } else {
                target = parseFloat(text);
            }
        } else {
            target = parseFloat(text);
        }
        
        // Skip if not a valid number
        if (isNaN(target)) return;
        
        // Animation settings
        const duration = 2000; // 2 seconds
        const fps = 60;
        const totalFrames = (duration / 1000) * fps;
        const increment = target / totalFrames;
        let current = 0;
        let frame = 0;
        
        // Easing function for smooth animation
        function easeOutQuart(x) {
            return 1 - Math.pow(1 - x, 4);
        }
        
        // Update counter
        function updateCounter() {
            frame++;
            const progress = frame / totalFrames;
            current = target * easeOutQuart(progress);
            
            if (frame < totalFrames) {
                // Format based on whether it's a decimal or integer
                let displayValue;
                if (target % 1 !== 0) {
                    displayValue = current.toFixed(1);
                } else {
                    displayValue = Math.floor(current);
                }
                counter.textContent = prefix + displayValue + suffix;
                requestAnimationFrame(updateCounter);
            } else {
                // Final value
                let finalValue;
                if (target % 1 !== 0) {
                    finalValue = target.toFixed(1);
                } else {
                    finalValue = target;
                }
                counter.textContent = prefix + finalValue + suffix;
            }
        }
        
        // Start animation
        counter.textContent = prefix + '0' + suffix;
        requestAnimationFrame(updateCounter);
    }
    
    // Create intersection observer for each counter
    const observerOptions = {
        threshold: 0.3,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe all counters
    allCounters.forEach(counter => {
        counterObserver.observe(counter);
    });
}

// Initialize counters AFTER page loader finishes
// Listen for custom event dispatched by page-loader.js
window.addEventListener('loaderHidden', function() {
    initCounterAnimations();
});

// Fallback: If no loader exists on page, initialize after DOM ready
if (!document.getElementById('pageLoader')) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCounterAnimations);
    } else {
        initCounterAnimations();
    }
}

// ========== MOBILE FLOATING CONTROLS POSITIONING ==========
// Keep floating controls pinned inside visible viewport on mobile.
function pinFloatingControls() {
    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    if (!isMobile) return;

    // Mobile positioning is managed by unified CSS files.
    // Keep this hook for resize/orientation listeners without inline overrides.
}
window.pinFloatingControls = pinFloatingControls;

window.addEventListener('resize', pinFloatingControls, { passive: true });
window.addEventListener('orientationchange', pinFloatingControls, { passive: true });
window.addEventListener('loaderHidden', pinFloatingControls);
if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', pinFloatingControls, { passive: true });
}
pinFloatingControls();

// ========== SMOOTH SCROLL FOR ANCHOR LINKS ==========
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        
        // Skip if it's just "#"
        if (href === '#') {
            e.preventDefault();
            return;
        }
        
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            const headerHeight = header.offsetHeight;
            const targetPosition = target.offsetTop - headerHeight;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// ========== LAZY LOAD IMAGES ==========
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                observer.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ========== PREVENT LAYOUT SHIFT ==========
window.addEventListener('load', () => {
    document.body.classList.add('loaded');
});

// ========== PERFORMANCE OPTIMIZATION ==========
// Debounce function for scroll events
function debounce(func, wait = 10) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Apply debounce to scroll-heavy functions
window.addEventListener('scroll', debounce(() => {
    highlightNavLink();
}, 10));

// ========== CONSOLE MESSAGE ==========
console.log('%c ST. LAWRENCE JUNIOR SCHOOL ', 'background: #1e4d9f; color: white; font-size: 20px; padding: 10px;');
console.log('%c Where Excellence Meets Innovation in Education ', 'background: #10b981; color: white; font-size: 14px; padding: 5px;');

