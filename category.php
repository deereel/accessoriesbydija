<?php
$category = $_GET['cat'] ?? '';
$subcategory = $_GET['sub'] ?? '';

$page_title = ucfirst($subcategory ?: $category);
$page_description = "Browse our premium " . ($subcategory ?: $category) . " collection at Dija Accessories. Quality jewelry for every occasion.";

include 'includes/header.php';
?>

<main>
    <section class="category-hero">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <?php if ($category): ?>
                    <span>/</span>
                    <a href="category.php?cat=<?php echo $category; ?>"><?php echo ucfirst($category); ?></a>
                <?php endif; ?>
                <?php if ($subcategory): ?>
                    <span>/</span>
                    <span><?php echo ucfirst($subcategory); ?></span>
                <?php endif; ?>
            </div>
            <h1><?php echo ucfirst($subcategory ?: $category); ?> Collection</h1>
            <p>Discover our exquisite <?php echo strtolower($subcategory ?: $category); ?> crafted with precision and elegance</p>
        </div>
    </section>

    <section class="category-content">
        <div class="container">
            <div class="category-layout">
                <aside class="filters">
                    <h3>Filter By</h3>
                    
                    <div class="filter-group">
                        <h4>Price Range</h4>
                        <div class="price-range">
                            <input type="range" id="price-min" min="0" max="1000" value="0">
                            <input type="range" id="price-max" min="0" max="1000" value="1000">
                            <div class="price-display">
                                <span>$<span id="min-price">0</span></span>
                                <span>$<span id="max-price">1000</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="filter-group">
                        <h4>Material</h4>
                        <label><input type="checkbox" value="gold"> Gold</label>
                        <label><input type="checkbox" value="silver"> Silver</label>
                        <label><input type="checkbox" value="platinum"> Platinum</label>
                        <label><input type="checkbox" value="rose-gold"> Rose Gold</label>
                    </div>

                    <div class="filter-group">
                        <h4>Stone Type</h4>
                        <label><input type="checkbox" value="diamond"> Diamond</label>
                        <label><input type="checkbox" value="ruby"> Ruby</label>
                        <label><input type="checkbox" value="emerald"> Emerald</label>
                        <label><input type="checkbox" value="sapphire"> Sapphire</label>
                        <label><input type="checkbox" value="pearl"> Pearl</label>
                    </div>

                    <?php if ($subcategory === 'rings'): ?>
                    <div class="filter-group">
                        <h4>Ring Size</h4>
                        <select id="ring-size">
                            <option value="">Select Size</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </aside>

                <div class="products-section">
                    <div class="products-header">
                        <div class="results-count">
                            <span id="product-count">Loading...</span> products found
                        </div>
                        <div class="sort-options">
                            <select id="sort-by">
                                <option value="featured">Featured</option>
                                <option value="price-low">Price: Low to High</option>
                                <option value="price-high">Price: High to Low</option>
                                <option value="newest">Newest First</option>
                                <option value="rating">Highest Rated</option>
                            </select>
                        </div>
                    </div>

                    <div class="product-grid" id="product-grid">
                        <!-- Products loaded via JavaScript -->
                    </div>

                    <div class="pagination" id="pagination">
                        <!-- Pagination loaded via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.category-hero {
    background: linear-gradient(135deg, #f8f8f8 0%, #fff 100%);
    padding: 2rem 0;
    margin-top: 80px;
}

.breadcrumb {
    margin-bottom: 1rem;
    color: #666;
}

.breadcrumb a {
    color: #c487a5;
    text-decoration: none;
}

.breadcrumb span {
    margin: 0 0.5rem;
}

.category-content {
    padding: 2rem 0;
}

.category-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
}

.filters {
    background: #f8f8f8;
    padding: 1.5rem;
    border-radius: 8px;
    height: fit-content;
}

.filter-group {
    margin-bottom: 2rem;
}

.filter-group h4 {
    margin-bottom: 1rem;
    color: #333;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    cursor: pointer;
}

.filter-group input[type="checkbox"] {
    margin-right: 0.5rem;
}

.price-range {
    margin-top: 1rem;
}

.price-display {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    font-weight: 600;
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
}

.pagination button {
    padding: 0.5rem 1rem;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
    border-radius: 4px;
}

.pagination button.active {
    background: #c487a5;
    color: white;
    border-color: #c487a5;
}

@media (max-width: 768px) {
    .category-layout {
        grid-template-columns: 1fr;
    }
    
    .filters {
        order: 2;
    }
    
    .products-section {
        order: 1;
    }
    
    .products-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
}
</style>

<?php include 'includes/footer.php'; ?>