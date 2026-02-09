<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$page_title = "Premium Jewelry Collection";
$page_description = "Discover handcrafted jewelry at Dija Accessories. Expert artisans create rings, necklaces, earrings, bracelets, and custom pieces. Free shipping, lifetime warranty, ethically sourced materials.";
include 'includes/header.php';

// AI-Optimized Structured Data for Homepage
$org_structured_data = generateBusinessStructuredData();
echo '<script type="application/ld+json">' . json_encode($org_structured_data) . '</script>';

// Add WebSite schema for AI understanding
$base_url = isset($BASE_URL) ? $BASE_URL : 'https://' . $_SERVER['HTTP_HOST'];
$website_schema = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => "Accessories By Dija",
    "url" => $base_url,
    "description" => "Premium handcrafted jewelry collection featuring rings, necklaces, earrings, bracelets, and custom pieces. Expert artisans create timeless jewelry with ethically sourced materials.",
    "publisher" => [
        "@type" => "Organization",
        "@id" => $base_url . "#organization"
    ],
    "potentialAction" => [
        [
            "@type" => "SearchAction",
            "target" => [
                "@type" => "EntryPoint",
                "urlTemplate" => $base_url . "/search?q={search_term_string}"
            ],
            "query-input" => "required name=search_term_string"
        ]
    ],
    "mainEntity" => [
        "@type" => "ItemList",
        "name" => "Featured Jewelry Collection",
        "description" => "Our most popular and trending jewelry pieces"
    ]
];

echo '<script type="application/ld+json">' . json_encode($website_schema) . '</script>';

// FAQ Schema for AI understanding
$faq_schema = [
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => [
        [
            "@type" => "Question",
            "name" => "What materials do you use for your jewelry?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "We use premium materials including 14K and 18K gold, sterling silver, and ethically sourced gemstones. All our pieces are crafted with attention to quality and durability."
            ]
        ],
        [
            "@type" => "Question",
            "name" => "Do you offer custom jewelry design?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "Yes, we offer custom jewelry design services. Our expert artisans work with you to create one-of-a-kind pieces that match your vision and style."
            ]
        ],
        [
            "@type" => "Question",
            "name" => "What is your return policy?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "We offer a 30-day return policy on all jewelry purchases. Items must be in original condition with tags attached. Custom pieces are not eligible for return."
            ]
        ],
        [
            "@type" => "Question",
            "name" => "Do you provide international shipping?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "Yes, we ship worldwide. Shipping costs and delivery times vary by location. Free shipping is available on orders over £100 within the UK."
            ]
        ],
        [
            "@type" => "Question",
            "name" => "How do I care for my jewelry?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "Store jewelry in a cool, dry place away from direct sunlight. Clean with a soft cloth and mild soap when needed. Remove before swimming or strenuous activities. Regular professional cleaning is recommended for gold pieces."
            ]
        ]
    ]
];

echo '<script type="application/ld+json">' . json_encode($faq_schema) . '</script>';
?>

<!-- Load critical JavaScript files -->
<script src="/assets/js/category-section.js"></script>
<script src="/assets/js/testimonials.js"></script>

