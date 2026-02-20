<?php
$page_title = "Shipping & Delivery";
$page_description = "Learn about our shipping policies, delivery times, and costs for jewelry orders from Accessories By Dija.";
include 'includes/header.php';
?>

<main>
    <section class="shipping-hero">
        <div class="container">
            <h1>Shipping & Delivery</h1>
            <p>Fast, reliable shipping worldwide with secure packaging for your precious jewelry.</p>
        </div>
    </section>

    <section class="shipping-content">
        <div class="container">
            <div class="shipping-grid">
                <div class="shipping-info">
                    <h2>Delivery Options</h2>

                    <div class="delivery-option">
                        <h3>Standard Shipping</h3>
                        <p>5-7 business days</p>
                        <p>£4.99</p>
                    </div>

                    <div class="delivery-option">
                        <h3>Express Shipping</h3>
                        <p>2-3 business days</p>
                        <p>£9.99</p>
                        <p>Available for UK orders</p>
                    </div>

                    <div class="delivery-option">
                        <h3>International Shipping</h3>
                        <p>7-14 business days</p>
                        <p>£12.99</p>
                    </div>
                </div>

                <div class="shipping-details">
                    <h2>Shipping Information</h2>

                    <div class="info-section">
                        <h3>Processing Time</h3>
                        <p>Orders are typically processed within 1-2 business days. Custom jewelry may take 2-3 weeks for creation.</p>
                    </div>

                    <div class="info-section">
                        <h3>Tracking</h3>
                        <p>You will receive a tracking number via email once your order ships. You can track your package on our website or the carrier's site.</p>
                    </div>

                    <div class="info-section">
                        <h3>International Orders</h3>
                        <p>We ship worldwide! International orders may be subject to customs duties and taxes, which are the responsibility of the recipient.</p>
                    </div>

                    <div class="info-section">
                        <h3>Secure Packaging</h3>
                        <p>All jewelry is carefully packaged in secure, tamper-evident boxes with protective materials to ensure safe delivery.</p>
                    </div>
                </div>
            </div>

            <div class="shipping-faq">
                <h2>Frequently Asked Questions</h2>

                <div class="faq-item">
                    <h3>When will my order ship?</h3>
                    <p>Most orders ship within 1-2 business days. You'll receive an email with tracking information once shipped.</p>
                </div>

                <div class="faq-item">
                    <h3>Do you ship internationally?</h3>
                    <p>Yes! We ship to most countries worldwide. Delivery times and costs vary by location.</p>
                </div>

                <div class="faq-item">
                    <h3>What if my package is damaged?</h3>
                    <p>Contact us immediately if you receive a damaged package. We'll arrange for a replacement or refund.</p>
                </div>

                <div class="faq-item">
                    <h3>Can I change my shipping address?</h3>
                    <p>Please contact us within 2 hours of placing your order to change the shipping address.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.shipping-hero {
    background: linear-gradient(135deg, #f8f8f8 0%, #e8e8e8 100%);
    padding: 4rem 0;
    text-align: center;
}

.shipping-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.shipping-hero p {
    font-size: 1.2rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.shipping-content {
    padding: 4rem 0;
}

.shipping-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    margin-bottom: 4rem;
}

.shipping-info h2,
.shipping-details h2,
.shipping-faq h2 {
    font-size: 1.8rem;
    margin-bottom: 2rem;
    color: #333;
}

.delivery-option {
    background: #f8f8f8;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid #C27BA0;
}

.delivery-option h3 {
    color: #333;
    margin-bottom: 0.5rem;
}

.delivery-option p {
    margin: 0.25rem 0;
    color: #666;
}

.info-section {
    margin-bottom: 2rem;
}

.info-section h3 {
    color: #333;
    margin-bottom: 0.5rem;
}

.info-section p {
    color: #666;
    line-height: 1.6;
}

.shipping-faq {
    border-top: 1px solid #eee;
    padding-top: 2rem;
}

.faq-item {
    margin-bottom: 1.5rem;
}

.faq-item h3 {
    color: #333;
    margin-bottom: 0.5rem;
}

.faq-item p {
    color: #666;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .shipping-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .shipping-hero h1 {
        font-size: 2rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>