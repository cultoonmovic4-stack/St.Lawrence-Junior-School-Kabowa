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

            /* Desktop Dropdown Styles */
            @media (min-width: 992px) {
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
                .nav-dropdown.dropdown-active .dropdown-menu,
                .nav-dropdown.open .dropdown-menu {
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
            }

            /* Mobile Dropdown Styles */
            @media (max-width: 991px) {
                .dropdown-menu {
                    display: none;
                    position: static !important;
                    box-shadow: none !important;
                    border: none !important;
                    background: rgba(255, 255, 255, 0.06) !important;
                    border-radius: 6px !important;
                    margin: 0 0 10px 0 !important;
                    padding: 4px 0 !important;
                    width: 100% !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    transform: none !important;
                }

                .dropdown-menu.mobile-open,
                .nav-dropdown.open > .dropdown-menu,
                .nav-dropdown.dropdown-active > .dropdown-menu {
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                }

                .dropdown-menu li a {
                    display: block !important;
                    color: rgba(255, 255, 255, 0.85) !important;
                    padding: 12px 18px !important;
                    font-size: 14px !important;
                    text-decoration: none !important;
                }

                .nav-dropdown .nav-link i {
                    transition: transform 0.3s ease !important;
                }

                .nav-dropdown.open > .nav-link i,
                .nav-dropdown.dropdown-active > .nav-link i {
                    transform: rotate(180deg) !important;
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