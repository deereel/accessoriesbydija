<?php
$page_title = "Frequently Asked Questions";
$page_description = "Find answers to common questions about Dija Accessories jewelry, shipping, returns, custom designs, and more.";
include 'includes/header.php';
?>

<main>
    <section class="faq-hero">
        <div class="container">
            <h1>Frequently Asked Questions</h1>
            <p>Find answers to the most common questions about our jewelry, services, and policies.</p>
        </div>
    </section>

    <section class="faq-content" itemscope itemtype="https://schema.org/FAQPage">
        <div class="container">
            <div class="faq-grid">
                <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">What materials do you use in your jewelry?</h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">We use only premium materials including 14k and 18k gold, sterling silver, platinum, and ethically sourced gemstones. All our pieces are hypoallergenic and nickel-free.</p>
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
                        <p itemprop="text">We offer a 30-day return policy for unworn items in original condition. Custom pieces are final sale unless there's a manufacturing defect. All returns include free shipping.</p>
                    </div>
                </div>

                <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Do you provide certificates for diamonds?</h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Yes, all diamonds over 0.5 carats come with GIA or equivalent certification. We provide detailed information about cut, clarity, color, and carat weight for complete transparency.</p>
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
                        <p itemprop="text">Yes, we provide a lifetime warranty against manufacturing defects. This includes free repairs for normal wear and tear, stone tightening, and rhodium plating for white gold pieces.</p>
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