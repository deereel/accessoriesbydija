<?php
// Add FULLTEXT index for search functionality
require_once 'config/database.php';

echo "<h1>Adding Search Index</h1>";
echo "<pre>";

try {
    $pdo->exec('ALTER TABLE products ADD FULLTEXT(name, description, short_description)');
    echo "✅ FULLTEXT index added successfully!\n";
    echo "Search functionality is now optimized.\n";
} catch (PDOException $e) {
    $error = $e->getMessage();
    if (strpos($error, 'already exists') !== false) {
        echo "✅ FULLTEXT index already exists.\n";
        echo "Search functionality should work fine.\n";
    } else {
        echo "❌ Error adding index: " . $error . "\n";
        echo "Search will fall back to LIKE queries (slower but functional).\n";
    }
}

echo "\n<a href='index.php'>← Back to homepage</a>";
echo "</pre>";
?>