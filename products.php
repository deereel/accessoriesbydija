<?php
require_once 'config/database.php';

// Set page variables
$body_class = 'products-page';
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

// Fetch all materials
try {
    $stmt_mat = $pdo->query("SELECT DISTINCT m.name FROM materials m JOIN product_materials pm ON m.id = pm.material_id ORDER BY m.name ASC");
    $all_materials = $stmt_mat->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $all_materials = [];
}

$selected_genders = [];
if (isset($_GET['gender'])) {
    if (is_array($_GET['gender'])) {
        $selected_genders = $_GET['gender'];
    } elseif (is_string($_GET['gender']) && !empty($_GET['gender'])) {
        $selected_genders = [$_GET['gender']];
    }
}
$selected_categories = isset($_GET['category']) && is_array($_GET['category']) ? $_GET['category'] : [];
$selected_prices = isset($_GET['price']) && is_array($_GET['price']) ? $_GET['price'] : [];
$selected_materials = isset($_GET['material']) && is_array($_GET['material']) ? $_GET['material'] : [];

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
if (!empty($selected_genders)) {
    $gender_placeholders = implode(',', array_fill(0, count($selected_genders), '?'));
    $where[] = "LOWER(p.gender) IN (" . $gender_placeholders . ")";
    foreach ($selected_genders as $gender) {
        $params[] = strtolower($gender);
    }
}

// Handle category filter
if (!empty($selected_categories)) {
    $category_placeholders = implode(',', array_fill(0, count($selected_categories), '?'));
    $where[] = "p.id IN (SELECT product_id FROM product_categories pc_cat JOIN categories c_cat ON pc_cat.category_id = c_cat.id WHERE LOWER(c_cat.name) IN (" . $category_placeholders . "))";
    foreach ($selected_categories as $category) {
        $params[] = strtolower($category);
    }
}

// Handle price filters
if (!empty($selected_prices)) {
    $price_conditions = [];
    foreach ($selected_prices as $price_range) {
        list($min, $max) = explode('-', $price_range);
        $price_conditions[] = "(p.price >= ? AND p.price <= ?)";
        $params[] = $min;
        $params[] = $max;
    }
    $where[] = "(" . implode(" OR ", $price_conditions) . ")";
}

// Handle material filter
if (!empty($selected_materials)) {
    $material_placeholders = implode(',', array_fill(0, count($selected_materials), '?'));
    $where[] = "p.id IN (SELECT pm.product_id FROM product_materials pm JOIN materials m ON pm.material_id = m.id WHERE LOWER(m.name) IN (" . $material_placeholders . "))";
    foreach ($selected_materials as $material) {
        $params[] = strtolower($material);
    }
}



