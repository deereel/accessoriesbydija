<?php
/**
 * Shipping Calculator
 * Calculates shipping fees based on location and total weight of order items
 * Also handles free shipping thresholds for first-time and returning customers
 */

/**
 * Check if customer is a first-time customer (no previous orders)
 * 
 * @param int|null $customer_id Customer ID (null for guest)
 * @param PDO $pdo Database connection
 * @return bool True if first-time customer or guest
 */
function isFirstTimeCustomer($customer_id, $pdo) {
    if (!$customer_id) {
        return true; // Guests are treated as first-time customers
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders 
                               WHERE customer_id = ? AND status IN ('completed', 'shipped', 'delivered', 'processing')");
        $stmt->execute([$customer_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['order_count'] ?? 0) == 0;
    } catch (PDOException $e) {
        return true; // Default to first-time if error
    }
}

/**
 * Get free shipping threshold for customer
 * 
 * @param int|null $customer_id Customer ID (null for guest)
 * @param string $country Shipping country
 * @param PDO $pdo Database connection
 * @return float Threshold in GBP (0 if not eligible for free shipping)
 */
function getFreeShippingThreshold($customer_id, $country, $pdo) {
    // Check if order qualifies for free shipping
    // First-time customers: UK only, threshold £100
    // Returning customers: Any of 4 countries, threshold £300
    
    if (isFirstTimeCustomer($customer_id, $pdo)) {
        // First-time customer: only UK gets free shipping at £100
        if ($country === 'United Kingdom') {
            return 100.00;
        }
        return PHP_FLOAT_MAX; // No free shipping for first-time customers outside UK
    } else {
        // Returning customer: all 4 supported countries get free shipping at £300
        $supported_countries = ['United Kingdom', 'Canada', 'United States', 'Ireland'];
        if (in_array($country, $supported_countries)) {
            return 300.00;
        }
        return PHP_FLOAT_MAX; // No free shipping for unsupported countries
    }
}

/**
 * Get shipping fee based on country and total weight
 * 
 * @param string $country Country name
 * @param float $total_weight_grams Total weight of items in grams
 * @param float $subtotal Order subtotal for free shipping check
 * @param int|null $customer_id Customer ID (null for guest)
 * @param PDO $pdo Database connection
 * @return float Shipping cost in GBP (0 if free shipping applies)
 */
function calculateShippingFee($country, $total_weight_grams = 0, $subtotal = 0, $customer_id = null, $pdo = null) {
    // Check if eligible for free shipping
    if ($subtotal > 0 && $customer_id !== false && $pdo !== null) {
        $threshold = getFreeShippingThreshold($customer_id, $country, $pdo);
        if ($subtotal >= $threshold) {
            return 0.00; // Free shipping
        }
    }
    
    // Convert grams to kg for calculation
    $weight_kg = $total_weight_grams / 1000;
    
    $shipping_rates = [
        'United Kingdom' => [
            'name' => 'UK standard shipping',
            'rates' => [
                ['max' => 1, 'fee' => 3.50],
                ['max' => 4, 'fee' => 5.20],
                ['max' => PHP_FLOAT_MAX, 'fee' => 7.00]
            ]
        ],
        'Canada' => [
            'name' => 'Canada international tracked',
            'rates' => [
                ['max' => 1, 'fee' => 19.30],
                ['max' => 2, 'fee' => 23.35],
                ['max' => PHP_FLOAT_MAX, 'fee' => 28.00]
            ]
        ],
        'United States' => [
            'name' => 'USA tracked',
            'rates' => [
                ['max' => 1, 'fee' => 16.50],
                ['max' => 2, 'fee' => 20.20],
                ['max' => PHP_FLOAT_MAX, 'fee' => 30.90]
            ]
        ],
        'Ireland' => [
            'name' => 'Ireland tracked',
            'rates' => [
                ['max' => 1, 'fee' => 8.65],
                ['max' => 2, 'fee' => 10.10],
                ['max' => PHP_FLOAT_MAX, 'fee' => 11.10]
            ]
        ]
    ];
    
    // Normalize country name
    $country = trim($country);
    
    // Check if country has defined rates
    if (!isset($shipping_rates[$country])) {
        // Country not found in defined rates - return null to indicate location-based pricing
        return null;
    }
    
    $rates = $shipping_rates[$country]['rates'];
    
    // Find applicable rate based on weight
    foreach ($rates as $rate) {
        if ($weight_kg <= $rate['max']) {
            return round($rate['fee'], 2);
        }
    }
    
    // Default to highest rate if weight exceeds all tiers
    return round(end($rates)['fee'], 2);
}

/**
 * Get shipping fee with display text
 * Returns array with fee and description
 * 
 * @param string $country Country name
 * @param float $total_weight_grams Total weight of items in grams
 * @param float $subtotal Order subtotal for free shipping check
 * @param int|null $customer_id Customer ID (null for guest)
 * @param PDO $pdo Database connection
 * @return array ['fee' => float|null, 'description' => string, 'country_found' => bool]
 */
function getShippingFeeWithDescription($country, $total_weight_grams = 0, $subtotal = 0, $customer_id = null, $pdo = null) {
    $fee = calculateShippingFee($country, $total_weight_grams, $subtotal, $customer_id, $pdo);
    $weight_kg = $total_weight_grams / 1000;
    
    if ($fee === null) {
        return [
            'fee' => null,
            'description' => 'Shipping fee depends on location',
            'country_found' => false
        ];
    }
    
    // Check if free shipping applies
    if ($fee == 0) {
        return [
            'fee' => 0,
            'description' => 'FREE shipping',
            'country_found' => true
        ];
    }
    
    // Determine tier name
    $tier_name = '';
    if ($weight_kg <= 1) {
        $tier_name = '0-1kg';
    } elseif ($weight_kg <= 2) {
        $tier_name = '1-2kg';
    } elseif ($weight_kg <= 3) {
        $tier_name = '2-3kg';
    } else {
        $tier_name = '3kg+';
    }
    
    return [
        'fee' => $fee,
        'description' => "£" . number_format($fee, 2) . " (" . htmlspecialchars($country) . ", " . round($weight_kg, 2) . "kg)",
        'country_found' => true
    ];
}

/**
 * Calculate total weight of cart items
 * 
 * @param array $cart_items Array of cart items with product_id
 * @param PDO $pdo Database connection
 * @return float Total weight in grams
 */
function calculateTotalWeight($cart_items, $pdo) {
    $total_weight = 0;
    
    foreach ($cart_items as $item) {
        $product_id = isset($item['product_id']) ? $item['product_id'] : null;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
        
        if ($product_id && $quantity > 0) {
            // Fetch product weight
            $stmt = $pdo->prepare("SELECT weight FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product && $product['weight']) {
                // weight is in grams
                $total_weight += floatval($product['weight']) * $quantity;
            }
        }
    }
    
    return round($total_weight, 2);
}
?>
