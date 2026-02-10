<?php
/**
 * Create Colors and Adornments Tables
 * Run this script to create the required tables for the categories management page
 */

require_once '../../app/config/database.php';

try {
    // Create colors table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS colors (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            hex_code VARCHAR(7) DEFAULT '#C0C0C0',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        )
    ");
    echo "✓ Colors table created or already exists\n";
    
    // Create adornments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS adornments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        )
    ");
    echo "✓ Adornments table created or already exists\n";
    
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
        echo "✓ Default colors inserted (" . count($defaultColors) . " colors)\n";
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
        echo "✓ Default adornments inserted (" . count($defaultAdornments) . " adornments)\n";
    }
    
    echo "\n✓ Setup complete! The categories management page is ready to use.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
