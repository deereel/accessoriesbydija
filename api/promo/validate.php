<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';

/**
 * POST /api/promo/validate.php
 * Validates a promo code
 * 
 * Expected JSON:
 * {
 *   "code": "DISCOUNT20"
 * }
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['code'])) {
        http_response_code(400);
        echo json_encode(['valid' => false, 'message' => 'Code is required']);
        exit;
    }

    $code = strtoupper(trim($data['code']));

    $stmt = $pdo->prepare("SELECT * FROM promo_codes 
                           WHERE code = ? 
                           AND is_active = 1 
                           AND start_date <= NOW() 
                           AND end_date >= NOW()");
    $stmt->execute([$code]);
    $promo = $stmt->fetch();

    if (!$promo) {
        echo json_encode(['valid' => false, 'message' => 'Promo code not found or expired']);
        exit;
    }

    // Check usage limit
    if ($promo['usage_limit'] && $promo['usage_count'] >= $promo['usage_limit']) {
        echo json_encode(['valid' => false, 'message' => 'Promo code limit exceeded']);
        exit;
    }

    // Return discount details
    $discount_info = [
        'valid' => true,
        'code' => $promo['code'],
        'type' => $promo['type'],
        'discount_percent' => null,
        'discount_amount' => null,
        'min_order_amount' => $promo['min_order_amount'],
        'max_discount' => $promo['max_discount'],
        'message' => 'Promo code is valid'
    ];

    if ($promo['type'] === 'percent') {
        $discount_info['discount_percent'] = $promo['value'];
    } else {
        $discount_info['discount_amount'] = $promo['value'];
    }

    echo json_encode($discount_info);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'message' => 'Database error']);
}
?>
