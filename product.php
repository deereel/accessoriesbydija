<?php
$page_title = "Product Details";
include 'config/database.php';
include 'includes/header.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: products.php');
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, pi.image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 WHERE p.slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$page_title = $product['name'];
$page_description = $product['short_description'] ?? substr($product['description'], 0, 160);
?>

<style>
.product-detail { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; }
.product-image { aspect-ratio: 1; background: #f8f8f8; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #C27BA0; }
.product-info h1 { font-size: 2rem; margin-bottom: 1rem; }
.product-price { font-size: 1.5rem; color: #C27BA0; font-weight: 600; margin-bottom: 1rem; }
.product-description { line-height: 1.6; margin-bottom: 2rem; }
.product-meta { margin-bottom: 2rem; }
.product-meta span { display: block; margin-bottom: 0.5rem; }
.product-actions { display: flex; gap: 1rem; }
.btn { padding: 1rem 2rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; }
.btn-primary { background: #222; color: white; }
.btn-primary:hover { background: #C27BA0; }
.btn-secondary { background: transparent; border: 1px solid #ddd; }
.breadcrumb { margin-bottom: 2rem; }
.breadcrumb a { color: #666; text-decoration: none; }
@media (max-width: 768px) { .product-detail { grid-template-columns: 1fr; gap: 2rem; } }
</style>

<main>
    <div class="breadcrumb">
        <a href="index.php">Home</a> / <a href="products.php">Products</a> / <?= htmlspecialchars($product['name']) ?>
    </div>

    <div class="product-detail">
        <div class="product-image">
            <?= $product['image_url'] ? "<img src='{$product['image_url']}' alt='{$product['name']}' style='width:100%;height:100%;object-fit:cover;border-radius:8px;'>" : '💎' ?>
        </div>
        
        <div class="product-info">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price">£<?= number_format($product['price'], 2) ?></div>
            
            <div class="product-description">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>
            
            <div class="product-meta">
                <span><strong>SKU:</strong> <?= htmlspecialchars($product['sku']) ?></span>
                <span><strong>Material:</strong> <?= htmlspecialchars($product['material']) ?></span>
                <?php if ($product['stone_type']): ?>
                <span><strong>Stone:</strong> <?= htmlspecialchars($product['stone_type']) ?></span>
                <?php endif; ?>
                <span><strong>Stock:</strong> <?= $product['stock_quantity'] ?> available</span>
            </div>
            
            <div class="product-actions">
                <button class="btn btn-primary" onclick="addToCart(<?= $product['id'] ?>)">Add to Cart</button>
                <button class="btn btn-secondary" onclick="toggleWishlist(<?= $product['id'] ?>)">♡ Wishlist</button>
            </div>
        </div>
    </div>
</main>

<script>
function addToCart(id) {
    event.target.textContent = 'Added!';
    setTimeout(() => event.target.textContent = 'Add to Cart', 1500);
}

function toggleWishlist(id) {
    const btn = event.target;
    btn.textContent = btn.textContent === '♡ Wishlist' ? '♥ Added' : '♡ Wishlist';
}
</script>

<?php include 'includes/footer.php'; ?>