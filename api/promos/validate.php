<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = isset($input['code']) ? trim($input['code']) : '';
$subtotal = isset($input['subtotal']) ? (float)$input['subtotal'] : 0.0;

if ($code === '' || $subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Find promo by code (case-insensitive)
    $stmt = $pdo->prepare("SELECT id, code, type, value, min_order_amount, max_discount, start_date, end_date, usage_limit, usage_count, is_active
                           FROM promo_codes WHERE UPPER(code) = UPPER(?) LIMIT 1");
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Promo code not found']);
        exit;
    }

    if (!(int)$promo['is_active']) {
        echo json_encode(['success' => false, 'message' => 'Promo code is inactive']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    if (!empty($promo['start_date']) && $now < $promo['start_date']) {
        echo json_encode(['success' => false, 'message' => 'Promo not yet active']);
        exit;
    }
    if (!empty($promo['end_date']) && $now > $promo['end_date']) {
        echo json_encode(['success' => false, 'message' => 'Promo has expired']);
        exit;
    }

    if (!empty($promo['usage_limit']) && (int)$promo['usage_limit'] > 0) {
        if ((int)$promo['usage_count'] >= (int)$promo['usage_limit']) {
            echo json_encode(['success' => false, 'message' => 'Promo usage limit reached']);
            exit;
        }
    }

    $minOrder = isset($promo['min_order_amount']) ? (float)$promo['min_order_amount'] : 0.0;
    if ($minOrder > 0 && $subtotal < $minOrder) {
        echo json_encode(['success' => false, 'message' => 'Order does not meet the minimum amount for this promo']);
        exit;
    }

    $type = strtolower($promo['type']) === 'percent' ? 'percent' : 'amount';
    $value = (float)$promo['value'];

    $discount = 0.0;
    if ($type === 'percent') {
        if ($value <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid promo value']);
            exit;
        }
        $discount = round($subtotal * ($value / 100.0), 2);
        $maxCap = isset($promo['max_discount']) ? (float)$promo['max_discount'] : 0.0;
        if ($maxCap > 0 && $discount > $maxCap) {
            $discount = $maxCap;
        }
    } else { // amount
        $discount = max(0.0, $value);
    }

    // Ensure discount does not exceed subtotal
    if ($discount > $subtotal) {
        $discount = $subtotal;
    }

    echo json_encode([
        'success' => true,
        'type' => $type,
        'value' => $value,
        'discount' => (float)$discount
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
