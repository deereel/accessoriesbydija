<?php
header('Content-Type: application/json');
include '../config/database.php';

try {
    $stmt = $pdo->prepare("
        SELECT t.id, t.customer_name, t.email, t.rating, t.title, t.content, 
               t.product_id, t.client_image, t.is_featured, t.created_at,
               p.slug as product_slug 
        FROM testimonials t 
        LEFT JOIN products p ON t.product_id = p.id 
        WHERE t.is_approved = 1 AND t.is_featured = 1 
        ORDER BY t.created_at DESC 
        LIMIT 6
    ");
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'testimonials' => $testimonials]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>