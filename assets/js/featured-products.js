// Featured Products JavaScript
document.addEventListener('DOMContentLoaded', function() {
    loadFeaturedProducts();
});

async function loadFeaturedProducts() {
    const container = document.getElementById('featured-products');
    
    try {
        // Show loading spinner
        container.innerHTML = '<div class="loading-spinner">Loading featured products...</div>';
        
        const response = await fetch('api/featured-products.php');
        const data = await response.json();
        
        if (data.success && data.products.length > 0) {
                displayFeaturedProducts(data.products);
        } else {
            container.innerHTML = '<p style="text-align: center; color: #666;">No featured products available.</p>';
        }
    } catch (error) {
        console.error('Error loading featured products:', error);
        container.innerHTML = '<p style="text-align: center; color: #666;">Error loading products.</p>';
    }
}

function displayFeaturedProducts(products) {
    const container = document.getElementById('featured-products');
    
    const html = products.map(product => `
        <div class="featured-card" data-product-id="${product.id}">
            <div class="featured-image">
                <a href="product.php?slug=${product.slug}">
                ${product.image_url ? 
                    `<img src="${product.image_url}" alt="${product.name}" loading="lazy">` :
                    `<div class="featured-placeholder">💎</div>`
                }
                </a>
                <button class="wishlist-btn" onclick="toggleWishlist(${product.id})">
                    <i class="far fa-heart"></i>
                </button>
            </div>
            <div class="featured-info">
                <h3><a href="product.php?slug=${product.slug}" style="text-decoration:none;color:inherit;">${product.name}</a></h3>
                <p class="featured-description">${product.short_description || product.description?.substring(0, 80) + '...' || ''}</p>
                <div class="featured-footer">
                    <span class="featured-price"><span class="currency-symbol">£</span><span class="price-amount" data-price="${parseFloat(product.price).toFixed(2)}">${parseFloat(product.price).toFixed(2)}</span></span>
                    <div class="featured-actions">
                        <button class="action-btn add-to-cart" onclick="addToCart(${product.id})">
                            Add to Bag
                        </button>
                        <button class="action-btn quick-view" onclick="quickView(${product.id})">
                            Quick View
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html;
    
    // Update prices after loading products
    if (window.currencyConverter) {
        window.currencyConverter.convertAllPrices();
    }
}

function addToCart(productId) {
    const button = event.target;
    const originalText = button.textContent;
    
    button.textContent = 'Added!';
    button.style.background = '#C27BA0';
    
    // Simulate cart addition
    setTimeout(() => {
        button.textContent = originalText;
        button.style.background = '#222';
    }, 1500);
    
    console.log('Added product to cart:', productId);
}

function toggleWishlist(productId) {
    const button = event.target.closest('.wishlist-btn');
    const icon = button.querySelector('i');
    
    icon.classList.toggle('far');
    icon.classList.toggle('fas');
    
    if (icon.classList.contains('fas')) {
        button.style.background = '#C27BA0';
        button.style.color = 'white';
    } else {
        button.style.background = 'rgba(255,255,255,0.9)';
        button.style.color = '#666';
    }
    
    console.log('Toggled wishlist for product:', productId);
}

function quickView(productId) {
    // Placeholder for quick view functionality
    alert(`Quick view for product ${productId} - Feature coming soon!`);
    console.log('Quick view for product:', productId);
}