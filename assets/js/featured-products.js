// Featured Products JavaScript
document.addEventListener('DOMContentLoaded', function() {
    loadFeaturedProducts();
    // Poll for stock updates every 5 seconds to reflect real-time changes
    setInterval(updateStockBadges, 5000);
});

async function loadFeaturedProducts() {
    const container = document.getElementById('featured-products');
    if (!container) return; // Not on a page with featured products

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

// Poll for stock updates without reloading entire page
async function updateStockBadges() {
    // Featured section uses .featured-card
    const productCards = document.querySelectorAll('.featured-card[data-product-id]');
    if (productCards.length === 0) return;
    
    const productIds = Array.from(productCards).map(card => card.getAttribute('data-product-id'));
    
    try {
        const response = await fetch('api/check-stock-levels.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ product_ids: productIds })
        });
        
        const data = await response.json();
        if (!data.success) return;
        
        // Update each product card with fresh stock data
        Object.entries(data.stocks || {}).forEach(([productId, stockData]) => {
            const card = document.querySelector(`.featured-card[data-product-id="${productId}"]`);
            if (card) {
                const stockBadge = card.querySelector('.stock-badge');
                const addBtn = card.querySelector('.add-to-cart');
                
                if (stockBadge && stockData.stock_quantity !== undefined) {
                    const qty = parseInt(stockData.stock_quantity);
                    let badgeHtml;
                    
                    if (qty <= 0) {
                        badgeHtml = '<span style="color: #d32f2f; background-color: #ffebee; padding: 4px 8px; border-radius: 4px; display: inline-block;">Out of Stock</span>';
                        if (addBtn) addBtn.disabled = true;
                    } else if (qty < 10) {
                        badgeHtml = `<span style="color: #f57c00; background-color: #fff3e0; padding: 4px 8px; border-radius: 4px; display: inline-block;">Only ${qty} left</span>`;
                        if (addBtn) addBtn.disabled = false;
                    } else {
                        badgeHtml = '<span style="color: #388e3c; background-color: #e8f5e9; padding: 4px 8px; border-radius: 4px; display: inline-block;">In Stock</span>';
                        if (addBtn) addBtn.disabled = false;
                    }
                    
                    stockBadge.innerHTML = badgeHtml;
                }
            }
        });
    } catch (error) {
        console.error('Error updating stock badges:', error);
    }
}

function displayFeaturedProducts(products) {
    const container = document.getElementById('featured-products');
    if (!container) return;
    container.innerHTML = '';

    // Ensure only up to 8 products are displayed for a 4x2 grid initially
    const productsToDisplay = products.slice(0, 8); // Take first 8 products

    productsToDisplay.forEach(product => {
        const card = document.createElement('div');
        card.className = 'featured-card';
        card.setAttribute('data-product-id', product.id);
        card.setAttribute('data-price', product.price);
        card.setAttribute('data-name', product.name);

        const price = Number.parseFloat(product.price || 0);
        const desc = product.description ? String(product.description).trim() : '';
        const shortDesc = desc ? (desc.length > 80 ? desc.substring(0, 80) + '…' : desc) : '';

        card.innerHTML = `
            <button class="wishlist-btn" data-product-id="${product.id}" onclick="toggleWishlist(${product.id})" aria-label="Add to wishlist">
                <i class="far fa-heart"></i>
            </button>

            <div class="featured-image">
                <a href="product.php?slug=${product.slug}" aria-label="View ${escapeHtml(product.name)}">
                    ${product.image_url ?
                        `<img src="${product.image_url}" alt="${escapeHtml(product.name)}" loading="lazy">` :
                        `<div class="featured-placeholder">${escapeHtml(product.name.substring(0, 2).toUpperCase())}</div>`
                    }
                </a>
            </div>

            <div class="featured-info">
                <h3><a href="product.php?slug=${product.slug}" style="text-decoration:none;color:inherit;">${escapeHtml(product.name)}</a></h3>
                ${shortDesc ? `<p class="featured-description">${escapeHtml(shortDesc)}</p>` : ''}
                ${product.weight ? `<p style="font-size: 0.75rem; color: #888; margin-bottom: 0.5rem;">⚖️ ${escapeHtml(String(product.weight))}g</p>` : ''}

                <div class="featured-footer">
                    <span class="featured-price" data-price="${price}">£${price.toFixed(2)}</span>
                    <div class="featured-actions">
                        <button class="action-btn add-to-cart" onclick="addToCart(${product.id})" data-product-id="${product.id}" ${product.stock_quantity <= 0 ? 'disabled' : ''}>
                            Add to Cart
                        </button>
                    </div>
                </div>

                <div class="stock-badge" style="margin-top: 8px; text-align: center; font-size: 0.85rem; font-weight: 500;">
                    ${product.stock_quantity <= 0 ?
                        '<span style="color: #d32f2f; background-color: #ffebee; padding: 4px 8px; border-radius: 4px; display: inline-block;">Out of Stock</span>' :
                        product.stock_quantity < 10 ?
                        `<span style="color: #f57c00; background-color: #fff3e0; padding: 4px 8px; border-radius: 4px; display: inline-block;">Only ${product.stock_quantity} left</span>` :
                        '<span style="color: #388e3c; background-color: #e8f5e9; padding: 4px 8px; border-radius: 4px; display: inline-block;">In Stock</span>'
                    }
                </div>
            </div>
        `;
        container.appendChild(card);
    });

    // Initialize wishlist button states
    initializeWishlistButtons();
}

