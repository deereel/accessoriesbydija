<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/security.php';
require_once 'config/seo.php';
require_once 'config/cache.php';
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="assets/images/logo.webp">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#C27BA0">

    <!-- Open Graph / Facebook -->
    <?php foreach ($og_tags as $property => $content): ?>
    <meta property="<?php echo $property; ?>" content="<?php echo $content; ?>">
    <?php endforeach; ?>

    <!-- Twitter -->
    <?php foreach ($twitter_tags as $name => $content): ?>
    <meta name="twitter:<?php echo $name; ?>" content="<?php echo $content; ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
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
    <link href="assets/css/all.min.css" rel="stylesheet">
    <script src="assets/js/header.js" defer></script>
    <script src="assets/js/new-nav.js" defer></script>
    <script src="assets/js/cart-handler.js"></script>
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
    </style>
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">
    <header class="header">
        <div class="header-top">
            <div class="search-container">
                <input type="text" placeholder="Type to start searching…" id="search-input">
                <span class="search-icon">&#128269;</span>
            </div>
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/logo.webp" alt="Accessories By Dija" />
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
            <div class="nav-item" data-menu="new">
                <a href="new-arrivals.php">NEW &#9660;</a>
            </div>
            <div class="nav-item" data-menu="shop">
                <a href="products.php">SHOP;</a>
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
        const searchIcon = document.querySelector('.search-container .search-icon');
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
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
          .then(registration => {
            console.log('Service Worker registered successfully:', registration);
          })
          .catch(error => {
            console.log('Service Worker registration failed:', error);
          });
      });
    }
    </script>
</body>
</html>
