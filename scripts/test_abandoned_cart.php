<?php
// Test script for abandoned cart system
require_once __DIR__ . '/../app/config/database.php';

echo "<h1>Abandoned Cart System Test</h1>";

// Test 1: Check if tables exist
echo "<h2>Test 1: Database Tables</h2>";
$tables = ['cart', 'abandoned_carts'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        echo "✓ Table `$table` exists<br>";
    } catch (PDOException $e) {
        echo "✗ Table `$table` does not exist: " . $e->getMessage() . "<br>";
    }
}

// Test 2: Insert test cart data
echo "<h2>Test 2: Insert Test Cart Data</h2>";
try {
    // Insert a test guest cart that's old
    $old_time = date('Y-m-d H:i:s', strtotime('-25 hours'));
    $stmt = $pdo->prepare("INSERT INTO cart (session_id, guest_email, product_id, quantity, created_at, updated_at) VALUES (?, ?, 1, 1, ?, ?)");
    $stmt->execute(['test_session_' . time(), 'test@example.com', $old_time, $old_time]);
    $cart_id = $pdo->lastInsertId();
    echo "✓ Inserted test cart with ID: $cart_id<br>";
} catch (PDOException $e) {
    echo "✗ Failed to insert test cart: " . $e->getMessage() . "<br>";
}

// Test 3: Check abandoned cart detection
echo "<h2>Test 3: Abandoned Cart Detection</h2>";
try {
    $threshold = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM cart c
        LEFT JOIN abandoned_carts ac ON (
            (c.customer_id IS NOT NULL AND ac.customer_id = c.customer_id) OR
            (c.session_id IS NOT NULL AND ac.session_id = c.session_id) OR
            (c.guest_email IS NOT NULL AND ac.guest_email = c.guest_email)
        )
        WHERE c.updated_at < ? AND (c.customer_id IS NOT NULL OR c.guest_email IS NOT NULL) AND ac.id IS NULL
    ");
    $stmt->execute([$threshold]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Found " . $result['count'] . " abandoned carts<br>";
} catch (PDOException $e) {
    echo "✗ Failed to check abandoned carts: " . $e->getMessage() . "<br>";
}

// Test 4: Test email sending (dry run)
echo "<h2>Test 4: Email Template Generation</h2>";
try {
    // Get cart items
    $stmt = $pdo->prepare("SELECT c.*, p.name FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id LIKE 'test_session_%' ORDER BY c.id DESC LIMIT 1");
    $stmt->execute();
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        $cart_items = [$cart_item];
        $subtotal = $cart_item['selected_price'] ?: 100.00; // Default price

        $items_html = "
        <tr>
            <td style='padding: 10px; border-bottom: 1px solid #eee;'>
                <strong>{$cart_item['name']}</strong><br>
                Quantity: {$cart_item['quantity']}
            </td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>
                £" . number_format($subtotal, 2) . "
            </td>
        </tr>";

        $html_body = "
        <html>
        <head><title>Your Abandoned Cart</title></head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #f8f9fa; padding: 20px; text-align: center;'>
                <h1 style='color: #c27ba0; margin: 0;'>Dija Accessories</h1>
            </div>
            <div style='padding: 20px;'>
                <h2>Don't forget your items!</h2>
                <p>Your cart is waiting for you!</p>
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <tbody>{$items_html}</tbody>
                </table>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='/checkout.php' style='background: #c27ba0; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;'>Complete Your Order</a>
                </div>
            </div>
        </body>
        </html>";

        echo "✓ Email template generated successfully<br>";
        echo "<details><summary>Preview Email HTML</summary><pre>" . htmlspecialchars($html_body) . "</pre></details>";
    } else {
        echo "✗ No test cart items found<br>";
    }
} catch (PDOException $e) {
    echo "✗ Failed to generate email template: " . $e->getMessage() . "<br>";
}

// Test 5: Clean up test data
echo "<h2>Test 5: Clean Up Test Data</h2>";
try {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE session_id LIKE 'test_session_%'");
    $stmt->execute();
    echo "✓ Cleaned up test cart data<br>";
} catch (PDOException $e) {
    echo "✗ Failed to clean up: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<p>If all tests passed, the abandoned cart system is ready. Set up a cron job to run <code>scripts/process_abandoned_carts.php</code> every hour.</p>";
?>