<?php
$page_title = "Search Results";
include 'config/database.php';

$query = $_GET['q'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

include 'includes/header.php';

// Search products
$products = [];
$total_products = 0;

if (!empty($query)) {
    // Sanitize search query
    $search_term = trim($query);
    $search_term = htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8');

    try {
        // Try FULLTEXT search first
        $sql = "
            SELECT
                p.*,
                pi.image_url,
                MATCH(p.name, p.description, p.short_description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1
            AND MATCH(p.name, p.description, p.short_description) AGAINST(? IN NATURAL LANGUAGE MODE)
            ORDER BY relevance DESC, p.created_at DESC
            LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$search_term, $search_term]);
        $products = $stmt->fetchAll();

        // Get total count
        $count_sql = "
            SELECT COUNT(*) as total
            FROM products p
            WHERE p.is_active = 1
            AND MATCH(p.name, p.description, p.short_description) AGAINST(? IN NATURAL LANGUAGE MODE)
        ";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute([$search_term]);
        $total_products = $count_stmt->fetch()['total'];

    } catch (PDOException $e) {
        // Fallback to LIKE search if FULLTEXT index doesn't exist
        $like_term = '%' . $search_term . '%';

        $sql = "
            SELECT
                p.*,
                pi.image_url,
                1 as relevance
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1
            AND (p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)
            ORDER BY p.created_at DESC
            LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like_term, $like_term, $like_term]);
        $products = $stmt->fetchAll();

        // Get total count
        $count_sql = "
            SELECT COUNT(*) as total
            FROM products p
            WHERE p.is_active = 1
            AND (p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)
        ";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute([$like_term, $like_term, $like_term]);
        $total_products = $count_stmt->fetch()['total'];
    }
} else {
    // If no query, show featured products or redirect
    header('Location: products.php');
    exit;
}

$total_pages = ceil($total_products / $per_page);
$page_description = "Search results for '$search_term' - " . $total_products . " products found";
?>

<style>
.search-results-header {
    background: #f9f9f9;
    padding: 3rem 0;
    text-align: center;
}

.search-results-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: #333;
}

.search-query {
    font-size: 1.2rem;
    color: #666;
    margin-bottom: 1rem;
}

.search-stats {
    color: #888;
    font-size: 0.9rem;
}

.search-form {
    max-width: 600px;
    margin: 2rem auto;
    display: flex;
    gap: 10px;
}

.search-form input {
    flex: 1;
    padding: 1rem;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}

.search-form button {
    padding: 1rem 2rem;
    background: #C27BA0;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.search-form button:hover {
    background: #a66889;
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin: 2rem auto;
    max-width: 600px;
}

.no-results h2 {
    color: #333;
    margin-bottom: 1rem;
}

.no-results p {
    color: #666;
    margin-bottom: 2rem;
}

.suggestions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}

.suggestion-card {
    background: #f8f8f8;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    transition: transform 0.3s;
}

.suggestion-card:hover {
    transform: translateY(-2px);
}

.suggestion-card h3 {
    margin-bottom: 0.5rem;
    color: #333;
}

.suggestion-card a {
    color: #C27BA0;
    text-decoration: none;
    font-weight: 600;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin: 3rem 0;
}

.pagination a, .pagination span {
    padding: 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.pagination a:hover, .pagination .current {
    background: #C27BA0;
    color: white;
    border-color: #C27BA0;
}

.pagination .disabled {
    opacity: 0.5;
    pointer-events: none;
}

/* Products Grid Styles */
.products-section {
    padding: 3rem 0;
    background: white;
}

.products-section .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.product-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    width: 100%;
    max-width: 320px;
    margin: 0 auto;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-card a {
    text-decoration: none;
    color: inherit;
    display: block;
}

.product-image {
    position: relative;
    width: 100%;
    height: 220px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.placeholder-img {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    color: #999;
}

.product-info {
    padding: 1.5rem;
}

.product-info h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.product-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #C27BA0;
}

.product-sale-price {
    font-size: 1rem;
    color: #999;
    text-decoration: line-through;
    margin-left: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .product-card {
        max-width: 280px;
    }

    .product-image {
        height: 180px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .product-card {
        max-width: 100%;
    }
}
</style>

<main>
    <section class="search-results-header">
        <div class="container">
            <h1>Search Results</h1>
            <div class="search-query">"<?= htmlspecialchars($search_term) ?>"</div>
            <div class="search-stats">
                <?php if ($total_products > 0): ?>
                    Found <?= $total_products ?> product<?= $total_products !== 1 ? 's' : '' ?>
                    <?php if ($total_pages > 1): ?>
                        (Page <?= $page ?> of <?= $total_pages ?>)
                    <?php endif; ?>
                <?php else: ?>
                    No products found
                <?php endif; ?>
            </div>

            <form class="search-form" action="search.php" method="GET">
                <input type="text" name="q" value="<?= htmlspecialchars($search_term) ?>" placeholder="Search for products..." required>
                <button type="submit">Search</button>
            </form>
        </div>
    </section>

    <?php if (!empty($products)): ?>
        <section class="products-section">
            <div class="container">
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <a href="product.php?slug=<?= htmlspecialchars($product['slug']) ?>">
                                <div class="product-image">
                                    <?php if ($product['image_url']): ?>
                                        <img src="/<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php else: ?>
                                        <div class="placeholder-img"><?= htmlspecialchars(substr($product['name'], 0, 2)) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                                    <div class="product-price">£<?= number_format($product['price'], 2) ?></div>
                                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                        <div class="product-sale-price">£<?= number_format($product['sale_price'], 2) ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?q=<?= urlencode($search_term) ?>&page=<?= $page - 1 ?>" class="prev">Previous</a>
                        <?php else: ?>
                            <span class="disabled">Previous</span>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1): ?>
                            <a href="?q=<?= urlencode($search_term) ?>&page=1">1</a>
                            <?php if ($start_page > 2): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?q=<?= urlencode($search_term) ?>&page=<?= $i ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span>...</span>
                            <?php endif; ?>
                            <a href="?q=<?= urlencode($search_term) ?>&page=<?= $total_pages ?>"><?= $total_pages ?></a>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?q=<?= urlencode($search_term) ?>&page=<?= $page + 1 ?>" class="next">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="no-results-section">
            <div class="container">
                <div class="no-results">
                    <h2>No products found for "<?= htmlspecialchars($search_term) ?>"</h2>
                    <p>Try adjusting your search terms or browse our categories below.</p>

                    <div class="suggestions">
                        <div class="suggestion-card">
                            <h3>Rings</h3>
                            <a href="products.php?category=rings">Browse Rings</a>
                        </div>
                        <div class="suggestion-card">
                            <h3>Necklaces</h3>
                            <a href="products.php?category=necklaces">Browse Necklaces</a>
                        </div>
                        <div class="suggestion-card">
                            <h3>Earrings</h3>
                            <a href="products.php?category=earrings">Browse Earrings</a>
                        </div>
                        <div class="suggestion-card">
                            <h3>Bracelets</h3>
                            <a href="products.php?category=bracelets">Browse Bracelets</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>