<?php
require_once 'config/database.php';

// --- LOGIC: GET AND VALIDATE PRODUCT ---
$slug = $_GET['slug'] ?? '';

if (!$slug) {
    // If no slug is provided, redirect.
    header('Location: products.php');
    exit;
}

// Fetch the product from the database
$stmt = $pdo->prepare("SELECT p.* FROM products p WHERE p.slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

// If no product is found for the given slug, redirect.
if (!$product) {
    header('Location: products.php');
    exit;
}

// --- PAGE SETUP & HEADER ---
$page_title = $product['name'];
$page_description = $product['short_description'] ?? substr($product['description'], 0, 160);
include 'includes/header.php';

// Get all product images
$images_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$images_stmt->execute([$product['id']]);
$product_images = $images_stmt->fetchAll();
?>

<style>
    /* General Layout Styles */
    .breadcrumb { margin: 2rem auto 1rem; max-width: 1200px; padding: 0 1rem; }
    .breadcrumb a { color: #666; text-decoration: none; }
    .product-detail-container {
        display: flex;
        flex-wrap: wrap; /* Allow wrapping on smaller screens */
        gap: 2rem;
        max-width: 1200px;
        margin: auto;
        padding: 1rem;
    }
    .product-gallery, .product-details {
        flex: 1;
        min-width: 300px; /* Prevent excessive shrinking */
    }
    .product-details h1 { font-size: 2rem; margin-bottom: 1rem; }
    .product-price { font-size: 1.5rem; color: #C27BA0; font-weight: 600; margin-bottom: 1rem; }
    .product-description { line-height: 1.6; margin-bottom: 2rem; }
    .product-meta { margin-bottom: 2rem; }
    .product-meta span { display: block; margin-bottom: 0.5rem; }
    .product-actions { display: flex; gap: 1rem; }
    .btn { padding: 1rem 2rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; }
    .btn-primary { background: #222; color: white; }
    .btn-primary:hover { background: #C27BA0; }
    .btn-secondary { background: transparent; border: 1px solid #ddd; }

    /* Image Zoom Styles */
    .main-image-wrapper {
        position: relative;
        width: 100%;
        max-width: 450px; /* Max width for the main image */
        cursor: crosshair;
    }
    #main-image {
        width: 100%;
        height: auto;
        display: block;
    }
    .image-thumbnails {
        display: flex;
        gap: 10px;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    .thumbnail {
        width: 70px;
        height: 70px;
        cursor: pointer;
        border: 2px solid transparent;
        object-fit: cover;
        transition: border-color 0.3s;
    }
    .thumbnail.active {
        border-color: #C27BA0;
    }
    .thumbnail:hover {
        border-color: #999;
    }

    /* Magnifier lens and result pane */
    .img-zoom-lens {
        position: absolute;
        border: 1px solid #d4d4d4;
        background-color: rgba(255, 255, 255, 0.4);
        z-index: 100;
        /* The size is set by JavaScript */
    }
    .img-zoom-result {
        position: absolute;
        left: 105%; /* Positioned to the right of the wrapper */
        top: 0;
        width: 100%; /* Same width as the wrapper */
        height: 100%; /* Same height as the wrapper */
        border: 1px solid #ccc;
        background-repeat: no-repeat;
        z-index: 100;
        pointer-events: none; /* Prevent the result pane from capturing mouse events */
    }

    /* Responsive: Hide zoom on smaller screens */
    @media (max-width: 768px) {
        .img-zoom-result, .img-zoom-lens {
            display: none !important; /* Use !important to override JS visibility */
        }
        .main-image-wrapper {
            cursor: default; /* Remove crosshair cursor on mobile */
        }
        .product-detail-container {
            flex-direction: column;
        }
    }
</style>

<main>
    <div class="breadcrumb">
        <a href="index.php">Home</a> / <a href="products.php">Products</a> / <?= htmlspecialchars($product['name']) ?>
    </div>

    <div class="product-detail-container">
        <div class="product-gallery">
            <div class="main-image-wrapper" id="main-image-wrapper">
                <?php if (!empty($product_images)): ?>
                    <img id="main-image" src="<?= htmlspecialchars($product_images[0]['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php else: ?>
                    <img id="main-image" src="placeholder.jpg" alt="No Image Available">
                <?php endif; ?>
                <!-- Zoom result pane will be positioned relative to this wrapper -->
                <div id="zoom-result" class="img-zoom-result"></div>
            </div>
            <div class="image-thumbnails">
                <?php if (!empty($product_images)): ?>
                    <?php foreach ($product_images as $index => $image): ?>
                        <img class="thumbnail <?= $index === 0 ? 'active' : '' ?>" src="<?= htmlspecialchars($image['image_url']) ?>" alt="Thumbnail <?= $index + 1 ?>" onclick="changeImage(this)">
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="product-details">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price">£<?= number_format($product['price'], 2) ?></div>
            <div class="product-description"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
            <div class="product-meta">
                <span><strong>SKU:</strong> <?= htmlspecialchars($product['sku']) ?></span>
                <span><strong>Material:</strong> <?= htmlspecialchars($product['material']) ?></span>
                <?php if ($product['stone_type']): ?><span><strong>Stone:</strong> <?= htmlspecialchars($product['stone_type']) ?></span><?php endif; ?>
                <?php if ($product['weight']): ?><span><strong>Weight:</strong> <?= htmlspecialchars($product['weight']) ?>g</span><?php endif; ?>
                <span><strong>Stock:</strong> <?= $product['stock_quantity'] ?> available</span>
            </div>
            <div class="product-actions">
                <button class="btn btn-primary add-to-cart" data-product-id="<?= $product['id'] ?>">Add to Cart</button>
                <button class="btn btn-secondary" onclick="toggleWishlist(this, <?= $product['id'] ?>)">♡ Wishlist</button>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the zoom functionality on page load
    imageZoom("main-image", "zoom-result");

    // Attach event listeners for 'Add to Cart' buttons
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            addToCart(this.dataset.productId, this);
        });
    });
});

