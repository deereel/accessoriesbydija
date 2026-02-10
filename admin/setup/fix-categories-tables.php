<?php
/**
 * Fix Colors and Adornments Tables
 * Run this script to add missing columns and set up the tables properly
 */

require_once '../../app/config/database.php';

try {
    echo "Starting database fix...\n\n";
    
    // Create colors table with proper structure
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS colors (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            hex_code VARCHAR(7) DEFAULT '#C0C0C0',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name),
            UNIQUE KEY unique_slug (slug)
        )
    ");
    echo "✓ Colors table created/verified\n";
    
    // Create adornments table with proper structure
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS adornments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name),
            UNIQUE KEY unique_slug (slug)
        )
    ");
    echo "✓ Adornments table created/verified\n";
    
    // Insert default colors if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM colors");
    if ($stmt->fetchColumn() == 0) {
        $defaultColors = [
            ['Gold', '#FFD700'],
            ['Silver', '#C0C0C0'],
            ['Rose Gold', '#B76E79'],
            ['White Gold', '#E8E8E8'],
            ['Black', '#000000'],
            ['Blue', '#0000FF'],
            ['Red', '#FF0000'],
            ['Pink', '#FFC0CB'],
            ['Green', '#008000'],
            ['Purple', '#800080'],
            ['Yellow', '#FFFF00'],
            ['Orange', '#FFA500'],
            ['Brown', '#A52A2A'],
        ];
        
        $stmt = $pdo->prepare("INSERT INTO colors (name, slug, hex_code) VALUES (?, ?, ?)");
        foreach ($defaultColors as $color) {
            $slug = strtolower(str_replace(' ', '-', $color[0]));
            $stmt->execute([$color[0], $slug, $color[1]]);
        }
        echo "✓ Inserted " . count($defaultColors) . " default colors\n";
    } else {
        echo "✓ Colors table already has data\n";
    }
    
    // Insert default adornments if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM adornments");
    if ($stmt->fetchColumn() == 0) {
        $defaultAdornments = [
            ['Diamond', 'Classic diamond gemstone'],
            ['Ruby', 'Deep red gemstone'],
            ['Emerald', 'Rich green gemstone'],
            ['Zirconia', 'Affordable diamond alternative'],
            ['Sapphire', 'Blue gemstone'],
            ['Pearl', 'Natural pearl'],
            ['Moissanite', 'Brilliant diamond alternative'],
            ['Blue Gem', 'Blue colored gemstone'],
            ['Pink Gem', 'Pink colored gemstone'],
            ['White Gem', 'White colored gemstone'],
            ['Red Gem', 'Red colored gemstone'],
            ['White Stone', 'Clear white stone'],
            ['Black Stone', 'Black stone'],
            ['Red Stone', 'Red stone'],
            ['Pink Stone', 'Pink stone'],
        ];
        
        $stmt = $pdo->prepare("INSERT INTO adornments (name, slug, description) VALUES (?, ?, ?)");
        foreach ($defaultAdornments as $adornment) {
            $slug = strtolower(str_replace(' ', '-', $adornment[0]));
            $stmt->execute([$adornment[0], $slug, $adornment[1]]);
        }
        echo "✓ Inserted " . count($defaultAdornments) . " default adornments\n";
    } else {
        echo "✓ Adornments table already has data\n";
    }
    
    echo "\n========================================\n";
    echo "✓ Database fix complete!\n";
    echo "========================================\n\n";
    echo "The categories management page should now work.\n";
    echo "You can now:\n";
    echo "1. Add new colors with names and hex codes\n";
    echo "2. Add new adornments with names and descriptions\n";
    echo "3. Edit and delete existing colors/adornments\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
