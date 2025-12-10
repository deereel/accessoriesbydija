<?php
$page_title = "Premium Jewelry Collection";
$page_description = "Discover handcrafted jewelry at Dija Accessories. Expert artisans create rings, necklaces, earrings, bracelets, and custom pieces. Free shipping, lifetime warranty, ethically sourced materials.";
include 'includes/header.php';
?>

<main>
    <!-- Hero Slider -->
    <section class="hero-slider">
        <div class="slider-container">
            <div class="slide active" data-bg="assets/images/hero-1.jpg">
                <div class="slide-content">
                    <h1>Timeless Elegance</h1>
                    <p>Discover modern jewelry made to match your everyday elegance</p>
                    <div class="hero-buttons">
                        <a href="category.php?cat=women" class="hero-btn">Shop Women</a>
                        <a href="category.php?cat=men" class="hero-btn hero-btn-outline">Shop Men</a>
                    </div>
                </div>
            </div>
            <div class="slide" data-bg="assets/images/hero-2.jpg">
                <div class="slide-content">
                    <h1>Luxury Redefined</h1>
                    <p>Handcrafted pieces that tell your unique story</p>
                    <a href="exclusives.php" class="hero-btn">Explore Collections</a>
                </div>
            </div>
            <div class="slide" data-bg="assets/images/hero-3.jpg">
                <div class="slide-content">
                    <h1>Custom Creations</h1>
                    <p>Work with our designers to create your perfect piece</p>
                    <a href="custom-jewelry.php" class="hero-btn">Start Designing</a>
                </div>
            </div>
        </div>
        <div class="slider-nav">
            <button class="prev" onclick="changeSlide(-1)"><i class="fas fa-chevron-left"></i></button>
            <button class="next" onclick="changeSlide(1)"><i class="fas fa-chevron-right"></i></button>
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
                <button class="category-btn" data-category="women" onclick="toggleCategory('women')">
                    <div class="category-icon">♀</div>
                    <h3>Women</h3>
                    <p>Elegant jewelry for her</p>
                </button>
                <button class="category-btn" data-category="men" onclick="toggleCategory('men')">
                    <div class="category-icon">♂</div>
                    <h3>Men</h3>
                    <p>Sophisticated pieces for him</p>
                </button>
            </div>
            
            <div class="subcategories" id="subcategories">
                <div class="subcategory-grid" id="women-subcategories" style="display: none;">
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=rings'">
                        <div class="subcategory-image">💍</div>
                        <h4>Rings</h4>
                        <p>Engagement, wedding & fashion rings</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=earrings'">
                        <div class="subcategory-image">👂</div>
                        <h4>Earrings</h4>
                        <p>Studs, hoops & statement pieces</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=necklaces'">
                        <div class="subcategory-image">📿</div>
                        <h4>Necklaces</h4>
                        <p>Pendants, chains & chokers</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=bracelets'">
                        <div class="subcategory-image">💎</div>
                        <h4>Bracelets</h4>
                        <p>Tennis, charm & chain bracelets</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=bangles'">
                        <div class="subcategory-image">⭕</div>
                        <h4>Bangles/Cuffs</h4>
                        <p>Statement bangles & cuffs</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=women&sub=anklets'">
                        <div class="subcategory-image">🦶</div>
                        <h4>Anklets</h4>
                        <p>Delicate ankle jewelry</p>
                    </div>
                </div>
                
                <div class="subcategory-grid" id="men-subcategories" style="display: none;">
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=rings'">
                        <div class="subcategory-image">💍</div>
                        <h4>Rings</h4>
                        <p>Wedding bands & signet rings</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=bracelets'">
                        <div class="subcategory-image">⛓️</div>
                        <h4>Bracelets</h4>
                        <p>Leather, metal & beaded</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=necklaces'">
                        <div class="subcategory-image">📿</div>
                        <h4>Necklaces</h4>
                        <p>Pendants & dog tags</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=chains'">
                        <div class="subcategory-image">🔗</div>
                        <h4>Chains</h4>
                        <p>Gold, silver & steel chains</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=watches'">
                        <div class="subcategory-image">⌚</div>
                        <h4>Watches</h4>
                        <p>Luxury timepieces</p>
                    </div>
                    <div class="subcategory-card" onclick="location.href='category.php?cat=men&sub=cufflinks'">
                        <div class="subcategory-image">🔘</div>
                        <h4>Cufflinks</h4>
                        <p>Elegant shirt accessories</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Collection Banner Swiper -->
    <section class="collection-banners">
        <div class="banner-swiper">
            <div class="banner-slide active" data-bg="assets/images/golden-hour-banner.jpg">
                <div class="banner-content">
                    <h2>Golden Hour Glow</h2>
                    <p>Discover our exclusive collection of warm-toned jewelry that captures the magic of golden hour</p>
                    <a href="collection.php?name=golden-hour" class="banner-btn">Shop Collection</a>
                </div>
            </div>
            <div class="banner-slide" data-bg="assets/images/luxury-banner.jpg">
                <div class="banner-content">
                    <h2>Luxury Collection</h2>
                    <p>Exquisite pieces crafted with the finest materials and exceptional attention to detail</p>
                    <a href="collection.php?name=luxury" class="banner-btn">Explore Luxury</a>
                </div>
            </div>
            <div class="banner-slide" data-bg="assets/images/holiday-banner.jpg">
                <div class="banner-content">
                    <h2>Holiday Sale</h2>
                    <p>Special offers on selected jewelry pieces - perfect gifts for your loved ones</p>
                    <a href="sale.php" class="banner-btn">Shop Sale</a>
                </div>
            </div>
        </div>
        <div class="banner-nav">
            <button class="banner-prev" onclick="changeBannerSlide(-1)"><i class="fas fa-chevron-left"></i></button>
            <button class="banner-next" onclick="changeBannerSlide(1)"><i class="fas fa-chevron-right"></i></button>
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
            <div class="product-grid" id="featured-products">
                <!-- Products loaded via JavaScript -->
            </div>
        </div>
    </section>

    <!-- Custom Jewelry CTA -->
    <section class="custom-cta" itemscope itemtype="https://schema.org/Service">
        <div class="container">
            <div class="cta-content">
                <h2 itemprop="name">Create Your Perfect Piece</h2>
                <p itemprop="description">Work with our expert designers to create custom jewelry that tells your unique story. From engagement rings to personalized gifts, we bring your vision to life with premium materials and expert craftsmanship.</p>
                <div class="custom-features">
                    <span>✓ Free Consultation</span>
                    <span>✓ 3D Design Preview</span>
                    <span>✓ Expert Artisans</span>
                </div>
                <a href="custom-jewelry.php" class="btn btn-primary" itemprop="url">Start Custom Design</a>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials" itemscope itemtype="https://schema.org/Organization">
        <div class="container">
            <h2>What Our Customers Say</h2>
            <p class="testimonials-intro">Real reviews from satisfied customers who chose Dija Accessories for their special moments.</p>
            <div class="testimonial-grid">
                <div class="testimonial-card" itemprop="review" itemscope itemtype="https://schema.org/Review">
                    <div class="stars" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                        <meta itemprop="ratingValue" content="5">
                        <meta itemprop="bestRating" content="5">
                        ★★★★★
                    </div>
                    <p itemprop="reviewBody">"Beautiful quality jewelry and excellent customer service. My custom engagement ring exceeded all expectations!"</p>
                    <cite itemprop="author" itemscope itemtype="https://schema.org/Person">
                        - <span itemprop="name">Sarah M.</span>
                    </cite>
                </div>
                <div class="testimonial-card" itemprop="review" itemscope itemtype="https://schema.org/Review">
                    <div class="stars" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                        <meta itemprop="ratingValue" content="5">
                        <meta itemprop="bestRating" content="5">
                        ★★★★★
                    </div>
                    <p itemprop="reviewBody">"Fast shipping and gorgeous pieces. The necklace I ordered looks even better in person."</p>
                    <cite itemprop="author" itemscope itemtype="https://schema.org/Person">
                        - <span itemprop="name">Michael R.</span>
                    </cite>
                </div>
                <div class="testimonial-card" itemprop="review" itemscope itemtype="https://schema.org/Review">
                    <div class="stars" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                        <meta itemprop="ratingValue" content="5">
                        <meta itemprop="bestRating" content="5">
                        ★★★★★
                    </div>
                    <p itemprop="reviewBody">"Amazing craftsmanship and attention to detail. Will definitely be ordering again!"</p>
                    <cite itemprop="author" itemscope itemtype="https://schema.org/Person">
                        - <span itemprop="name">Emma L.</span>
                    </cite>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>