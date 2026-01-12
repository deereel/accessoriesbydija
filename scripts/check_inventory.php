<?php
/**
 * Inventory Alert Script
 * Run via cron: 0 */6 * * * php /path/to/scripts/check_inventory.php
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/email.php';

$low_stock_threshold = 5; // Alert when stock <= 5

try {
    // Check products
    $stmt = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE stock_quantity <= ? AND is_active = 1");
    $stmt->execute([$low_stock_threshold]);
    $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($low_stock_products)) {
        $admin_email = $_ENV['ADMIN_EMAIL'] ?? 'admin@accessoriesbydija.uk';
        $subject = "Low Stock Alert - Dija Accessories";

        $body = "The following products are low on stock:\n\n";
        foreach ($low_stock_products as $product) {
            $body .= "- {$product['name']} (ID: {$product['id']}): {$product['stock_quantity']} remaining\n";
        }
        $body .= "\nPlease restock these items promptly.\n";

        send_email_smtp($admin_email, $subject, $body);
        error_log("Low stock alert sent for " . count($low_stock_products) . " products");
    }
} catch (PDOException $e) {
    error_log("Inventory check failed: " . $e->getMessage());
}
?>