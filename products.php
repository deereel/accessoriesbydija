<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com fonts.googleapis.com; connect-src 'self' api.exchangerate-api.com; img-src 'self' data:; font-src 'self' fonts.gstatic.com;");
require_once 'config/database.php';
$page_title = 'All Products | Accessories by Dija';
include 'includes/header.php';

// --- FILTER DATA FETCHING ---

// Define gender filter options (only Men and Women, unisex products will appear in both)
$all_genders = ['Men', 'Women'];

// Fetch all categories from the 'categories' table
try {
    $stmt_cat = $pdo->query("SELECT name FROM categories ORDER BY name ASC");
    $all_categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $all_categories = [];
    // Log or handle the error appropriately
}


// --- BUILD QUERY BASED ON FILTERS ---
$selected_gender = $_GET['gender'] ?? '';
$selected_category = $_GET['category'] ?? '';
$selected_price_min = $_GET['price_min'] ?? '';
$selected_price_max = $_GET['price_max'] ?? '';

// Base query with all necessary joins
$sql = "SELECT p.*, 
               GROUP_CONCAT(DISTINCT c.name) as categories,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image, 
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 0 ORDER BY sort_order ASC LIMIT 1) as hover_image 
        FROM products p
        LEFT JOIN product_categories pc ON p.id = pc.product_id
        LEFT JOIN categories c ON pc.category_id = c.id";

$where = ["p.is_active = 1"];
$params = [];

// Handle gender filter using the products.gender column
if (!empty($selected_gender)) {
    $selected_gender_lower = strtolower($selected_gender);
    if ($selected_gender_lower === 'men') {
        // Show products for men: gender = 'men' OR gender = 'unisex'
        $where[] = "LOWER(p.gender) IN ('men', 'unisex')";
    } elseif ($selected_gender_lower === 'women') {
        // Show products for women: gender = 'women' OR gender = 'unisex'
        $where[] = "LOWER(p.gender) IN ('women', 'unisex')";
    }
}

// Handle category filter
if (!empty($selected_category)) {
    $where[] = "p.id IN (SELECT product_id FROM product_categories pc_cat JOIN categories c_cat ON pc_cat.category_id = c_cat.id WHERE LOWER(c_cat.name) = ?)";
    $params[] = strtolower($selected_category);
}

