<?php
$page_title = "Product Details";
include 'config/database.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: products.php');
    exit;
}

include 'includes/header.php';

// Get product
$stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ?");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get product images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->execute([$product['id']]);
$images = $stmt->fetchAll();

// Check and move images from wrong directory if necessary
foreach ($images as &$image) {
    $image_url = $image['image_url'];
    // Remove leading slash if it exists
    if (strpos($image_url, '/') === 0) {
        $image_url = substr($image_url, 1);
    }

    if (!file_exists($image_url)) {
        $wrong_path = 'admin/' . $image_url;
        if (file_exists($wrong_path)) {
            $dir = dirname($image_url);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            if (rename($wrong_path, $image_url)) {
                $image['image_url'] = $image_url;
            }
        }
    }
}
unset($image); // Unset the reference to the last element

// If no images in database, check filesystem
if (empty($images)) {
    $image_dir = "assets/images/products/";
    $image_files = [];
    if (is_dir($image_dir)) {
        $files = scandir($image_dir);
        foreach ($files as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                if (strpos($file, $product['id'] . '_') === 0) {
                    $image_files[] = $image_dir . $file;
                }
            }
        }
    }

    foreach ($image_files as $index => $file) {
        $images[] = [
            'image_url' => $file,
            'is_primary' => $index === 0 ? 1 : 0,
            'alt_text' => 'Product image'
        ];
    }
}

