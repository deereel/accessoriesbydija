console.log('Loading new products...');
fetch('/api/new-products.php')
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
            // Limit to exactly 8 items for 4x2 grid
            const limitedProducts = data.products.slice(0, 8);
            limitedProducts.forEach(product => {
                const card = document.createElement('div');
                card.className = 'product-card';
                const imageHtml = product.image_url
                    ? `<img src="/${product.image_url}" alt="${product.name}" style="width:100%;height:100%;object-fit:cover;">`
                    : `<div class="placeholder-img">${product.name.substring(0, 3)}</div>`;
                card.innerHTML = `
                    <div class="product-image">
                        ${imageHtml}
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