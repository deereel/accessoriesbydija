<?php
$page_title = "Frequently Asked Questions";
$page_description = "Find answers to common questions about Accessories By Dija jewelry, shipping, returns, custom designs, and more.";
include 'includes/header.php';

// Get materials from database
require_once 'config/database.php';
$materials = [];
$adornments = [];

try {
    $stmt = $pdo->query("SELECT name FROM materials ORDER BY name");
    $materials = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT DISTINCT adornment FROM product_variations WHERE adornment IS NOT NULL AND adornment != '' ORDER BY adornment");
    $adornments = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Fallback if database query fails
    $materials = ['Gold', 'Silver', 'Platinum'];
    $adornments = ['Diamond', 'Ruby', 'Sapphire', 'Emerald'];
}
?>

<main>
    <section class="faq-hero">
        <div class="container">
            <h1>Frequently Asked Questions</h1>
            <p>Find answers to the most common questions about Accessories By Dija jewelry, services, and policies.</p>
        </div>
    </section>

    <section class="faq-content" itemscope itemtype="https://schema.org/FAQPage">
        <div class="container">
            <div class="faq-grid">
                <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">What materials do you use in your jewelry?</h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">We use premium materials including: <?php echo htmlspecialchars(implode(', ', $materials)); ?>. We also offer various gemstone options such as: <?php echo htmlspecialchars(implode(', ', $adornments)); ?>. All our pieces are hypoallergenic and nickel-free.</p>
                    </div>
                </div>

                <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Do you offer custom jewelry design?</h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Yes! We specialize in custom jewelry design. Our expert designers work with you to create unique pieces including engagement rings, wedding bands, and personalized gifts. The process includes consultation, 3D design preview, and expert craftsmanship.</p>
                    </div>
                </div>

                <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">What is your return policy?</h3>
                     <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">We accept returns within 48 hours of delivery. Items must be unused, in their original packaging, and in resalable condition. Proof of purchase is required.</p>
                    </div>
                </div>

                <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">How long does shipping take?</h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Standard shipping takes 3-5 business days. Express shipping (1-2 days) is available. Custom pieces typically take 2-4 weeks depending on complexity. All orders include tracking and insurance.</p>
                    </div>
                </div>

                <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Do you offer warranty on your jewelry?</h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Yes, we provide up to 6 months warranty against manufacturing defects.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.faq-hero {
    background: linear-gradient(135deg, #f8f8f8 0%, #fff 100%);
    padding: 4rem 0;
    margin-top: 80px;
    text-align: center;
}

.faq-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.faq-content {
    padding: 4rem 0;
}

.faq-grid {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    background: #f8f8f8;
    margin-bottom: 1rem;
    border-radius: 8px;
    overflow: hidden;
}

.faq-item h3 {
    background: #c487a5;
    color: white;
    padding: 1.5rem;
    margin: 0;
    cursor: pointer;
    transition: background 0.3s;
}

.faq-item h3:hover {
    background: #b07591;
}

.faq-item div {
    padding: 1.5rem;
}

.faq-item p {
    margin: 0;
    line-height: 1.6;
    color: #333;
}
</style>

<?php include 'includes/footer.php'; ?>