/**
 * Dropdown Navigation Fix
 * St. Lawrence Junior School - Kabowa
 * 
 * Ensures dropdown menus work on both desktop and mobile
 */

(function() {
    'use strict';

    console.log('Dropdown Fix: Initializing...');

    // Event handling for desktop/mobile dropdowns is centralized in
    // hamburger-menu-fix.js to avoid duplicate listeners and race conditions.

    // ========== ENSURE DROPDOWN STYLES ==========
    function ensureDropdownStyles() {
        const style = document.createElement('style');
        style.id = 'dropdown-fix-styles';
        style.textContent = `
            /* Ensure dropdown menus work properly */
            .nav-dropdown {
                position: relative;
            }
            
            .dropdown-menu {
                position: absolute;
                top: 100%;
                left: 0;
                background: white;
                border-radius: 8px;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: all 0.3s ease;
                z-index: 1000;
                min-width: 200px;
                padding: 10px 0;
                margin-top: 5px;
            }
            
            .nav-dropdown:hover .dropdown-menu,
            .nav-dropdown.dropdown-active .dropdown-menu {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            
            .dropdown-menu li {
                list-style: none;
            }
            
            .dropdown-menu li a {
                display: block;
                padding: 12px 20px;
                color: #333;
                text-decoration: none;
                transition: all 0.2s ease;
                border-left: 3px solid transparent;
            }
            
            .dropdown-menu li a:hover {
                background: linear-gradient(90deg, rgba(0, 102, 204, 0.1) 0%, transparent 100%);
                border-left-color: #0066cc;
                color: #0066cc;
            }
            
            /* Mobile expandable styles */
            .mobile-expandable .mobile-submenu {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
                background: rgba(255, 255, 255, 0.1);
                margin-top: 10px;
                border-radius: 8px;
            }
            
            .mobile-expandable.active .mobile-submenu {
                max-height: 200px;
            }
            
            .mobile-expand-icon {
                font-size: 1.2rem;
                font-weight: bold;
                transition: transform 0.3s ease;
            }
            
            .mobile-expandable.active .mobile-expand-icon {
                transform: rotate(180deg);
            }
            
            /* Ensure dropdowns work with anti-shake CSS */
            @media (max-width: 768px) {
                .dropdown-menu,
                .mobile-submenu,
                .nav-dropdown,
                .mobile-expandable {
                    transition: all 0.3s ease !important;
                    -webkit-transition: all 0.3s ease !important;
                }
            }
        `;
        
        document.head.appendChild(style);
    }

    // ========== INITIALIZATION ==========
    function init() {
        console.log('Dropdown Fix: Starting initialization...');
        
        // Apply styles first
        ensureDropdownStyles();
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                }, 100);
            });
        } else {
            setTimeout(() => {
            }, 100);
        }

        console.log('Dropdown Fix: Initialized');
    }

    // ========== AUTO-INITIALIZE ==========
    init();

    // Export for manual control
    window.DropdownFix = {
        init: init,
        ensureDropdownStyles: ensureDropdownStyles
    };

})();