// Handle price filters
if (!empty($selected_price_min)) {
    $where[] = "p.price >= ?";
    $params[] = $selected_price_min;
}
if (!empty($selected_price_max)) {
    $where[] = "p.price <= ?";
    $params[] = $selected_price_max;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products = [];
    // It's a good idea to log the error for debugging
    // error_log($e->getMessage());
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
.products-area { flex: 1; position: relative; }

.filter-section { margin-bottom: 1.5rem; }
.filter-section h3 { font-weight: 500; margin-bottom: 0.75rem; }
.filter-list a { display: block; padding: 0.3rem 0; text-decoration: none; color: #333; }
.filter-list a:hover { color: #C27BA0; }
.filter-list a.active { font-weight: 700; color: #C27BA0; }


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
    transform: none !important; /* Prevent swiper transforms */
    transition: box-shadow 0.3s ease; /* Only allow box-shadow transitions */
}
.product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: none !important; /* Prevent any transforms on hover */
}

.wishlist-btn { position: absolute; top: 0.5rem; right: 0.5rem; background: white; border: none; border-radius: 50%; padding: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer; }
.product-image { aspect-ratio: 1; background: #f5f5f5; display: block; position: relative; overflow: hidden; }
.product-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
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
            <a href="/products.php" class="clear-btn">Clear Filters</a>

            <!-- Gender Filter -->
            <div class="filter-section">
                <h3>GENDER</h3>
                <div class="filter-list">
                    <a href="#" class="filter-link <?= !$selected_gender ? 'active' : '' ?>" data-filter-key="gender" data-filter-value="">All</a>
                    <?php foreach ($all_genders as $gender): ?>
                        <a href="#" class="filter-link <?= strtolower($selected_gender) == strtolower($gender) ? 'active' : '' ?>" data-filter-key="gender" data-filter-value="<?= htmlspecialchars($gender) ?>"><?= htmlspecialchars(ucfirst($gender)) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Category Filter -->
            <div class="filter-section">
                <h3>CATEGORY</h3>
                <div class="filter-list">
                    <a href="#" class="filter-link <?= !$selected_category ? 'active' : '' ?>" data-filter-key="category" data-filter-value="">All</a>
                    <?php foreach ($all_categories as $category): ?>
                        <a href="#" class="filter-link <?= strtolower($selected_category) == strtolower($category) ? 'active' : '' ?>" data-filter-key="category" data-filter-value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars(ucfirst($category)) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Price Filter -->
            <div class="filter-section">
                <h3>FILTER BY PRICE</h3>
                <div class="filter-list">
                    <a href="#" class="filter-link <?= (!$selected_price_min && !$selected_price_max) ? 'active' : '' ?>" data-filter-key="price" data-filter-value="">All</a>
                    <a href="#" class="filter-link <?= ($selected_price_min == 0 && $selected_price_max == 50) ? 'active' : '' ?>" data-filter-key="price" data-filter-value="0-50">£0 - £50</a>
                    <a href="#" class="filter-link <?= ($selected_price_min == 50 && $selected_price_max == 100) ? 'active' : '' ?>" data-filter-key="price" data-filter-value="50-100">£50 - £100</a>
                    <a href="#" class="filter-link <?= ($selected_price_min == 100 && $selected_price_max == 200) ? 'active' : '' ?>" data-filter-key="price" data-filter-value="100-200">£100 - £200</a>
                    <a href="#" class="filter-link <?= ($selected_price_min == 200 && $selected_price_max == 9999) ? 'active' : '' ?>" data-filter-key="price" data-filter-value="200-9999">£200+</a>
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
            <div class="swiper-container products-swiper">
                <div class="swiper-wrapper">
                    <?php
                    $chunks = array_chunk($products, 16);
                    if (empty($chunks)) {
                        echo '<div class="swiper-slide" style="display: flex; justify-content: center; align-items: center; min-height: 300px;"><p>No products found matching your criteria.</p></div>';
                    } else {
                        foreach ($chunks as $index => $chunk):
                        ?>
                        <div class="swiper-slide" data-slide-index="<?= $index ?>">
                            <div class="product-grid">
                                <?php foreach ($chunk as $product): ?>
                                   <div class="product-card"
                                       data-product-id="<?= $product['id'] ?>"
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
                                        <?php if ($product['weight']): ?>
                                        <p style="font-size: 0.75rem; color: #888; margin-bottom: 0.5rem;">⚖️ <?= htmlspecialchars($product['weight']) ?>g</p>
                                        <?php endif; ?>
                                        <div class="product-footer">
                                            <span class="product-price" data-price="<?= $product['price'] ?>">£<?= number_format($product['price'], 2) ?></span>
                                            <button class="cart-btn add-to-cart" data-product-id="<?= $product['id'] ?>">Add to Cart</button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach;
                    }
                    ?>
                </div>
                <!-- Navigation Controls -->
                <div class="swiper-navigation">
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterLinks = document.querySelectorAll('.filter-link');

    filterLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const url = new URL(window.location);
            const key = this.dataset.filterKey;
            const value = this.dataset.filterValue;

            if (key === 'price') {
                url.searchParams.delete('price_min');
                url.searchParams.delete('price_max');
                if (value) {
                    const [min, max] = value.split('-');
                    url.searchParams.set('price_min', min);
                    if(max) url.searchParams.set('price_max', max);
                }
            } else {
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            }
            window.location.href = url.toString();
        });
    });
});

// Cart functionality
document.querySelectorAll('.cart-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const productId = this.dataset.productId || this.closest('.product-card')?.dataset.productId;
        if (!productId) return;

        const card = this.closest('.product-card');
        const name = card?.dataset.name || '';
        const price = parseFloat(card?.dataset.price || 0);
        const image = card?.querySelector('.main-img')?.getAttribute('src') || '';

        const originalText = this.textContent;
        this.textContent = 'Adding...';

        try {
            const res = await fetch('/api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, product_name: name, price: price, image: image, quantity: 1 })
            });
            const data = await res.json();
            if (data.success) {
                this.textContent = 'Added!';
                if (window.cartHandler && typeof window.cartHandler.updateCartCount === 'function') {
                    window.cartHandler.updateCartCount();
                }
            } else {
                this.textContent = originalText;
                alert(data.message || 'Failed to add to cart');
            }
        } catch (err) {
            console.error(err);
            this.textContent = originalText;
            alert('Error adding to cart');
        }

        setTimeout(() => this.textContent = originalText, 1500);
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

