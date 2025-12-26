<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/security.php';
require_once 'config/seo.php';
require_once 'config/cache.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Accessories By Dija - Premium Jewelry</title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Discover premium jewelry collection at Accessories By Dija. Rings, necklaces, earrings, bracelets, and custom jewelry for men and women.'; ?>">
    <link rel="canonical" href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/hero.css">
    <link rel="stylesheet" href="assets/css/category-section.css">
    <link rel="stylesheet" href="assets/css/collection-banners.css">
    <link rel="stylesheet" href="assets/css/featured-products.css">
    <link rel="stylesheet" href="assets/css/custom-cta.css">
    <link rel="stylesheet" href="assets/css/gift-guide.css">
    <link rel="stylesheet" href="assets/css/shop-by-price.css">
    <link rel="stylesheet" href="assets/css/testimonials.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/product-cards.css">
    <link rel="stylesheet" href="assets/css/megamenu.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="assets/js/header.js" defer></script>
    <script src="assets/js/new-nav.js" defer></script>
    <script>
    // Lightweight client-side event tracker that posts to server for basic analytics
    window.trackEvent = function(eventName, payload) {
        try {
            fetch('/api/analytics/collect.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event: eventName, payload: payload || {} })
            }).catch(function(){ /* ignore failures */ });
        } catch (e) {}
    };
    </script>
    <style>
    /* Scroll-to-top button (global) */
    .scroll-top {
        position: fixed;
        right: 18px;
        bottom: 22px;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #C27BA0;
        color: #fff;
        border: none;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 2000;
        box-shadow: 0 6px 18px rgba(0,0,0,0.15);
        font-size: 18px;
    }
    .scroll-top.show { display: flex; }
    .scroll-top:focus { outline: 2px solid #fff; }
    </style>
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">
    <header class="header">
        <div class="header-top">
            <div class="search-container">
                <input type="text" placeholder="Type to start searching…" id="search-input">
                <i class="fas fa-search"></i>
            </div>
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/logo.webp" alt="Accessories By Dija" />
                </a>
            </div>
            <div class="header-icons">
                <a href="account.php" class="icon-link">
                    <i class="fas fa-user"></i>
                </a>
                <a href="cart.php" class="icon-link cart-icon">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count">0</span>
                </a>
                <div class="hamburger" id="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
        <nav class="main-nav">
            <div class="mobile-close" id="mobile-close">
                <i class="fas fa-times"></i>
            </div>
            <div class="nav-item" data-menu="new">
                <a href="new-arrivals.php">NEW <i class="fas fa-chevron-down"></i></a>
            </div>
            <div class="nav-item" data-menu="shop">
                <a href="products.php">SHOP <i class="fas fa-chevron-down"></i></a>
            </div>
            <div class="nav-item" data-menu="women">
                <a href="products.php?gender=women">WOMEN <i class="fas fa-chevron-down"></i></a>
            </div>
            <div class="nav-item" data-menu="men">
                <a href="products.php?gender=men">MEN <i class="fas fa-chevron-down"></i></a>
            </div>
            <div class="nav-item" data-menu="gift">
                <a href="gift-box.php">GIFT BOX <i class="fas fa-chevron-down"></i></a>
            </div>
        </nav>
        
        <!-- Mega Menu Dropdowns -->
        <div class="mega-menu" id="mega-menu">
            <!-- NEW Dropdown -->
            <div class="dropdown-content" data-content="new">
                <div class="new-products-grid" id="new-products-grid">
                    <div class="product-card"><div class="product-image"><div class="placeholder-img">NEW</div></div><h4>New Product 1</h4><p class="price">£99.99</p></div>
                    <div class="product-card"><div class="product-image"><div class="placeholder-img">NEW</div></div><h4>New Product 2</h4><p class="price">£149.99</p></div>
                    <div class="product-card"><div class="product-image"><div class="placeholder-img">NEW</div></div><h4>New Product 3</h4><p class="price">£199.99</p></div>
                    <div class="product-card"><div class="product-image"><div class="placeholder-img">NEW</div></div><h4>New Product 4</h4><p class="price">£249.99</p></div>
                    <div class="product-card"><div class="product-image"><div class="placeholder-img">NEW</div></div><h4>New Product 5</h4><p class="price">£299.99</p></div>
                    <div class="product-card"><div class="product-image"><div class="placeholder-img">NEW</div></div><h4>New Product 6</h4><p class="price">£349.99</p></div>
                    <div class="product-card"><div class="product-image"><div class="placeholder-img">NEW</div></div><h4>New Product 7</h4><p class="price">£399.99</p></div>
                    <div class="product-card"><div class="product-image"><div class="placeholder-img">NEW</div></div><h4>New Product 8</h4><p class="price">£449.99</p></div>
                </div>
            </div>

            <!-- WOMEN Dropdown -->
            <div class="dropdown-content" data-content="women">
                <div class="category-layout">
                    <div class="category-list">
                        <a href="products.php?gender=women&category=rings">Rings</a>
                        <a href="products.php?gender=women&category=bracelets">Bracelets</a>
                        <a href="products.php?gender=women&category=necklaces">Necklaces</a>
                        <a href="products.php?gender=women&category=bangles">Bangles/Cuffs</a>
                        <a href="products.php?gender=women&category=anklets">Anklets</a>
                        <a href="products.php?gender=women&category=earrings">Earrings</a>
                        <a href="products.php?gender=women&category=watches">Watches</a>
                    </div>
                    <div class="promo-banner">
                        <img src="assets/images/women-menu.jpg" alt="Women's Collection" class="promo-banner-img">
                        <div class="promo-banner-overlay">
                            <h2>Women's Collection</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- MEN Dropdown -->
            <div class="dropdown-content" data-content="men">
                <div class="category-layout">
                    <div class="category-list">
                        <a href="products.php?gender=men&category=rings">Rings</a>
                        <a href="products.php?gender=men&category=bracelets">Bracelets</a>
                        <a href="products.php?gender=men&category=necklaces">Necklaces</a>
                        <a href="products.php?gender=men&category=chains">Chains</a>
                        <a href="products.php?gender=men&category=watches">Watches</a>
                        <a href="products.php?gender=men&category=cufflinks">Cufflinks</a>
                    </div>
                    <div class="promo-banner">
                        <img src="assets/images/men-menu.jpg" alt="Men's Collection" class="promo-banner-img">
                        <div class="promo-banner-overlay">
                            <h2>Men's Collection</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- GIFT BOX Dropdown -->
            <div class="dropdown-content" data-content="gift">
                <div class="gift-layout">
                    <div class="gift-column">
                        <span class="disabled-link" aria-disabled="true">Gift Boxes — Coming soon</span>
                    </div>
                    <div class="gift-column">
                        <span class="disabled-link" aria-disabled="true">Gift Cards — Coming soon</span>
                    </div>
                    <div class="gift-column">
                        <span class="disabled-link" aria-disabled="true">Holiday Specials — Coming soon</span>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hamburger menu
        const hamburger = document.getElementById('hamburger');
        const nav = document.querySelector('.main-nav');
        
        if (hamburger && nav) {
            hamburger.addEventListener('click', function() {
                nav.classList.toggle('active');
                hamburger.classList.toggle('active');
            });
        }
        
        // Mobile close button
        const mobileClose = document.getElementById('mobile-close');
        if (mobileClose) {
            mobileClose.addEventListener('click', function() {
                nav.classList.remove('active');
                hamburger.classList.remove('active');
            });
        }
        
        // Search icon click handler
        const searchIcon = document.querySelector('.search-container i');
        const searchInput = document.querySelector('.search-container input');
        
        if (searchIcon && searchInput) {
            searchIcon.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    searchInput.style.display = searchInput.style.display === 'block' ? 'none' : 'block';
                    if (searchInput.style.display === 'block') {
                        searchInput.focus();
                    }
                }
            });
        }
        
        // Close mobile nav when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.main-nav') && !e.target.closest('#hamburger')) {
                nav.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
        
    });
    </script>