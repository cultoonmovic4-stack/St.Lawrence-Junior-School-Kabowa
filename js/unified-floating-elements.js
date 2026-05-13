/**
 * UNIFIED FLOATING ELEMENTS HANDLER
 * St. Lawrence Junior School - Kabowa
 * 
 * Clean, conflict-free floating elements management
 * Replaces 5+ conflicting JavaScript files
 */

(function() {
    'use strict';

    // ========== BACK-TO-TOP FUNCTIONALITY ==========
    function initBackToTop() {
        const backToTopBtn = document.querySelector('.back-to-top') || document.getElementById('backToTop');
        
        if (!backToTopBtn) return;

        // Show/hide based on scroll position
        function toggleBackToTop() {
            if (window.scrollY > 0) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        }

        // Scroll to top functionality
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Event listeners
        window.addEventListener('scroll', toggleBackToTop);
        backToTopBtn.addEventListener('click', scrollToTop);

        // Initial check
        toggleBackToTop();
    }

    // ========== CHATBOT FUNCTIONALITY ==========
    function initChatbot() {
        const chatButton = document.querySelector('.chat-button') || document.getElementById('chatButton');
        
        if (!chatButton) return;

        // Ensure chatbot is visible and functional
        chatButton.style.opacity = '1';
        chatButton.style.visibility = 'visible';
        chatButton.style.pointerEvents = 'auto';

        // Add click handler if not already present
        if (!chatButton.hasAttribute('data-initialized')) {
            chatButton.addEventListener('click', function() {
                // Chatbot click functionality (if needed)
                console.log('Chatbot clicked');
            });
            chatButton.setAttribute('data-initialized', 'true');
        }
    }

    // ========== INITIALIZATION ==========
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initBackToTop();
                initChatbot();
            });
        } else {
            initBackToTop();
            initChatbot();
        }
    }

    // Start initialization
    init();

})();