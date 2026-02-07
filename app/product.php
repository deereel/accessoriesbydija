<?php
$page_title = "Product Details";
include 'config/database.php';
include 'config/cache.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: products.php');
    exit;
}

include 'includes/header.php';

// Get product with caching
$cache_key = 'product_' . $slug;
$product = cache_get($cache_key);

if (!$product) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ?");
    $stmt->execute([$slug]);
    $product = $stmt->fetch();

    if ($product) {
        cache_set($cache_key, $product, 3600); // Cache for 1 hour
    }
}

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
$stmt = $pdo->prepare("\n    SELECT DISTINCT m.id, m.name\n    FROM materials m\n    JOIN product_variations pv ON m.id = pv.material_id\n    WHERE pv.product_id = ?\n    ORDER BY m.name\n");
$stmt->execute([$product['id']]);
$materials = $stmt->fetchAll();

// Get reviews for this product
$stmt = $pdo->prepare("SELECT * FROM testimonials WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC");
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll();

// Get similar products
$stmt = $pdo->prepare("\n    SELECT DISTINCT p.*, pi.image_url\n    FROM products p\n    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1\n    WHERE p.id != ? AND p.is_active = 1 AND (\n        p.category = ? OR\n        p.material = ? OR\n        p.gender = ?\n    )\n    ORDER BY RAND()\n    LIMIT 4\n");
$stmt->execute([$product['id'], $product['category'], $product['material'], $product['gender']]);
$similar_products = $stmt->fetchAll();

// Get frequently bought together
$stmt = $pdo->prepare("\n    SELECT p.id, p.name, p.slug, p.price, p.description, p.stock_quantity, p.is_active, p.created_at, p.updated_at,\n           pi.image_url, COUNT(oi2.product_id) as buy_count\n    FROM order_items oi1\n    JOIN orders o ON oi1.order_id = o.id\n    JOIN order_items oi2 ON oi1.order_id = oi2.order_id AND oi2.product_id != oi1.product_id\n    JOIN products p ON oi2.product_id = p.id\n    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1\n    WHERE oi1.product_id = ? AND p.is_active = 1\n    GROUP BY p.id, p.name, p.slug, p.price, p.description, p.stock_quantity, p.is_active, p.created_at, p.updated_at, pi.image_url\n    ORDER BY buy_count DESC\n    LIMIT 4\n");
$stmt->execute([$product['id']]);
$frequently_bought = $stmt->fetchAll();

$page_title = $product['name'];
$gender_display = ['U' => 'Unisex', 'M' => 'Male', 'F' => 'Female'][$product['gender']] ?? $product['gender'];
$page_description = $product['name'] . ' - Premium ' . ($product['material'] ?? 'jewelry') . ' ' . ($product['category'] ?? '') . ' for ' . ($gender_display ?? 'men and women') . '. Handcrafted with ethically sourced materials. Free shipping over £100. ' . substr($product['description'], 0, 100);

// Structured Data for Product
$structured_data = [
    "@context" => "https://schema.org",
    "@type" => "Product",
    "name" => $product['name'],
    "description" => $product['description'],
    "sku" => $product['sku'] ?? '',
    "brand" => [
        "@type" => "Brand",
        "name" => "Dija Accessories"
    ],
    "offers" => [
        "@type" => "Offer",
        "price" => $product['price'],
        "priceCurrency" => "GBP",
        "availability" => $product['stock_quantity'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
        "seller" => [
            "@type" => "Organization",
            "name" => "Dija Accessories"
        ]
    ]
];

if (!empty($images)) {
    $structured_data["image"] = array_map(function($img) {
        return "https://accessoriesbydija.uk/" . $img['image_url'];
    }, $images);
}

if (!empty($reviews)) {
    $structured_data["aggregateRating"] = [
        "@type" => "AggregateRating",
        "ratingValue" => array_sum(array_column($reviews, 'rating')) / count($reviews),
        "reviewCount" => count($reviews)
    ];
}

// Add structured data script
echo '<script type="application/ld+json">' . json_encode($structured_data) . '</script>';

// HowTo Schema for Care Instructions
$howto_schema = [
    "@context" => "https://schema.org",
    "@type" => "HowTo",
    "name" => "How to Care for Your " . $product['name'],
    "description" => "Proper care instructions to maintain the beauty and longevity of your jewelry piece",
    "step" => [
        [
            "@type" => "HowToStep",
            "name" => "Storage",
            "text" => "Store your jewelry in a cool, dry place away from direct sunlight to prevent discoloration and damage."
        ],
        [
            "@type" => "HowToStep",
            "name" => "Cleaning",
            "text" => "Clean your jewelry regularly with a soft cloth and mild soap. Avoid harsh chemicals and abrasive materials."
        ],
        [
            "@type" => "HowToStep",
            "name" => "Wear and Tear",
            "text" => "Remove jewelry before swimming, exercising, or engaging in activities that may cause impact or exposure to lotions and perfumes."
        ],
        [
            "@type" => "HowToStep",
            "name" => "Professional Care",
            "text" => "For gold and precious metal jewelry, consider professional cleaning and inspection every 6-12 months."
        ]
    ],
    "supply" => [
        [
            "@type" => "HowToSupply",
            "name" => "Soft cloth"
        ],
        [
            "@type" => "HowToSupply",
            "name" => "Mild soap"
        ]
    ]
];

echo '<script type="application/ld+json">' . json_encode($howto_schema) . '</script>';

// Breadcrumb Schema
$breadcrumb_schema = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        [
            "@type" => "ListItem",
            "position" => 1,
            "name" => "Home",
            "item" => $base_url . "/"
        ],
        [
            "@type" => "ListItem",
            "position" => 2,
            "name" => "Products",
            "item" => $base_url . "/products.php"
        ],
        [
            "@type" => "ListItem",
            "position" => 3,
            "name" => $product['name'],
            "item" => $base_url . "/product/" . $product['slug']
        ]
    ]
];

