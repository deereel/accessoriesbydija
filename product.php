<?php
require_once 'config/database.php';

// --- LOGIC: GET AND VALIDATE PRODUCT ---
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: products.php');
    exit;
}

// Fetch the base product
$stmt = $pdo->prepare("SELECT p.* FROM products p WHERE p.slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Fetch all product images
$images_stmt = $pdo->prepare("SELECT id, image_url, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$images_stmt->execute([$product['id']]);
$product_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all data for variants of this product
$variants_data = [];
try {
    $variants_stmt = $pdo->prepare("
        SELECT
            pv.id as variant_id,
            pv.sku as variant_sku,
            vt.tag,
            pv.price_override,
            pv.size_override,
            vs.stock_quantity,
            pi.image_url
        FROM product_variants pv
        LEFT JOIN variant_tags vt ON pv.id = vt.variant_id
        LEFT JOIN variant_stock vs ON pv.id = vs.variant_id
        LEFT JOIN product_images pi ON pv.id = pi.variant_id
        WHERE pv.product_id = ?
        ORDER BY vt.tag
    ");
    $variants_stmt->execute([$product['id']]);
    $variants_data = $variants_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $variants_data = [];
}

// Collect unique sizes from variants
$sizes = [];
foreach ($variants_data as $v) {
    $size = $v['size_override'] ?: $product['size'];
    if ($size) {
        $sizes = array_merge($sizes, array_map('trim', explode(',', $size)));
    }
}
$sizes = array_unique(array_filter($sizes));

// Fetch available materials, colors, adornments for the entire product
$materials_stmt = $pdo->prepare("SELECT m.name FROM materials m JOIN product_materials pm ON m.id = pm.material_id WHERE pm.product_id = ?");
$materials_stmt->execute([$product['id']]);
$materials = $materials_stmt->fetchAll(PDO::FETCH_COLUMN);

$colors_stmt = $pdo->prepare("SELECT c.name FROM colors c JOIN product_colors pc ON c.id = pc.color_id WHERE pc.product_id = ?");
$colors_stmt->execute([$product['id']]);
$colors = $colors_stmt->fetchAll(PDO::FETCH_COLUMN);

$adornments_stmt = $pdo->prepare("SELECT a.name FROM adornments a JOIN product_adornments pa ON a.id = pa.adornment_id WHERE pa.product_id = ?");
$adornments_stmt->execute([$product['id']]);
$adornments = $adornments_stmt->fetchAll(PDO::FETCH_COLUMN);


// --- PAGE SETUP & HEADER ---
$page_title = $product['name'];
$page_description = substr($product['description'], 0, 160);
include 'includes/header.php';
?>

<style>
    /* ... (existing styles can be kept, but add new ones for selections) ... */
    .variant-selection-group { margin-bottom: 1.5rem; }
    .variant-selection-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
    .variant-options .option {
        display: inline-block;
        padding: 8px 15px;
        border: 1px solid #ddd;
        border-radius: 20px;
        cursor: pointer;
        margin-right: 10px;
        transition: all 0.2s;
    }
    .variant-options .option.selected {
        background-color: #222;
        color: white;
        border-color: #222;
    }
    .variant-options .option.disabled {
        color: #ccc;
        background-color: #f9f9f9;
        cursor: not-allowed;
        text-decoration: line-through;
    }
    #stock-display.out-of-stock { color: #dc3545; font-weight: bold; }
    #add-to-cart-btn:disabled { background-color: #6c757d; cursor: not-allowed; }
    .selection-summary { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee; }
    .selection-summary p { margin: 0.3rem 0; }

    /* Image gallery styles */
    .product-gallery { max-width: 500px; }
    .main-image-wrapper { position: relative; overflow: hidden; border: 1px solid #ddd; margin-bottom: 10px; }
    .zoom-container { width: 100%; height: 400px; overflow: hidden; position: relative; cursor: zoom-in; }
    .zoom-container img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.1s ease-out; }
    .zoom-container:hover img { transform: scale(3); }
    .image-thumbnails { display: flex; gap: 10px; flex-wrap: wrap; }
    .thumbnail { width: 80px; height: 80px; object-fit: cover; cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s; }
    .thumbnail:hover, .thumbnail.active { border-color: #007bff; }
</style>

<main>
    <div class="breadcrumb">
        <a href="index.php">Home</a> / <a href="products.php">Products</a> / <?= htmlspecialchars($product['name']) ?>
    </div>

    <div class="product-detail-container">
        <div class="product-gallery">
            <div class="main-image-wrapper">
                <div id="zoom-container" class="zoom-container">
                    <img id="main-image" src="<?= htmlspecialchars($product_images[0]['image_url'] ?? 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
            </div>
            <div class="image-thumbnails">
                <?php foreach ($product_images as $img): ?>
                    <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="" class="thumbnail" data-src="<?= htmlspecialchars($img['image_url']) ?>">
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="product-details">
            <h1 id="product-name"><?= htmlspecialchars($product['name']) ?></h1>
            <div id="product-price" class="product-price">£<?= number_format($product['price'], 2) ?></div>
            
            <!-- Variant Selections -->
            <div class="variant-selection-group">
                <label>Select Material</label>
                <div id="material-options" class="variant-options">
                    <?php foreach ($materials as $material): ?>
                        <span class="option" data-value="<?= htmlspecialchars($material) ?>"><?= htmlspecialchars($material) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="variant-selection-group">
                <label>Select Variant</label>
                <div id="variant-options" class="variant-options">
                    <?php foreach ($variants_data as $variant): ?>
                        <span class="option" data-tag="<?= htmlspecialchars($variant['tag']) ?>" data-variant-id="<?= $variant['variant_id'] ?>"><?= htmlspecialchars($variant['tag']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($sizes)): ?>
            <div class="variant-selection-group">
                <label>Select Size</label>
                <div id="size-options" class="variant-options">
                    <?php foreach ($sizes as $size): ?>
                        <span class="option" data-size="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="product-meta">
                <span><strong>SKU:</strong> <span id="product-sku"><?= htmlspecialchars($product['sku']) ?></span></span>
                <span><strong>Stock:</strong> <span id="stock-display"><?= $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock' ?></span></span>
            </div>

            <div class="product-actions">
                <button id="add-to-cart-btn" class="btn btn-primary" data-product-id="<?= $product['id'] ?>">Add to Cart</button>
            </div>
            
            <div class="selection-summary" id="selection-summary" style="display:none;">
                <h4>Your Selection</h4>
                <p><strong>Material:</strong> <?= htmlspecialchars(implode(', ', $materials)) ?></p>
                <p><strong>Color:</strong> <?= htmlspecialchars(implode(', ', $colors)) ?></p>
                <p><strong>Adornment:</strong> <?= htmlspecialchars(implode(', ', $adornments)) ?></p>
                <p><strong>Size:</strong> <span id="summary-size"></span></p>
            </div>

            <div class="product-description" style="margin-top: 2rem;">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productData = {
        basePrice: <?= (float)$product['price'] ?>,
        baseSize: '<?= htmlspecialchars($product['size']) ?>',
        baseSku: '<?= htmlspecialchars($product['sku']) ?>',
        variants: <?= json_encode($variants_data) ?>
    };

    const state = {
        selectedMaterial: null,
        selectedVariant: null,
        selectedSize: null
    };

    const selectors = {
        materialOptions: document.getElementById('material-options'),
        variantOptions: document.getElementById('variant-options'),
        sizeOptions: document.getElementById('size-options'),
        zoomContainer: document.getElementById('zoom-container'),
        mainImage: document.getElementById('main-image'),
        thumbnails: document.querySelectorAll('.thumbnail'),
        price: document.getElementById('product-price'),
        sku: document.getElementById('product-sku'),
        stock: document.getElementById('stock-display'),
        addToCartBtn: document.getElementById('add-to-cart-btn'),
        summary: document.getElementById('selection-summary'),
        summarySize: document.getElementById('summary-size')
    };

    function updateUI() {
        const variant = state.selectedVariant;
        let imageSrc = 'placeholder.jpg'; // Default

        // Update Image
        if (variant && variant.image_url) {
            imageSrc = variant.image_url;
        }
        selectors.mainImage.src = imageSrc;

        // Update Price
        const priceToShow = variant && variant.price_override ? variant.price_override : productData.basePrice;
        selectors.price.textContent = `£${parseFloat(priceToShow).toFixed(2)}`;

        // Update SKU
        selectors.sku.textContent = variant ? variant.variant_sku : productData.baseSku;

        // Update Stock & Add to Cart button
        if (variant) {
            if (variant.stock_quantity > 0) {
                selectors.stock.textContent = `In Stock: ${variant.stock_quantity}`;
                selectors.stock.classList.remove('out-of-stock');
                selectors.addToCartBtn.disabled = false;
            } else {
                selectors.stock.textContent = 'Out of Stock';
                selectors.stock.classList.add('out-of-stock');
                selectors.addToCartBtn.disabled = true;
            }
        } else {
            selectors.stock.textContent = 'Please select a variant';
            selectors.addToCartBtn.disabled = true;
        }

        // Update Summary
        if (variant || state.selectedSize) {
            const sizeToShow = (variant && variant.size_override) || state.selectedSize || productData.baseSize;
            selectors.summarySize.textContent = sizeToShow || 'Not specified';
            selectors.summary.style.display = 'block';
        } else {
            selectors.summary.style.display = 'none';
        }
    }

    function handleVariantSelection(tag) {
        // Deselect if already selected
        if (state.selectedVariant && state.selectedVariant.tag === tag) {
            state.selectedVariant = null;
        } else {
            state.selectedVariant = productData.variants.find(v => v.tag === tag) || null;
        }

        // Update 'selected' class
        selectors.variantOptions.querySelectorAll('.option').forEach(el => {
            if (el.dataset.tag === (state.selectedVariant ? state.selectedVariant.tag : null)) {
                el.classList.add('selected');
            } else {
                el.classList.remove('selected');
            }
        });

        updateUI();
    }

    function switchImage(src) {
        selectors.mainImage.src = src;
        // Update active thumbnail
        selectors.thumbnails.forEach(thumb => {
            thumb.classList.toggle('active', thumb.dataset.src === src);
        });
    }

    function handleSizeSelection(size) {
        // Toggle selection
        if (state.selectedSize === size) {
            state.selectedSize = null;
        } else {
            state.selectedSize = size;
        }

        // Update 'selected' class
        if (selectors.sizeOptions) {
            selectors.sizeOptions.querySelectorAll('.option').forEach(el => {
                el.classList.toggle('selected', el.dataset.size === state.selectedSize);
            });
        }

        updateUI();
    }

    function handleZoom(e) {
        const rect = selectors.zoomContainer.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const xPercent = (x / rect.width) * 100;
        const yPercent = (y / rect.height) * 100;
        selectors.mainImage.style.transformOrigin = `${xPercent}% ${yPercent}%`;
    }

    // Initial setup
    selectors.variantOptions.querySelectorAll('.option').forEach(el => {
        el.addEventListener('click', () => handleVariantSelection(el.dataset.tag));
    });

    if (selectors.sizeOptions) {
        selectors.sizeOptions.querySelectorAll('.option').forEach(el => {
            el.addEventListener('click', () => handleSizeSelection(el.dataset.size));
        });
    }

    // Thumbnail click handlers
    selectors.thumbnails.forEach(thumb => {
        thumb.addEventListener('click', () => switchImage(thumb.dataset.src));
    });

    // Zoom functionality
    selectors.zoomContainer.addEventListener('mousemove', handleZoom);
    selectors.zoomContainer.addEventListener('mouseleave', () => {
        selectors.mainImage.style.transformOrigin = 'center';
    });

    updateUI();
});

// The existing addToCart function can remain, but it needs to be adapted
// to get the selected variant_id instead of the base product_id
async function addToCart(baseProductId, btn) {
    // This needs to be updated to get the currently selected variant ID
    const selectedVariantId = 123; // Placeholder for selected variant
    
    // ... rest of the function
}
</script>

<?php include 'includes/footer.php'; ?>