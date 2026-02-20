<?php
// Include application initialization (includes session, logger, database, etc.)
require_once 'init.php';
require_once APP_PATH . '/config/security.php';
require_once APP_PATH . '/config/seo.php';
require_once APP_PATH . '/config/cache.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="no">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="no">
    <!-- Force mobile viewport on all mobile devices - prevents cached desktop view -->
    <script>
    (function() {
        // Check if device is mobile based on screen width
        var isMobile = window.innerWidth <= 1024;
        
        // If on mobile but viewport is not set to device-width, fix it
        if (isMobile) {
            var viewport = document.querySelector('meta[name="viewport"]');
            if (viewport) {
                viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover');
            }
        }
        
        // Listen for resize events to ensure viewport stays correct
        window.addEventListener('resize', function() {
            var viewport = document.querySelector('meta[name="viewport"]');
            if (window.innerWidth <= 1024) {
                if (viewport) {
                    viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover');
                }
            }
        });
    })();
    </script>
    <style>
    /* Mobile viewport enforcement - ensures mobile styles apply regardless of cached CSS */
    @media screen and (max-width: 1024px) {
        html {
            font-size: 14px !important;
        }
        
        body {
            width: 100% !important;
            max-width: 100vw !important;
            overflow-x: hidden !important;
        }
        
        .container, .featured-grid-4x2, .featured-grid {
            width: 100% !important;
            max-width: 100vw !important;
            padding-left: 15px !important;
            padding-right: 15px !important;
        }
        
        .header-top {
            padding: 10px 15px !important;
        }
        
        .main-nav {
            padding: 0 15px !important;
        }
        
        /* Force hamburger menu visibility on mobile */
        .hamburger {
            display: flex !important;
        }
        
        /* Hide desktop nav items on mobile */
        .nav-menu {
            display: none !important;
        }
    }
    
    /* Small mobile devices */
    @media screen and (max-width: 480px) {
        html {
            font-size: 13px !important;
        }
        
        .featured-grid-4x2 {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
    </style>
    <?php
    // Prepare page data for SEO functions
    $base_url = isset($BASE_URL) ? $BASE_URL : 'https://' . $_SERVER['HTTP_HOST'];
    $page_data = [
        'title' => (isset($page_title) ? $page_title . ' - ' : '') . 'Accessories By Dija - Premium Jewelry',
        'description' => isset($page_description) ? $page_description : 'Discover premium jewelry collection at Accessories By Dija. Rings, necklaces, earrings, bracelets, and custom jewelry for men and women.',
        'keywords' => isset($page_keywords) ? $page_keywords : 'jewelry, rings, necklaces, earrings, bracelets, accessories',
        'canonical' => isset($canonical_url) ? $canonical_url : $base_url . $_SERVER['REQUEST_URI'],
        'og:type' => isset($og_type) ? $og_type : 'website',
        'og:image' => isset($og_image) ? $og_image : $base_url . '/assets/images/logo.webp',
        'twitter:card' => isset($twitter_card) ? $twitter_card : 'summary_large_image'
    ];

    $meta_tags = generateMetaTags($page_data);
    $og_tags = generateOpenGraphTags($page_data);
    $twitter_tags = generateTwitterTags($page_data);
    ?>

    <title><?php echo $meta_tags['title']; ?></title>
    <meta name="description" content="<?php echo $meta_tags['description']; ?>">
    <meta name="keywords" content="<?php echo $meta_tags['keywords']; ?>">
    <meta name="robots" content="<?php echo $meta_tags['robots']; ?>">
    <link rel="canonical" href="<?php echo $meta_tags['canonical']; ?>">
    <link rel="icon" href="/favicon.ico" sizes="16x16 32x32 48x48" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <link rel="manifest" href="/app/manifest.json">
    <meta name="theme-color" content="#C27BA0">
    <meta name="author" content="Accessories By Dija">

    <!-- Open Graph / Facebook -->
    <?php foreach ($og_tags as $property => $content): ?>
    <meta property="<?php echo $property; ?>" content="<?php echo $content; ?>">
    <?php endforeach; ?>

    <!-- Twitter -->
    <?php foreach ($twitter_tags as $name => $content): ?>
    <meta name="twitter:<?php echo $name; ?>" content="<?php echo $content; ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/hero.css">
    <link rel="stylesheet" href="/assets/css/category-section.css">
    <link rel="stylesheet" href="/assets/css/collection-banners.css">
    <link rel="stylesheet" href="/assets/css/featured-products.css">
    <link rel="stylesheet" href="/assets/css/custom-cta.css">
    <link rel="stylesheet" href="/assets/css/gift-guide.css">
    <link rel="stylesheet" href="/assets/css/shop-by-price.css">
    <link rel="stylesheet" href="/assets/css/testimonials.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
    <link rel="stylesheet" href="/assets/css/product-cards.css">
    <link rel="stylesheet" href="/assets/css/megamenu.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/assets/css/all.min.css" rel="stylesheet">
    <script src="/assets/js/header.js" defer></script>
    <script src="/assets/js/new-nav.js" defer></script>
    <script src="/assets/js/hero.js" defer></script>
    <script src="/assets/js/cart-handler.js"></script>
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
    /* Mobile-first responsive fixes */
    html {
        -webkit-text-size-adjust: 100%;
        -ms-text-size-adjust: 100%;
        font-size: 16px;
    }
    
    body {
        overflow-x: hidden;
        max-width: 100vw;
        min-width: 320px;
    }
    
    /* Force mobile viewport behavior on all mobile devices */
    @media screen and (max-width: 1024px) {
        html {
            font-size: 14px;
        }
        
        * {
            max-width: 100%;
        }
        
        .container, .featured-grid-4x2, .featured-grid {
            width: 100% !important;
            max-width: 100vw !important;
            padding-left: 15px !important;
            padding-right: 15px !important;
        }
        
        .header-top {
            padding: 10px 15px !important;
        }
        
        .main-nav {
            padding: 0 15px !important;
        }
    }
    
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
    
    /* Search Suggestions */
    .search-suggestions {
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 1000;
    }
    
    .suggestions-header {
        padding: 0.75rem 1rem;
        background: #f8f8f8;
        border-bottom: 1px solid #eee;
        font-size: 0.9rem;
        color: #666;
        font-weight: 600;
    }
    
    .suggestion-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f0f0f0;
        text-decoration: none;
        color: inherit;
        transition: background 0.2s;
    }
    
    .suggestion-item:hover {
        background: #f8f8f8;
    }
    
    .suggestion-item:last-child {
        border-bottom: none;
    }
    
    .suggestion-image {
        width: 50px;
        height: 50px;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .suggestion-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .no-image {
        width: 100%;
        height: 100%;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-size: 1.5rem;
    }
    
    .suggestion-info {
        flex: 1;
    }
    
    .suggestion-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
    }
    
    .suggestion-price {
        color: #C27BA0;
        font-weight: 600;
    }
    
    .suggestions-footer {
        padding: 0.75rem 1rem;
        background: #f8f8f8;
        border-top: 1px solid #eee;
        text-align: center;
    }
    
    .suggestions-footer a {
        color: #C27BA0;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .suggestions-footer a:hover {
        text-decoration: underline;
    }
    
    .no-suggestions {
        padding: 1rem;
        text-align: center;
        color: #666;
        font-style: italic;
    }
    
    /* Search Modal for Mobile */
    .search-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        justify-content: center;
        align-items: flex-start;
        padding-top: 20vh;
    }
    
    .search-modal.active {
        display: flex;
    }
    
    .search-modal-content {
        background: white;
        width: 90%;
        max-width: 500px;
        border-radius: 12px;
        padding: 20px;
        position: relative;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        animation: searchModalSlide 0.3s ease;
    }
    
    @keyframes searchModalSlide {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .search-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .search-modal-header h3 {
        margin: 0;
        font-size: 1.2rem;
        color: #333;
    }
    
    .search-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
        padding: 5px;
        line-height: 1;
    }
    
    .search-modal-close:hover {
        color: #333;
    }
    
    .search-modal-input-wrapper {
        position: relative;
    }
    
    .search-modal-input {
        width: 100%;
        padding: 15px 50px 15px 20px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
        outline: none;
        transition: border-color 0.3s;
    }
    
    .search-modal-input:focus {
        border-color: #C27BA0;
    }
    
    .search-modal-search-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        font-size: 1.3rem;
        cursor: pointer;
        color: #C27BA0;
        padding: 5px 10px;
    }
    
    .search-modal-search-btn:hover {
        color: #a66889;
    }
    
    .search-modal-suggestions {
        margin-top: 15px;
        max-height: 300px;
        overflow-y: auto;
    }
    
    @media (max-width: 768px) {
        /* Hide desktop search box on mobile */
        .search-container {
            display: none !important;
        }
        
        /* Mobile Search Icon - only show on mobile */
        .mobile-search-icon {
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.3rem;
            color: #333;
            padding: 0;
            margin-right: 10px;
            transition: color 0.3s;
        }
        
        .mobile-search-icon:hover {
            color: #C27BA0;
        }
    }
    
    /* Desktop styles - hide mobile search icon by default */
    @media (min-width: 769px) {
        .mobile-search-icon {
            display: none !important;
        }
    }
    </style>
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">
    <header class="header">
        <div class="header-top">
            <div class="search-container">
                <input type="text" placeholder="Type to start searching…" id="search-input">
                <span class="search-icon" id="search-icon-trigger">&#128269;</span>
            </div>
            <div class="logo">
                <a href="index.php">
                    <img src="/assets/images/logo.webp" alt="Accessories By Dija" />
                </a>
            </div>
            <div class="header-icons">
                <a href="account.php" class="icon-link">
                    &#128100;
                </a>
                <a href="cart.php" class="icon-link cart-icon">
                    &#128722;
                    <span class="cart-count">0</span>
                </a>
                <!-- Mobile Search Icon -->
                <button class="mobile-search-icon" id="mobile-search-icon" aria-label="Search">
                    &#128269;
                </button>
                <div class="hamburger" id="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
        <nav class="main-nav">
            <div class="mobile-close" id="mobile-close">
                &#10005;
            </div>
            <div class="nav-item">
                <a href="products.php?new=1">NEW</a>
            </div>
            <div class="nav-item" data-menu="shop">
                <a href="products.php">SHOP</a>
            </div>
            <div class="nav-item" data-menu="women">
                <a href="products.php?gender=women">WOMEN &#9660;</a>
            </div>
            <div class="nav-item" data-menu="men">
                <a href="products.php?gender=men">MEN &#9660;</a>
            </div>
            <div class="nav-item" data-menu="gift">
                <a href="gift-box.php">GIFT BOX &#9660;</a>
            </div>
            <div class="nav-item">
                <a href="about.php">ABOUT</a>
            </div>
            <div class="nav-item">
                <a href="custom-jewelry.php">CUSTOMIZE</a>
            </div>
        </nav>
        
        <!-- Mega Menu Dropdowns -->
        <div class="mega-menu" id="mega-menu">

            <!-- WOMEN Dropdown -->
            <div class="dropdown-content" data-content="women">
                <div class="category-layout">
                    <div class="category-list-scroll">
                        <a href="products.php?gender=women&category=anklets">Anklets</a>
                        <a href="products.php?gender=women&category=bangles">Bangles</a>
                        <a href="products.php?gender=women&category=bracelets">Bracelets</a>
                        <a href="products.php?gender=women&category=earrings">Earrings</a>
                        <a href="products.php?gender=women&category=necklaces">Necklaces</a>
                        <a href="products.php?gender=women&category=pendants">Pendants</a>
                        <a href="products.php?gender=women&category=rings">Rings</a>
                        <a href="products.php?gender=women&category=sets">Sets</a>
                        <a href="products.php?gender=women&category=studs">Studs</a>
                        <a href="products.php?gender=women&category=watches">Watches</a>
                        <a href="custom-jewelry.php">Custom Jewelry</a>
                    </div>
                    <div class="promo-banner-fixed">
                        <img src="/assets/images/women-menu.jpg" alt="Women's Collection" class="promo-banner-img">
                        <div class="promo-banner-overlay">
                            <h2>Women's Collection</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- MEN Dropdown -->
            <div class="dropdown-content" data-content="men">
                <div class="category-layout">
                    <div class="category-list-scroll">
                        <a href="products.php?gender=men&category=bracelets">Bracelets</a>
                        <a href="products.php?gender=men&category=cufflinks">Cufflinks</a>
                        <a href="products.php?gender=men&category=Hand Chains">Hand Chains</a>
                        <a href="products.php?gender=men&category=lapels-clips">Lapels/Clips</a>
                        <a href="products.php?gender=men&category=necklaces">Necklaces</a>
                        <a href="products.php?gender=men&category=pendants">Pendants</a>
                        <a href="products.php?gender=men&category=rings">Rings</a>
                        <a href="products.php?gender=men&category=sets">Sets</a>
                        <a href="products.php?gender=men&category=studs">Studs</a>
                        <a href="products.php?gender=men&category=watches">Watches</a>
                        <a href="custom-jewelry.php">Custom Jewelry</a>
                    </div>
                    <div class="promo-banner-fixed">
                        <img src="/assets/images/men-menu.jpg" alt="Men's Collection" class="promo-banner-img">
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
    
    <!-- Search Modal for Mobile -->
    <div class="search-modal" id="search-modal">
        <div class="search-modal-content">
            <div class="search-modal-header">
                <h3>Search</h3>
                <button class="search-modal-close" id="search-modal-close">&times;</button>
            </div>
            <div class="search-modal-input-wrapper">
                <input type="text" class="search-modal-input" id="search-modal-input" placeholder="Type to search...">
                <button class="search-modal-search-btn" id="search-modal-search-btn">&#128269;</button>
            </div>
            <div class="search-modal-suggestions" id="search-modal-suggestions"></div>
        </div>
    </div>
    
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
        
        // Search modal functionality for mobile
        const searchModal = document.getElementById('search-modal');
        const searchModalInput = document.getElementById('search-modal-input');
        const searchModalClose = document.getElementById('search-modal-close');
        const searchModalSearchBtn = document.getElementById('search-modal-search-btn');
        const searchModalSuggestions = document.getElementById('search-modal-suggestions');
        const mobileSearchIcon = document.getElementById('mobile-search-icon');
        
        // Open search modal from header search icon
        const searchIconTrigger = document.getElementById('search-icon-trigger');
        if (searchIconTrigger) {
            searchIconTrigger.addEventListener('click', function() {
                searchModal.classList.add('active');
                searchModalInput.focus();
            });
        }
        
        // Open search modal from mobile search icon
        if (mobileSearchIcon) {
            mobileSearchIcon.addEventListener('click', function() {
                searchModal.classList.add('active');
                searchModalInput.focus();
            });
        }
        
        // Close modal
        if (searchModalClose) {
            searchModalClose.addEventListener('click', function() {
                searchModal.classList.remove('active');
            });
        }
        
        // Close when clicking outside
        if (searchModal) {
            searchModal.addEventListener('click', function(e) {
                if (e.target === searchModal) {
                    searchModal.classList.remove('active');
                }
            });
        }
        
        // Search on button click
        if (searchModalSearchBtn) {
            searchModalSearchBtn.addEventListener('click', function() {
                performSearch(searchModalInput.value);
            });
        }
        
        // Search on Enter key
        if (searchModalInput) {
            searchModalInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch(searchModalInput.value);
                }
            });
        }
        
        function performSearch(query) {
            query = query.trim();
            if (query.length > 0) {
                window.location.href = '/app/search.php?q=' + encodeURIComponent(query);
            }
        }
        
        // Search suggestions for modal
        if (searchModalInput) {
            let searchTimeout;
            searchModalInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    searchModalSuggestions.innerHTML = '';
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    fetchSearchSuggestions(query);
                }, 300);
            });
        }
        
        function fetchSearchSuggestions(query) {
            fetch('/api/search.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data && data.products && data.products.length > 0) {
                        let html = '<div class="suggestions-header">Suggestions</div>';
                        data.products.slice(0, 5).forEach(item => {
                            html += '<a href="' + item.url + '" class="suggestion-item">';
                            if (item.image_url) {
                                html += '<div class="suggestion-image"><img src="' + item.image_url + '" alt="' + item.name + '"></div>';
                            } else {
                                html += '<div class="suggestion-image no-image">' + item.name.substring(0, 2).toUpperCase() + '</div>';
                            }
                            html += '<div class="suggestion-info">';
                            html += '<div class="suggestion-name">' + item.name + '</div>';
                            if (item.price) {
                                html += '<div class="suggestion-price">£' + item.price.toFixed(2) + '</div>';
                            }
                            html += '</div></a>';
                        });
                        searchModalSuggestions.innerHTML = html;
                    } else {
                        searchModalSuggestions.innerHTML = '<div class="no-suggestions">No products found</div>';
                    }
                })
                .catch(() => {
                    searchModalSuggestions.innerHTML = '<div class="no-suggestions">Error loading suggestions</div>';
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
    
    <?php if (isset($_SESSION['show_newsletter_popup'])): ?>
    <div id="newsletterPopupModal" class="modal" style="display: block;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h4>Stay Connected</h4>
            <p>Subscribe for exclusive offers and new arrivals</p>
            <form class="newsletter-form" id="popupNewsletterForm">
                <div class="newsletter-input-group">
                    <input type="email" name="email" placeholder="Your email address" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </div>
                <div id="popupNewsletterMessage" class="newsletter-message" style="display: none;"></div>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('newsletterPopupModal');
        const closeButton = modal.querySelector('.close-button');
        const newsletterForm = document.getElementById('popupNewsletterForm');
        const newsletterMessage = document.getElementById('popupNewsletterMessage');

        closeButton.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = this.querySelector('input[name="email"]');
            const submitButton = this.querySelector('button[type="submit"]');
            const email = emailInput.value.trim();

            if (!email) {
                newsletterMessage.textContent = 'Please enter your email address.';
                newsletterMessage.className = 'newsletter-message error';
                newsletterMessage.style.display = 'block';
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Subscribing...';

            const formData = new FormData();
            formData.append('email', email);

            fetch('/api/newsletter.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    newsletterMessage.textContent = 'Thank you for subscribing!';
                    newsletterMessage.className = 'newsletter-message success';
                    newsletterMessage.style.display = 'block';
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 2000);
                } else {
                    newsletterMessage.textContent = data.message || 'Subscription failed. Please try again.';
                    newsletterMessage.className = 'newsletter-message error';
                    newsletterMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Newsletter subscription error:', error);
                newsletterMessage.textContent = 'Subscription failed. Please try again.';
                newsletterMessage.className = 'newsletter-message error';
                newsletterMessage.style.display = 'block';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Subscribe';
            });
        });
    });
    </script>
    <?php unset($_SESSION['show_newsletter_popup']); ?>
    <?php endif; ?>

    <!-- Service Worker Registration for PWA -->
    <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', async () => {
        if (window.location.pathname.startsWith('/admin/')) {
          return;
        }

        try {
          // Force clear all caches on every page load
          const cacheNames = await caches.keys();
          await Promise.all(cacheNames.map(name => caches.delete(name)));
          
          // Unregister old service workers
          const registrations = await navigator.serviceWorker.getRegistrations();
          await Promise.all(registrations.map(reg => reg.unregister()));
          
          // Register fresh service worker
          const registration = await navigator.serviceWorker.register('/app/sw.js', { scope: '/app/' });
          await registration.update();
          console.log('Service Worker registered with fresh cache');
        } catch (error) {
          console.error('Service Worker error:', error);
        }
      });
    }
    </script>
    

</html>
