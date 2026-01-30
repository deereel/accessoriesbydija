<?php
$page_title = "Jewelry Care Guide";
$page_description = "Learn how to care for your jewelry to keep it looking beautiful for years. Cleaning, storage, and maintenance tips.";
include 'includes/header.php';
?>

<main>
    <section class="care-hero">
        <div class="container">
            <h1>Jewelry Care Guide</h1>
            <p>Keep your jewelry looking its best with our expert care and maintenance tips.</p>
        </div>
    </section>

    <section class="care-content">
        <div class="container">
            <div class="care-grid">
                <div class="care-tips">
                    <h2>General Care Tips</h2>

                    <div class="tip-item">
                        <h3>Remove Before Activities</h3>
                        <p>Take off jewelry before swimming, showering, exercising, or applying lotions and perfumes.</p>
                    </div>

                    <div class="tip-item">
                        <h3>Store Properly</h3>
                        <p>Store jewelry in a cool, dry place away from direct sunlight. Use individual pouches or compartments.</p>
                    </div>

                    <div class="tip-item">
                        <h3>Clean Regularly</h3>
                        <p>Clean jewelry every 4-6 weeks or when it looks dull. Use appropriate cleaning methods for each material.</p>
                    </div>

                    <div class="tip-item">
                        <h3>Avoid Chemicals</h3>
                        <p>Keep jewelry away from household chemicals, chlorine, and harsh cleaning products.</p>
                    </div>
                </div>

                <div class="material-care">
                    <h2>Care by Material</h2>

                    <div class="material-section">
                        <h3>Gold Jewelry</h3>
                        <ul>
                            <li>Clean with mild soap and warm water</li>
                            <li>Use a soft toothbrush for detailed cleaning</li>
                            <li>Rinse thoroughly and dry with a soft cloth</li>
                            <li>Avoid ultrasonic cleaners for delicate pieces</li>
                        </ul>
                    </div>

                    <div class="material-section">
                        <h3>Silver Jewelry</h3>
                        <ul>
                            <li>Clean with silver polish or baking soda paste</li>
                            <li>Rinse and buff with a soft cloth</li>
                            <li>Store in anti-tarnish bags when not wearing</li>
                            <li>Tarnish is normal and can be polished away</li>
                        </ul>
                    </div>

                    <div class="material-section">
                        <h3>Gemstone Jewelry</h3>
                        <ul>
                            <li>Clean gently with mild soap and water</li>
                            <li>Use a soft brush for stones with crevices</li>
                            <li>Avoid harsh chemicals that can damage stones</li>
                            <li>Check specific care instructions for each gem</li>
                        </ul>
                    </div>

                    <div class="material-section">
                        <h3>Pearl Jewelry</h3>
                        <ul>
                            <li>Wipe with a soft, damp cloth</li>
                            <li>Avoid chemicals and ultrasonic cleaning</li>
                            <li>Store separately to prevent scratches</li>
                            <li>Re-string pearls every 1-2 years</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="care-faq">
                <h2>Care FAQs</h2>

                <div class="faq-item">
                    <h3>How often should I clean my jewelry?</h3>
                    <p>Clean gold and silver jewelry every 4-6 weeks. Gemstone jewelry can be cleaned monthly. Always check for damage before cleaning.</p>
                </div>

                <div class="faq-item">
                    <h3>Can I wear jewelry in the pool?</h3>
                    <p>It's best to remove jewelry before swimming. Chlorine can damage metals and some gemstones. Saltwater can also cause corrosion.</p>
                </div>

                <div class="faq-item">
                    <h3>How do I prevent my silver from tarnishing?</h3>
                    <p>Store silver in anti-tarnish bags, avoid exposure to lotions and perfumes, and clean regularly. Tarnish is a natural process but can be minimized.</p>
                </div>

                <div class="faq-item">
                    <h3>What should I do if my jewelry gets damaged?</h3>
                    <p>Contact us immediately. We can often repair or restore damaged pieces. Some repairs may be covered under warranty.</p>
                </div>

                <div class="faq-item">
                    <h3>How do I clean diamond jewelry?</h3>
                    <p>Use a soft brush with mild soap and warm water. For professional cleaning, use an ultrasonic cleaner approved for diamonds.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.care-hero {
    background: linear-gradient(135deg, #f8f8f8 0%, #e8e8e8 100%);
    padding: 4rem 0;
    text-align: center;
}

.care-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.care-hero p {
    font-size: 1.2rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.care-content {
    padding: 4rem 0;
}

.care-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    margin-bottom: 4rem;
}

.care-tips h2,
.material-care h2,
.care-faq h2 {
    font-size: 1.8rem;
    margin-bottom: 2rem;
    color: #333;
}

.tip-item {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f8f8;
    border-radius: 8px;
    border-left: 4px solid #C27BA0;
}

.tip-item h3 {
    color: #333;
    margin-bottom: 0.5rem;
}

.tip-item p {
    color: #666;
    line-height: 1.6;
}

.material-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f8f8;
    border-radius: 8px;
}

.material-section h3 {
    color: #333;
    margin-bottom: 1rem;
}

.material-section ul {
    color: #666;
    padding-left: 1.5rem;
}

.material-section li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.care-faq {
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
    .care-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .care-hero h1 {
        font-size: 2rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>