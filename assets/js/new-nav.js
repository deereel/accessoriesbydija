// Load new products for navigation
function loadNewProducts() {
    fetch('api/new-products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const grid = document.getElementById('new-products-grid');
                if (grid) {
                    grid.innerHTML = '';
                    
                    data.products.forEach(product => {
                        const card = document.createElement('div');
                        card.className = 'product-card';
                        card.innerHTML = `
                            <div class="product-image">
                                <div class="placeholder-img">${product.name.substring(0, 3)}</div>
                            </div>
                            <h4><a href="product.php?slug=${product.slug}">${product.name}</a></h4>
                            <p class="price">£${parseFloat(product.price).toFixed(2)}</p>
                        `;
                        grid.appendChild(card);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Failed to load new products:', error);
            const grid = document.getElementById('new-products-grid');
            if (grid) {
                grid.innerHTML = '<div class="error">Failed to load products</div>';
            }
        });
}

// Load on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadNewProducts);
} else {
    loadNewProducts();
}