echo '<script type="application/ld+json">' . json_encode($breadcrumb_schema) . '</script>';

// Social Media Meta Tags
$og_image = !empty($images) ? 'https://accessoriesbydija.uk/' . $images[0]['image_url'] : 'https://accessoriesbydija.uk/assets/images/placeholder.jpg';
$og_url = 'https://accessoriesbydija.uk/product/' . $product['slug'];

echo '<meta property="og:title" content="' . htmlspecialchars($product['name']) . '">' . "\n";
echo '<meta property="og:description" content="' . htmlspecialchars($page_description) . '">' . "\n";
echo '<meta property="og:image" content="' . $og_image . '">' . "\n";
echo '<meta property="og:url" content="' . $og_url . '">' . "\n";
echo '<meta property="og:type" content="product">' . "\n";
echo '<meta property="og:site_name" content="Dija Accessories">' . "\n";

echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
echo '<meta name="twitter:title" content="' . htmlspecialchars($product['name']) . '">' . "\n";
echo '<meta name="twitter:description" content="' . htmlspecialchars($page_description) . '">' . "\n";
echo '<meta name="twitter:image" content="' . $og_image . '">' . "\n";

// Additional meta tags
echo '<link rel="canonical" href="' . $og_url . '">' . "\n";
$keywords = $product['name'] . ', ' . $product['category'] . ', ' . $product['material'] . ', ' . ($gender_display ?? '') . ', jewelry, accessories, rings, necklaces, earrings, bracelets, custom jewelry, handcrafted, premium, ethical, Dija Accessories';
echo '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";

