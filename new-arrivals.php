<?php
$page_title = "New Arrivals";
$page_description = "Discover our latest jewelry collection with fresh designs and trending styles.";
include 'includes/header.php';
?>

<style>
.new-arrivals-container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
.page-header { text-align: center; margin-bottom: 50px; }
.arrivals-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; }
.arrival-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
.arrival-card:hover { transform: translateY(-5px); }
.arrival-image { height: 300px; background: linear-gradient(135deg, #f8f8f8, #e8e8e8); display: flex; align-items: center; justify-content: center; font-size: 64px; color: #C27BA0; position: relative; }
.new-badge { position: absolute; top: 15px; left: 15px; background: #ff4444; color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: 600; }
.arrival-info { padding: 25px; }
.arrival-name { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
.arrival-price { color: #C27BA0; font-size: 20px; font-weight: 600; margin-bottom: 15px; }
.arrival-description { color: #666; font-size: 14px; margin-bottom: 20px; }
.add-to-cart { background: #C27BA0; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 500; }
.add-to-cart:hover { background: #a66890; }
</style>

<main>
    <div class="new-arrivals-container">
        <div class="page-header">
            <h1>New Arrivals</h1>
            <p>Discover our latest collection of exquisite jewelry pieces</p>
        </div>

        <div class="arrivals-grid">
            <div class="arrival-card">
                <div class="arrival-image">
                    <span class="new-badge">NEW</span>
                    💎
                </div>
                <div class="arrival-info">
                    <h3 class="arrival-name">Diamond Eternity Ring</h3>
                    <div class="arrival-price">£450.00</div>
                    <p class="arrival-description">Stunning eternity ring with brilliant cut diamonds</p>
                    <button class="add-to-cart">Add to Cart</button>
                </div>
            </div>

            <div class="arrival-card">
                <div class="arrival-image">
                    <span class="new-badge">NEW</span>
                    🌟
                </div>
                <div class="arrival-info">
                    <h3 class="arrival-name">Star Pendant Necklace</h3>
                    <div class="arrival-price">£180.00</div>
                    <p class="arrival-description">Delicate star pendant with sparkling crystals</p>
                    <button class="add-to-cart">Add to Cart</button>
                </div>
            </div>

            <div class="arrival-card">
                <div class="arrival-image">
                    <span class="new-badge">NEW</span>
                    ✨
                </div>
                <div class="arrival-info">
                    <h3 class="arrival-name">Crystal Drop Earrings</h3>
                    <div class="arrival-price">£120.00</div>
                    <p class="arrival-description">Elegant drop earrings with premium crystals</p>
                    <button class="add-to-cart">Add to Cart</button>
                </div>
            </div>

            <div class="arrival-card">
                <div class="arrival-image">
                    <span class="new-badge">NEW</span>
                    💫
                </div>
                <div class="arrival-info">
                    <h3 class="arrival-name">Infinity Bracelet</h3>
                    <div class="arrival-price">£95.00</div>
                    <p class="arrival-description">Symbol of eternal love in rose gold</p>
                    <button class="add-to-cart">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>