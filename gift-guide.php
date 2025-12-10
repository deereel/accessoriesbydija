<?php
$page_title = "Gift Guide - Find Perfect Jewelry Gifts";
$page_description = "Discover the perfect jewelry gift with our curated gift guide. Shop by material, price range, recipient, or occasion.";
include 'includes/header.php';
?>

<style>
.page-header { text-align: center; padding: 3rem 0 2rem; background: #f9f9f9; }
.page-header h1 { font-size: 2.5rem; font-weight: 300; margin-bottom: 1rem; }
.breadcrumb { text-align: center; margin-bottom: 2rem; }
.breadcrumb a { color: #666; text-decoration: none; }
</style>

<main>
    <div class="page-header">
        <h1>Gift Guide</h1>
        <div class="breadcrumb">
            <a href="index.php">Home</a> / Gift Guide
        </div>
    </div>

    <section class="gift-guide">
        <div class="container">
            <p class="gift-guide-intro">Find the perfect jewelry gift for any occasion. Browse our curated collections by material, price range, recipient, or special offers.</p>
            
            <div class="gift-grid">
                <!-- By Material -->
                <a href="products.php?material=gold" class="gift-card">
                    <div class="gift-icon">🥇</div>
                    <div class="gift-content">
                        <h3>Gold Collection</h3>
                        <p>Timeless gold jewelry pieces that never go out of style</p>
                        <div class="gift-features">
                            <span class="gift-tag">14K & 18K Gold</span>
                            <span class="gift-tag">Classic</span>
                        </div>
                    </div>
                </a>

                <a href="products.php?material=silver" class="gift-card">
                    <div class="gift-icon">🥈</div>
                    <div class="gift-content">
                        <h3>Silver Collection</h3>
                        <p>Elegant sterling silver pieces for everyday luxury</p>
                        <div class="gift-features">
                            <span class="gift-tag">Sterling Silver</span>
                            <span class="gift-tag">Versatile</span>
                        </div>
                    </div>
                </a>

                <!-- By Price Range -->
                <a href="products.php?price_min=0&price_max=100" class="gift-card">
                    <div class="gift-icon">💝</div>
                    <div class="gift-content">
                        <h3>Under £100</h3>
                        <p>Beautiful jewelry gifts that won't break the bank</p>
                        <div class="gift-features">
                            <span class="gift-tag">Affordable</span>
                            <span class="gift-tag">Quality</span>
                        </div>
                    </div>
                </a>

                <a href="products.php?price_min=100&price_max=300" class="gift-card">
                    <div class="gift-icon">💎</div>
                    <div class="gift-content">
                        <h3>£100 - £300</h3>
                        <p>Premium pieces for special occasions and milestones</p>
                        <div class="gift-features">
                            <span class="gift-tag">Premium</span>
                            <span class="gift-tag">Special</span>
                        </div>
                    </div>
                </a>

                <!-- By Recipient -->
                <a href="products.php?gender=women" class="gift-card">
                    <div class="gift-icon">👩</div>
                    <div class="gift-content">
                        <h3>Gifts for Her</h3>
                        <p>Elegant jewelry designed for the modern woman</p>
                        <div class="gift-features">
                            <span class="gift-tag">Rings</span>
                            <span class="gift-tag">Necklaces</span>
                            <span class="gift-tag">Earrings</span>
                        </div>
                    </div>
                </a>

                <a href="products.php?gender=men" class="gift-card">
                    <div class="gift-icon">👨</div>
                    <div class="gift-content">
                        <h3>Gifts for Him</h3>
                        <p>Sophisticated jewelry pieces for the discerning gentleman</p>
                        <div class="gift-features">
                            <span class="gift-tag">Rings</span>
                            <span class="gift-tag">Chains</span>
                            <span class="gift-tag">Cufflinks</span>
                        </div>
                    </div>
                </a>

                <!-- Special Offers -->
                <a href="custom-jewelry.php" class="gift-card special">
                    <div class="gift-icon">
                        ✨
                        <span class="gift-badge">Custom</span>
                    </div>
                    <div class="gift-content">
                        <h3>Custom Design</h3>
                        <p>Create a one-of-a-kind piece that tells their unique story</p>
                        <div class="gift-features">
                            <span class="gift-tag">Free Consultation</span>
                            <span class="gift-tag">3D Preview</span>
                        </div>
                    </div>
                </a>

                <a href="products.php?featured=1" class="gift-card special">
                    <div class="gift-icon">
                        🎁
                        <span class="gift-badge">Popular</span>
                    </div>
                    <div class="gift-content">
                        <h3>Gift Box Collection</h3>
                        <p>Curated gift sets with premium packaging included</p>
                        <div class="gift-features">
                            <span class="gift-tag">Gift Wrap</span>
                            <span class="gift-tag">Personal Note</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>