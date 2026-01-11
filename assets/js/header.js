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

// Search functionality
function initSearch() {
    const searchInput = document.getElementById('search-input');
    const searchIcon = document.querySelector('.search-container i');
    let searchTimeout;
    let suggestionsContainer = null;

    if (!searchInput) return;

    // Create suggestions dropdown
    function createSuggestionsContainer() {
        if (suggestionsContainer) return;

        suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'search-suggestions';
        suggestionsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        `;

        searchInput.parentElement.style.position = 'relative';
        searchInput.parentElement.appendChild(suggestionsContainer);
    }

    // Show suggestions
    function showSuggestions(products, total) {
        if (!suggestionsContainer) createSuggestionsContainer();

        if (products.length === 0) {
            suggestionsContainer.innerHTML = '<div class="no-suggestions">No products found</div>';
        } else {
            let html = `<div class="suggestions-header">Found ${total} product${total !== 1 ? 's' : ''}</div>`;
            products.forEach(product => {
                html += `
                    <a href="${product.url}" class="suggestion-item">
                        <div class="suggestion-image">
                            ${product.image_url ? `<img src="${product.image_url}" alt="${product.name}">` : '<div class="no-image">💎</div>'}
                        </div>
                        <div class="suggestion-info">
                            <div class="suggestion-name">${product.name}</div>
                            <div class="suggestion-price">£${product.price.toFixed(2)}</div>
                        </div>
                    </a>
                `;
            });
            html += '<div class="suggestions-footer"><a href="search.php?q=' + encodeURIComponent(searchInput.value) + '">View all results</a></div>';
            suggestionsContainer.innerHTML = html;
        }

        suggestionsContainer.style.display = 'block';
    }

    // Hide suggestions
    function hideSuggestions() {
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }
    }

    // Search function
    function performSearch(query) {
        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        fetch(`api/search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuggestions(data.products, data.total);
                } else {
                    hideSuggestions();
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                hideSuggestions();
            });
    }

    // Input event handler
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(searchTimeout);

        if (query.length === 0) {
            hideSuggestions();
            return;
        }

        searchTimeout = setTimeout(() => performSearch(query), 300);
    });

    // Focus/blur handlers
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            performSearch(this.value.trim());
        }
    });

    searchInput.addEventListener('blur', function() {
        // Delay hiding to allow clicking on suggestions
        setTimeout(hideSuggestions, 150);
    });

    // Search icon click handler
    if (searchIcon) {
        searchIcon.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `search.php?q=${encodeURIComponent(query)}`;
            }
        });
    }

    // Enter key handler
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = this.value.trim();
            if (query) {
                window.location.href = `search.php?q=${encodeURIComponent(query)}`;
            }
        }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            hideSuggestions();
        }
    });
}

// Initialize currency selector when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initCurrencySelector();
    initSearch();
    document.addEventListener('turbolinks:load', function() {
        initCurrencySelector();
        initSearch();
    });
});
