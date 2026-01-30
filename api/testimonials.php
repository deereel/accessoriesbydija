<?php
require_once '../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Get approved testimonials
    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.customer_name,
            t.rating,
            t.title,
            t.content,
            t.product_id,
            t.client_image,
            t.created_at,
            p.slug as product_slug
        FROM testimonials t
        LEFT JOIN products p ON t.product_id = p.id
        WHERE t.is_approved = 1
        ORDER BY t.created_at DESC
        LIMIT 10
    ");

    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'testimonials' => $testimonials
    ]);

} catch (Exception $e) {
    error_log('Testimonials API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load testimonials',
        'error' => $e->getMessage()
    ]);
}