/**
 * Handles switching the main image and re-initializing the zoom.
 * @param {HTMLElement} thumbnailEl - The clicked thumbnail image element.
 */
function changeImage(thumbnailEl) {
    const mainImage = document.getElementById('main-image');
    
    // Update the main image source
    mainImage.src = thumbnailEl.src;

    // Update the active state for thumbnails
    document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
    thumbnailEl.classList.add('active');

    // When the new image is loaded, re-initialize the zoom functionality.
    // This is crucial for ensuring the zoom works on the new image.
    mainImage.onload = function() {
        imageZoom("main-image", "zoom-result");
    };
}

/**
 * Core function to create the image zoom effect.
 * @param {string} imgID - The ID of the main image to be zoomed.
 * @param {string} resultID - The ID of the container for the zoomed image.
 */
function imageZoom(imgID, resultID) {
    // --- 1. CONFIGURATION ---\\n
    const zoomLevel = 2; // Easily configurable zoom level. 2 means 2x zoom.

    // --- 2. ELEMENT SELECTION & SETUP ---\n
    const img = document.getElementById(imgID);
    const result = document.getElementById(resultID);
    const wrapper = document.getElementById('main-image-wrapper');
    
    // Clean up any existing lens to prevent duplicates on image change
    let existingLens = wrapper.querySelector(".img-zoom-lens");
    if (existingLens) {
        wrapper.removeChild(existingLens);
    }

    // Create the magnifier lens
    const lens = document.createElement("DIV");
    lens.setAttribute("class", "img-zoom-lens");
    wrapper.appendChild(lens);

    // --- 3. RESPONSIVENESS CHECK ---\n
    // Disable on mobile by checking window width.
    if (window.innerWidth <= 768) {
        // Ensure lens and result are not visible and stop further execution.
        lens.style.display = 'none';
        result.style.display = 'none';
        wrapper.style.cursor = 'default';
        // Remove event listeners to be safe
        wrapper.onmousemove = null;
        wrapper.onmouseenter = null;
        wrapper.onmouseleave = null;
        return; 
    }

    // --- 4. CALCULATIONS ---\n
    // Calculate lens size based on the result pane and zoom level
    lens.style.width = (result.offsetWidth / zoomLevel) + "px";
    lens.style.height = (result.offsetHeight / zoomLevel) + "px";

    // Calculate the ratio between result background size and lens size.
    // This is used to position the background image in the result pane.
    const cx = result.offsetWidth / lens.offsetWidth;
    const cy = result.offsetHeight / lens.offsetHeight;

    // --- 5. EVENT HANDLERS ---\n
    // Set background properties for the result pane
    result.style.backgroundImage = "url('" + img.src + "')";
    result.style.backgroundSize = (img.width * cx) + "px " + (img.height * cy) + "px";

    // Show lens and result pane on mouse enter
    wrapper.onmouseenter = function() {
        lens.style.visibility = 'visible';
        result.style.visibility = 'visible';
    };

    // Hide lens and result pane on mouse leave
    wrapper.onmouseleave = function() {
        lens.style.visibility = 'hidden';
        result.style.visibility = 'hidden';
    };

    // Execute when the mouse moves over the image:
    wrapper.onmousemove = moveLens;

    function moveLens(e) {
        // Get cursor's x and y positions:
        const pos = getCursorPos(e);
        
        // Calculate the position of the lens:
        let x = pos.x - (lens.offsetWidth / 2);
        let y = pos.y - (lens.offsetHeight / 2);
        
        // Prevent the lens from going outside the image boundaries:
        if (x > img.width - lens.offsetWidth) { x = img.width - lens.offsetWidth; }
        if (x < 0) { x = 0; }
        if (y > img.height - lens.offsetHeight) { y = img.height - lens.offsetHeight; }
        if (y < 0) { y = 0; }
        
        // Set the position of the lens:
        lens.style.left = x + "px";
        lens.style.top = y + "px";
        
        // Display what the lens "sees" in the result pane:
        result.style.backgroundPosition = "-" + (x * cx) + "px -" + (y * cy) + "px";
    }

    function getCursorPos(e) {
        e = e || window.event;
        // Get the x and y positions of the image:
        const a = img.getBoundingClientRect();
        // Calculate the cursor's x and y coordinates, relative to the image:
        let x = e.pageX - a.left - window.pageXOffset;
        let y = e.pageY - a.top - window.pageYOffset;
        return { x: x, y: y };
    }
}

// --- Other Page Functions ---

async function addToCart(id, btn) {
    const original = btn.textContent;
    btn.textContent = 'Adding...';
    try {
        const res = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: id, quantity: 1 })
        });
        const data = await res.json();
        if (data.success) {
            btn.textContent = 'Added!';
            if (window.cartHandler && typeof window.cartHandler.updateCartCount === 'function') {
                window.cartHandler.updateCartCount();
            }
        } else {
            btn.textContent = original;
            alert(data.message || 'Failed to add to cart');
        }
    } catch (err) {
        console.error(err);
        btn.textContent = original;
        alert('Error adding to cart');
    }
    setTimeout(() => btn.textContent = original, 1500);
}

function toggleWishlist(el, id) {
    el.textContent = el.textContent === '♡ Wishlist' ? '♥ Added' : '♡ Wishlist';
}
</script>

<?php include 'includes/footer.php'; ?>