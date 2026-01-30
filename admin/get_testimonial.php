<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../app/config/database.php';

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, customer_name, email, rating, title, content, product_id, client_image, is_featured, is_approved, created_at FROM testimonials WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testimonial) {
            header('Content-Type: application/json');
            echo json_encode($testimonial);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Testimonial not found']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No ID provided']);
}
?>
