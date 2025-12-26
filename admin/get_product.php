<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../config/database.php';

if (isset($_GET['id'])) {
    try {
        $product_id = (int)$_GET['id'];

        // Get product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Get product images
            $img_stmt = $pdo->prepare("SELECT id, image_url, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
            $img_stmt->execute([$product_id]);
            $images = $img_stmt->fetchAll(PDO::FETCH_ASSOC);

            $product['images'] = $images;

            // Get product categories
            $cat_stmt = $pdo->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
            $cat_stmt->execute([$product_id]);
            $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
            $product['category_ids'] = $categories;
            $product['category_id'] = !empty($categories) ? $categories[0] : null; // For backward compatibility

            // Get product materials
            $mat_stmt = $pdo->prepare("SELECT material_id FROM product_materials WHERE product_id = ?");
            $mat_stmt->execute([$product_id]);
            $product['materials'] = $mat_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get product adornments
            $ado_stmt = $pdo->prepare("SELECT adornment_id FROM product_adornments WHERE product_id = ?");
            $ado_stmt->execute([$product_id]);
            $product['adornments'] = $ado_stmt->fetchAll(PDO::FETCH_COLUMN);

            // Get product color
            $col_stmt = $pdo->prepare("SELECT color_id FROM product_colors WHERE product_id = ? LIMIT 1");
            $col_stmt->execute([$product_id]);
            $product['color'] = $col_stmt->fetchColumn();
            $product['color_id'] = $product['color']; // For backward compatibility

            // Get variants
            $var_stmt = $pdo->prepare("
                SELECT pv.id, pv.sku, pv.price_override, pv.size_override, vt.tag, vs.stock_quantity
                FROM product_variants pv
                LEFT JOIN variant_tags vt ON pv.id = vt.variant_id
                LEFT JOIN variant_stock vs ON pv.id = vs.variant_id
                WHERE pv.product_id = ?
            ");
            $var_stmt->execute([$product_id]);
            $product['variants'] = $var_stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request. Product ID is required.']);
}
?>