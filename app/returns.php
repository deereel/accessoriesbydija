<?php
$page_title = "Returns & Refunds - Accessories by Dija";
$page_description = "Our returns and refunds policy for jewelry purchases. We offer a 48-hour return window for unused items in original condition.";
include 'includes/header.php';
?>

<main>
    <section class="returns-hero">
        <div class="container">
            <h1>Returns & Refunds</h1>
            <p>Shop with confidence. We offer a 48-hours return policy on most items.</p>
        </div>
    </section>

    <section class="returns-content">
        <div class="container">
            <div class="returns-grid">
                <div class="returns-policy">
                    <h2>Returns Policy</h2>

                    <div class="policy-item">
                        <h3>48 Hours Returns</h3>
                        <p>We accept returns within 48hrs of delivery. Returns must be processed within 2 days after you have received your item.</p>
                    </div>

                    <div class="policy-item">
                        <h3>Return Eligibility</h3>
                        <p>To qualify for a return, items must be unused, in their original packaging, in a resaleable and same condition which you received it. Proof or receipt of purchase will also be required.</p>
                    </div>

                    <div class="policy-item">
                        <h3>Return Process</h3>
                        <p>If your return is accepted, we’ll send you a return shipping label, as well as instructions on how and where to send your package. Items sent back to us without first requesting a return will not be accepted.</p>
                    </div>

                    <div class="policy-item">
                        <h3>Refunds</h3>
                        <p>Once your return is received and inspected, we’ll process your refund within 5–7 business days to your original payment method. Original shipping fees are non-refundable unless the item is faulty.</p>
                    </div>

                    <div class="policy-item">
                        <h3>Faulty or Damaged Items</h3>
                        <p>If you receive a faulty or damaged item, please contact us immediately or within 24hours of delivery. Include your order number and clear photos. We will offer a replacement or full refund promptly.</p>
                    </div>

                    <div class="policy-item">
                        <h3>Exchanges</h3>
                        <p>We do not offer direct exchanges. If you wish to exchange an item, please return the original and place a new order.</p>
                    </div>
                </div>

                <div class="returns-process">
                    <h2>How to Return</h2>

                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Contact Us</h3>
                            <p>Email us at accessoriesbydija@gmail.com or call +44 7823794582 with your order number.</p>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Pack Your Item</h3>
                            <p>Place the item in its original packaging with all tags attached. Include the return authorization form.</p>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Ship It Back</h3>
                            <p>Use the return label for UK customers, or ship to: Accessories By Dija, Returns Dept, London, UK.</p>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>Get Refunded</h3>
                            <p>Once received, we'll process your refund within 5-7 business days.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="returns-faq">
                <h2>Frequently Asked Questions</h2>

                <div class="faq-item">
                    <h3>What items cannot be returned?</h3>
                    <p>Earrings, custom jewelry, and items that have been worn, damaged, or altered cannot be returned.</p>
                </div>

                <div class="faq-item">
                    <h3>How long does a refund take?</h3>
                    <p>Refunds are processed within 5-7 business days after we receive your return. It may take additional time for your bank to process.</p>
                </div>

                <div class="faq-item">
                    <h3>Can I exchange for store credit?</h3>
                    <p>Yes, you can choose a refund to your original payment method or store credit for future purchases.</p>
                </div>

                <div class="faq-item">
                    <h3>What if my item arrives damaged?</h3>
                    <p>Contact us immediately with photos. We'll send a replacement or process a full refund.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.returns-hero {
    background: linear-gradient(135deg, #f8f8f8 0%, #e8e8e8 100%);
    padding: 4rem 0;
    text-align: center;
}

.returns-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.returns-hero p {
    font-size: 1.2rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.returns-content {
    padding: 4rem 0;
}

.returns-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    margin-bottom: 4rem;
}

.returns-policy h2,
.returns-process h2,
.returns-faq h2 {
    font-size: 1.8rem;
    margin-bottom: 2rem;
    color: #333;
}

.policy-item {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f8f8;
    border-radius: 8px;
}

.policy-item h3 {
    color: #333;
    margin-bottom: 0.5rem;
}

.policy-item p {
    color: #666;
    line-height: 1.6;
}

.step {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    align-items: flex-start;
}

.step-number {
    background: #C27BA0;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-content h3 {
    color: #333;
    margin-bottom: 0.5rem;
}

.step-content p {
    color: #666;
    line-height: 1.6;
}

.returns-faq {
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
    .returns-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .returns-hero h1 {
        font-size: 2rem;
    }

    .step {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
