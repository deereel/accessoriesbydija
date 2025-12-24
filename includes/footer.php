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
    <script src="assets/js/testimonials.js"></script>
    <script>
    // Currency conversion for price ranges
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof updatePrices === 'function') {
            updatePrices();
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
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
</body>
</html>
