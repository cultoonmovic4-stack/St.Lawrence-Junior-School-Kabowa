/**
 * Custom Dropdown Component - Mobile Optimized
 * Replaces native select elements with custom dropdowns that don't overflow
 */

class CustomDropdown {
    constructor(selectElement) {
        this.select = selectElement;
        this.isOpen = false;
        this.isMobile = window.innerWidth <= 768;
        
        this.init();
    }

    init() {
        // Add class to original select
        this.select.classList.add('custom-select-mobile');
        
        // Create custom dropdown structure
        this.createDropdown();
        
        // Bind events
        this.bindEvents();
        
        // Update on window resize
        window.addEventListener('resize', () => {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth <= 768;
            
            if (wasMobile !== this.isMobile) {
                this.destroy();
                this.init();
            }
        });
    }

    createDropdown() {
        // Create container
        this.container = document.createElement('div');
        this.container.className = 'custom-dropdown';
        
        // Create trigger
        this.trigger = document.createElement('div');
        this.trigger.className = 'custom-dropdown-trigger';
        
        const selectedText = this.select.options[this.select.selectedIndex]?.text || this.select.querySelector('option[value=""]')?.text || 'Select...';
        const isPlaceholder = !this.select.value;
        
        this.trigger.innerHTML = `
            <span class="${isPlaceholder ? 'placeholder' : 'selected-value'}">${selectedText}</span>
            <span class="arrow">▼</span>
        `;
        
        // Create dropdown menu
        this.menu = document.createElement('div');
        this.menu.className = 'custom-dropdown-menu';
        
        if (this.isMobile) {
            // Create overlay for mobile
            this.overlay = document.createElement('div');
            this.overlay.className = 'custom-dropdown-overlay';
            
            // Create header for mobile
            const header = document.createElement('div');
            header.className = 'custom-dropdown-header';
            header.innerHTML = `
                <h3>${this.select.previousElementSibling?.textContent || 'Select Option'}</h3>
                <button class="custom-dropdown-close" type="button">×</button>
            `;
            
            // Create options container
            const optionsContainer = document.createElement('div');
            optionsContainer.className = 'custom-dropdown-options';
            
            // Build options
            this.buildOptions(optionsContainer);
            
            this.menu.appendChild(header);
            this.menu.appendChild(optionsContainer);
            
            // Append to body for mobile
            document.body.appendChild(this.overlay);
            document.body.appendChild(this.menu);
        } else {
            // Build options directly for desktop
            this.buildOptions(this.menu);
        }
        
        // Assemble
        this.container.appendChild(this.trigger);
        if (!this.isMobile) {
            this.container.appendChild(this.menu);
        }
        
        // Replace select with custom dropdown
        this.select.parentNode.insertBefore(this.container, this.select);
    }

    buildOptions(container) {
        const options = Array.from(this.select.children);
        
        options.forEach(element => {
            if (element.tagName === 'OPTGROUP') {
                // Create optgroup label
                const groupLabel = document.createElement('div');
                groupLabel.className = 'custom-dropdown-optgroup';
                groupLabel.textContent = element.label;
                container.appendChild(groupLabel);
                
                // Add options in group
                Array.from(element.children).forEach(option => {
                    if (option.value) {
                        this.createOption(option, container);
                    }
                });
            } else if (element.tagName === 'OPTION' && element.value) {
                this.createOption(element, container);
            }
        });
    }

    createOption(option, container) {
        const optionDiv = document.createElement('div');
        optionDiv.className = 'custom-dropdown-option';
        optionDiv.textContent = option.text;
        optionDiv.dataset.value = option.value;
        
        if (option.selected) {
            optionDiv.classList.add('selected');
        }
        
        optionDiv.addEventListener('click', () => {
            this.selectOption(option.value, option.text);
        });
        
        container.appendChild(optionDiv);
    }

    bindEvents() {
        // Trigger click
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });
        
        // Close button (mobile)
        if (this.isMobile) {
            const closeBtn = this.menu.querySelector('.custom-dropdown-close');
            closeBtn?.addEventListener('click', () => this.close());
            
            this.overlay?.addEventListener('click', () => this.close());
        }
        
        // Close on outside click (desktop)
        if (!this.isMobile) {
            document.addEventListener('click', (e) => {
                if (!this.container.contains(e.target)) {
                    this.close();
                }
            });
        }
        
        // Keyboard support
        this.trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggle();
            }
        });
        
        this.trigger.setAttribute('tabindex', '0');
        this.trigger.setAttribute('role', 'button');
        this.trigger.setAttribute('aria-haspopup', 'listbox');
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        this.trigger.classList.add('active');
        this.menu.classList.add('active');
        
        if (this.isMobile) {
            this.overlay?.classList.add('active');
            document.body.classList.add('dropdown-open');
        }
        
        this.trigger.setAttribute('aria-expanded', 'true');
    }

    close() {
        this.isOpen = false;
        this.trigger.classList.remove('active');
        this.menu.classList.remove('active');
        
        if (this.isMobile) {
            this.overlay?.classList.remove('active');
            document.body.classList.remove('dropdown-open');
        }
        
        this.trigger.setAttribute('aria-expanded', 'false');
    }

    selectOption(value, text) {
        // Update native select
        this.select.value = value;
        
        // Trigger change event
        const event = new Event('change', { bubbles: true });
        this.select.dispatchEvent(event);
        
        // Update trigger text
        const textSpan = this.trigger.querySelector('span:first-child');
        textSpan.textContent = text;
        textSpan.className = 'selected-value';
        
        // Update selected state in options
        const options = this.menu.querySelectorAll('.custom-dropdown-option');
        options.forEach(opt => {
            if (opt.dataset.value === value) {
                opt.classList.add('selected');
            } else {
                opt.classList.remove('selected');
            }
        });
        
        // Close dropdown
        this.close();
    }

    destroy() {
        if (this.container && this.container.parentNode) {
            this.container.remove();
        }
        if (this.overlay && this.overlay.parentNode) {
            this.overlay.remove();
        }
        if (this.menu && this.menu.parentNode && this.isMobile) {
            this.menu.remove();
        }
        document.body.classList.remove('dropdown-open');
    }
}

// Initialize custom dropdowns
function initCustomDropdowns() {
    // Only initialize on mobile or for specific selects
    const selects = document.querySelectorAll('select[id="classToJoin"], select[id="gender"]');
    
    selects.forEach(select => {
        new CustomDropdown(select);
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCustomDropdowns);
} else {
    initCustomDropdowns();
}
