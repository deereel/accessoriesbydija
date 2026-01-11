<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

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

    // Validate CSRF token
    if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    if (empty($data['code'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Code is required']);
        exit;
    }

    $code = strtoupper(trim($data['code']));
    $subtotal = isset($data['subtotal']) ? floatval($data['subtotal']) : 0.0;

    $stmt = $pdo->prepare("SELECT * FROM promo_codes 
                           WHERE code = ? 
                           AND is_active = 1 
                           AND start_date <= NOW() 
                           AND end_date >= NOW()");
    $stmt->execute([$code]);
    $promo = $stmt->fetch();

    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Promo code not found or expired']);
        exit;
    }

    // Check usage limit
    if ($promo['usage_limit'] && $promo['usage_count'] >= $promo['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Promo code limit exceeded']);
        exit;
    }

    // Return discount details
    // Compute discount based on subtotal if provided
    $type = $promo['type']; // 'percent' or 'amount'
    $value = floatval($promo['value']);
    $min_order = floatval($promo['min_order_amount'] ?? 0);
    $max_discount = isset($promo['max_discount']) ? floatval($promo['max_discount']) : null;

    if ($min_order > 0 && $subtotal < $min_order) {
        echo json_encode(['success' => false, 'message' => 'Order does not meet minimum amount for this promo', 'min_order_amount' => $min_order]);
        exit;
    }

    $discount = 0.0;
    if ($type === 'percent') {
        $discount = ($subtotal * ($value / 100.0));
        if ($max_discount !== null && $max_discount > 0) {
            $discount = min($discount, $max_discount);
        }
    } else {
        $discount = $value;
    }

    // Cap discount to subtotal
    if ($discount > $subtotal) $discount = $subtotal;

    $discount = round($discount, 2);

    echo json_encode([
        'success' => true,
        'code' => $promo['code'],
        'type' => $type,
        'value' => $value,
        'discount' => $discount,
        'min_order_amount' => $min_order,
        'max_discount' => $max_discount,
        'message' => 'Promo code is valid'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'message' => 'Database error']);
}
?>
