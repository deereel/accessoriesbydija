// Mega menu functionality
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
        const menuType = this.dataset.menu;
        const megaMenu = document.getElementById('mega-menu');
        const dropdownContent = document.querySelector(`[data-content="${menuType}"]`);
        
        // Hide all dropdown contents
        document.querySelectorAll('.dropdown-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Show current dropdown content
        if (dropdownContent) {
            dropdownContent.classList.add('active');
            megaMenu.classList.add('active');
        }
    });
});

// Hide mega menu when mouse leaves
document.querySelector('.header').addEventListener('mouseleave', function() {
    const megaMenu = document.getElementById('mega-menu');
    megaMenu.classList.remove('active');
    document.querySelectorAll('.dropdown-content').forEach(content => {
        content.classList.remove('active');
    });
});

// Currency selector
document.getElementById('currency-btn').addEventListener('click', function() {
    document.getElementById('currency-dropdown').classList.toggle('active');
});

document.querySelectorAll('.currency-option').forEach(option => {
    option.addEventListener('click', function() {
        const currency = this.dataset.currency;
        document.getElementById('current-currency').textContent = currency;
        document.getElementById('currency-dropdown').classList.remove('active');
        
        // Update currency in localStorage
        localStorage.setItem('selectedCurrency', currency);
        
        // Trigger currency change event
        window.dispatchEvent(new CustomEvent('currencyChanged', { detail: currency }));
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.currency-selector')) {
        document.getElementById('currency-dropdown').classList.remove('active');
    }
});
