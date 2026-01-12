<?php ?>
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Dija Accessories</h3>
                <p>Premium jewelry collection for the modern individual. Crafted with precision and designed for elegance.</p>
                <div class="contact-info">
                    <p><i class="fas fa-phone"></i> +44 20 7946 0958</p>
                    <p><i class="fas fa-envelope"></i> hello@dijaccessories.com</p>
                    <p><i class="fas fa-map-marker-alt"></i> London, United Kingdom</p>
                </div>
            </div>
            <div class="footer-section">
                <h4>Shop</h4>
                <ul>
                    <li><a href="products.php?gender=women">Women's Jewelry</a></li>
                    <li><a href="products.php?gender=men">Men's Jewelry</a></li>
                    <li><a href="products.php?featured=1">Featured Products</a></li>
                    <li><a href="gift-guide.php">Gift Guide</a></li>
                    <li><a href="custom-jewelry.php">Custom Design</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Customer Care</h4>
                <ul>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="shipping.php">Shipping & Delivery</a></li>
                    <li><a href="returns.php">Returns & Exchanges</a></li>
                    <li><a href="size-guide.php">Size Guide</a></li>
                    <li><a href="care-guide.php">Jewelry Care</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Stay Connected</h4>
                <p>Subscribe for exclusive offers and new arrivals</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Your email address" required>
                    <button type="submit">Subscribe</button>
                </form>
                <div class="social-links">
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-middle">
            <div class="trust-badges">
                <div class="badge"><i class="fas fa-shield-alt"></i> Secure Payment</div>
                <div class="badge"><i class="fas fa-undo"></i> Easy Returns</div>
                <div class="badge"><i class="fas fa-certificate"></i> Lifetime Warranty</div>
                <div class="badge"><i class="fas fa-gem"></i> Premium Quality</div>
            </div>
            <div class="payment-icons">
                <i class="fab fa-cc-visa"></i>
                <i class="fab fa-cc-mastercard"></i>
                <i class="fab fa-cc-paypal"></i>
                <i class="fab fa-cc-apple-pay"></i>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-legal">
                <a href="privacy-policy.php">Privacy Policy</a>
                <a href="terms-conditions.php">Terms & Conditions</a>
                <a href="returns.php">Returns Policy</a>
            </div>
            <p>&copy; 2024 Dija Accessories. All rights reserved.</p>
        </div>
    </footer>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/cart-handler.js"></script>
    <script src="assets/js/header.js"></script>
    <script src="assets/js/hero.js"></script>
    <script src="assets/js/category-section.js"></script>
    <script src="assets/js/collection-banners.js"></script>
    <script src="assets/js/currency.js"></script>
    <script src="assets/js/featured-products.js"></script>
    <script src="assets/js/custom-cta.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="assets/js/testimonials.js"></script>
    <script>
    // Currency conversion for price ranges
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof updatePrices === 'function') {
            updatePrices();
        }
    });
    </script>
<button class="scroll-top" id="scrollTopBtn" aria-label="Scroll to top">↑</button>
    <script>
    (function(){
        const btn = document.getElementById('scrollTopBtn');
        if (!btn) return;
        // Show after user scrolls down 300px
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) btn.classList.add('show'); else btn.classList.remove('show');
        });
        // Smooth scroll to top
        btn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    })();
    </script>

    <!-- Cookie Consent Banner -->
    <div id="cookieConsent" class="cookie-consent" style="display: none;">
        <div class="cookie-content">
            <p>We use cookies to improve your experience. By continuing to use our site, you agree to our <a href="cookies.php">Cookie Policy</a> and <a href="privacy.php">Privacy Policy</a>.</p>
            <button id="acceptCookies" class="btn btn-primary">Accept</button>
            <button id="declineCookies" class="btn btn-secondary">Decline</button>
        </div>
    </div>

    <style>
    .cookie-consent {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #333;
        color: white;
        padding: 1rem;
        z-index: 1000;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }
    .cookie-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .cookie-content p {
        margin: 0;
        flex: 1;
    }
    .cookie-content a {
        color: #C27BA0;
    }
    @media (max-width: 768px) {
        .cookie-content {
            flex-direction: column;
            text-align: center;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const consent = localStorage.getItem('cookieConsent');
        if (!consent) {
            document.getElementById('cookieConsent').style.display = 'block';
        }

        document.getElementById('acceptCookies').addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'accepted');
            document.getElementById('cookieConsent').style.display = 'none';
        });

        document.getElementById('declineCookies').addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'declined');
            document.getElementById('cookieConsent').style.display = 'none';
        });
    });
    </script>
</body>
</html>