if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$current_sort = '';
$sort_order = "COALESCE(p.created_at, '1970-01-01') DESC, p.id DESC";
if (isset($_GET['sort']) && is_string($_GET['sort'])) {
    if ($_GET['sort'] === 'price-low') {
        $current_sort = 'price-low';
        $sort_order = 'CAST(p.price AS DECIMAL(10,2)) ASC, p.id ASC';
    } elseif ($_GET['sort'] === 'price-high') {
        $current_sort = 'price-high';
        $sort_order = 'CAST(p.price AS DECIMAL(10,2)) DESC, p.id DESC';
    }
}
$sql .= " GROUP BY p.id ORDER BY " . $sort_order;

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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<style>
/* Products Page Styles */
main { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
.page-header { margin-bottom: 2rem; }
.page-header h1 { font-size: 2rem; font-weight: 300; margin-bottom: 0.5rem; }
.breadcrumb { display: flex; align-items: center; font-size: 0.875rem; color: #666; }
.breadcrumb a { color: #666; text-decoration: none; }
.breadcrumb span { margin: 0 0.5rem; }

.filters-horizontal { display: flex; gap: 1rem; margin-bottom: 2rem; align-items: center; }
.filter-dropdown { position: relative; }
.filter-dropdown-toggle { padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 4px; background: white; cursor: pointer; }
.filter-dropdown-content { display: none; position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 1rem; z-index: 10; min-width: 200px; }
.filter-dropdown-content label { display: block; margin-bottom: 0.5rem; }
.filter-dropdown:hover .filter-dropdown-content { display: block; }

.products-layout { display: flex; flex-direction: column; gap: 2rem; }
.products-area { position: relative; }

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
.wishlist-btn.active { background: #C27BA0; color: white; }
.wishlist-btn.active i { color: white; }
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
    .filters-sidebar { 
        width: 100%; 
        display: grid;
        grid-template-columns: auto repeat(3, 1fr);
        gap: 0.75rem;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .clear-btn {
        grid-column: 1;
        margin-bottom: 0;
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
    }
    .filter-section {
        margin-bottom: 0;
        grid-column: span 1;
    }
    .filter-section h3 {
        display: none;
    }
    .filter-list {
        position: relative;
    }
    .filter-list::before {
        content: attr(data-label);
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #666;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
    }
    .filter-dropdown-toggle {
        display: block;
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: white;
        cursor: pointer;
        font-size: 0.85rem;
        text-align: left;
    }
    .filter-dropdown-toggle::after {
        content: ' ▼';
        font-size: 0.65rem;
        float: right;
    }
    .filter-list.mobile-dropdown .filter-link {
        display: none;
    }
    .filter-list.mobile-dropdown.active .filter-link {
        display: block;
        padding: 0.5rem 0.75rem;
        background: #f9f9f9;
        border-bottom: 1px solid #eee;
        font-size: 0.85rem;
    }
    .filter-list.mobile-dropdown .filter-link:last-child {
        border-bottom: none;
    }
    .filter-list.mobile-dropdown .filter-link.active {
        background: #f0f0f0;
        font-weight: 600;
    }
    .product-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    .product-info { padding: 0.75rem; }
    .product-info h3 { font-size: 0.75rem; }
    .product-info p { font-size: 0.7rem; }
    .cart-btn { padding: 0.2rem 0.5rem; font-size: 0.7rem; }
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
        <div class="filters-horizontal">
    <!-- Gender Filter -->
    <div class="filter-dropdown">
        <button class="filter-dropdown-toggle">Gender</button>
        <div class="filter-dropdown-content">
            <?php foreach ($all_genders as $gender): ?>
                <label><input type="checkbox" name="gender[]" value="<?= htmlspecialchars($gender) ?>" <?= (isset($_GET['gender']) && in_array($gender, (array)$_GET['gender'])) ? 'checked' : '' ?>> <?= htmlspecialchars(ucfirst($gender)) ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Category Filter -->
    <div class="filter-dropdown">
        <button class="filter-dropdown-toggle">Category</button>
        <div class="filter-dropdown-content">
            <?php foreach ($all_categories as $category): ?>
                <label><input type="checkbox" name="category[]" value="<?= htmlspecialchars($category) ?>" <?= (isset($_GET['category']) && in_array($category, (array)$_GET['category'])) ? 'checked' : '' ?>> <?= htmlspecialchars(ucfirst($category)) ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Material Filter -->
    <div class="filter-dropdown">
        <button class="filter-dropdown-toggle">Material</button>
        <div class="filter-dropdown-content">
            <?php foreach ($all_materials as $material): ?>
                <label><input type="checkbox" name="material[]" value="<?= htmlspecialchars($material) ?>" <?= (isset($_GET['material']) && in_array($material, (array)$_GET['material'])) ? 'checked' : '' ?>> <?= htmlspecialchars(ucfirst($material)) ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Price Filter -->
    <div class="filter-dropdown">
        <button class="filter-dropdown-toggle">Price</button>
        <div class="filter-dropdown-content">
            <label><input type="checkbox" name="price[]" value="0-50" <?= (isset($_GET['price']) && in_array('0-50', (array)$_GET['price'])) ? 'checked' : '' ?>> £0 - £50</label>
            <label><input type="checkbox" name="price[]" value="50-100" <?= (isset($_GET['price']) && in_array('50-100', (array)$_GET['price'])) ? 'checked' : '' ?>> £50 - £100</label>
            <label><input type="checkbox" name="price[]" value="100-200" <?= (isset($_GET['price']) && in_array('100-200', (array)$_GET['price'])) ? 'checked' : '' ?>> £100 - £200</label>
            <label><input type="checkbox" name="price[]" value="200-9999" <?= (isset($_GET['price']) && in_array('200-9999', (array)$_GET['price'])) ? 'checked' : '' ?>> £200+</label>
        </div>
    </div>
    <a href="/products.php" class="clear-btn">Clear Filters</a>
</div>

        <!-- Products Area -->
        <div class="products-area">
            <div class="products-header">
                <h2>All Products (<?= count($products) ?>)</h2>
                <select id="sort-select" class="sort-select">
                    <option value="" <?= $current_sort === '' ? 'selected' : '' ?>>Sort by latest</option>
                    <option value="price-low" <?= $current_sort === 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price-high" <?= $current_sort === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
                </select>
            </div>

            <!-- Products Grid -->
            <div class="swiper">
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
                                        data-name="<?= htmlspecialchars($product['name']) ?>"
                                        <?= $product['hover_image'] ? 'data-has-hover-image="true"' : '' ?>>

                                     <!-- Wishlist Button -->
                                     <button class="wishlist-btn" data-product-id="<?= $product['id'] ?>" onclick="toggleWishlist(<?= $product['id'] ?>, this)">
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
                                             <button class="cart-btn add-to-cart" data-product-id="<?= $product['id'] ?>" <?php if ($product['stock_quantity'] <= 0): ?>disabled<?php endif; ?>>Add to Cart</button>
                                         </div>
                                         <!-- Stock Status Badge -->
                                         <div class="stock-badge" style="margin-top: 8px; text-align: center; font-size: 0.85rem; font-weight: 500;">
                                             <?php if ($product['stock_quantity'] <= 0): ?>
                                                 <span style="color: #d32f2f; background-color: #ffebee; padding: 4px 8px; border-radius: 4px; display: inline-block;">Out of Stock</span>
                                             <?php elseif ($product['stock_quantity'] < 10): ?>
                                                 <span style="color: #f57c00; background-color: #fff3e0; padding: 4px 8px; border-radius: 4px; display: inline-block;">Only <?= $product['stock_quantity'] ?> left</span>
                                             <?php else: ?>
                                                 <span style="color: #388e3c; background-color: #e8f5e9; padding: 4px 8px; border-radius: 4px; display: inline-block;">In Stock</span>
                                             <?php endif; ?>
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
             </div>
             <div class="swiper-navigation">
                 <button class="swiper-button-prev" type="button"></button>
                 <div class="swiper-pagination"></div>
                 <button class="swiper-button-next" type="button"></button>
             </div>
        </div>
    </div>
</main>



<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterCheckboxes = document.querySelectorAll('.filter-dropdown-content input[type="checkbox"]');
    const sortSelect = document.getElementById('sort-select');

    filterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateUrl();
        });
    });

    sortSelect.addEventListener('change', function () {
        updateUrl();
    });

    function updateUrl() {
        const url = new URL(window.location.href.split('?')[0]);
        const params = new URLSearchParams();

        filterCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                params.append(checkbox.name, checkbox.value);
            }
        });

        if (sortSelect.value) {
            params.set('sort', sortSelect.value);
        }

        url.search = params.toString();
        window.location.href = url.toString();
    }

    const swiper = new Swiper('.swiper', {
        slidesPerView: 1,
        spaceBetween: 0,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
    });
});

