// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Mega menu functionality
    function initMegaMenu() {
        const navItems = document.querySelectorAll('.nav-item');
        const megaMenu = document.getElementById('mega-menu');
        
        if (!megaMenu || navItems.length === 0) return;

        // Mouse enter handler for nav items
        function handleNavItemEnter(e) {
            const menuType = this.dataset.menu;
            if (!menuType) return;
            
            // Hide all dropdown contents
            document.querySelectorAll('.dropdown-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show current dropdown content
            const dropdownContent = document.querySelector(`[data-content="${menuType}"]`);
            if (dropdownContent) {
                dropdownContent.classList.add('active');
                megaMenu.classList.add('active');
            }
        }

        // Add event listeners to each nav item
        navItems.forEach(item => {
            item.removeEventListener('mouseenter', handleNavItemEnter); // Remove existing to prevent duplicates
            item.addEventListener('mouseenter', handleNavItemEnter);
        });

        // Hide mega menu when mouse leaves the header or mega menu
        const header = document.querySelector('.header');
        if (header) {
            header.addEventListener('mouseleave', function() {
                megaMenu.classList.remove('active');
                document.querySelectorAll('.dropdown-content').forEach(content => {
                    content.classList.remove('active');
                });
            });
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-item') && !e.target.closest('.mega-menu')) {
                megaMenu.classList.remove('active');
                document.querySelectorAll('.dropdown-content').forEach(content => {
                    content.classList.remove('active');
                });
            }
        });
    }

    // Initialize megamenu
    initMegaMenu();

    // Re-initialize when navigating with turbolinks/pjax if needed
    document.addEventListener('turbolinks:load', initMegaMenu);
    window.addEventListener('popstate', initMegaMenu);
});

// Currency selector
function initCurrencySelector() {
    const currencyBtn = document.getElementById('currency-btn');
    const currencyDropdown = document.getElementById('currency-dropdown');
    
    if (!currencyBtn || !currencyDropdown) return;

    currencyBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        currencyDropdown.classList.toggle('active');
    });

    document.querySelectorAll('.currency-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.stopPropagation();
            const currency = this.dataset.currency;
            const currentCurrency = document.getElementById('current-currency');
            
            if (currentCurrency) {
                currentCurrency.textContent = currency;
            }
            
            currencyDropdown.classList.remove('active');
            
            // Update currency in localStorage
            localStorage.setItem('selectedCurrency', currency);
            
            // Trigger currency change event
            window.dispatchEvent(new CustomEvent('currencyChanged', { detail: currency }));
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        currencyDropdown.classList.remove('active');
    });

    // Prevent dropdown from closing when clicking inside it
    currencyDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
}

// Initialize currency selector when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initCurrencySelector();
    document.addEventListener('turbolinks:load', initCurrencySelector);
});
