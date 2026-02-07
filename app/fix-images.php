<?php
require_once 'config/database.php';

// Get actual files in the products directory
$productDir = '../assets/images/products/';
$actualFiles = [];
if (is_dir($productDir)) {
    $files = scandir($productDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($productDir . $file)) {
            $actualFiles[] = $file;
        }
    }
}

echo "Found " . count($actualFiles) . " actual files\n";

// Get products with image URLs from database
$stmt = $pdo->query("SELECT id, name FROM products WHERE is_active = 1");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($products) . " products\n";

// Update product images to use actual files
$fileIndex = 0;
foreach ($products as $product) {
    if ($fileIndex < count($actualFiles)) {
        $actualFile = $actualFiles[$fileIndex % count($actualFiles)];
        $newPath = 'assets/images/products/' . $actualFile;
        
        // Check if product_images entry exists
        $checkStmt = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1");
        $checkStmt->execute([$product['id']]);
        $existingImage = $checkStmt->fetch();
        
        if ($existingImage) {
            // Update existing
            $updateStmt = $pdo->prepare("UPDATE product_images SET image_url = ? WHERE id = ?");
            $updateStmt->execute([$newPath, $existingImage['id']]);
            echo "Updated product {$product['id']} with {$actualFile}\n";
        } else {
            // Insert new
            $insertStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, sort_order) VALUES (?, ?, 1, 0)");
            $insertStmt->execute([$product['id'], $newPath]);
            echo "Added image for product {$product['id']} with {$actualFile}\n";
        }
        
        $fileIndex++;
    }
}

echo "Database update complete\n";
?>