<main>
    <h1 style="display:none;">Premium Jewelry Collection</h1>
    <!-- Hero Slider -->
    <section class="hero-slider">
        <div class="slider-container">
            <div class="slide active" data-bg="/assets/images/hero1.webp" aria-label="Timeless Elegance" title="Timeless Elegance">
                <div class="slide-content">
                    <h2>Timeless Elegance</h2>
                    <p>Discover modern jewelry made to match your everyday elegance</p>
                    <div class="hero-buttons">
                        <a href="category.php?cat=women" class="hero-btn">Shop Women</a>
                        <a href="category.php?cat=men" class="hero-btn hero-btn-outline">Shop Men</a>
                    </div>
                </div>
            </div>
            <div class="slide" data-bg="/assets/images/hero2.webp" aria-label="Luxury Redefined" title="Luxury Redefined">
                <div class="slide-content">
                    <h2>Luxury Redefined</h2>
                    <p>Handcrafted pieces that tell your unique story</p>
                    <a href="category.php?cat=women" class="hero-btn">Explore Collections</a>
                </div>
            </div>
            <div class="slide" data-bg="/assets/images/hero3.webp" aria-label="Custom Creations" title="Custom Creations">
                <div class="slide-content">
                    <h2>Custom Creations</h2>
                    <p>Work with our designers to create your perfect piece</p>
                    <a href="custom-jewelry.php" class="hero-btn">Start Designing</a>
                </div>
            </div>
        </div>
        <div class="slider-nav">
            <button class="prev" onclick="changeSlide(-1)">&larr;</button>
            <button class="next" onclick="changeSlide(1)">&rarr;</button>
        </div>
        <div class="slider-dots">
            <span class="dot active" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
            <span class="dot" onclick="currentSlide(3)"></span>
        </div>
    </section>


    <!-- Shop by Category -->
    <section class="shop-by-category">
        <div class="container">
            <h2>Shop by Category</h2>
            <p class="section-intro">Discover our curated collections designed for every style and occasion</p>
            
            <div class="main-categories">
                <button class="category-btn" data-category="women">
                    <div class="category-overlay">
                        <h3>Women</h3>
                        <p>Elegant jewelry for her</p>
                    </div>
                </button>
                <button class="category-btn" data-category="men">
                    <div class="category-overlay">
                        <h3>Men</h3>
                        <p>Sophisticated pieces for him</p>
                    </div>
                </button>
            </div>
            
            <div class="subcategories" id="subcategories">
                <div class="subcategory-grid" id="women-subcategories" style="display: none;">
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=anklets'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/anklets.webp" alt="Anklets" loading="lazy" onerror="this.src='/assets/images/placeholder.webp'; this.onerror=null;">
                        </div>
                        <h4>Anklets</h4>
                        <p>Delicate ankle jewelry</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=bangles'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/bangles.webp" alt="Bangles" loading="lazy">
                        </div>
                        <h4>Bangles</h4>
                        <p>Statement bangles & cuffs</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=bracelets'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/f-bracelet.webp" alt="Female Bracelets" loading="lazy" onerror="this.src='/assets/images/placeholder.webp'; this.onerror=null;">
                        </div>
                        <h4>Bracelets</h4>
                        <p>Tennis, charm & chain bracelets</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=earrings'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/earrings.webp" alt="Earrings" loading="lazy">
                        </div>
                        <h4>Earrings</h4>
                        <p>Studs, hoops & statement pieces</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=hand-chains'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/f-hand-chain.webp" alt="Hand Chains" loading="lazy">
                        </div>
                        <h4>Hand Chains</h4>
                        <p>Stylish hand jewelry</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=necklaces'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/f-necklace.webp" alt="Necklaces" loading="lazy" onerror="this.src='/assets/images/placeholder.webp'; this.onerror=null;">
                        </div>
                        <h4>Necklaces</h4>
                        <p>Pendants, chains & chokers</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=pendants'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/pendants.webp" alt="Pendants" loading="lazy">
                        </div>
                        <h4>Pendants</h4>
                        <p>Elegant pendant designs</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=rings'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/f-ring.webp" alt="Rings" loading="lazy">
                        </div>
                        <h4>Rings</h4>
                        <p>Engagement, wedding & fashion rings</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=sets'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/f-set.webp" alt="Sets" loading="lazy">
                        </div>
                        <h4>Sets</h4>
                        <p>Matching jewelry sets</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=studs'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/studs.webp" alt="Studs" loading="lazy">
                        </div>
                        <h4>Studs</h4>
                        <p>Classic stud earrings</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=watches'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/f-watch.webp" alt="Watches" loading="lazy" onerror="this.src='/assets/images/placeholder.webp'; this.onerror=null;">
                        </div>
                        <h4>Watches</h4>
                        <p>Elegant timepieces</p>
                    </div>
                </div>
                
                <div class="subcategory-grid" id="men-subcategories" style="display: none;">
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=bracelets'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/m-bracelet.webp" alt="Bracelets" loading="lazy">
                        </div>
                        <h4>Bracelets</h4>
                        <p>Leather, metal & beaded</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=cufflinks'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/cufflinks.webp" alt="Cufflinks" loading="lazy">
                        </div>
                        <h4>Cufflinks</h4>
                        <p>Elegant shirt accessories</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=studs'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/studs.webp" alt="Studs" loading="lazy">
                        </div>
                        <h4>Studs</h4>
                        <p>Classic stud earrings</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=Hand Chains'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/m-hand-chain.webp" alt="Hand Chains" loading="lazy">
                        </div>
                        <h4>Hand Chains</h4>
                        <p>Stylish hand jewelry</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=necklaces'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/m-necklace.webp" alt="Necklaces" loading="lazy" onerror="this.src='/assets/images/placeholder.webp'; this.onerror=null;">
                        </div>
                        <h4>Necklaces</h4>
                        <p>Pendants & dog tags</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=pendants'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/pendants.webp" alt="Pendants" loading="lazy">
                        </div>
                        <h4>Pendants</h4>
                        <p>Elegant pendant designs</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=rings'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/m-ring.webp" alt="Rings" loading="lazy">
                        </div>
                        <h4>Rings</h4>
                        <p>Wedding bands & signet rings</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=sets'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/m-set.webp" alt="Sets" loading="lazy">
                        </div>
                        <h4>Sets</h4>
                        <p>Matching jewelry sets</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=watches'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/m-watch.webp" alt="Watches" loading="lazy">
                        </div>
                        <h4>Watches</h4>
                        <p>Luxury timepieces</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=lapels-clips'">
                        <div class="subcategory-image">
                            <img src="/assets/images/subcategories/clips.webp" alt="Lapels/Clips" loading="lazy">
                        </div>
                        <h4>Lapels/Clips</h4>
                        <p>Tie clips & lapel pins</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Collection Banner Swiper -->
    <section class="collection-banners">
        <div class="banner-swiper">
            <div class="banner-slide active" data-bg="/assets/images/golden-hour-banner.jpg" aria-label="Golden Hour Glow" title="Golden Hour Glow">
                <div class="banner-content">
                    <h2>Golden Hour Glow</h2>
                    <p>Discover our exclusive collection of warm-toned jewelry that captures the magic of golden hour</p>
                    <a href="collection.php?name=golden-hour" class="banner-btn">Shop Collection</a>
                </div>
            </div>
            <div class="banner-slide" data-bg="/assets/images/luxury-banner.jpg" aria-label="Luxury Collection" title="Luxury Collection">
                <div class="banner-content">
                    <h2>Luxury Collection</h2>
                    <p>Exquisite pieces crafted with the finest materials and exceptional attention to detail</p>
                    <a href="collection.php?name=luxury" class="banner-btn">Explore Luxury</a>
                </div>
            </div>
            <div class="banner-slide" data-bg="/assets/images/holiday-banner.jpg" aria-label="Holiday Sale" title="Holiday Sale">
                <div class="banner-content">
                    <h2>Holiday Sale</h2>
                    <p>Special offers on selected jewelry pieces - perfect gifts for your loved ones</p>
                    <a href="sale.php" class="banner-btn">Shop Sale</a>
                </div>
            </div>
        </div>
        <div class="banner-nav">
            <button class="banner-prev" onclick="changeBannerSlide(-1)">&larr;</button>
            <button class="banner-next" onclick="changeBannerSlide(1)">&rarr;</button>
        </div>
        <div class="banner-dots">
            <span class="banner-dot active" onclick="currentBannerSlide(1)"></span>
            <span class="banner-dot" onclick="currentBannerSlide(2)"></span>
            <span class="banner-dot" onclick="currentBannerSlide(3)"></span>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <h2>Featured Products</h2>
            <div class="featured-grid-4x2" id="featured-products">
                <?php
                try {
                    $shuffle_seed = floor(time() / (2 * 24 * 60 * 60));
                    $stmt = $pdo->prepare("
                        SELECT p.*, 
                               COALESCE(pi_primary.image_url, pi_first.image_url) as image_url
                        FROM products p 
                        LEFT JOIN product_images pi_primary ON p.id = pi_primary.product_id AND pi_primary.is_primary = 1
                        LEFT JOIN product_images pi_first ON p.id = pi_first.product_id AND pi_first.id = (
                            SELECT MIN(id) FROM product_images WHERE product_id = p.id
                        )
                        WHERE p.is_featured = 1 AND p.is_active = 1 
                        ORDER BY RAND(?) 
                        LIMIT 8
                    ");
                    $stmt->execute([$shuffle_seed]);
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($products) {
                        foreach ($products as $product) {
                            $price = (float)($product['price'] ?? 0);
                            $desc = trim($product['description'] ?? '');
                            $shortDesc = strlen($desc) > 80 ? substr($desc, 0, 80) . '…' : $desc;
                            
                            // Ensure image URL is absolute path
                            $imageUrl = $product['image_url'];
                            if ($imageUrl && strpos($imageUrl, '/') !== 0) {
                                $imageUrl = '/' . $imageUrl;
                            }
                            ?>
                            <div class="featured-card" data-product-id="<?php echo $product['id']; ?>" data-price="<?php echo $price; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                <button class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>" onclick="toggleWishlist(<?php echo $product['id']; ?>)" aria-label="Add to wishlist">
                                    <i class="far fa-heart"></i>
                                </button>
                                <div class="featured-image">
                                    <a href="product.php?slug=<?php echo $product['slug']; ?>" aria-label="View <?php echo htmlspecialchars($product['name']); ?>">
                                        <?php if ($imageUrl): ?>
                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy" onerror="this.src='/assets/images/placeholder.jpg'; this.onerror=null;">
                                        <?php else: ?>
                                            <div class="featured-placeholder"><?php echo htmlspecialchars(strtoupper(substr($product['name'], 0, 2))); ?></div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="featured-info">
                                    <h3><a href="product.php?slug=<?php echo $product['slug']; ?>" style="text-decoration:none;color:inherit;"><?php echo htmlspecialchars($product['name']); ?></a></h3>
                                    <?php if ($shortDesc): ?>
                                        <p class="featured-description"><?php echo htmlspecialchars($shortDesc); ?></p>
                                    <?php endif; ?>
                                    <?php if ($product['weight']): ?>
                                        <p style="font-size: 0.75rem; color: #888; margin-bottom: 0.5rem;">⚖️ <?php echo htmlspecialchars((string)$product['weight']); ?>g</p>
                                    <?php endif; ?>
                                    <div class="featured-footer">
                                        <span class="featured-price" data-price="<?php echo $price; ?>">£<?php echo number_format($price, 2); ?></span>
                                        <div class="featured-actions">
                                            <button class="action-btn add-to-cart" onclick="addToCart(<?php echo $product['id']; ?>)" data-product-id="<?php echo $product['id']; ?>" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                                Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                    <div class="stock-badge" style="margin-top: 8px; text-align: center; font-size: 0.85rem; font-weight: 500;">
                                        <?php if ($product['stock_quantity'] <= 0): ?>
                                            <span style="color: #d32f2f; background-color: #ffebee; padding: 4px 8px; border-radius: 4px; display: inline-block;">Out of Stock</span>
                                        <?php elseif ($product['stock_quantity'] < 10): ?>
                                            <span style="color: #f57c00; background-color: #fff3e0; padding: 4px 8px; border-radius: 4px; display: inline-block;">Only <?php echo $product['stock_quantity']; ?> left</span>
                                        <?php else: ?>
                                            <span style="color: #388e3c; background-color: #e8f5e9; padding: 4px 8px; border-radius: 4px; display: inline-block;">In Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p style="text-align: center; color: #666;">No featured products available.</p>';
                    }
                } catch (Exception $e) {
                    error_log('Error fetching featured products in index.php: ' . $e->getMessage());
                    echo '<p style="text-align: center; color: #666;">Error loading products.</p>';
                }
                ?>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="products.php" class="custom-btn">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Custom CTA Section -->
    <section class="custom-cta">
        <div class="container">
            <div class="custom-content">
                <div class="custom-text">
                    <h2>Create Your Perfect Piece</h2>
                    <p>Work with our expert designers to create custom jewelry that tells your unique story. From engagement rings to personalized gifts, we bring your vision to life with premium materials and expert craftsmanship.</p>
                    <div class="custom-features">
                        <span>✓ Free Consultation</span>
                        <span>✓ 3D Design Preview</span>
                        <span>✓ Expert Artisans</span>
                    </div>
                    <a href="custom-jewelry.php" class="custom-btn">Start Custom Design</a>
                </div>
                <div class="custom-image">
                    <div class="jewelry-sketch">💍</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gift Guide Section -->
    <section class="gift-guide">
        <div class="container">
            <h2>Gift Guide</h2>
            <p class="gift-guide-intro">Find the perfect jewelry gift for any occasion</p>
            
            <div class="gift-grid">
                <a href="products.php?material=gold" class="gift-card">
                    <div class="gift-icon">🥇</div>
                    <div class="gift-content">
                        <h3>Gold Collection</h3>
                        <p>Timeless gold jewelry pieces</p>
                        <div class="gift-features">
                            <span class="gift-tag">14K & 18K</span>
                        </div>
                    </div>
                </a>

                <a href="products.php?gender=women" class="gift-card">
                    <div class="gift-icon">👩</div>
                    <div class="gift-content">
                        <h3>Gifts for Her</h3>
                        <p>Elegant jewelry for women</p>
                        <div class="gift-features">
                            <span class="gift-tag">Popular</span>
                        </div>
                    </div>
                </a>

                <a href="products.php?price_min=0&price_max=100" class="gift-card">
                    <div class="gift-icon">💝</div>
                    <div class="gift-content">
                        <h3>Under £100</h3>
                        <p>Affordable luxury gifts</p>
                        <div class="gift-features">
                            <span class="gift-tag">Budget</span>
                        </div>
                    </div>
                </a>

                <a href="custom-jewelry.php" class="gift-card special">
                    <div class="gift-icon">
                        ✨
                        <span class="gift-badge">Custom</span>
                    </div>
                    <div class="gift-content">
                        <h3>Custom Design</h3>
                        <p>One-of-a-kind pieces</p>
                        <div class="gift-features">
                            <span class="gift-tag">Unique</span>
                        </div>
                    </div>
                </a>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="gift-guide.php" class="custom-btn">View Full Gift Guide</a>
            </div>
        </div>
    </section>

    <!-- Shop by Price Section -->
    <section class="shop-by-price">
        <div class="container">
            <h2>Shop by Price</h2>
            <p class="price-intro">Find jewelry that fits your budget with our curated price ranges</p>
            
            <div class="price-buckets">
                <a href="products.php?price_min=0&price_max=50" class="price-bucket">
                    <div class="price-range">Under <span class="currency-symbol">£</span><span class="price-amount" data-price="50">50</span></div>
                    <div class="price-description">Affordable everyday pieces</div>
                    <div class="price-count">Budget-friendly</div>
                </a>

                <a href="products.php?price_min=50&price_max=150" class="price-bucket">
                    <div class="price-range"><span class="currency-symbol">£</span><span class="price-amount" data-price="50">50</span> - <span class="currency-symbol">£</span><span class="price-amount" data-price="150">150</span></div>
                    <div class="price-description">Quality jewelry for gifts</div>
                    <div class="price-count">Most popular</div>
                </a>

                <a href="products.php?price_min=150&price_max=300" class="price-bucket">
                    <div class="price-range"><span class="currency-symbol">£</span><span class="price-amount" data-price="150">150</span> - <span class="currency-symbol">£</span><span class="price-amount" data-price="300">300</span></div>
                    <div class="price-description">Premium collections</div>
                    <div class="price-count">Special occasions</div>
                </a>

                <a href="products.php?price_min=300" class="price-bucket">
                    <div class="price-range"><span class="currency-symbol">£</span><span class="price-amount" data-price="300">300</span>+</div>
                    <div class="price-description">Luxury statement pieces</div>
                    <div class="price-count">Exclusive</div>
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <h2>What Our Customers Say</h2>
            <p class="testimonials-intro">
                Real reviews from satisfied customers who chose Dija Accessories
            </p>

            <div id="testimonials-slider" class="testimonials-slider swiper">
                <div class="swiper-wrapper">
                    <!-- Slides injected by testimonials.js -->
                </div>

                <!-- Pagination -->
                <div class="swiper-pagination"></div>

                <!-- Navigation (MUST be inside slider for Swiper) -->
                <div class="testimonials-nav">
                    <button class="testimonials-prev">
                        &larr;
                    </button>
                    <button class="testimonials-next">
                        &rarr;
                    </button>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>