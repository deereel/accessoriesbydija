<?php
$page_title = "Product Details";
include 'config/database.php';
include 'includes/header.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: products.php');
    exit;
}

$stmt = $pdo->prepare("SELECT p.* FROM products p WHERE p.slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

// Get all product images
$images_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$images_stmt->execute([$product['id']]);
$product_images = $images_stmt->fetchAll();

if (!$product) {
    header('Location: products.php');
    exit;
}

$page_title = $product['name'];
$page_description = $product['short_description'] ?? substr($product['description'], 0, 160);
?>

<style>
.breadcrumb { margin: 2rem auto 1rem; max-width: 1200px; padding: 0 1rem; }
.breadcrumb a { color: #666; text-decoration: none; }
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
</style>

<main>
    <div class="breadcrumb">
        <a href="index.php">Home</a> / <a href="products.php">Products</a> / <?= htmlspecialchars($product['name']) ?>
    </div>

    <div class="product-detail-container">
        <div class="product-images">
            <div class="main-image-container">
                <?php if (!empty($product_images)): ?>
                    <img class="main-image" id="mainImage" src="<?= htmlspecialchars($product_images[0]['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php else: ?>
                    <div class="main-image" id="mainImage">💎</div>
                <?php endif; ?>
            </div>
            <div class="image-thumbnails">
                <?php if (!empty($product_images)): ?>
                    <?php foreach ($product_images as $index => $image): ?>
                        <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="changeImage(this, '<?= htmlspecialchars($image['image_url']) ?>')">
                            <img src="<?= htmlspecialchars($image['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="thumbnail active" onclick="changeImage(this)">
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:24px;">💎</div>
                    </div>
                    <div class="thumbnail" onclick="changeImage(this)">
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:24px;">✨</div>
                    </div>
                    <div class="thumbnail" onclick="changeImage(this)">
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:24px;">💍</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="product-details">
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
function changeImage(thumbnail, imageUrl) {
    // Remove active class from all thumbnails
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    // Add active class to clicked thumbnail
    thumbnail.classList.add('active');
    // Update main image
    const mainImage = document.getElementById('mainImage');
    if (imageUrl) {
        mainImage.src = imageUrl;
    } else {
        mainImage.innerHTML = thumbnail.innerHTML;
    }
}

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