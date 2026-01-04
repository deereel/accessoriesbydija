<?php
/**
 * Update existing order_items to populate variation_tag based on variations
 */

require_once 'config/database.php';

try {
    echo "Starting update of order_items variation_tags...\n";

    // Get all order_items that have variations but no variation_tag
    $stmt = $pdo->prepare("
        SELECT oi.id, oi.product_id, oi.material_name, oi.color, oi.adornment, oi.size
        FROM order_items oi
        WHERE oi.variation_tag IS NULL
        AND (oi.material_name IS NOT NULL OR oi.color IS NOT NULL OR oi.adornment IS NOT NULL OR oi.size IS NOT NULL)
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($items) . " items to update.\n";

    $updateStmt = $pdo->prepare("UPDATE order_items SET variation_tag = ? WHERE id = ?");

    foreach ($items as $item) {
        // Find matching product_variation
        $pvStmt = $pdo->prepare("
            SELECT pv.tag
            FROM product_variations pv
            LEFT JOIN materials m ON m.id = pv.material_id
            WHERE pv.product_id = ?
            AND (m.name = ? OR (? IS NULL AND m.name IS NULL))
            AND (pv.color = ? OR (? IS NULL AND pv.color IS NULL))
            AND (pv.adornment = ? OR (? IS NULL AND pv.adornment IS NULL))
            LIMIT 1
        ");
        $pvStmt->execute([
            $item['product_id'],
            $item['material_name'], $item['material_name'],
            $item['color'], $item['color'],
            $item['adornment'], $item['adornment']
        ]);
        $pv = $pvStmt->fetch(PDO::FETCH_ASSOC);

        if ($pv && $pv['tag']) {
            $updateStmt->execute([$pv['tag'], $item['id']]);
            echo "Updated item {$item['id']} with tag {$pv['tag']}\n";
        } else {
            echo "No matching variation found for item {$item['id']}\n";
        }
    }

    echo "Update completed.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>