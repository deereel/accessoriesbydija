<?php
/**
 * Public API for fetching products currently on sale or promo
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../app/config/database.php';

try {
    $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.sale_percentage, p.sale_end_date,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 0 ORDER BY sort_order ASC LIMIT 1) as hover_image
            FROM products p
            WHERE p.is_active = 1
              AND p.is_on_sale = 1
              AND p.sale_price IS NOT NULL
              AND p.sale_price < p.price
              AND (p.sale_end_date IS NULL OR p.sale_end_date >= NOW())
            ORDER BY p.created_at DESC
            LIMIT 24";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($products as $product) {
        $main_image = $product['main_image'];
        if ($main_image && strpos($main_image, '/') !== 0) {
            $main_image = '/' . $main_image;
        }

        $hover_image = $product['hover_image'];
        if ($hover_image && strpos($hover_image, '/') !== 0) {
            $hover_image = '/' . $hover_image;
        }

        $discountPercent = 0;
        if ($product['sale_percentage']) {
            $discountPercent = (int)$product['sale_percentage'];
        } elseif ($product['sale_price']) {
            $discountPercent = (int)round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
        }

        $formatted[] = [
            'id' => (int)$product['id'],
            'name' => htmlspecialchars($product['name']),
            'slug' => $product['slug'],
            'price' => (float)$product['price'],
            'sale_price' => (float)$product['sale_price'],
            'sale_percentage' => $discountPercent,
            'main_image' => $main_image,
            'hover_image' => $hover_image
        ];
    }

    echo json_encode(['success' => true, 'products' => $formatted]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error', 'products' => []]);
}
