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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
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
                <div class="currency-selector">
                    <button class="currency-btn" id="currency-btn">
                        <span id="current-currency">GBP</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="currency-dropdown" id="currency-dropdown">
                        <div class="currency-option" data-currency="GBP">£ GBP</div>
                        <div class="currency-option" data-currency="USD">$ USD</div>
                        <div class="currency-option" data-currency="EUR">€ EUR</div>
                        <div class="currency-option" data-currency="CNY">¥ CNY</div>
                        <div class="currency-option" data-currency="NGN">₦ NGN</div>
                    </div>
                </div>
                <div class="hamburger" id="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
        <nav class="main-nav">
            <div class="nav-item" data-menu="new">
                <a href="new-arrivals.php">NEW <i class="fas fa-chevron-down"></i></a>
            </div>
            <div class="nav-item" data-menu="women">
                <a href="category.php?cat=women">WOMEN <i class="fas fa-chevron-down"></i></a>
            </div>
            <div class="nav-item" data-menu="men">
                <a href="category.php?cat=men">MEN <i class="fas fa-chevron-down"></i></a>
            </div>
            <div class="nav-item" data-menu="exclusives">
                <a href="exclusives.php">EXCLUSIVES <i class="fas fa-chevron-down"></i></a>
            </div>
            <div class="nav-item" data-menu="gift">
                <a href="gift-box.php">GIFT BOX <i class="fas fa-chevron-down"></i></a>
            </div>
        </nav>
        
        <!-- Mega Menu Dropdowns -->
        <div class="mega-menu" id="mega-menu">
            <!-- NEW Dropdown -->
            <div class="dropdown-content" data-content="new">
                <div class="new-products-grid">
                    <?php for($i = 1; $i <= 9; $i++): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <div class="placeholder-img">Product <?php echo $i; ?></div>
                        </div>
                        <h4>New Product <?php echo $i; ?></h4>
                        <p class="price">£<?php echo rand(50, 500); ?></p>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- WOMEN Dropdown -->
            <div class="dropdown-content" data-content="women">
                <div class="category-layout">
                    <div class="category-list">
                        <a href="category.php?cat=women&sub=rings">Rings</a>
                        <a href="category.php?cat=women&sub=bracelets">Bracelets</a>
                        <a href="category.php?cat=women&sub=necklaces">Necklaces</a>
                        <a href="category.php?cat=women&sub=bangles">Bangles/Cuffs</a>
                        <a href="category.php?cat=women&sub=anklets">Anklets</a>
                        <a href="category.php?cat=women&sub=earrings">Earrings</a>
                    </div>
                    <div class="promo-banner">
                        <div class="placeholder-banner">Women's Collection</div>
                    </div>
                </div>
            </div>
            
            <!-- MEN Dropdown -->
            <div class="dropdown-content" data-content="men">
                <div class="category-layout">
                    <div class="category-list">
                        <a href="category.php?cat=men&sub=rings">Rings</a>
                        <a href="category.php?cat=men&sub=bracelets">Bracelets</a>
                        <a href="category.php?cat=men&sub=necklaces">Necklaces</a>
                        <a href="category.php?cat=men&sub=chains">Chains</a>
                        <a href="category.php?cat=men&sub=watches">Watches</a>
                        <a href="category.php?cat=men&sub=cufflinks">Cufflinks</a>
                    </div>
                    <div class="promo-banner">
                        <div class="placeholder-banner">Men's Collection</div>
                    </div>
                </div>
            </div>
            
            <!-- EXCLUSIVES Dropdown -->
            <div class="dropdown-content" data-content="exclusives">
                <div class="exclusives-layout">
                    <div class="exclusives-links">
                        <a href="luxury.php">Luxury Pieces</a>
                        <a href="limited.php">Limited Editions</a>
                        <a href="custom-jewelry.php">Customized Jewelry</a>
                    </div>
                    <div class="promo-banner">
                        <div class="placeholder-banner">Exclusive Collection</div>
                    </div>
                </div>
            </div>
            
            <!-- GIFT BOX Dropdown -->
            <div class="dropdown-content" data-content="gift">
                <div class="gift-layout">
                    <div class="gift-column">
                        <a href="gift-boxes.php">Gift Boxes</a>
                    </div>
                    <div class="gift-column">
                        <a href="gift-cards.php">Gift Cards</a>
                    </div>
                    <div class="gift-column">
                        <a href="holiday.php">Holiday Specials</a>
                    </div>
                </div>
            </div>
        </div>
    </header>