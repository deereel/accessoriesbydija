<?php
$page_title = "Exclusive Collection";
$page_description = "Discover our premium exclusive jewelry pieces - limited edition and luxury designs.";
include 'includes/header.php';
?>

<style>
.exclusives-container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
.hero-section { background: linear-gradient(135deg, #f8f8f8, #e8e8e8); padding: 80px 40px; text-align: center; border-radius: 16px; margin-bottom: 60px; }
.hero-title { font-size: 48px; font-weight: 700; margin-bottom: 20px; color: #222; }
.hero-subtitle { font-size: 20px; color: #666; margin-bottom: 30px; }
.exclusives-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 40px; }
.exclusive-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.1); transition: transform 0.3s; }
.exclusive-card:hover { transform: translateY(-8px); }
.exclusive-image { height: 350px; background: linear-gradient(135deg, #C27BA0, #a66890); display: flex; align-items: center; justify-content: center; font-size: 80px; color: white; position: relative; }
.exclusive-badge { position: absolute; top: 20px; right: 20px; background: gold; color: #222; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.exclusive-info { padding: 35px; }
.exclusive-name { font-size: 24px; font-weight: 700; margin-bottom: 12px; }
.exclusive-price { color: #C27BA0; font-size: 28px; font-weight: 700; margin-bottom: 15px; }
.exclusive-description { color: #666; line-height: 1.6; margin-bottom: 25px; }
.exclusive-features { list-style: none; margin-bottom: 25px; }
.exclusive-features li { padding: 5px 0; color: #555; }
.exclusive-features li:before { content: "✓"; color: #C27BA0; font-weight: bold; margin-right: 10px; }
.exclusive-btn { background: linear-gradient(135deg, #C27BA0, #a66890); color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600; font-size: 16px; }
</style>

<main>
    <div class="exclusives-container">
        <div class="hero-section">
            <h1 class="hero-title">Exclusive Collection</h1>
            <p class="hero-subtitle">Limited edition luxury pieces crafted for the discerning collector</p>
        </div>

        <div class="exclusives-grid">
            <div class="exclusive-card">
                <div class="exclusive-image">
                    <span class="exclusive-badge">LIMITED</span>
                    👑
                </div>
                <div class="exclusive-info">
                    <h3 class="exclusive-name">Royal Crown Collection</h3>
                    <div class="exclusive-price">£2,500.00</div>
                    <p class="exclusive-description">An exquisite collection inspired by royal heritage, featuring rare gemstones and intricate craftsmanship.</p>
                    <ul class="exclusive-features">
                        <li>18K Gold Setting</li>
                        <li>Certified Diamonds</li>
                        <li>Limited to 50 pieces</li>
                        <li>Lifetime Warranty</li>
                    </ul>
                    <button class="exclusive-btn">Reserve Now</button>
                </div>
            </div>

            <div class="exclusive-card">
                <div class="exclusive-image">
                    <span class="exclusive-badge">EXCLUSIVE</span>
                    🌙
                </div>
                <div class="exclusive-info">
                    <h3 class="exclusive-name">Moonlight Serenade</h3>
                    <div class="exclusive-price">£1,800.00</div>
                    <p class="exclusive-description">Ethereal design capturing the beauty of moonlight with rare pearls and sapphires.</p>
                    <ul class="exclusive-features">
                        <li>Tahitian Pearls</li>
                        <li>Blue Sapphires</li>
                        <li>Platinum Setting</li>
                        <li>Custom Sizing</li>
                    </ul>
                    <button class="exclusive-btn">Reserve Now</button>
                </div>
            </div>

            <div class="exclusive-card">
                <div class="exclusive-image">
                    <span class="exclusive-badge">LUXURY</span>
                    🔥
                </div>
                <div class="exclusive-info">
                    <h3 class="exclusive-name">Phoenix Rising</h3>
                    <div class="exclusive-price">£3,200.00</div>
                    <p class="exclusive-description">Bold statement piece symbolizing rebirth and transformation with rare fire opals.</p>
                    <ul class="exclusive-features">
                        <li>Fire Opals</li>
                        <li>Rose Gold Accents</li>
                        <li>Hand-Engraved Details</li>
                        <li>Certificate of Authenticity</li>
                    </ul>
                    <button class="exclusive-btn">Reserve Now</button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>