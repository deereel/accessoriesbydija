<?php
require_once 'config/database.php';

$page_title = "New Arrivals";
$page_description = "Discover our latest jewelry collection with fresh designs and trending styles.";
include 'includes/header.php';
?>

<style>
.new-arrivals-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
.page-header { text-align: center; margin-bottom: 50px; }
.arrivals-grid { 
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 16px !important;
    width: 100%;
}
.arrival-card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s; }
.arrival-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.arrival-image { height: 150px; background: linear-gradient(135deg, #f8f8f8, #e8e8e8); display: flex; align-items: center; justify-content: center; font-size: 48px; color: #C27BA0; position: relative; }
.new-badge { position: absolute; top: 8px; left: 8px; background: #ff4444; color: white; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; z-index: 10; }
.arrival-info { padding: 12px; }
.arrival-name { font-size: 13px; font-weight: 600; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.arrival-price { color: #C27BA0; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
.arrival-description { color: #999; font-size: 11px; margin-bottom: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.add-to-cart { background: #C27BA0; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; width: 100%; font-weight: 500; font-size: 11px; }
.add-to-cart:hover { background: #a66890; }
@media (max-width: 900px) { .arrivals-grid { grid-template-columns: repeat(3, 1fr) !important; } }
@media (max-width: 600px) { .arrivals-grid { grid-template-columns: repeat(2, 1fr) !important; } }
</style>

<main>
    <div class="new-arrivals-container">
        <div class="page-header">
            <h1>New Arrivals</h1>
            <p>Discover our latest collection of exquisite jewelry pieces</p>
        </div>

        <div class="arrivals-grid">
            <?php
              // Fetch products added in the last 30 days from database
              $query = "SELECT id, name, slug, price, description, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url FROM products p WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY created_at DESC";
              $result = $pdo->query($query);

              $emoji_list = ['💍', '✨', '👑', '💎', '🌟', '💫', '🎀', '💝'];
              $index = 0;

              if ($result) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                  $emoji = $emoji_list[$index % count($emoji_list)];
                  $image_html = $row['image_url'] ? '<img src="' . htmlspecialchars($row['image_url']) . '" style="width:100%; height:100%; object-fit:cover;" alt="' . htmlspecialchars($row['name']) . '">' : '<span>' . $emoji . '</span>';

                  echo '<div class="arrival-card" data-product-id="' . $row['id'] . '" data-price="' . $row['price'] . '" data-product-name="' . htmlspecialchars($row['name']) . '" data-image="' . htmlspecialchars($row['image_url'] ?? '') . '">
                    <a href="product.php?slug=' . htmlspecialchars($row['slug']) . '" class="arrival-image">
                      <span class="new-badge">NEW</span>
                      ' . $image_html . '
                    </a>
                    <div class="arrival-info">
                      <h3 class="arrival-name"><a href="product.php?slug=' . htmlspecialchars($row['slug']) . '">' . htmlspecialchars($row['name']) . '</a></h3>
                      <p class="arrival-price product-price" data-price="' . $row['price'] . '">£' . number_format($row['price'], 2) . '</p>
                      <p class="arrival-description">' . substr(htmlspecialchars($row['description'] ?? ''), 0, 50) . '...</p>
                      <button class="add-to-cart">Add to Cart</button>
                    </div>
                  </div>';

                  $index++;
                }
              } else {
                echo '<p style="grid-column: 1/-1; text-align: center; color: #999;">No new products available yet.</p>';
              }
            ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>