<?php
/**
 * GET /api/shipping/calculate.php
 * Calculate shipping fee based on country, weight, and customer status
 * 
 * Query parameters:
 * - country: Country name (e.g., "United Kingdom", "Canada", "United States", "Ireland")
 * - subtotal: Order subtotal in GBP (optional, for free shipping threshold check)
 * - cart_items: JSON array of cart items (optional, for weight calculation)
 * 
 * Returns JSON:
 * {
 *   "success": true,
 *   "fee": 3.50 or 0 (for free shipping),
 *   "description": "£3.50 (United Kingdom, 0.5kg)" or "FREE shipping",
 *   "country_found": true,
 *   "is_free_shipping": false,
 *   "free_shipping_threshold": 100.00
 * }
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/shipping-calculator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$country = isset($_GET['country']) ? trim($_GET['country']) : 'United Kingdom';
$subtotal = isset($_GET['subtotal']) ? floatval($_GET['subtotal']) : 0;
$cart_items = [];

// Get customer ID from session
$customer_id = $_SESSION['customer_id'] ?? null;

// If cart items are provided, calculate total weight
if (isset($_GET['cart_items'])) {
    try {
        $cart_items = json_decode($_GET['cart_items'], true);
        if (!is_array($cart_items)) {
            $cart_items = [];
        }
    } catch (Exception $e) {
        $cart_items = [];
    }
}

$total_weight = calculateTotalWeight($cart_items, $pdo);
$shipping_info = getShippingFeeWithDescription($country, $total_weight, $subtotal, $customer_id, $pdo);

// Get free shipping threshold
$threshold = getFreeShippingThreshold($customer_id, $country, $pdo);
$is_first_time = isFirstTimeCustomer($customer_id, $pdo);

echo json_encode([
    'success' => true,
    'fee' => $shipping_info['fee'],
    'description' => $shipping_info['description'],
    'country_found' => $shipping_info['country_found'],
    'is_free_shipping' => ($shipping_info['fee'] === 0),
    'free_shipping_threshold' => ($threshold === PHP_FLOAT_MAX) ? null : $threshold,
    'is_first_time_customer' => $is_first_time,
    'weight_kg' => $total_weight / 1000
]);
?>
