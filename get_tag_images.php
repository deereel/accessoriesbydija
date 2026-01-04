<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$product_id = $_GET['product_id'] ?? 0;
$tag = $_GET['tag'] ?? '';

if (!$product_id) {
    echo json_encode([]);
    exit;
}

try {
    $images = [];
    if ($tag) {
        // Get images for specific tag
        $stmt = $pdo->prepare("
            SELECT image_url, alt_text, is_primary
            FROM product_images
            WHERE product_id = ? AND tag = ?
            ORDER BY is_primary DESC, sort_order ASC, id ASC
        ");
        $stmt->execute([$product_id, $tag]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // If no images for tag or no tag, get general product images
    if (empty($images)) {
        $stmt = $pdo->prepare("
            SELECT image_url, alt_text, is_primary
            FROM product_images
            WHERE product_id = ? AND (tag IS NULL OR tag = '')
            ORDER BY is_primary DESC, sort_order ASC, id ASC
        ");
        $stmt->execute([$product_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($images);
} catch (Exception $e) {
    echo json_encode([]);
}
?>