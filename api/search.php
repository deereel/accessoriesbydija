<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$query = trim($_GET['q'] ?? '');

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit;
}

// Sanitize input
$query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

try {
    // Try FULLTEXT search first
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.slug,
            p.price,
            pi.image_url,
            MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.is_active = 1
        AND MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE)
        ORDER BY relevance DESC
        LIMIT 10
    ");

    $stmt->execute([$query, $query]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM products p
        WHERE p.is_active = 1
        AND MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE)
    ");
    $count_stmt->execute([$query]);
    $total = $count_stmt->fetch()['total'];

} catch (PDOException $e) {
    // Fallback to LIKE search if FULLTEXT index doesn't exist
    $like_term = '%' . $query . '%';

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.slug,
            p.price,
            pi.image_url,
            1 as relevance
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.is_active = 1
        AND (p.name LIKE ? OR p.description LIKE ?)
        ORDER BY p.created_at DESC
        LIMIT 10
    ");

    $stmt->execute([$like_term, $like_term]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM products p
        WHERE p.is_active = 1
        AND (p.name LIKE ? OR p.description LIKE ?)
    ");
    $count_stmt->execute([$like_term, $like_term]);
    $total = $count_stmt->fetch()['total'];
}

echo json_encode([
    'success' => true,
    'query' => $query,
    'total' => $total,
    'products' => array_map(function($product) {
        return [
            'id' => $product['id'],
            'name' => htmlspecialchars_decode($product['name']),
            'slug' => $product['slug'],
            'price' => (float)$product['price'],
            'image_url' => $product['image_url'] ? '/' . $product['image_url'] : null,
            'url' => 'product.php?slug=' . $product['slug']
        ];
    }, $products)
]);
?>