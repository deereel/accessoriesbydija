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
    // UK: £100 for first-time, £300 for returning
    // Foreign countries: £500 for all customers

    if ($country === 'United Kingdom') {
        if (isFirstTimeCustomer($customer_id, $pdo)) {
            return 100.00; // First-time UK customers
        } else {
            return 300.00; // Returning UK customers
        }
    } else {
        // Foreign countries (non-UK)
        return 500.00;
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
    if ($subtotal > 0 && $pdo !== null) {
        $threshold = getFreeShippingThreshold($customer_id, $country, $pdo);
        if ($subtotal >= $threshold) {
            return 0.00; // Free shipping
        }
    }

    // If no database connection, return null
    if ($pdo === null) {
        return null;
    }

    // Convert grams to kg for calculation
    $weight_kg = $total_weight_grams / 1000;

    // Normalize country name
    $country = trim($country);

    try {
        // Fetch shipping rates from database
        $stmt = $pdo->prepare("SELECT weight_min, weight_max, fee FROM shipping_rates
                              WHERE country = ? AND is_active = 1
                              ORDER BY weight_min ASC");
        $stmt->execute([$country]);
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if country has defined rates
        if (empty($rates)) {
            // Country not found in database rates - return null to indicate location-based pricing
            return null;
        }

        // Find applicable rate based on weight
        foreach ($rates as $rate) {
            if ($weight_kg >= $rate['weight_min'] && $weight_kg <= $rate['weight_max']) {
                return round($rate['fee'], 2);
            }
        }

        // Default to highest rate if weight exceeds all tiers
        return round(end($rates)['fee'], 2);

    } catch (PDOException $e) {
        // Log error and fall back to null
        error_log("Shipping calculation error: " . $e->getMessage());
        return null;
    }
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
        $variation_id = isset($item['variation_id']) ? $item['variation_id'] : null;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;

        if ($product_id && $quantity > 0) {
            $weight = 0;

            // Check for variation weight first
            if ($variation_id) {
                $stmt = $pdo->prepare("SELECT weight FROM product_variations WHERE id = ?");
                $stmt->execute([$variation_id]);
                $variation = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($variation && $variation['weight']) {
                    $weight = floatval($variation['weight']);
                }
            }

            // If no variation weight, use product weight
            if ($weight == 0) {
                $stmt = $pdo->prepare("SELECT weight FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($product && $product['weight']) {
                    $weight = floatval($product['weight']);
                }
            }

            if ($weight > 0) {
                // weight is in grams
                $total_weight += $weight * $quantity;
            }
        }
    }

    return round($total_weight, 2);
}
?>
