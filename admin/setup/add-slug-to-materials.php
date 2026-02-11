<?php
/**
 * Fix Materials Table - Add missing slug column
 * Run this script to add the missing slug column to the materials table
 */

require_once __DIR__ . '/../../app/config/database.php';

try {
    echo "Checking materials table structure...\n\n";
    
    // Check if slug column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM materials LIKE 'slug'");
    if ($stmt->fetch()) {
        echo "✓ slug column already exists\n";
    } else {
        // Add slug column
        $pdo->exec("ALTER TABLE materials ADD COLUMN slug VARCHAR(100) AFTER name");
        echo "✓ Added slug column to materials table\n";
        
        // Generate slugs for existing records
        $stmt = $pdo->query("SELECT id, name FROM materials");
        $materials = $stmt->fetchAll();
        
        foreach ($materials as $material) {
            $slug = strtolower(str_replace(' ', '-', $material['name']));
            $updateStmt = $pdo->prepare("UPDATE materials SET slug = ? WHERE id = ?");
            $updateStmt->execute([$slug, $material['id']]);
        }
        echo "✓ Generated slugs for " . count($materials) . " existing materials\n";
    }
    
    // Check if description column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM materials LIKE 'description'");
    if ($stmt->fetch()) {
        echo "✓ description column already exists\n";
    } else {
        $pdo->exec("ALTER TABLE materials ADD COLUMN description TEXT AFTER slug");
        echo "✓ Added description column to materials table\n";
    }
    
    // Check if is_active column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM materials LIKE 'is_active'");
    if ($stmt->fetch()) {
        echo "✓ is_active column already exists\n";
    } else {
        $pdo->exec("ALTER TABLE materials ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER description");
        echo "✓ Added is_active column to materials table\n";
    }
    
    // Check if created_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM materials LIKE 'created_at'");
    if ($stmt->fetch()) {
        echo "✓ created_at column already exists\n";
    } else {
        $pdo->exec("ALTER TABLE materials ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_active");
        echo "✓ Added created_at column to materials table\n";
    }
    
    // Check if updated_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM materials LIKE 'updated_at'");
    if ($stmt->fetch()) {
        echo "✓ updated_at column already exists\n";
    } else {
        $pdo->exec("ALTER TABLE materials ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "✓ Added updated_at column to materials table\n";
    }
    
    echo "\n========================================\n";
    echo "✓ Materials table fix complete!\n";
    echo "========================================\n\n";
    echo "The categories page should now work properly.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
