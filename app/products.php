<?php
require_once 'config/database.php';

// Set page variables
$body_class = 'products-page';
if ($is_new_filter) {
    $page_title = 'New Arrivals | Accessories by Dija';
    $page_description = 'Discover our latest jewelry arrivals including new rings, necklaces, earrings, bracelets, and custom pieces. Fresh styles and trending designs with free shipping on orders over £100.';
} else {
    $page_title = 'All Products | Accessories by Dija';
    $page_description = 'Browse our complete collection of premium jewelry including rings, necklaces, earrings, bracelets, and custom pieces. Shop by category, material, and price range with free shipping on orders over £100.';
}

include 'includes/header.php';

// Structured Data for Product Collection
$collection_structured_data = [
    "@context" => "https://schema.org",
    "@type" => "CollectionPage",
    "name" => "Jewelry Collection - Accessories By Dija",
    "description" => "Complete collection of premium handcrafted jewelry including rings, necklaces, earrings, bracelets, and custom pieces.",
    "url" => "https://accessoriesbydija.uk/products.php",
    "mainEntity" => [
        "@type" => "ItemList"
    ],
    "publisher" => [
        "@type" => "Organization",
        "name" => "Accessories By Dija",
        "url" => "https://accessoriesbydija.uk"
    ]
];

echo '<script type="application/ld+json">' . json_encode($collection_structured_data) . '</script>';

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
    $stmt_mat = $pdo->query("SELECT name FROM materials ORDER BY name ASC");
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
$selected_categories = [];
if (isset($_GET['category'])) {
    if (is_array($_GET['category'])) {
        $selected_categories = $_GET['category'];
    } elseif (is_string($_GET['category']) && !empty($_GET['category'])) {
        $selected_categories = [$_GET['category']];
    }
}
$selected_prices = isset($_GET['price']) && is_array($_GET['price']) ? $_GET['price'] : [];

if (isset($_GET['price_min']) && isset($_GET['price_max'])) {
    $min = (float)$_GET['price_min'];
    $max = (float)$_GET['price_max'];
    $selected_prices[] = $min . '-' . $max;
}
$selected_materials = [];
if (isset($_GET['material'])) {
    if (is_array($_GET['material'])) {
        $selected_materials = $_GET['material'];
    } elseif (is_string($_GET['material']) && !empty($_GET['material'])) {
        $selected_materials = [$_GET['material']];
    }
}

$selected_colors = [];
if (isset($_GET['color'])) {
    if (is_array($_GET['color'])) {
        $selected_colors = $_GET['color'];
    } elseif (is_string($_GET['color']) && !empty($_GET['color'])) {
        $selected_colors = [$_GET['color']];
    }
}

$selected_adornments = [];
if (isset($_GET['adornment'])) {
    if (is_array($_GET['adornment'])) {
        $selected_adornments = $_GET['adornment'];
    } elseif (is_string($_GET['adornment']) && !empty($_GET['adornment'])) {
        $selected_adornments = [$_GET['adornment']];
    }
}

$is_new_filter = isset($_GET['new']) && $_GET['new'] == '1';

// Base query with all necessary joins
$sql = "SELECT p.*, 
               GROUP_CONCAT(DISTINCT c.name) as categories,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image, 
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 0 ORDER BY sort_order ASC LIMIT 1) as hover_image 
        FROM products p
        LEFT JOIN product_categories pc ON p.id = pc.product_id
        LEFT JOIN categories c ON pc.category_id = c.id";

$where = ["p.is_active = 1"];

