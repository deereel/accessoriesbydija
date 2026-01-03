<?php
$page_title = "Product Details";
include 'config/database.php';
include 'includes/header.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: products.php');
    exit;
}

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
</style>

<main>
    <div class="breadcrumb">
        <a href="index.php">Home</a> / <a href="products.php">Products</a> / <?= htmlspecialchars($product['name']) ?>
    </div>

    <div class="product-detail-container">
        <div class="product-images">
            <div class="main-image-container">
                <div class="main-image" id="mainImage">💎</div>
            </div>
            <div class="image-thumbnails">
                <?php if ($images): ?>
                    <?php foreach ($images as $index => $image): ?>
                    <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="changeImage(this)">
                        <img src="<?= htmlspecialchars($image['image_url']) ?>" alt="Product image">
                    </div>
                    <?php endforeach; ?>
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
                <button class="btn btn-primary" id="addToCartBtn" onclick="addToCart()" disabled>Select Options</button>
                <button class="btn btn-secondary" onclick="toggleWishlist()">♡ Wishlist</button>
            </div>
        </div>
    </div>
</main>

<script>
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
                img.style.transformOrigin = 'center center';
            } else {
                this.style.transform = 'scale(1)';
                this.style.transformOrigin = 'center center';
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
    selectedMaterial = materialId;
    selectedVariation = null;
    selectedSize = null;
    
    // Store material name
    const materialBtn = document.querySelector(`[data-material-id="${materialId}"]`);
    selectedMaterialName = materialBtn.textContent;
    
    // Update material buttons
    document.querySelectorAll('#materialOptions .option-btn').forEach(btn => {
        btn.classList.remove('selected');
        if (btn.dataset.materialId == materialId) {
            btn.classList.add('selected');
        }
    });
    
    // Load variations for this material
    fetch(`get_variations.php?product_id=<?= $product['id'] ?>&material_id=${materialId}`)
        .then(response => response.json())
        .then(variations => {
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
                    btn.dataset.finish = variation.finish || '';
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
    selectedVariation = variationId;
    selectedSize = null;
    selectedVariationData = variationData || {};
    
    // Update variation buttons
    document.querySelectorAll('#variationOptions .option-btn').forEach(btn => {
        btn.classList.remove('selected');
        if (btn.dataset.variationId == variationId) {
            btn.classList.add('selected');
        }
    });
    
    // Update images based on variation tag
    const selectedBtn = document.querySelector(`[data-variation-id="${variationId}"]`);
    const tag = selectedBtn ? selectedBtn.textContent : null;
    const stock = selectedBtn ? selectedBtn.dataset.stock : 0;
    
    // Update stock info for variation
    document.getElementById('stockInfo').textContent = `${stock} in stock`;
    
    if (tag) {
        updateImagesForTag(tag);
    }
    
    // Update price
    const finalPrice = parseFloat(priceAdjustment) || basePrice;
    document.getElementById('finalPrice').innerHTML = `£${finalPrice.toFixed(2)}`;
    document.getElementById('finalPrice').style.display = 'block';
    document.getElementById('basePrice').style.display = 'none';
    
    // Load sizes for this variation
    fetch(`get_sizes.php?variation_id=${variationId}`)
        .then(response => response.json())
        .then(sizes => {
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
        });
}

function selectSize(sizeId, priceAdjustment, stock, sizeData) {
    selectedSize = sizeId;
    selectedSizeData = sizeData || {};
    maxStock = stock;
    
    // Update size buttons
    document.querySelectorAll('#sizeOptions .option-btn').forEach(btn => {
        btn.classList.remove('selected');
        if (btn.dataset.sizeId == sizeId) {
            btn.classList.add('selected');
        }
    });
    
    // Update price with size adjustment
    const selectedVariationBtn = document.querySelector('#variationOptions .selected');
    const variationPrice = selectedVariationBtn ? parseFloat(selectedVariationBtn.dataset.priceAdjustment || 0) : 0;
    const finalPrice = parseFloat(priceAdjustment) || (variationPrice > 0 ? variationPrice : basePrice);
    document.getElementById('finalPrice').innerHTML = `£${finalPrice.toFixed(2)}`;
    
    // Update stock info
    document.getElementById('stockInfo').textContent = `${stock} in stock`;
    
    // Update quantity input max
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
    
    if (selectedVariationData.finish) {
        summaryHtml += `<strong>Adornment:</strong> ${selectedVariationData.finish}<br>`;
    }
    
    if (selectedSize && selectedSizeData.size) {
        summaryHtml += `<strong>Size:</strong> ${selectedSizeData.size}<br>`;
    } else {
        summaryHtml += `<strong>Size:</strong> <br>`;
    }
    
    const quantity = document.getElementById('quantityInput')?.value || 1;
    summaryHtml += `<strong>Quantity:</strong> ${quantity}<br>`;
    
    // Calculate and display price
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
    const sizeGroup = document.getElementById('sizeGroup');
    
    if (selectedMaterial && selectedVariation && (sizeGroup.style.display === 'none' || selectedSize)) {
        btn.disabled = false;
        btn.textContent = 'Add to Cart';
        document.getElementById('quantitySelector').style.display = 'block';
    } else {
        btn.disabled = true;
        btn.textContent = 'Select Options';
        document.getElementById('quantitySelector').style.display = 'none';
        if (!selectedVariation) {
            document.getElementById('componentSummary').style.display = 'none';
        }
    }
}

function addToCart() {
    if (!selectedMaterial || !selectedVariation) return;
    
    const btn = document.getElementById('addToCartBtn');
    btn.textContent = 'Added!';
    setTimeout(() => btn.textContent = 'Add to Cart', 1500);
}

function toggleWishlist() {
    const btn = event.target;
    btn.textContent = btn.textContent === '♡ Wishlist' ? '♥ Added' : '♡ Wishlist';
}

function updateImagesForTag(tag) {
    // Fetch images for this tag
    fetch(`get_tag_images.php?product_id=<?= $product['id'] ?>&tag=${encodeURIComponent(tag)}`)
        .then(response => response.json())
        .then(images => {
            if (images.length > 0) {
                // Update thumbnails
                const thumbnailContainer = document.querySelector('.image-thumbnails');
                thumbnailContainer.innerHTML = '';
                
                images.forEach((image, index) => {
                    const thumbnail = document.createElement('div');
                    thumbnail.className = `thumbnail ${index === 0 ? 'active' : ''}`;
                    thumbnail.onclick = () => changeImage(thumbnail);
                    thumbnail.innerHTML = `<img src="${image.image_url}" alt="${image.alt_text || 'Product image'}">`;
                    thumbnailContainer.appendChild(thumbnail);
                });
                
                // Update main image
                if (images[0]) {
                    const mainImage = document.getElementById('mainImage');
                    mainImage.innerHTML = `<img src="${images[0].image_url}" alt="${images[0].alt_text || 'Product image'}" style="width:100%;height:100%;object-fit:cover;">`;
                }
            }
        })
        .catch(error => console.error('Error loading tag images:', error));
}
</script>

<?php include 'includes/footer.php'; ?>