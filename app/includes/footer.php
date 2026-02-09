<?php ?>
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Accessories By Dija</h3>
                <p>Premium jewelry collection for the modern individual. Crafted with precision and designed for elegance.</p>
                <div class="contact-info">
                    <p><i class="fas fa-phone"></i> +44 7823794582</p>
                    <p><i class="fas fa-envelope"></i> hello@accessoriesbydija.uk</p>
                    <p><i class="fas fa-map-marker-alt"></i> 35 Clare Street, Northampton UK NN1 3JE</p>
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
                <form class="newsletter-form" id="newsletterForm">
                    <div class="newsletter-input-group">
                        <input type="email" name="email" placeholder="Your email address" required>
                        <button type="submit" class="btn btn-primary">Subscribe</button>
                    </div>
                    <div id="newsletterMessage" class="newsletter-message" style="display: none;"></div>
                </form>
                <div class="social-links">
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
                <div class="payment-icons">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                    <i class="fab fa-cc-apple-pay"></i>
                </div>
            </div>
        </div>
        
        
        <div class="footer-bottom">
            <div class="trust-badges">
                <div class="badge"><i class="fas fa-shield-alt"></i> Secure Payment</div>
                <div class="badge"><i class="fas fa-undo"></i> Easy Returns</div>
                <div class="badge"><i class="fas fa-certificate"></i> Lifetime Warranty</div>
                <div class="badge"><i class="fas fa-gem"></i> Premium Quality</div>
            </div>
            <div class="footer-legal">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms & Conditions</a>
                <a href="returns.php">Returns Policy</a>
            </div>
            <p>&copy; 2024 Dija Accessories. All rights reserved.</p>
        </div>
    </footer>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/cart-handler.js"></script>
    <script src="/assets/js/header.js"></script>
    <script src="/assets/js/hero.js"></script>
    <script src="/assets/js/category-section.js"></script>
    <script src="/assets/js/collection-banners.js"></script>
    <script src="/assets/js/currency.js"></script>
    <script src="/assets/js/featured-products.js"></script>
    <script src="/assets/js/custom-cta.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="/assets/js/testimonials.js"></script>
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

    <!-- Newsletter Success Modal -->
    <div id="newsletterSuccessModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <p id="newsletterSuccessMessage"></p>
        </div>
    </div>

    <!-- Cookie Consent Banner -->
    <div id="cookieConsent" class="cookie-consent" style="display: none;">
        <div class="cookie-content">
            <p>We use cookies to improve your experience. By continuing to use our site, you agree to our <a href="cookies.php">Cookie Policy</a> and <a href="privacy.php">Privacy Policy</a>.</p>
            <button id="acceptCookies" class="btn btn-primary">Accept</button>
            <button id="declineCookies" class="btn btn-secondary">Decline</button>
        </div>
    </div>

    <style>
    /* The Modal (background) */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1001; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    /* Modal Content/Box */
    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Could be more or less, depending on screen size */
        max-width: 500px;
        position: relative;
        border-radius: 8px;
    }

    /* The Close Button */
    .close-button {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        position: absolute;
        top: 10px;
        right: 20px;
    }

    .close-button:hover,
    .close-button:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    </style>
    
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

    /* Newsletter styles */
    .newsletter-form {
        margin-bottom: 1rem;
    }
    .newsletter-input-group {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .newsletter-input-group input {
        flex: 1;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .newsletter-input-group button {
        padding: 0.5rem 1rem;
        background: #C27BA0;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .newsletter-input-group button:hover {
        background: #a85d8a;
    }
    .newsletter-input-group button:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    .newsletter-message {
        padding: 0.5rem;
        border-radius: 4px;
        margin-top: 0.5rem;
        font-size: 0.9rem;
    }
    .newsletter-message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .newsletter-message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    @media (max-width: 768px) {
        .newsletter-input-group {
            flex-direction: column;
        }
        .newsletter-input-group button {
            width: 100%;
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

        // Newsletter subscription
        const newsletterForm = document.getElementById('newsletterForm');
        const newsletterMessage = document.getElementById('newsletterMessage');

        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const emailInput = this.querySelector('input[name="email"]');
                const submitButton = this.querySelector('button[type="submit"]');
                const email = emailInput.value.trim();

                if (!email) {
                    showNewsletterMessage('Please enter your email address.', 'error');
                    return;
                }

                // Disable form during submission
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
                        showNewsletterMessage('Thank you for subscribing! You will receive our latest updates.', 'success');
                        newsletterForm.reset();
                    } else {
                        showNewsletterMessage(data.message || 'Subscription failed. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Newsletter subscription error:', error);
                    showNewsletterMessage('Subscription failed. Please try again.', 'error');
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Subscribe';
                });
            });
        }

        function showNewsletterMessage(message, type) {
            const modal = document.getElementById('newsletterSuccessModal');
            const modalMessage = document.getElementById('newsletterSuccessMessage');
            const closeButton = modal.querySelector('.close-button');

            if (type === 'success') {
                if (modal && modalMessage && closeButton) {
                    modalMessage.textContent = message;
                    modal.style.display = 'block';

                    closeButton.onclick = function() {
                        modal.style.display = 'none';
                    }

                    window.onclick = function(event) {
                        if (event.target == modal) {
                            modal.style.display = 'none';
                        }
                    }
                }
            } else {
                if (newsletterMessage) {
                    newsletterMessage.textContent = message;
                    newsletterMessage.className = `newsletter-message ${type}`;
                    newsletterMessage.style.display = 'block';

                    // Hide message after 5 seconds
                    setTimeout(() => {
                        newsletterMessage.style.display = 'none';
                    }, 5000);
                }
            }
        }
    });
    </script>

    <!-- BEGIN JIVOSITE CODE -->
    <script type="text/javascript">
    (function(){var widget_id = 'YOUR_WIDGET_ID_HERE'; // Replace with your actual JivoChat widget ID
    var d=document;var w=window;function l(){
    var s = document.createElement('script'); s.type = 'text/javascript'; s.async = true;
    s.src = '//code.jivosite.com/script/widget/'+widget_id;
    var ss = document.getElementsByTagName('script')[0]; ss.parentNode.insertBefore(s, ss);}
    if(d.readyState=='complete'){l();}else{if(w.attachEvent){w.attachEvent('onload',l);}
    else{w.addEventListener('load',l,false);}}})();</script>
    <!-- END JIVOSITE CODE -->
</body>
</html>
