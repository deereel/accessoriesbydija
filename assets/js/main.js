// Mobile Navigation
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }

    // Search functionality
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    window.location.href = `search.php?q=${encodeURIComponent(query)}`;
                }
            }
        });
    }

    // Load featured products on homepage
    if (document.getElementById('featured-products')) {
        loadFeaturedProducts();
    }

    // Load category products
    if (document.getElementById('product-grid')) {
        loadCategoryProducts();
    }

    // Newsletter form
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            if (email) {
                alert('Thank you for subscribing to our newsletter!');
                this.reset();
            }
        });
    }
});

// Featured Products
function loadFeaturedProducts() {
    const featuredProducts = [
        {
            id: 1,
            name: "Diamond Solitaire Ring",
            price: 299,
            image: "assets/images/ring-1.jpg",
            category: "rings"
        },
        {
            id: 2,
            name: "Pearl Drop Earrings",
            price: 89,
            image: "assets/images/earrings-1.jpg",
            category: "earrings"
        },
        {
            id: 3,
            name: "Gold Chain Necklace",
            price: 159,
            image: "assets/images/necklace-1.jpg",
            category: "necklaces"
        },
        {
            id: 4,
            name: "Silver Charm Bracelet",
            price: 79,
            image: "assets/images/bracelet-1.jpg",
            category: "bracelets"
        }
    ];

    const container = document.getElementById('featured-products');
    if (container) {
        container.innerHTML = featuredProducts.map(product => `
            <div class="product-card">
                <div class="product-image">
                    <img src="${product.image}" alt="${product.name}" loading="lazy" onerror="this.src='assets/images/placeholder.jpg'">
                    <div class="product-overlay">
                        <button class="btn btn-primary" onclick="addToCart(${product.id})">Add to Cart</button>
                    </div>
                </div>
                <div class="product-info">
                    <h3>${product.name}</h3>
                    <p class="price">$${product.price}</p>
                    <div class="rating">
                        <span class="stars">★★★★★</span>
                        <span class="review-count">(24)</span>
                    </div>
                </div>
            </div>
        `).join('');
    }
}

// Category Products
function loadCategoryProducts() {
    const urlParams = new URLSearchParams(window.location.search);
    const category = urlParams.get('cat') || '';
    const subcategory = urlParams.get('sub') || '';
    
    // Sample products - in real implementation, this would be an API call
    const products = generateSampleProducts(category, subcategory);
    
    const container = document.getElementById('product-grid');
    const countElement = document.getElementById('product-count');
    
    if (container && products) {
        container.innerHTML = products.map(product => `
            <div class="product-card">
                <div class="product-image">
                    <img src="${product.image}" alt="${product.name}" loading="lazy" onerror="this.src='assets/images/placeholder.jpg'">
                    <div class="product-overlay">
                        <button class="btn btn-primary" onclick="addToCart(${product.id})">Add to Cart</button>
                        <button class="btn btn-secondary" onclick="viewProduct(${product.id})">View Details</button>
                    </div>
                </div>
                <div class="product-info">
                    <h3>${product.name}</h3>
                    <p class="price">$${product.price}</p>
                    <div class="rating">
                        <span class="stars">${'★'.repeat(product.rating)}${'☆'.repeat(5-product.rating)}</span>
                        <span class="review-count">(${product.reviews})</span>
                    </div>
                </div>
            </div>
        `).join('');
        
        if (countElement) {
            countElement.textContent = products.length;
        }
    }
}

function generateSampleProducts(category, subcategory) {
    const productTypes = {
        rings: ['Solitaire Ring', 'Wedding Band', 'Cocktail Ring', 'Eternity Ring'],
        necklaces: ['Chain Necklace', 'Pendant Necklace', 'Choker', 'Statement Necklace'],
        earrings: ['Stud Earrings', 'Drop Earrings', 'Hoop Earrings', 'Chandelier Earrings'],
        bracelets: ['Chain Bracelet', 'Charm Bracelet', 'Bangle', 'Tennis Bracelet'],
        bangles: ['Gold Bangle', 'Silver Bangle', 'Diamond Bangle', 'Designer Bangle'],
        anklets: ['Chain Anklet', 'Charm Anklet', 'Beaded Anklet', 'Gold Anklet']
    };

    const materials = ['Gold', 'Silver', 'Platinum', 'Rose Gold'];
    const stones = ['Diamond', 'Ruby', 'Emerald', 'Sapphire', 'Pearl'];
    
    const types = productTypes[subcategory] || ['Jewelry Piece'];
    const products = [];
    
    for (let i = 0; i < 12; i++) {
        const type = types[i % types.length];
        const material = materials[Math.floor(Math.random() * materials.length)];
        const stone = Math.random() > 0.5 ? stones[Math.floor(Math.random() * stones.length)] : '';
        
        products.push({
            id: i + 1,
            name: `${material} ${stone} ${type}`.trim(),
            price: Math.floor(Math.random() * 500) + 50,
            image: `assets/images/${subcategory || category}-${(i % 4) + 1}.jpg`,
            rating: Math.floor(Math.random() * 2) + 4,
            reviews: Math.floor(Math.random() * 50) + 5
        });
    }
    
    return products;
}

// Cart functionality
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function addToCart(productId) {
    // Try server-side add first (if API available), otherwise fall back to localStorage
    (async () => {
        try {
            const res = await fetch('/api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            });

            const data = await res.json();
            if (data && data.success) {
                // If CartHandler exists, update its count; otherwise reload cart from server
                if (window.cartHandler && typeof window.cartHandler.updateCartCount === 'function') {
                    window.cartHandler.updateCartCount();
                } else {
                    // quick fetch to refresh header count
                    try {
                        const cRes = await fetch('/api/cart.php');
                        const cData = await cRes.json();
                        const countEl = document.querySelector('.cart-count');
                        if (countEl && cData.items) {
                            const total = cData.items.reduce((s,i) => s + (i.quantity||0), 0);
                            countEl.textContent = total;
                        }
                    } catch (e) {}
                }

                showNotification('Product added to cart!');
                return;
            }
        } catch (e) {
            // ignore and fallback to localStorage
        }

        // Fallback: use localStorage cart
        const existingItem = cart.find(item => item.id === productId);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({ id: productId, quantity: 1 });
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount();
        showNotification('Product added to cart!');
    })();
}

function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
}

function viewProduct(productId) {
    window.location.href = `product.php?id=${productId}`;
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: #c487a5;
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Initialize cart count on page load
updateCartCount();

// Add CSS for product cards
const style = document.createElement('style');
style.textContent = `
    .product-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
    }
    
    .product-image {
        position: relative;
        overflow: hidden;
    }
    
    .product-image img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .product-card:hover .product-image img {
        transform: scale(1.05);
    }
    
    .product-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .product-card:hover .product-overlay {
        opacity: 1;
    }
    
    .product-info {
        padding: 1.5rem;
    }
    
    .product-info h3 {
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
        color: #333;
    }
    
    .price {
        font-size: 1.3rem;
        font-weight: 700;
        color: #c487a5;
        margin-bottom: 0.5rem;
    }
    
    .rating {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .stars {
        color: #c487a5;
    }
    
    .review-count {
        color: #666;
        font-size: 0.9rem;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }
`;
document.head.appendChild(style);