if ($is_new_filter) {
    $where[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}
$params = [];

// Handle gender filter using the products.gender column
if (!empty($selected_genders)) {
    $gender_conditions = [];
    foreach ($selected_genders as $gender) {
        if (strtolower($gender) == 'men') {
            $gender_conditions[] = "(p.gender IN ('M', 'U'))";
        } elseif (strtolower($gender) == 'women') {
            $gender_conditions[] = "(p.gender IN ('F', 'W', 'U'))";
        }
    }
    if (!empty($gender_conditions)) {
        $where[] = '(' . implode(' OR ', $gender_conditions) . ')';
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

// Handle color filter
if (!empty($selected_colors)) {
    $color_placeholders = implode(',', array_fill(0, count($selected_colors), '?'));
    $where[] = "p.id IN (SELECT pv.product_id FROM product_variations pv WHERE LOWER(pv.color) IN (" . $color_placeholders . "))";
    foreach ($selected_colors as $color) {
        $params[] = strtolower($color);
    }
}

// Handle adornment filter
if (!empty($selected_adornments)) {
    $adornment_placeholders = implode(',', array_fill(0, count($selected_adornments), '?'));
    $where[] = "p.id IN (SELECT pv.product_id FROM product_variations pv WHERE LOWER(pv.adornment) IN (" . $adornment_placeholders . "))";
    foreach ($selected_adornments as $adornment) {
        $params[] = strtolower($adornment);
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

// Debug logging
AppLogger::debug("Products Filter Debug - SQL: " . $sql, [
    'params' => $params,
    'get' => $_GET,
    'selected_categories' => $selected_categories,
    'selected_genders' => $selected_genders
]);


try {
    start_performance_timer('products_query');
    list($stmt, ) = monitored_db_query($pdo, $sql, $params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    end_performance_timer('products_query', [
        'product_count' => count($products),
        'filters_applied' => count(array_filter([
            'gender' => !empty($selected_genders),
            'category' => !empty($selected_categories),
            'price' => !empty($selected_prices),
            'material' => !empty($selected_materials),
            'color' => !empty($selected_colors),
            'adornment' => !empty($selected_adornments)
        ]))
    ]);
    AppLogger::debug("Products query completed", [
        'product_count' => count($products),
        'sql_length' => strlen($sql)
    ]);
} catch (Exception $e) {
    $products = [];
    AppLogger::error("Products query failed: " . $e->getMessage(), [
        'sql' => $sql,
        'params' => $params
    ]);
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
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
/* Removed hover display, now handled by JS */

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
        <div class="filter-dropdown-toggle" onclick="document.querySelectorAll('.filter-dropdown-content').forEach(el => el.style.display = 'none'); const content = this.nextElementSibling; content.style.display = content.style.display === 'block' ? 'none' : 'block';">Gender</div>
        <div class="filter-dropdown-content">
            <?php foreach ($all_genders as $gender): ?>
                <label><input type="checkbox" name="gender[]" value="<?= htmlspecialchars($gender) ?>" <?= (isset($_GET['gender']) && in_array($gender, (array)$_GET['gender'])) ? 'checked' : '' ?>> <?= htmlspecialchars(ucfirst($gender)) ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Category Filter -->
    <div class="filter-dropdown">
        <div class="filter-dropdown-toggle" onclick="document.querySelectorAll('.filter-dropdown-content').forEach(el => el.style.display = 'none'); const content = this.nextElementSibling; content.style.display = content.style.display === 'block' ? 'none' : 'block';">Category</div>
        <div class="filter-dropdown-content">
            <?php foreach ($all_categories as $category): ?>
                <label><input type="checkbox" name="category[]" value="<?= htmlspecialchars($category) ?>" <?= (isset($_GET['category']) && in_array($category, (array)$_GET['category'])) ? 'checked' : '' ?>> <?= htmlspecialchars(ucfirst($category)) ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Material Filter -->
    <div class="filter-dropdown">
        <div class="filter-dropdown-toggle" onclick="document.querySelectorAll('.filter-dropdown-content').forEach(el => el.style.display = 'none'); const content = this.nextElementSibling; content.style.display = content.style.display === 'block' ? 'none' : 'block';">Material</div>
        <div class="filter-dropdown-content">
            <?php foreach ($all_materials as $material): ?>
                <label><input type="checkbox" name="material[]" value="<?= htmlspecialchars($material) ?>" <?= (isset($_GET['material']) && in_array($material, (array)$_GET['material'])) ? 'checked' : '' ?>> <?= htmlspecialchars(ucfirst($material)) ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Price Filter -->
    <div class="filter-dropdown">
        <div class="filter-dropdown-toggle" onclick="document.querySelectorAll('.filter-dropdown-content').forEach(el => el.style.display = 'none'); const content = this.nextElementSibling; content.style.display = content.style.display === 'block' ? 'none' : 'block';">Price</div>
        <div class="filter-dropdown-content">
            <label><input type="checkbox" name="price[]" value="0-50" <?= (isset($_GET['price']) && in_array('0-50', (array)$_GET['price'])) ? 'checked' : '' ?>> £0 - £50</label>
            <label><input type="checkbox" name="price[]" value="50-100" <?= (isset($_GET['price']) && in_array('50-100', (array)$_GET['price'])) ? 'checked' : '' ?>> £50 - £100</label>
            <label><input type="checkbox" name="price[]" value="100-200" <?= (isset($_GET['price']) && in_array('100-200', (array)$_GET['price'])) ? 'checked' : '' ?>> £100 - £200</label>
            <label><input type="checkbox" name="price[]" value="200-9999" <?= (isset($_GET['price']) && in_array('200-9999', (array)$_GET['price'])) ? 'checked' : '' ?>> £200+</label>
        </div>
    </div>
    <a href="products.php" class="clear-btn">Clear Filters</a>
</div>

        <!-- Products Area -->
        <div class="products-area">
            <div class="products-header">
                <h2><?= $is_new_filter ? 'New Arrivals' : 'All Products' ?> (<?= count($products) ?>)</h2>
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
                                         <p><?= htmlspecialchars(substr($product['short_description'] ?: $product['description'] ?: '', 0, 50)) ?>...</p>
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
        checkbox.addEventListener('change', function (e) {
            e.stopPropagation();
            updateUrlAndProducts();
        });
    });

    sortSelect.addEventListener('change', function () {
        updateUrlAndProducts();
    });

    function updateUrlAndProducts() {
        const url = new URL(window.location.href);
        const params = new URLSearchParams();

        // Add checked filter checkboxes
        filterCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                params.append(checkbox.name, checkbox.value);
            }
        });

        // Update sort parameter
        if (sortSelect.value) {
            params.set('sort', sortSelect.value);
        }

        // Update URL without reloading
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.pushState({}, '', newUrl);

        // Fetch filtered products
        fetchFilteredProducts(params.toString());
    }

    function fetchFilteredProducts(queryString) {
        const apiUrl = 'api/filtered-products.php?' + queryString;
        
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateProductGrid(data.products);
                    updateProductCount(data.count);
                } else {
                    console.error('API error:', data.message);
                }
            })
            .catch(error => console.error('Fetch error:', error));
    }

    function updateProductGrid(products) {
        const productGrid = document.querySelector('.swiper-wrapper');
        
        if (!products || products.length === 0) {
            productGrid.innerHTML = '<div class="swiper-slide" style="display: flex; justify-content: center; align-items: center; min-height: 300px; width: 100%;"><p>No products found matching your criteria.</p></div>';
            return;
        }

        // Create chunks of 16 products per slide
        const chunks = [];
        for (let i = 0; i < products.length; i += 16) {
            chunks.push(products.slice(i, i + 16));
        }

        let html = '';
        chunks.forEach((chunk, index) => {
            html += '<div class="swiper-slide" data-slide-index="' + index + '">';
            html += '<div class="product-grid">';

            chunk.forEach(product => {
                html += '<div class="product-card" data-product-id="' + product.id + '" data-price="' + product.price + '" data-type="jewelry" data-name="' + product.name + '"' + (product.hover_image ? ' data-has-hover-image="true"' : '') + '>';
                html += '<button class="wishlist-btn" data-product-id="' + product.id + '" onclick="toggleWishlist(' + product.id + ', this)"><i class="far fa-heart"></i></button>';
                html += '<a href="product.php?slug=' + product.slug + '" class="product-image">';
                
                if (product.main_image) {
                    html += '<img class="main-img" src="' + product.main_image + '" alt="' + product.name + '">';
                    if (product.hover_image) {
                        html += '<img class="hover-img" src="' + product.hover_image + '" alt="' + product.name + '">';
                    }
                } else {
                    html += '<div class="main-img placeholder">' + product.name.substring(0, 3) + '</div>';
                }
                
                html += '</a>';
                html += '<div class="product-info">';
                html += '<h3><a href="product.php?slug=' + product.slug + '" style="text-decoration:none;color:inherit;">' + product.name + '</a></h3>';
                html += '<p>' + product.short_description + '...</p>';
                
                if (product.weight) {
                    html += '<p style="font-size: 0.75rem; color: #888; margin-bottom: 0.5rem;">⚖️ ' + product.weight + 'g</p>';
                }
                
                html += '<div class="product-footer">';
                html += '<span class="product-price" data-price="' + product.price + '">£' + product.price.toFixed(2) + '</span>';
                const disabledAttr = product.stock_quantity <= 0 ? 'disabled' : '';
                html += '<button class="cart-btn add-to-cart" data-product-id="' + product.id + '" ' + disabledAttr + '>Add to Cart</button>';
                html += '</div>';
                html += '<div class="stock-badge" style="margin-top: 8px; text-align: center; font-size: 0.85rem; font-weight: 500;">';
                
                if (product.stock_quantity <= 0) {
                    html += '<span style="color: #d32f2f; background-color: #ffebee; padding: 4px 8px; border-radius: 4px; display: inline-block;">Out of Stock</span>';
                } else if (product.stock_quantity < 10) {
                    html += '<span style="color: #f57c00; background-color: #fff3e0; padding: 4px 8px; border-radius: 4px; display: inline-block;">Only ' + product.stock_quantity + ' left</span>';
                } else {
                    html += '<span style="color: #388e3c; background-color: #e8f5e9; padding: 4px 8px; border-radius: 4px; display: inline-block;">In Stock</span>';
                }
                
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });

            html += '</div>';
            html += '</div>';
        });

        productGrid.innerHTML = html;

        // Update swiper after changing slides
        swiper.update();

        // Reinitialize wishlist buttons
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

        // Re-attach add to cart listeners
        document.querySelectorAll('.add-to-cart').forEach(btn => {
            btn.addEventListener('click', function() {
                addToCart(this.getAttribute('data-product-id'));
            });
        });
    }

    function updateProductCount(count) {
        const header = document.querySelector('.products-header h2');
        if (header) {
            const isNew = new URLSearchParams(window.location.search).get('new') === '1';
            header.textContent = (isNew ? 'New Arrivals' : 'All Products') + ' (' + count + ')';
        }
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

    // Close filter dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.filter-dropdown')) {
            document.querySelectorAll('.filter-dropdown-content').forEach(content => content.style.display = 'none');
        }
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