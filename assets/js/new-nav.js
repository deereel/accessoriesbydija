console.log('Loading new products...');
fetch('api/new-products.php')
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        const data = JSON.parse(text);
        const grid = document.getElementById('new-products-grid');
        if (data.success && data.products && grid) {
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
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });