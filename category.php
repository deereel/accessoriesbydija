<?php
$page_title = "Category";
$page_description = "Browse our jewelry collection by category.";
include 'includes/header.php';

$category = $_GET['cat'] ?? 'all';
$subcategory = $_GET['sub'] ?? '';
?>

<style>
.category-container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
.category-header { text-align: center; margin-bottom: 40px; }
.category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
.category-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
.category-card:hover { transform: translateY(-5px); }
.category-image { height: 250px; background: linear-gradient(135deg, #f5f5f5, #e8e8e8); display: flex; align-items: center; justify-content: center; font-size: 48px; color: #999; }
.category-info { padding: 25px; text-align: center; }
.category-name { font-size: 24px; font-weight: 600; margin-bottom: 10px; }
.category-description { color: #666; margin-bottom: 20px; }
.category-btn { background: #C27BA0; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
</style>

<main>
    <div class="category-container">
        <div class="category-header">
            <h1><?= ucfirst($category) ?> Collection</h1>
            <p>Discover our exquisite jewelry pieces</p>
        </div>

        <div class="category-grid">
            <?php if ($category === 'women' || $category === 'all'): ?>
            <div class="category-card">
                <div class="category-image">💍</div>
                <div class="category-info">
                    <h3 class="category-name">Rings</h3>
                    <p class="category-description">Elegant rings for every occasion</p>
                    <a href="products.php?category=rings" class="category-btn">Shop Rings</a>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-image">📿</div>
                <div class="category-info">
                    <h3 class="category-name">Necklaces</h3>
                    <p class="category-description">Beautiful necklaces to complement your style</p>
                    <a href="products.php?category=necklaces" class="category-btn">Shop Necklaces</a>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-image">👂</div>
                <div class="category-info">
                    <h3 class="category-name">Earrings</h3>
                    <p class="category-description">Stunning earrings for any look</p>
                    <a href="products.php?category=earrings" class="category-btn">Shop Earrings</a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($category === 'men' || $category === 'all'): ?>
            <div class="category-card">
                <div class="category-image">⌚</div>
                <div class="category-info">
                    <h3 class="category-name">Watches</h3>
                    <p class="category-description">Luxury timepieces for men</p>
                    <a href="products.php?category=watches" class="category-btn">Shop Watches</a>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-image">🔗</div>
                <div class="category-info">
                    <h3 class="category-name">Chains</h3>
                    <p class="category-description">Bold chains and pendants</p>
                    <a href="products.php?category=chains" class="category-btn">Shop Chains</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>