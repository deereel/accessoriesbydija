<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Unauthorized');
}

require_once '../app/config/database.php';

if ($_POST && isset($_POST['product_id'])) {
    try {
        $product_id = (int)$_POST['product_id'];
        
        // Get current featured status
        $stmt = $pdo->prepare("SELECT is_featured FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Toggle featured status
            $new_status = $product['is_featured'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE products SET is_featured = ? WHERE id = ?");
            $stmt->execute([$new_status, $product_id]);
            
            echo json_encode(['success' => true, 'featured' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
