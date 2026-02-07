<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $customer_name = trim($_POST['customer_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Validation
    if (!$product_id || !$customer_name || !$email || !$rating || !$content) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid rating']);
        exit;
    }

    // Check if product exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid product']);
        exit;
    }

    // Insert review (pending approval)
    $stmt = $pdo->prepare("INSERT INTO testimonials (customer_name, email, rating, title, content, product_id, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$customer_name, $email, $rating, $title, $content, $product_id]);

    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>