<?php
require_once 'config/database.php';
$page_title = 'All Products | Accessories by Dija';
include 'includes/header.php';

// Build query based on filters
$where = ["is_active = 1"];
$params = [];

if (isset($_GET['material']) && $_GET['material']) {
    $where[] = "material = ?";
    $params[] = $_GET['material'];
}

if (isset($_GET['gender']) && $_GET['gender']) {
    $where[] = "gender = ?";
    $params[] = $_GET['gender'];
}

if (isset($_GET['price_min']) && $_GET['price_min']) {
    $where[] = "price >= ?";
    $params[] = $_GET['price_min'];
}

if (isset($_GET['price_max']) && $_GET['price_max']) {
    $where[] = "price <= ?";
    $params[] = $_GET['price_max'];
} elseif (isset($_GET['price_min']) && $_GET['price_min'] && !isset($_GET['price_max'])) {
    // Handle "300+" case where only min is set
    // No additional condition needed as price_min already handles this
}

if (isset($_GET['featured']) && $_GET['featured']) {
    $where[] = "is_featured = 1";
}

try {
    $sql = "SELECT * FROM products WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products = [];
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
<style>
/* Products Page Styles */
main { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
.page-header { margin-bottom: 2rem; }
.page-header h1 { font-size: 2rem; font-weight: 300; margin-bottom: 0.5rem; }
.breadcrumb { display: flex; align-items: center; font-size: 0.875rem; color: #666; }
.breadcrumb a { color: #666; text-decoration: none; }
.breadcrumb span { margin: 0 0.5rem; }

.products-layout { display: flex; gap: 2rem; }
.filters-sidebar { width: 250px; }
.products-area { flex: 1; }

.filter-section { margin-bottom: 1.5rem; }
.filter-section h3 { font-weight: 500; margin-bottom: 0.75rem; }
.filter-buttons { display: flex; gap: 0.5rem; }
.type-filter { padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 4px; background: white; cursor: pointer; }
.type-filter.active { background: #222; color: white; }
.price-filters { display: flex; flex-direction: column; gap: 0.5rem; }
.price-filters label { display: flex; align-items: center; gap: 0.5rem; }

.products-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.products-header h2 { font-size: 1.5rem; font-weight: 300; }
.sort-select { padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }

.product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* 4 products per row */
    gap: 1.5rem;
}
.product-card {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    transition: box-shadow 0.3s;
}
.product-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

.wishlist-btn { position: absolute; top: 0.5rem; right: 0.5rem; background: white; border: none; border-radius: 50%; padding: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer; }
.product-image { aspect-ratio: 1; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.875rem; }
.product-info { padding: 1rem; }
.product-info h3 { font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; }
.product-info p { font-size: 0.75rem; color: #666; margin-bottom: 0.5rem; }
.product-footer { display: flex; justify-content: space-between; align-items: center; }
.product-price { font-weight: 600; }
.cart-btn { background: #222; color: white; border: none; padding: 0.25rem 0.75rem; font-size: 0.75rem; border-radius: 4px; cursor: pointer; }
.cart-btn:hover { background: #333; }

.clear-btn { padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 4px; background: white; cursor: pointer; margin-bottom: 1rem; }
.clear-btn:hover { background: #f5f5f5; }

@media (max-width: 768px) {
    .products-layout { flex-direction: column; }
    .filters-sidebar { width: 100%; }
    .product-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
}
</style>

<main>
    <div class="page-header">
        <h1>ACCESSORIES IN OUR COLLECTION</h1>
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <span>Products</span>
        </div>
    </div>

    <div class="products-layout">
        <!-- Filters Sidebar -->
        <div class="filters-sidebar">
            <button id="clear-filters" class="clear-btn">Clear Filters</button>
            
            <!-- Type Filter -->
            <div class="filter-section">
                <h3>FILTER BY TYPE</h3>
                <div class="filter-buttons">
                    <button class="type-filter active" data-type="all">All</button>
                    <button class="type-filter" data-type="jewelry">Jewelry</button>
                </div>
            </div>

            <!-- Price Filter -->
            <div class="filter-section">
                <h3>FILTER BY PRICE</h3>
                <div class="price-filters">
                    <label><input type="checkbox" class="price-filter" data-min="0" data-max="50"> £0 - £50</label>
                    <label><input type="checkbox" class="price-filter" data-min="50" data-max="100"> £50 - £100</label>
                    <label><input type="checkbox" class="price-filter" data-min="100" data-max="200"> £100 - £200</label>
                    <label><input type="checkbox" class="price-filter" data-min="200" data-max="999"> £200+</label>
                </div>
            </div>
        </div>

        <!-- Products Area -->
        <div class="products-area">
            <div class="products-header">
                <h2>All Products (<?= count($products) ?>)</h2>
                <select id="sort-select" class="sort-select">
                    <option value="">Sort by latest</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                </select>
            </div>

            <!-- Products Grid -->
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    <?php
                    $chunks = array_chunk($products, 16);
                    foreach ($chunks as $chunk):
                    ?>
                    <div class="swiper-slide">
                        <div class="product-grid">
                            <?php foreach ($chunk as $product): ?>
                            <div class="product-card"
                                 data-price="<?= $product['price'] ?>"
                                 data-type="jewelry"
                                 data-name="<?= htmlspecialchars($product['name']) ?>">
                                
                                <!-- Wishlist Button -->
                                <button class="wishlist-btn">
                                    <i class="far fa-heart"></i>
                                </button>
                                
                                <!-- Product Image -->
                                <a href="product.php?slug=<?= $product['slug'] ?>" class="product-image">
                                    <?php if ($product['main_image']): ?>
                                        <img class="main-img" src="<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                        <?php if ($product['hover_image']): ?>
                                            <img class="hover-img" src="<?= htmlspecialchars($product['hover_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="main-img placeholder"><?= htmlspecialchars(substr($product['name'], 0, 3)) ?></div>
                                    <?php endif; ?>
                                </a>
                                
                                <!-- Product Info -->
                                <div class="product-info">
                                    <h3><a href="product.php?slug=<?= $product['slug'] ?>" style="text-decoration:none;color:inherit;"><?= htmlspecialchars($product['name']) ?></a></h3>
                                    <p><?= htmlspecialchars(substr($product['description'] ?? '', 0, 50)) ?>...</p>
                                    <div class="product-footer">
                                        <span class="product-price" data-price="<?= $product['price'] ?>">£<?= number_format($product['price'], 2) ?></span>
                                        <button class="cart-btn">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Add Pagination -->
                <div class="swiper-pagination"></div>
                <!-- Add Navigation -->
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
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

document.querySelectorAll('.price-filter').forEach(cb => {
    cb.addEventListener('change', filterProducts);
});

document.getElementById('sort-select').addEventListener('change', sortProducts);
document.getElementById('clear-filters').addEventListener('click', clearFilters);

function filterProducts() {
    const cards = document.querySelectorAll('.product-card');
    const activeType = document.querySelector('.type-filter.active').dataset.type;
    const checkedPrices = Array.from(document.querySelectorAll('.price-filter:checked'));

    cards.forEach(card => {
        let show = true;
        const price = parseFloat(card.dataset.price);
        const type = card.dataset.type;

        if (activeType !== 'all' && type !== activeType) show = false;

        if (checkedPrices.length > 0) {
            let priceMatch = false;
            checkedPrices.forEach(cb => {
                const min = parseFloat(cb.dataset.min);
                const max = parseFloat(cb.dataset.max);
                if (price >= min && price <= max) priceMatch = true;
            });
            if (!priceMatch) show = false;
        }

        card.style.display = show ? 'block' : 'none';
    });
}

function sortProducts() {
    const sortValue = document.getElementById('sort-select').value;
    const container = document.querySelector('.product-grid');
    const cards = Array.from(container.querySelectorAll('.product-card'));
    
    cards.sort((a, b) => {
        const priceA = parseFloat(a.dataset.price);
        const priceB = parseFloat(b.dataset.price);
        
        if (sortValue === 'price-low') return priceA - priceB;
        if (sortValue === 'price-high') return priceB - priceA;
        return 0;
    });
    
    cards.forEach(card => container.appendChild(card));
}

function clearFilters() {
    document.querySelectorAll('.type-filter').forEach(b => b.classList.remove('active'));
    document.querySelector('.type-filter[data-type="all"]').classList.add('active');
    document.querySelectorAll('.price-filter').forEach(cb => cb.checked = false);
    document.getElementById('sort-select').value = '';
    filterProducts();
}

// Cart functionality
document.querySelectorAll('.cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        this.textContent = 'Added!';
        setTimeout(() => this.textContent = 'Add to Cart', 1000);
    });
});

// Wishlist functionality
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const icon = this.querySelector('i');
        icon.classList.toggle('far');
        icon.classList.toggle('fas');
    });
});

<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
// Swiper initialization
var swiper = new Swiper('.swiper-container', {
    slidesPerView: 1,
    spaceBetween: 30,
    loop: false,
    autoplay: false,
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
});
</script>
</script>

<?php include 'includes/footer.php'; ?>