// Get all materials for this product
$stmt = $pdo->prepare("
    SELECT DISTINCT m.id, m.name
    FROM materials m
    JOIN product_variations pv ON m.id = pv.material_id
    WHERE pv.product_id = ?
    ORDER BY m.name
");
$stmt->execute([$product['id']]);
$materials = $stmt->fetchAll();

// Get reviews for this product
$stmt = $pdo->prepare("SELECT * FROM testimonials WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC");
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll();

$page_title = $product['name'];
$page_description = substr($product['description'], 0, 160);
?>

<style>
.breadcrumb { margin: 2rem auto 1rem; max-width: 1200px; padding: 0 1rem; }
.breadcrumb a { color: #666; text-decoration: none; }
.product-info h1 { font-size: 2rem; margin-bottom: 1rem; }
.product-price { font-size: 1.5rem; color: #C27BA0; font-weight: 600; margin-bottom: 1rem; }
.product-description { line-height: 1.6; margin-bottom: 2rem; }
.product-options { margin-bottom: 2rem; }
.option-group { margin-bottom: 1.5rem; }
.option-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
.option-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
.option-btn { padding: 8px 16px; border: 2px solid #ddd; background: white; cursor: pointer; border-radius: 4px; transition: all 0.3s; }
.option-btn:hover { border-color: #C27BA0; }
.option-btn.selected { border-color: #C27BA0; background: #C27BA0; color: white; }
.option-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.product-actions { display: flex; gap: 1rem; }
.btn { padding: 1rem 2rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; }
.btn-primary { background: #222; color: white; }
.btn-primary:hover { background: #C27BA0; }
.btn-secondary { background: transparent; border: 1px solid #ddd; }
.price-display { font-size: 1.2rem; margin-bottom: 1rem; }
.stock-info { color: #666; margin-bottom: 1rem; }

/* Reviews Styles */
.product-reviews { background: #f9f9f9; padding: 3rem 0; }
.product-reviews h2 { text-align: center; margin-bottom: 2rem; color: #333; }
.product-reviews .container { max-width: 800px; margin: 0 auto; padding: 0 1rem; }

.review-form-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
.review-form-container h3 { margin-bottom: 1.5rem; color: #333; }
.form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
.form-row .form-group { flex: 1; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
.form-group textarea { resize: vertical; }

.reviews-list { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
.review-item { padding: 1.5rem; border-bottom: 1px solid #eee; }
.review-item:last-child { border-bottom: none; }
.review-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.review-author { display: flex; align-items: center; gap: 1rem; }
.review-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
.review-avatar-placeholder { width: 50px; height: 50px; border-radius: 50%; background: #C27BA0; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
.review-meta { flex: 1; }
.review-rating { margin-top: 0.25rem; }
.star { color: #ddd; font-size: 1.2rem; }
.star.filled { color: #ffc107; }
.review-date { color: #666; font-size: 0.9rem; }
.review-title { margin: 0 0 0.5rem 0; color: #333; font-size: 1.1rem; }
.review-content { color: #555; line-height: 1.6; margin: 0; }
.no-reviews { text-align: center; padding: 2rem; color: #666; font-style: italic; }
</style>

<main>
    <div class="breadcrumb">
        <a href="index.php">Home</a> / <a href="products.php">Products</a> / <?= htmlspecialchars($product['name']) ?>
    </div>

    <div class="product-detail-container">
        <div class="product-images">
            <div class="main-image-container">
                <div class="main-image" id="mainImage">
                    <?php if ($images && isset($images[0])): ?>
                        <img src="/<?= htmlspecialchars($images[0]['image_url']) ?>" alt="<?= htmlspecialchars($images[0]['alt_text'] ?? 'Product image') ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        💎
                    <?php endif; ?>
                </div>
            </div>
            <div class="image-thumbnails">
                <?php if ($images): ?>
                    <?php foreach ($images as $index => $image): ?>
                    <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="changeImage(this)" data-image-id="<?= $image['id'] ?>">
                        <img src="/<?= htmlspecialchars($image['image_url']) ?>" alt="Product image">
                    </div>                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="thumbnail active" onclick="changeImage(this)">
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:24px;">💎</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="product-details">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price" id="basePrice">£<?= number_format($product['price'], 2) ?></div>
            <div class="price-display" id="finalPrice" style="display: none;"></div>
            
            <div class="product-description">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>
            
            <div class="product-options">
                <div class="option-group">
                    <label>Material:</label>
                    <div class="option-buttons" id="materialOptions">
                        <?php foreach ($materials as $material): ?>
                        <button class="option-btn" data-material-id="<?= $material['id'] ?>" onclick="selectMaterial(<?= $material['id'] ?>)">
                            <?= htmlspecialchars($material['name']) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="option-group" id="variationGroup" style="display: none;">
                    <label>Variation:</label>
                    <div class="option-buttons" id="variationOptions"></div>
                </div>
                
                <div class="option-group" id="sizeGroup" style="display: none;">
                    <label>Size:</label>
                    <div class="option-buttons" id="sizeOptions"></div>
                </div>
            </div>
            
            <div class="stock-info" id="stockInfo"></div>
            
            <div class="component-summary" id="componentSummary" style="display: none; background: #f9f9f9; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <h3 style="margin-bottom: 0.5rem; font-size: 1.1rem;">Component Summary</h3>
                <div id="summaryContent"></div>
            </div>
            
            <div class="quantity-selector" id="quantitySelector" style="display: none; margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Quantity:</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" onclick="changeQuantity(-1)" class="btn" style="padding: 0.5rem; width: 40px;">-</button>
                    <input type="number" id="quantityInput" value="1" min="1" style="width: 80px; text-align: center; padding: 0.5rem;">
                    <button type="button" onclick="changeQuantity(1)" class="btn" style="padding: 0.5rem; width: 40px;">+</button>
                </div>
            </div>
            
            <div class="product-actions">
                <button class="btn btn-primary" id="addToCartBtn" onclick="addToCartFromProduct()" data-product-id="<?= $product['id'] ?>" disabled>Select Options</button>
                <button class="btn btn-secondary" onclick="toggleWishlist()">♡ Wishlist</button>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <section class="product-reviews">
        <div class="container">
            <h2>Customer Reviews</h2>

            <!-- Review Button -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <button id="writeReviewBtn" class="btn btn-primary" onclick="toggleReviewForm()">Write a Review</button>
            </div>

            <!-- Review Form -->
            <div class="review-form-container" id="reviewFormContainer" style="display: none;">
                <h3>Write a Review</h3>
                <form id="reviewForm" onsubmit="submitReview(event)">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_name">Name *</label>
                            <input type="text" id="customer_name" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rating">Rating *</label>
                        <select id="rating" name="rating" required>
                            <option value="">Select Rating</option>
                            <option value="5">5 Stars - Excellent</option>
                            <option value="4">4 Stars - Very Good</option>
                            <option value="3">3 Stars - Good</option>
                            <option value="2">2 Stars - Fair</option>
                            <option value="1">1 Star - Poor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="title">Review Title</label>
                        <input type="text" id="title" name="title" placeholder="Summarize your experience">
                    </div>
                    <div class="form-group">
                        <label for="content">Review *</label>
                        <textarea id="content" name="content" rows="4" required placeholder="Tell others about your experience with this product"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            </div>

            <!-- Reviews Display -->
            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-author">
                                <?php if ($review['client_image']): ?>
                                    <img src="<?= htmlspecialchars($review['client_image']) ?>" alt="Reviewer" class="review-avatar">
                                <?php else: ?>
                                    <div class="review-avatar-placeholder">
                                        <?= strtoupper(substr($review['customer_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="review-meta">
                                    <strong><?= htmlspecialchars($review['customer_name']) ?></strong>
                                    <div class="review-rating">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="review-date">
                                <?= date('M d, Y', strtotime($review['created_at'])) ?>
                            </div>
                        </div>
                        <?php if ($review['title']): ?>
                            <h4 class="review-title"><?= htmlspecialchars($review['title']) ?></h4>
                        <?php endif; ?>
                        <p class="review-content"><?= nl2br(htmlspecialchars($review['content'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<script>
const productImages = <?= json_encode($images) ?>;

let selectedMaterial = null;
let selectedVariation = null;
let selectedSize = null;
let selectedMaterialName = '';
let selectedVariationData = {};
let selectedSizeData = {};
let basePrice = <?= $product['price'] ?>;
let maxStock = 0;

// Mouse tracking magnification
document.addEventListener('DOMContentLoaded', function() {
    const mainImageContainer = document.getElementById('mainImage');
    if (mainImageContainer) {
        mainImageContainer.addEventListener('mouseenter', function() {
            this.style.cursor = 'zoom-in';
        });
        
        mainImageContainer.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            
            const img = this.querySelector('img');
            if (img) {
                img.style.transform = 'scale(2)';
                img.style.transformOrigin = `${x}% ${y}%`;
            } else {
                this.style.transform = 'scale(2)';
                this.style.transformOrigin = `${x}% ${y}%`;
            }
        });
        
        mainImageContainer.addEventListener('mouseleave', function() {
            const img = this.querySelector('img');
            if (img) {
                img.style.transform = 'scale(1)';
            } else {
                this.style.transform = 'scale(1)';
            }
        });
    }
});

function changeImage(thumbnail) {
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    thumbnail.classList.add('active');
    const mainImage = document.getElementById('mainImage');
    const img = thumbnail.querySelector('img');
    if (img) {
        mainImage.innerHTML = `<img src="${img.src}" alt="Product image" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
        mainImage.innerHTML = thumbnail.innerHTML;
    }
}

function selectMaterial(materialId) {
    console.log('Selected material:', materialId);
    selectedMaterial = materialId;
    selectedVariation = null;
    selectedSize = null;

    const materialBtn = document.querySelector(`[data-material-id="${materialId}"]`);
    selectedMaterialName = materialBtn.textContent;

    document.querySelectorAll('#materialOptions .option-btn').forEach(btn => {
        btn.classList.remove('selected');
        if (btn.dataset.materialId == materialId) {
            btn.classList.add('selected');
        }
    });

    fetch(`get_variations.php?product_id=<?= $product['id'] ?>&material_id=${materialId}`)
        .then(response => response.json())
        .then(variations => {
            console.log('Variations received:', variations);
            const variationGroup = document.getElementById('variationGroup');
            const variationOptions = document.getElementById('variationOptions');

            if (variations.length > 0) {
                variationOptions.innerHTML = '';
                variations.forEach(variation => {
                    const btn = document.createElement('button');
                    btn.className = 'option-btn';
                    btn.dataset.variationId = variation.id;
                    btn.dataset.priceAdjustment = variation.price_adjustment || 0;
                    btn.dataset.color = variation.color || '';
                    btn.dataset.adornment = variation.adornment || '';
                    btn.dataset.stock = variation.stock_quantity || 0;
                    btn.textContent = variation.tag || 'Standard';
                    btn.onclick = () => selectVariation(variation.id, variation.price_adjustment, variation);
                    variationOptions.appendChild(btn);
                });
                variationGroup.style.display = 'block';
            } else {
                variationGroup.style.display = 'none';
            }

            document.getElementById('sizeGroup').style.display = 'none';
            updateComponentSummary();
            updateAddToCartButton();
        });
}
function selectVariation(variationId, priceAdjustment, variationData) {
    console.log('Selected variation:', variationId, variationData);
    selectedVariation = variationId;
    selectedSize = null;
    selectedVariationData = variationData || {};

    // Update image based on variation tag
    const variationTag = variationData.tag;
    if (variationTag) {
        const image = productImages.find(img => img.tag === variationTag);
        if (image) {
            const mainImage = document.getElementById('mainImage');
            mainImage.innerHTML = `<img src="/${image.image_url}" alt="${image.alt_text || 'Product image'}" style="width:100%;height:100%;object-fit:cover;">`;

            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            const newActiveThumbnail = document.querySelector(`.thumbnail[data-image-id="${image.id}"]`);
            if (newActiveThumbnail) {
                newActiveThumbnail.classList.add('active');
            }
        }    }

    document.querySelectorAll('#variationOptions .option-btn').forEach(btn => {
        btn.classList.remove('selected');
        if (btn.dataset.variationId == variationId) {
            btn.classList.add('selected');
        }
    });

    const selectedBtn = document.querySelector(`[data-variation-id="${variationId}"]`);
    const stock = selectedBtn ? selectedBtn.dataset.stock : 0;
    document.getElementById('stockInfo').textContent = `${stock} in stock`;

    const finalPrice = parseFloat(priceAdjustment) || basePrice;

    document.getElementById('finalPrice').innerHTML = `£${finalPrice.toFixed(2)}`;

    document.getElementById('finalPrice').style.display = 'block';

    document.getElementById('basePrice').style.display = 'none';



    console.log('Fetching sizes for variation ID:', variationId);

    fetch(`get_sizes.php?variation_id=${variationId}`)

        .then(response => response.json())

        .then(sizes => {

            console.log('Sizes received:', sizes);

            const sizeGroup = document.getElementById('sizeGroup');

            const sizeOptions = document.getElementById('sizeOptions');





            if (sizes.length > 0) {

                sizeOptions.innerHTML = '';

                sizes.forEach(size => {

                    const btn = document.createElement('button');

                    btn.className = 'option-btn';

                    btn.dataset.sizeId = size.id;

                    btn.dataset.priceAdjustment = size.price_adjustment || 0;

                    btn.dataset.size = size.size;

                    btn.dataset.stock = size.stock_quantity;

                    btn.textContent = size.size;

                    btn.onclick = () => selectSize(size.id, size.price_adjustment, size.stock_quantity, size);

                    if (size.stock_quantity <= 0) {

                        btn.disabled = true;

                        btn.textContent += ' (Out of Stock)';

                    }

                    sizeOptions.appendChild(btn);

                });

                sizeGroup.style.display = 'block';

            } else {

                sizeGroup.style.display = 'none';

                updateAddToCartButton();

            }




            updateComponentSummary();

        })

        .catch(error => {

            console.error('Error fetching sizes:', error);

        });}
function selectSize(sizeId, priceAdjustment, stock, sizeData) {
    console.log('Selected size:', sizeId, sizeData);
    selectedSize = sizeId;
    selectedSizeData = sizeData || {};
    maxStock = stock;

    document.querySelectorAll('#sizeOptions .option-btn').forEach(btn => {
        btn.classList.remove('selected');
        if (btn.dataset.sizeId == sizeId) {
            btn.classList.add('selected');
        }
    });

    const finalPrice = parseFloat(priceAdjustment) || (selectedVariationData.price_adjustment > 0 ? parseFloat(selectedVariationData.price_adjustment) : basePrice);
    document.getElementById('finalPrice').innerHTML = `£${finalPrice.toFixed(2)}`;
    document.getElementById('stockInfo').textContent = `${stock} in stock`;

    const quantityInput = document.getElementById('quantityInput');
    quantityInput.max = stock;
    if (parseInt(quantityInput.value) > stock) {
        quantityInput.value = stock;
    }

    updateComponentSummary();
    updateAddToCartButton();
}

function updateComponentSummary() {
    const summary = document.getElementById('componentSummary');
    const content = document.getElementById('summaryContent');
    
    if (!selectedMaterial || !selectedVariation) {
        summary.style.display = 'none';
        return;
    }
    
    let summaryHtml = `<strong>Material:</strong> ${selectedMaterialName}<br>`;
    
    if (selectedVariationData.color) {
        summaryHtml += `<strong>Color:</strong> ${selectedVariationData.color}<br>`;
    }
    
    if (selectedVariationData.adornment) {
        summaryHtml += `<strong>Adornment:</strong> ${selectedVariationData.adornment}<br>`;
    }
    
    if (selectedSize && selectedSizeData.size) {
        summaryHtml += `<strong>Size:</strong> ${selectedSizeData.size}<br>`;
    }
    
    const quantity = document.getElementById('quantityInput')?.value || 1;
    summaryHtml += `<strong>Quantity:</strong> ${quantity}<br>`;
    
    let finalPrice = basePrice;
    if (selectedSize && selectedSizeData.price_adjustment && selectedSizeData.price_adjustment > 0) {
        finalPrice = parseFloat(selectedSizeData.price_adjustment);
    } else if (selectedVariationData.price_adjustment && selectedVariationData.price_adjustment > 0) {
        finalPrice = parseFloat(selectedVariationData.price_adjustment);
    }
    summaryHtml += `<strong>Price:</strong> £${finalPrice.toFixed(2)}`;
    
    content.innerHTML = summaryHtml;
    summary.style.display = 'block';
}

function changeQuantity(change) {
    const input = document.getElementById('quantityInput');
    const newValue = parseInt(input.value) + change;
    if (newValue >= 1 && newValue <= maxStock) {
        input.value = newValue;
        updateComponentSummary();
    }
}

function updateAddToCartButton() {
    const btn = document.getElementById('addToCartBtn');
    const variationGroup = document.getElementById('variationGroup');
    const sizeGroup = document.getElementById('sizeGroup');
    const materialOptions = document.getElementById('materialOptions');

    // If no materials exist (simple product), enable button
    if (materialOptions.children.length === 0) {
        btn.disabled = false;
        btn.textContent = 'Add to Cart';
        document.getElementById('quantitySelector').style.display = 'block';
        return;
    }

    // For products with variations
    if (selectedMaterial && (variationGroup.style.display === 'none' || selectedVariation) && (sizeGroup.style.display === 'none' || selectedSize)) {
        btn.disabled = false;
        btn.textContent = 'Add to Cart';
        document.getElementById('quantitySelector').style.display = 'block';
    } else {
        btn.disabled = true;
        btn.textContent = 'Select Your Preferred Material to Proceed';
        document.getElementById('quantitySelector').style.display = 'none';
        if (!selectedVariation) {
            document.getElementById('componentSummary').style.display = 'none';
        }
    }
}


function addToCartFromProduct() {
    if (!window.cartHandler) {
        console.error('Cart handler not available.');
        alert('Could not add to cart. Please refresh the page.');
        return;
    }

    const quantity = document.getElementById('quantityInput').value;

    let price = basePrice;
    if (selectedSize && selectedSizeData.price_adjustment > 0) {
        price = selectedSizeData.price_adjustment;
    } else if (selectedVariationData.price_adjustment > 0) {
        price = selectedVariationData.price_adjustment;
    }

    const productData = {
        product_id: <?= $product['id'] ?>,
        quantity: parseInt(quantity),
        material_id: selectedMaterial,
        variation_id: selectedVariation,
        size_id: selectedSize,
        selected_price: price,
        variation_name: selectedVariationData.tag || '',
        image: document.getElementById('mainImage').querySelector('img')?.src || ''
    };
    console.log('Constructed productData:', productData);
    console.log('productData.material_id:', productData.material_id);

    console.log('Final selections - Material:', selectedMaterial, 'Variation:', selectedVariation, 'Size:', selectedSize);
    console.log('Sending productData to cart:', productData);
    window.cartHandler.addToCart(productData);}

function toggleWishlist() {
    const btn = event.target;
    btn.textContent = btn.textContent === '♡ Wishlist' ? '♥ Added' : '♡ Wishlist';
}

function toggleReviewForm() {
    const formContainer = document.getElementById('reviewFormContainer');
    const btn = document.getElementById('writeReviewBtn');

    if (formContainer.style.display === 'none') {
        formContainer.style.display = 'block';
        btn.textContent = 'Cancel Review';
    } else {
        formContainer.style.display = 'none';
        btn.textContent = 'Write a Review';
    }
}

function submitReview(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    fetch('api/submit-review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you for your review! It will be published after approval.');
            form.reset();
            toggleReviewForm(); // Hide the form after submission
        } else {
            alert('Error submitting review: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting your review.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Review';
    });

    return false;
}
</script>

<?php include 'includes/footer.php'; ?>