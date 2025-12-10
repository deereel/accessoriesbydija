<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Dija Accessories - Premium Jewelry for Men & Women</title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Discover premium jewelry collection at Dija Accessories. Rings, necklaces, earrings, bracelets, and custom jewelry for men and women.'; ?>">
    <meta name="keywords" content="jewelry, rings, necklaces, earrings, bracelets, bangles, anklets, custom jewelry, men jewelry, women jewelry">
    <link rel="canonical" href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Dija Accessories">
    <meta property="og:description" content="<?php echo isset($page_description) ? $page_description : 'Premium jewelry collection for men and women'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Dija Accessories",
        "description": "Premium jewelry retailer specializing in rings, necklaces, earrings, bracelets, and custom jewelry for men and women",
        "url": "https://accessoriesbydija.com",
        "logo": "https://accessoriesbydija.com/assets/images/logo.png",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+1-555-0123",
            "contactType": "customer service"
        },
        "sameAs": [
            "https://facebook.com/dijaccessories",
            "https://instagram.com/dijaccessories"
        ]
    }
    </script>
</head>
<body itemscope itemtype="https://schema.org/WebPage">
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="index.php">
                        <h1>Dija Accessories</h1>
                    </a>
                </div>
                <div class="nav-menu" id="nav-menu">
                    <a href="index.php" class="nav-link">Home</a>
                    <div class="dropdown">
                        <a href="category.php?cat=women" class="nav-link">Women <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-content">
                            <a href="category.php?cat=women&sub=rings">Rings</a>
                            <a href="category.php?cat=women&sub=necklaces">Necklaces</a>
                            <a href="category.php?cat=women&sub=earrings">Earrings</a>
                            <a href="category.php?cat=women&sub=bracelets">Bracelets</a>
                            <a href="category.php?cat=women&sub=bangles">Bangles</a>
                            <a href="category.php?cat=women&sub=anklets">Anklets</a>
                            <a href="category.php?cat=women&sub=sets">Jewelry Sets</a>
                        </div>
                    </div>
                    <div class="dropdown">
                        <a href="category.php?cat=men" class="nav-link">Men <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-content">
                            <a href="category.php?cat=men&sub=rings">Rings</a>
                            <a href="category.php?cat=men&sub=necklaces">Necklaces</a>
                            <a href="category.php?cat=men&sub=bracelets">Bracelets</a>
                            <a href="category.php?cat=men&sub=chains">Chains</a>
                        </div>
                    </div>
                    <a href="custom-jewelry.php" class="nav-link">Custom Jewelry</a>
                    <a href="about.php" class="nav-link">About</a>
                    <a href="contact.php" class="nav-link">Contact</a>
                </div>
                <div class="nav-icons">
                    <div class="search-box">
                        <input type="text" placeholder="Search jewelry..." id="search-input">
                        <i class="fas fa-search"></i>
                    </div>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </a>
                    <div class="hamburger" id="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </nav>
    </header>