// Structured Data for Product
$structured_data = [
    "@context" => "https://schema.org",
    "@type" => "Product",
    "name" => $product['name'],
    "description" => $product['description'],
    "sku" => $product['sku'] ?? '',
    "category" => $product['category'] ?? '',
    "material" => $product['material'] ?? '',
    "brand" => [
        "@type" => "Brand",
        "name" => "Dija Accessories"
    ],
    "manufacturer" => [
        "@type" => "Organization",
        "name" => "Dija Accessories"
    ],
    "offers" => [
        "@type" => "Offer",
        "price" => $product['price'],
        "priceCurrency" => "GBP",
        "availability" => $product['stock_quantity'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
        "seller" => [
            "@type" => "Organization",
            "name" => "Dija Accessories"
        ],
        "priceValidUntil" => date('Y-m-d', strtotime('+1 year'))
    ],
    "additionalProperty" => [
        [
            "@type" => "PropertyValue",
            "name" => "Gender",
            "value" => $gender_display ?? ''
        ],
        [
            "@type" => "PropertyValue",
            "name" => "Care Instructions",
            "value" => "Store in cool dry place, clean with soft cloth and mild soap"
        ]
    ]
];

if (!empty($images)) {
    $structured_data["image"] = array_map(function($img) {
        return "https://accessoriesbydija.uk/" . $img['image_url'];
    }, $images);
}

if (!empty($reviews)) {
    $structured_data["aggregateRating"] = [
        "@type" => "AggregateRating",
        "ratingValue" => array_sum(array_column($reviews, 'rating')) / count($reviews),
        "reviewCount" => count($reviews)
    ];
}
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

/* Guidance Text Styles - Uses Site Theme Colors (#C27BA0) */
.selection-guidance {
    background: linear-gradient(135deg, #C27BA0 0%, #a66889 100%);
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-weight: 600;
    text-align: center;
    animation: pulse 2s infinite;
    box-shadow: 0 4px 15px rgba(194, 123, 160, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.selection-guidance i {
    font-size: 1.1rem;
}
@keyframes pulse {
    0% { transform: scale(1); box-shadow: 0 4px 15px rgba(194, 123, 160, 0.4); }
    50% { transform: scale(1.02); box-shadow: 0 6px 20px rgba(194, 123, 160, 0.6); }
    100% { transform: scale(1); box-shadow: 0 4px 15px rgba(194, 123, 160, 0.4); }
}
.selection-guidance.hidden {
    display: none !important;
}
.selection-guidance.visible {
    display: flex !important;
    animation: fadeInSlideUp 0.4s ease;
}
@keyframes fadeInSlideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
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
.btn-secondary.active { background: #C27BA0; color: white; border-color: #C27BA0; }
.wishlist-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border: 2px solid #C27BA0; background: white; color: #C27BA0; border-radius: 4px; cursor: pointer; transition: all 0.3s; font-weight: 600; }
.wishlist-btn:hover { background: #C27BA0; color: white; }
.wishlist-btn.active { background: #C27BA0; color: white; border-color: #C27BA0; }
.wishlist-btn i { font-size: 1.1rem; }
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

/* Accordion Styles */
.product-accordion { background: #f9f9f9; padding: 3rem 0; }
.product-accordion h2 { text-align: center; margin-bottom: 2rem; color: #333; font-size: 2rem; }
.accordion { width: 100%; }
.accordion-item { border: 1px solid #ddd; margin-bottom: 0.5rem; border-radius: 8px; overflow: hidden; }
.accordion-header { width: 100%; background: white; border: none; padding: 1.5rem; text-align: left; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; font-weight: 600; color: #333; transition: background 0.3s; }
.accordion-header:hover { background: #f5f5f5; }
.accordion-header i { transition: transform 0.3s; }
.accordion-header .fa-chevron-up { transform: rotate(180deg); }
.accordion-content { background: white; padding: 0; max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
.accordion-content p { margin-bottom: 1rem; }
.accordion-content ul { padding-left: 1.5rem; }
.accordion-content li { margin-bottom: 0.5rem; }
.size-chart { margin-top: 1rem; }
.size-chart h4 { margin-bottom: 1rem; color: #333; }
.size-chart table { width: 100%; border-collapse: collapse; }
.size-chart th, .size-chart td { padding: 0.5rem; text-align: center; border: 1px solid #ddd; }
.size-chart th { background: #f5f5f5; font-weight: 600; }

/* Product Suggestions Styles */
.product-suggestions { background: #f9f9f9; padding: 3rem 0; }
.product-suggestions h2 { text-align: center; margin-bottom: 2rem; color: #333; font-size: 2rem; }
.product-suggestions .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
.products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
.product-card { background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; }
.product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.product-card a { text-decoration: none; color: inherit; display: block; }
.product-card img { width: 100%; height: 200px; object-fit: cover; }
.product-card h3 { font-size: 1.1rem; margin: 1rem; color: #333; }
.product-card .price { font-size: 1.2rem; color: #C27BA0; font-weight: bold; margin: 0 1rem 1rem; }
</style>

<main data-product='<?php echo json_encode($product); ?>' data-images='<?php echo json_encode($images); ?>'>
    <div class="breadcrumb">
        <a href="index.php">Home</a> / <a href="products.php">Products</a> / <?php echo htmlspecialchars($product['name']); ?>
    </div>

    <div class="product-detail-container">
        <div class="product-images">
            <div class="main-image-container">
                <div class="main-image" id="mainImage">
                    <?php if ($images && isset($images[0])):
                    ?>
                        <img src="/<?php echo htmlspecialchars($images[0]['image_url']); ?>" alt="<?php echo htmlspecialchars($images[0]['alt_text'] ?? $product['name'] . ' - Premium Jewelry by Accessories By Dija'); ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                    <?php else:
                    ?>
                        💎
                    <?php endif; ?>
                </div>
            </div>
            <div class="image-thumbnails">
                <?php if ($images):
                ?>
                    <?php foreach ($images as $index => $image):
                    ?>
                    <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" data-action="change-image" data-image-id="<?php echo $image['id']; ?>">
                        <img src="/<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?> - View <?php echo $index + 1; ?>" loading="lazy">
                    </div>                    <?php endforeach; ?>
                <?php else:
                ?>
                    <div class="thumbnail active" data-action="change-image">
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:24px;">💎</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="product-details">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="product-price" id="basePrice">£<?php echo number_format($product['price'], 2); ?></div>
            <div class="price-display" id="finalPrice" style="display: none;"></div>

            <div class="product-description">
                <?php echo nl2br(htmlspecialchars($product['short_description'] ?: $product['description'])); ?>
            </div>
            
            <div class="product-options">
                <div class="option-group">
                    <label>Material:</label>
                    <div class="selection-guidance" id="materialGuidance">
                        <i class="fas fa-hand-pointer"></i> Please select a material below to proceed
                    </div>
                    <div class="option-buttons" id="materialOptions">
                        <?php foreach ($materials as $material):
                        ?>
                        <button type="button" class="option-btn" data-action="select-material" data-material-id="<?php echo $material['id']; ?>">
                            <?php echo htmlspecialchars($material['name']); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="option-group" id="variationGroup" style="display: none;">
                    <label>Variation:</label>
                    <div class="selection-guidance" id="variationGuidance">
                        <i class="fas fa-hand-pointer"></i> Please select a variant below to proceed
                    </div>
                    <div class="option-buttons" id="variationOptions"></div>
                </div>
                
                <div class="option-group" id="sizeGroup" style="display: none;">
                    <label>Size:</label>
                    <div class="selection-guidance" id="sizeGuidance">
                        <i class="fas fa-hand-pointer"></i> Please select a size below to proceed
                    </div>
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
                    <button type="button" data-action="change-quantity" data-change="-1" class="btn" style="padding: 0.5rem; width: 40px;">-</button>
                    <input type="number" id="quantityInput" value="1" min="1" style="width: 80px; text-align: center; padding: 0.5rem;">
                    <button type="button" data-action="change-quantity" data-change="1" class="btn" style="padding: 0.5rem; width: 40px;">+</button>
                </div>
            </div>
            
            <div class="product-actions">
                <button class="btn btn-primary" id="addToCartBtn" data-action="add-to-cart" data-product-id="<?php echo $product['id']; ?>" disabled>Select Your Preferred Material to Proceed</button>
                <button data-action="toggle-wishlist" data-product-id="<?php echo $product['id'] ?? 0; ?>" style="padding: 0.75rem 1.5rem; border: 2px solid #C27BA0; background: white; color: #C27BA0; border-radius: 4px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-left: 1rem;">
                    <i class="far fa-heart"></i> Add to Wishlist
                </button>
            </div>
        </div>
    </div>

    <!-- Accordion Section -->
    <section class="product-accordion">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h2 style="text-align: left;">Product Information</h2>
            <div class="accordion" style="max-width: none;">
                <div class="accordion-item">
                    <button class="accordion-header" data-action="toggle-accordion">
                        <span>Product Details</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <?php if (!empty($product['description'])):
                        ?>
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        <?php else:
                        ?>
                            <div class="product-specs">
                                <ul>
                                    <?php if ($product['weight']):
                                    ?>
                                        <li><strong>Weight:</strong> <?= htmlspecialchars($product['weight']) ?>g</li>
                                    <?php endif; ?>
                                    <li><strong>Stock:</strong> <?= htmlspecialchars($product['stock_quantity']) ?> available</li>
                                    <?php if ($product['material']):
                                    ?>
                                        <li><strong>Material:</strong> <?= htmlspecialchars($product['material']) ?></li>
                                    <?php endif; ?>
                                    <?php if ($product['stone_type']):
                                    ?>
                                        <li><strong>Stone Type:</strong> <?= htmlspecialchars($product['stone_type']) ?></li>
                                    <?php endif; ?>
                                    <li><strong>Gender:</strong> <?= htmlspecialchars($gender_display ?? 'Unisex') ?></li>
                                    <li>Premium materials sourced ethically</li>
                                    <li>Handcrafted by skilled artisans</li>
                                    <li>Lifetime warranty on all pieces</li>
                                    <li>Free shipping on orders over £100</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header" data-action="toggle-accordion">
                        <span>Shipping & Delivery</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p>We offer fast and reliable shipping worldwide. Orders are typically processed within 1-2 business days.</p>
                        <ul>
                            <li>Free standard shipping on orders over £100</li>
                            <li>Express delivery available</li>
                            <li>International shipping to most countries</li>
                            <li>Track your order with provided tracking number</li>
                        </ul>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header" data-action="toggle-accordion">
                        <span>Care Instructions</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p>To keep your jewelry looking its best, follow these simple care instructions:</p>
                        <ul>
                            <li>Store in a cool, dry place away from direct sunlight</li>
                            <li>Avoid contact with perfumes, lotions, and chemicals</li>
                            <li>Clean with a soft cloth and mild soap when needed</li>
                            <li>Remove before swimming or strenuous activities</li>
                        </ul>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header" data-action="toggle-accordion">
                        <span>Size Guide</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p>Find your perfect fit with our size guide. If you're unsure about your size, please contact our customer service team for assistance.</p>
                        <div class="size-chart">
                            <h4>Ring Sizes</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>UK Size</th>
                                        <th>US Size</th>
                                        <th>EU Size</th>
                                        <th>Circumference (mm)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>H</td><td>4</td><td>46.8</td><td>46.8</td></tr>
                                    <tr><td>I</td><td>4.5</td><td>48</td><td>48</td></tr>
                                    <tr><td>J</td><td>5</td><td>49.3</td><td>49.3</td></tr>
                                    <tr><td>K</td><td>5.5</td><td>50.6</td><td>50.6</td></tr>
                                    <tr><td>L</td><td>6</td><td>51.9</td><td>51.9</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <section class="product-reviews">
        <div class="container">
            <h2>Customer Reviews</h2>

            <!-- Review Button -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <button id="writeReviewBtn" class="btn btn-primary" data-action="toggle-review-form">Write a Review</button>
            </div>

            <!-- Review Form -->
            <div class="review-form-container" id="reviewFormContainer" style="display: none;">
                <h3>Write a Review</h3>
                <form id="reviewForm" onsubmit="submitReview(event)">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
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
                <?php if (empty($reviews)):
                ?>
                    <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
                <?php else:
                ?>
                    <?php foreach ($reviews as $review):
                    ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-author">
                                <?php if ($review['client_image']):
                                ?>
                                    <img src="<?= htmlspecialchars($review['client_image']) ?>" alt="Reviewer" class="review-avatar">
                                <?php else:
                                ?>
                                    <div class="review-avatar-placeholder">
                                        <?= strtoupper(substr($review['customer_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="review-meta">
                                    <strong><?= htmlspecialchars($review['customer_name']) ?></strong>
                                    <div class="review-rating">
                                        <?php for($i = 1; $i <= 5; $i++):
                                        ?>
                                            <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="review-date">
                                <?= date('M d, Y', strtotime($review['created_at'])) ?>
                            </div>
                        </div>
                        <?php if ($review['title']):
                        ?>
                            <h4 class="review-title"><?= htmlspecialchars($review['title']) ?></h4>
                        <?php endif; ?>
                        <p class="review-content"><?= nl2br(htmlspecialchars($review['content'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Similar Products Section -->
    <?php if (!empty($similar_products)):
    ?>
    <section class="product-suggestions">
        <div class="container">
            <h2>Similar Products</h2>
            <div class="products-grid">
                <?php foreach ($similar_products as $prod):
                ?>
                <div class="product-card">
                    <a href="product.php?slug=<?= htmlspecialchars($prod['slug']) ?>">
                        <img src="/<?= htmlspecialchars($prod['image_url'] ?? 'assets/images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
                        <h3><?= htmlspecialchars($prod['name']) ?></h3>
                        <p class="price">£<?= number_format($prod['price'], 2) ?></p>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Frequently Bought Together Section -->
    <?php if (!empty($frequently_bought)):
    ?>
    <section class="product-suggestions">
        <div class="container">
            <h2>Frequently Bought Together</h2>
            <div class="products-grid">
                <?php foreach ($frequently_bought as $prod):
                ?>
                <div class="product-card">
                    <a href="product.php?slug=<?= htmlspecialchars($prod['slug']) ?>">
                        <img src="/<?= htmlspecialchars($prod['image_url'] ?? 'assets/images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
                        <h3><?= htmlspecialchars($prod['name']) ?></h3>
                        <p class="price">£<?= number_format($prod['price'], 2) ?></p>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
<script src="/assets/js/product-details.js" defer></script>
