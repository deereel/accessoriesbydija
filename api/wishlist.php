<?php
session_start();
header('Content-Type: application/json');
// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../app/config/database.php';

// Helper: respond
function json($data) {
    echo json_encode($data);
    exit;
}

// Helper: get logged in customer id
function current_customer_id() {
    return isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = null;
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
}

try {
    $customer_id = current_customer_id();
    if (!$customer_id) {
        json(['success' => false, 'message' => 'Not authenticated']);
    }

    if ($method === 'GET') {
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;

        if ($product_id) {
            // Check if specific product is in wishlist
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$customer_id, $product_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            json(['success' => true, 'in_wishlist' => $result['count'] > 0]);
        } else {
            // Get user's wishlist items
            $stmt = $pdo->prepare("SELECT w.id as wishlist_id, w.product_id, w.created_at, p.name as product_name, p.slug, p.price
                FROM wishlists w
                JOIN products p ON p.id = w.product_id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC");
            $stmt->execute([$customer_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add primary image for each product
            foreach ($items as &$item) {
                $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY sort_order ASC LIMIT 1");
                $imgStmt->execute([$item['product_id']]);
                $imgRow = $imgStmt->fetch(PDO::FETCH_ASSOC);
                $item['image_url'] = $imgRow ? $imgRow['image_url'] : null;
            }

            json(['success' => true, 'items' => $items]);
        }
    }

    if ($method === 'POST') {
        if (!$input || !isset($input['product_id'])) {
            json(['success' => false, 'message' => 'Product ID missing']);
        }

        $product_id = (int)$input['product_id'];

        // Verify product exists and is active
        $pstmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
        $pstmt->execute([$product_id]);
        if (!$pstmt->fetch(PDO::FETCH_ASSOC)) {
            json(['success' => false, 'message' => 'Product not found']);
        }

        // Add to wishlist (INSERT IGNORE due to unique constraint)
        $stmt = $pdo->prepare("INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$customer_id, $product_id]);

        json(['success' => true, 'message' => 'Product added to wishlist']);
    }

    if ($method === 'DELETE') {
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        if (!$product_id) {
            json(['success' => false, 'message' => 'Product ID missing']);
        }

        $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$customer_id, $product_id]);

        json(['success' => true, 'message' => 'Product removed from wishlist']);
    }

    json(['success' => false, 'message' => 'Method not supported']);

} catch (PDOException $e) {
    json(['success' => false, 'message' => 'Database error']);
}
?>