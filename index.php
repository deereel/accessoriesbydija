<?php
$page_title = "Premium Jewelry Collection";
$page_description = "Discover handcrafted jewelry at Dija Accessories. Expert artisans create rings, necklaces, earrings, bracelets, and custom pieces. Free shipping, lifetime warranty, ethically sourced materials.";
include 'includes/header.php';
?>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "Dija Accessories",
    "url": "https://accessoriesbydija.com",
    "potentialAction": {
        "@type": "SearchAction",
        "target": "https://accessoriesbydija.com/search?q={search_term_string}",
        "query-input": "required name=search_term_string"
    }
}
</script>

<main>
    <!-- Hero Section -->
    <section class="hero" itemscope itemtype="https://schema.org/Product">
        <div class="hero-content">
            <h1 itemprop="name">Elegant Jewelry for Every Occasion</h1>
            <p itemprop="description">Discover our premium collection of handcrafted jewelry designed for the modern individual. Each piece is ethically sourced, expertly crafted, and comes with a lifetime warranty.</p>
            <div class="hero-features">
                <span class="feature">✓ Ethically Sourced</span>
                <span class="feature">✓ Lifetime Warranty</span>
                <span class="feature">✓ Free Shipping</span>
                <span class="feature">✓ Expert Craftsmanship</span>
            </div>
            <div class="hero-buttons">
                <a href="category.php?cat=women" class="btn btn-primary">Shop Women</a>
                <a href="category.php?cat=men" class="btn btn-secondary">Shop Men</a>
            </div>
        </div>
        <div class="hero-image" itemprop="image">
            <img src="assets/images/hero-jewelry.jpg" alt="Premium handcrafted jewelry collection featuring rings, necklaces, and earrings" loading="lazy">
        </div>
    </section>

    <!-- Featured Categories -->
    <section class="featured-categories" itemscope itemtype="https://schema.org/ItemList">
        <div class="container">
            <h2 itemprop="name">Shop by Category</h2>
            <p class="category-intro">Explore our curated jewelry collections, each piece designed with precision and crafted using premium materials. From everyday elegance to special occasion statements.</p>
            <div class="category-grid">
                <div class="category-card" itemprop="itemListElement" itemscope itemtype="https://schema.org/Product">
                    <img src="assets/images/rings-category.jpg" alt="Premium rings collection including engagement rings, wedding bands, and fashion rings" loading="lazy" itemprop="image">
                    <div class="category-info">
                        <h3 itemprop="name">Rings</h3>
                        <p itemprop="description">Elegant rings for every style - engagement, wedding, fashion, and statement pieces crafted in gold, silver, and platinum</p>
                        <a href="category.php?sub=rings" class="btn-link">Shop Rings</a>
                    </div>
                </div>
                <div class="category-card" itemprop="itemListElement" itemscope itemtype="https://schema.org/Product">
                    <img src="assets/images/necklaces-category.jpg" alt="Luxury necklaces collection featuring pendants, chains, and statement pieces" loading="lazy" itemprop="image">
                    <div class="category-info">
                        <h3 itemprop="name">Necklaces</h3>
                        <p itemprop="description">Statement pieces and delicate chains - from minimalist pendants to bold statement necklaces in precious metals</p>
                        <a href="category.php?sub=necklaces" class="btn-link">Shop Necklaces</a>
                    </div>
                </div>
                <div class="category-card" itemprop="itemListElement" itemscope itemtype="https://schema.org/Product">
                    <img src="assets/images/earrings-category.jpg" alt="Designer earrings collection including studs, hoops, and drop earrings" loading="lazy" itemprop="image">
                    <div class="category-info">
                        <h3 itemprop="name">Earrings</h3>
                        <p itemprop="description">From studs to statement drops - diamond studs, pearl drops, gold hoops, and chandelier earrings for every occasion</p>
                        <a href="category.php?sub=earrings" class="btn-link">Shop Earrings</a>
                    </div>
                </div>
                <div class="category-card" itemprop="itemListElement" itemscope itemtype="https://schema.org/Product">
                    <img src="assets/images/bracelets-category.jpg" alt="Luxury bracelets collection featuring tennis bracelets, bangles, and charm bracelets" loading="lazy" itemprop="image">
                    <div class="category-info">
                        <h3 itemprop="name">Bracelets</h3>
                        <p itemprop="description">Elegant wrist accessories - tennis bracelets, charm bracelets, bangles, and cuffs in gold, silver, and diamond</p>
                        <a href="category.php?sub=bracelets" class="btn-link">Shop Bracelets</a>
                    </div>
                </div>
            </div>
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