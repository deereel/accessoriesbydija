<?php
/**
 * Inventory Alert Script
 * Run via cron: 0 *\/6 * * * php /path/to/scripts/check_inventory.php
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/email.php';

$low_stock_threshold = $_ENV['LOW_STOCK_THRESHOLD'] ?? 5; // Alert when stock <= threshold

try {
    // Check product variations
    $stmt = $pdo->prepare("
        SELECT p.name, pv.tag, pv.stock_quantity, pv.id as variation_id, p.id as product_id
        FROM product_variations pv
        JOIN products p ON pv.product_id = p.id
        WHERE pv.stock_quantity <= ? AND p.is_active = 1
        ORDER BY p.name, pv.tag
    ");
    $stmt->execute([$low_stock_threshold]);
    $low_stock_variations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($low_stock_variations)) {
        $admin_email = $_ENV['ADMIN_EMAIL'] ?? 'admin@accessoriesbydija.uk';
        $subject = "Low Stock Alert - Dija Accessories";

        $body = "The following product variations are low on stock:\n\n";
        foreach ($low_stock_variations as $variation) {
            $body .= "- {$variation['name']} (Variation: {$variation['tag']}, ID: {$variation['variation_id']}): {$variation['stock_quantity']} remaining\n";
        }
        $body .= "\nPlease restock these items promptly.\n";

        send_email_smtp($admin_email, $subject, $body);
        error_log("Low stock alert sent for " . count($low_stock_variations) . " variations");
    }
} catch (PDOException $e) {
    error_log("Inventory check failed: " . $e->getMessage());
}
?>