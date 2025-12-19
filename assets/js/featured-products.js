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
    container.innerHTML = '';

    // Ensure only up to 8 products are displayed for a 4x2 grid initially
    const productsToDisplay = products.slice(0, 8); // Take first 8 products

    productsToDisplay.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.setAttribute('data-product-id', product.id);
        productCard.setAttribute('data-price', product.price);
        productCard.setAttribute('data-name', product.name);

        productCard.innerHTML = `
            <!-- Wishlist Button -->
            <button class="wishlist-btn">
                <i class="far fa-heart"></i>
            </button>

            <!-- Product Image -->
            <a href="product.php?slug=${product.slug}" class="product-image">
                ${product.image_url ?
                    `<img class="main-img" src="${product.image_url}" alt="${product.name}" loading="lazy">` :
                    `<div class="main-img placeholder">${product.name.substring(0, 3)}</div>`
                }
            </a>

            <!-- Product Info -->
            <div class="product-info">
                <h3><a href="product.php?slug=${product.slug}" style="text-decoration:none;color:inherit;">${product.name}</a></h3>
                <p>${product.description ? product.description.substring(0, 50) + '...' : ''}</p>
                ${product.weight ? `<p style="font-size: 0.75rem; color: #888; margin-bottom: 0.5rem;">⚖️ ${product.weight}g</p>` : ''}
                <div class="product-footer">
                    <span class="product-price" data-price="${product.price}">£${parseFloat(product.price).toFixed(2)}</span>
                    <button class="cart-btn add-to-cart" data-product-id="${product.id}">Add to Cart</button>
                </div>
            </div>
        `;
        container.appendChild(productCard);
    });
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