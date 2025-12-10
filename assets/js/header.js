document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item[data-menu]');
    const megaMenu = document.getElementById('mega-menu');
    const dropdownContents = document.querySelectorAll('.dropdown-content');
    const hamburger = document.getElementById('hamburger');
    const mainNav = document.querySelector('.main-nav');
    
    let activeMenu = null;
    let hoverTimeout = null;

    // Desktop mega menu functionality
    navItems.forEach(item => {
        const menuType = item.getAttribute('data-menu');
        
        item.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
            showMegaMenu(menuType);
        });
        
        item.addEventListener('mouseleave', function() {
            hoverTimeout = setTimeout(() => {
                hideMegaMenu();
            }, 100);
        });
    });

    // Mega menu hover handling
    megaMenu.addEventListener('mouseenter', function() {
        clearTimeout(hoverTimeout);
    });

    megaMenu.addEventListener('mouseleave', function() {
        hideMegaMenu();
    });

    function showMegaMenu(menuType) {
        // Hide all dropdown contents
        dropdownContents.forEach(content => {
            content.classList.remove('active');
        });
        
        // Show specific dropdown content
        const targetContent = document.querySelector(`[data-content="${menuType}"]`);
        if (targetContent) {
            targetContent.classList.add('active');
            megaMenu.classList.add('active');
            activeMenu = menuType;
        }
    }

    function hideMegaMenu() {
        megaMenu.classList.remove('active');
        dropdownContents.forEach(content => {
            content.classList.remove('active');
        });
        activeMenu = null;
    }

    // Mobile hamburger menu
    hamburger.addEventListener('click', function() {
        mainNav.classList.toggle('active');
        hamburger.classList.toggle('active');
        
        // Animate hamburger lines
        const spans = hamburger.querySelectorAll('span');
        if (hamburger.classList.contains('active')) {
            spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
        } else {
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!mainNav.contains(e.target) && !hamburger.contains(e.target)) {
            mainNav.classList.remove('active');
            hamburger.classList.remove('active');
            
            const spans = hamburger.querySelectorAll('span');
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    });

    // Search functionality
    const searchInput = document.getElementById('search-input');
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query) {
                window.location.href = `search.php?q=${encodeURIComponent(query)}`;
            }
        }
    });

    // Product card click handlers with animation
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
                const productName = this.querySelector('h4').textContent;
                window.location.href = `product.php?name=${encodeURIComponent(productName)}`;
            }, 150);
        });
    });

    // Update cart count from localStorage
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            const totalItems = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
            cartCount.textContent = totalItems;
        }
    }

    updateCartCount();
});