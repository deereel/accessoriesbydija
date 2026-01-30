<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../app/config/database.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request. Order ID is required.']);
    exit;
}

try {
    $order_id = (int) $_GET['id'];

    // Fetch order + customer basic info
    $stmt = $pdo->prepare(
        "SELECT o.*, 
                c.email AS customer_email,
                c.first_name,
                c.last_name,
                ca.type AS address_type,
                ca.first_name AS addr_first_name,
                ca.last_name AS addr_last_name,
                ca.company,
                ca.address_line_1,
                ca.address_line_2,
                ca.city,
                ca.state,
                ca.postal_code,
                ca.country
         FROM orders o
         LEFT JOIN customers c ON o.customer_id = c.id
         LEFT JOIN customer_addresses ca ON o.address_id = ca.id
         WHERE o.id = ?"
    );
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }

    // Fetch items
    $itemsStmt = $pdo->prepare(
        "SELECT oi.*, p.slug AS product_slug, oi.product_name, p.description as product_description,
         COALESCE(
             (SELECT image_url FROM product_images WHERE variant_id = oi.variation_id LIMIT 1),
             (SELECT image_url FROM product_images WHERE product_id = oi.product_id AND is_primary = 1 LIMIT 1)
         ) as product_image
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = ?
         ORDER BY oi.id ASC"
    );
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $order['items'] = $items;

    echo json_encode(['success' => true, 'order' => $order]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