// Wishlist functionality
function toggleWishlist(productId, btn) {
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
                        btn.classList.remove('active');
                        btn.querySelector('i').className = 'far fa-heart';
                    } else {
                        btn.classList.add('active');
                        btn.querySelector('i').className = 'fas fa-heart';
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

// Initialize wishlist button states
document.addEventListener('DOMContentLoaded', function() {
    const wishlistBtns = document.querySelectorAll('.wishlist-btn[data-product-id]');
    wishlistBtns.forEach(btn => {
        const productId = btn.getAttribute('data-product-id');
        fetch('api/wishlist.php?product_id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.in_wishlist) {
                    btn.classList.add('active');
                    btn.querySelector('i').className = 'fas fa-heart';
                }
            })
            .catch(error => console.error('Error checking wishlist:', error));
    });
});
</script>

<style>
/* Custom swiper container and slides */
.swiper {
    width: 100%;
    height: auto;
    position: relative;
    overflow: hidden; /* required so only the active slide is visible */
}

.swiper .swiper-wrapper {
    display: flex;
    flex-wrap: nowrap;
    align-items: flex-start;
}

.swiper .swiper-slide {
    width: 100%;
    height: auto;
    flex-shrink: 0;
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
    color: #000;
    opacity: 1;
    background: rgba(0,0,0,0.2);
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease;
    border: none;
    display: inline-block;
    font-weight: bold;
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
    display: flex;
    font-size: 16px;
    align-items: center;
    justify-content: center;
    font-family: 'Font Awesome 7 Free';
    font-weight: 900;
}

.swiper-button-next::before {
    content: '\f061'; /* fa-arrow-right */
}

.swiper-button-prev::before {
    content: '\f060'; /* fa-arrow-left */
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