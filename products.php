<?php
require_once 'config/database.php';

$page_title = 'All Products | Accessories by Dija';
include 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<main>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-light mb-2">ACCESSORIES IN OUR COLLECTION</h1>
            <div class="flex items-center text-sm text-gray-500">
                <a href="index.php">Home</a>
                <span class="mx-2">/</span>
                <span>Products</span>
            </div>
        </div>

        <div class="mb-12">
            <p>Discover our exquisite collection of handcrafted accessories.</p>
        </div>
    
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar Filters -->
            <div class="md:w-1/4 space-y-6">
                <button id="clear-filters-btn" class="mb-4 px-4 py-2 border border-gray-400 rounded hover:bg-gray-100 transition">Clear Filters</button>

                <div>
                    <h3 class="font-medium mb-3">FILTER BY TYPE</h3>
                    <div class="flex space-x-2">
                        <button class="type-filter px-3 py-1 border border-gray-300 rounded cursor-pointer active" data-type="all">All</button>
                        <button class="type-filter px-3 py-1 border border-gray-300 rounded cursor-pointer" data-type="jewelry">Jewelry</button>
                    </div>
                </div>

                <h3 class="font-medium mb-3">FILTER BY PRICE</h3>
                <div class="space-y-1">
                    <div><input id="price1" type="checkbox" class="mr-2"> <label for="price1">£10 - £25</label></div>
                    <div><input id="price2" type="checkbox" class="mr-2"> <label for="price2">£25 - £50</label></div>
                    <div><input id="price3" type="checkbox" class="mr-2"> <label for="price3">£50 - £100</label></div>
                    <div><input id="price4" type="checkbox" class="mr-2"> <label for="price4">£100+</label></div>
                </div>
            </div>
    
            <!-- Main Product Area -->
            <div class="md:w-3/4">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-light">All Products</h2>
                    <select class="border p-2" id="sortSelect">
                        <option value="">Sort by latest</option>
                        <option value="low">Sort by price: low to high</option>
                        <option value="high">Sort by price: high to low</option>
                    </select>
                </div>
    
                <!-- Product Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="product-grid">
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 50");
                        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($products as $product):
                            $mainImage = 'assets/images/placeholder.jpg';
                    ?>
                    <div class="group product-card relative"
                        data-price="<?= $product['price'] ?>"
                        data-type="jewelry"
                        data-created="<?= strtotime($product['created_at'] ?? 'now') ?>"
                        data-id="<?= $product['id'] ?>">
                        
                        <!-- Wishlist button -->
                        <div class="absolute top-2 right-2 z-10">
                            <button class="wishlist-btn bg-white rounded-full p-2 shadow-sm hover:shadow-md transition"
                                    data-product-id="<?= $product['id'] ?>">
                                <i class="far fa-heart text-gray-600 hover:text-red-500"></i>
                            </button>
                        </div>
                        
                        <!-- Product Image -->
                        <div class="relative overflow-hidden bg-gray-100 aspect-square">
                            <img src="<?= $mainImage ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='assets/images/placeholder.jpg'">
                        </div>
                        
                        <!-- Product Info -->
                        <div class="p-3">
                            <h3 class="font-medium text-sm mb-1"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="text-gray-600 text-xs mb-2"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 50)) ?>...</p>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold">£<?= number_format($product['price'], 2) ?></span>
                                <button class="cart-btn bg-black text-white px-3 py-1 text-xs rounded hover:bg-gray-800 transition"
                                        data-product-id="<?= $product['id'] ?>">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php } catch(PDOException $e) { echo "<p>Error loading products</p>"; } ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Filter functionality
    document.querySelectorAll('.type-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.type-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterProducts();
        });
    });

    document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', filterProducts);
    });

    document.getElementById('sortSelect').addEventListener('change', sortProducts);
    document.getElementById('clear-filters-btn').addEventListener('click', clearFilters);

    function filterProducts() {
        const cards = document.querySelectorAll('.product-card');
        const activeType = document.querySelector('.type-filter.active').dataset.type;
        const checkedPrices = Array.from(document.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.id);

        cards.forEach(card => {
            let show = true;
            const price = parseFloat(card.dataset.price);
            const type = card.dataset.type;

            if (activeType !== 'all' && type !== activeType) show = false;

            if (checkedPrices.length > 0) {
                let priceMatch = false;
                checkedPrices.forEach(priceId => {
                    if (priceId === 'price1' && price >= 10 && price <= 25) priceMatch = true;
                    if (priceId === 'price2' && price > 25 && price <= 50) priceMatch = true;
                    if (priceId === 'price3' && price > 50 && price <= 100) priceMatch = true;
                    if (priceId === 'price4' && price > 100) priceMatch = true;
                });
                if (!priceMatch) show = false;
            }

            card.style.display = show ? 'block' : 'none';
        });
    }

    function sortProducts() {
        const sortValue = document.getElementById('sortSelect').value;
        const container = document.getElementById('product-grid');
        const cards = Array.from(container.querySelectorAll('.product-card'));

        cards.sort((a, b) => {
            const priceA = parseFloat(a.dataset.price);
            const priceB = parseFloat(b.dataset.price);
            const dateA = parseInt(a.dataset.created);
            const dateB = parseInt(b.dataset.created);

            if (sortValue === 'low') return priceA - priceB;
            if (sortValue === 'high') return priceB - priceA;
            return dateB - dateA;
        });

        cards.forEach(card => container.appendChild(card));
    }

    function clearFilters() {
        document.querySelectorAll('.type-filter').forEach(b => b.classList.remove('active'));
        document.querySelector('.type-filter[data-type="all"]').classList.add('active');
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.getElementById('sortSelect').value = '';
        filterProducts();
    }

    // Cart functionality
    document.querySelectorAll('.cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const productId = this.dataset.productId;
            const existing = cart.find(item => item.id === productId);
            
            if (existing) {
                existing.quantity += 1;
            } else {
                cart.push({id: productId, quantity: 1});
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            this.textContent = 'Added!';
            setTimeout(() => this.textContent = 'Add to Cart', 1000);
        });
    });

    // Wishlist functionality
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            const productId = this.dataset.productId;
            const existing = wishlist.find(item => item.id === productId);
            const icon = this.querySelector('i');
            
            if (existing) {
                wishlist = wishlist.filter(item => item.id !== productId);
                icon.classList.remove('fas', 'text-red-500');
                icon.classList.add('far', 'text-gray-600');
            } else {
                wishlist.push({id: productId});
                icon.classList.remove('far', 'text-gray-600');
                icon.classList.add('fas', 'text-red-500');
            }
            
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
        });
    });
</script>

<?php include 'includes/footer.php'; ?>