// Custom Swiper functionality
document.addEventListener('DOMContentLoaded', function() {
    const swiperContainer = document.querySelector('.swiper-container.products-swiper');
    if (!swiperContainer) return;

    const slides = swiperContainer.querySelectorAll('.swiper-slide');
    const navigation = swiperContainer.querySelector('.swiper-navigation');
    const pagination = navigation.querySelector('.swiper-pagination');
    const nextBtn = navigation.querySelector('.swiper-button-next');
    const prevBtn = navigation.querySelector('.swiper-button-prev');

    if (slides.length === 0) return;

    let currentSlide = 0;

    // Initialize pagination bullets
    slides.forEach((_, index) => {
        const bullet = document.createElement('span');
        bullet.className = 'swiper-pagination-bullet';
        bullet.textContent = index + 1;
        bullet.addEventListener('click', () => goToSlide(index));
        pagination.appendChild(bullet);
    });

    // Show navigation buttons if more than one slide
    if (slides.length > 1) {
        nextBtn.style.display = 'flex';
        prevBtn.style.display = 'flex';
    } else {
        // For testing, show arrows even with one slide
        nextBtn.style.display = 'flex';
        prevBtn.style.display = 'flex';
    }

    function goToSlide(index) {
        // Hide current slide
        slides[currentSlide].classList.remove('active');

        // Update pagination
        const bullets = pagination.querySelectorAll('.swiper-pagination-bullet');
        bullets[currentSlide].classList.remove('swiper-pagination-bullet-active');

        // Set new slide
        currentSlide = index;

        // Show new slide
        slides[currentSlide].classList.add('active');

        // Update pagination
        bullets[currentSlide].classList.add('swiper-pagination-bullet-active');
    }

    function nextSlide() {
        const nextIndex = (currentSlide + 1) % slides.length;
        goToSlide(nextIndex);
    }

    function prevSlide() {
        const prevIndex = currentSlide === 0 ? slides.length - 1 : currentSlide - 1;
        goToSlide(prevIndex);
    }

    // Event listeners
    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);

    // Initialize first slide
    goToSlide(0);
});
</script>



<style>
/* Custom swiper container and slides */
.swiper-container {
    width: 100%;
    height: auto;
    position: relative;
}

.swiper-slide {
    display: none; /* Hidden by default, shown by JavaScript */
    opacity: 0;
    transition: opacity 0.3s ease; /* Only opacity transition */
    min-height: 400px;
}

.swiper-slide.active {
    display: block;
    opacity: 1;
}

/* Navigation controls container */
.swiper-navigation {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding: 1rem 0;
}

.swiper-pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin: 0;
}

.swiper-pagination-bullet {
    width: 30px;
    height: 30px;
    text-align: center;
    line-height: 30px;
    font-size: 12px;
    color:#000;
    opacity: 1;
    background: rgba(0,0,0,0.2);
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease;
    border: none;
    display: inline-block;
}

.swiper-pagination-bullet-active {
    color:#fff;
    background: #C27BA0;
}

.swiper-button-next, .swiper-button-prev {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.8);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: none;
    color: #C27BA0;
    cursor: pointer;
    transition: all 0.3s ease;
    display: none; /* Hidden by default, shown by JavaScript */
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.swiper-button-next::before {
    content: '→';
}

.swiper-button-prev::before {
    content: '←';
}

.swiper-button-next:hover, .swiper-button-prev:hover {
    background: rgba(194, 123, 160, 0.1);
    transform: scale(1.1);
}

/* Ensure product cards maintain their size */
.product-grid {
    width: 100%;
    max-width: 100%;
    transform: none !important;
    transition: none !important;
}

.product-card {
    transform: none !important;
    transition: box-shadow 0.3s ease;
}
</style>

<?php include 'includes/footer.php'; ?>