// Simple HTML escape for text we inject into templates
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function addToCart(productId) {
    if (!window.cartHandler) {
        console.error('Cart handler not available.');
        return;
    }

    const button = event.target;
    const card = button.closest('.featured-card') || button.closest('.product-card');

    // Get productId from parameter or button data
    if (!productId) {
        productId = button.getAttribute('data-product-id');
    }
    if (!productId) {
        console.error('Product ID not found');
        return;
    }

    if (card) {
        // From featured products / product grids
        const productData = {
            product_id: productId,
            product_name: card.getAttribute('data-name') || '',
            price: parseFloat(card.getAttribute('data-price') || 0),
            image: card.querySelector('img')?.src || '',
            quantity: 1
        };
        window.cartHandler.addToCart(productData);
    } else {
        // Assume from product page or other, simple add
        const productData = {
            product_id: productId,
            quantity: 1
        };
        window.cartHandler.addToCart(productData);
    }
}

function toggleWishlist(productId, btn = null) {
    const button = btn || event.target.closest('.wishlist-btn');
    const icon = button.querySelector('i');

    // Check if user is logged in
    fetch('api/wishlist.php?product_id=' + productId)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                // Not logged in, redirect to login
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                return;
            }

            const isInWishlist = data.in_wishlist;
            const method = isInWishlist ? 'DELETE' : 'POST';
            const url = isInWishlist ? 'api/wishlist.php?product_id=' + productId : 'api/wishlist.php';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: method === 'POST' ? JSON.stringify({ product_id: productId }) : null
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (isInWishlist) {
                        // Remove from wishlist - update visual
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        button.style.background = 'rgba(255,255,255,0.9)';
                        button.style.color = '#666';
                    } else {
                        // Add to wishlist - update visual
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        button.style.background = '#C27BA0';
                        button.style.color = 'white';
                    }
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        })
        .catch(error => {
            console.error('Error checking wishlist:', error);
            alert('An error occurred. Please try again.');
        });
}

function initializeWishlistButtons() {
    const wishlistBtns = document.querySelectorAll('.wishlist-btn[data-product-id]');
    wishlistBtns.forEach(btn => {
        const productId = btn.getAttribute('data-product-id') || btn.getAttribute('onclick')?.match(/toggleWishlist\((\d+)/)?.[1];
        if (productId) {
            fetch('api/wishlist.php?product_id=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.in_wishlist) {
                        const icon = btn.querySelector('i');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        btn.style.background = '#C27BA0';
                        btn.style.color = 'white';
                    }
                })
                .catch(error => console.error('Error checking wishlist:', error));
        }
    });
}

function quickView(productId) {
    // Placeholder for quick view functionality
    alert(`Quick view for product ${productId} - Feature coming soon!`);
    console.log('Quick view for product:', productId);
}