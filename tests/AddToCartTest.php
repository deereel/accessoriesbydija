<?php

// tests/AddToCartTest.php

// Note: This is a simple test script and not a replacement for a proper testing framework like PHPUnit.

// Include necessary files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/cart.php';

class AddToCartTest {

    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run() {
        // Start a transaction to avoid permanent changes to the database
        $this->pdo->beginTransaction();

        try {
            echo "Running Add to Cart test...\n";

            // 1. Find a product to add to the cart
            $product = $this->findProduct();
            if (!$product) {
                $this->fail("No product found to test.");
                return;
            }
            echo "Found product: {$product['name']}\n";

            // 2. Simulate selecting options (if any)
            $options = $this->selectOptions($product['id']);

            // 3. Simulate adding to cart
            $this->addToCart($product, $options);

            // 4. Verify item is in cart
            $this->verifyCart($product, $options);
            
            echo "Add to Cart test passed!\n";

        } catch (Exception $e) {
            $this->fail("Test failed with exception: " . $e->getMessage());
        } finally {
            // Rollback the transaction
            $this->pdo->rollBack();
            echo "Test finished, database rolled back.\n";
        }
    }

    private function findProduct() {
        $stmt = $this->pdo->query("SELECT * FROM products WHERE is_active = 1 LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function selectOptions($productId) {
        // For simplicity, we'll just grab the first available options.
        // A more complex test would try different combinations.

        $stmt = $this->pdo->prepare("SELECT * FROM product_variations WHERE product_id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $variation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$variation) {
            return []; // No options for this product
        }

        $stmt = $this->pdo->prepare("SELECT * FROM variation_sizes WHERE variation_id = ? AND stock_quantity > 0 LIMIT 1");
        $stmt->execute([$variation['id']]);
        $size = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'material_id' => $variation['material_id'],
            'variation_id' => $variation['id'],
            'size_id' => $size ? $size['id'] : null,
            'selected_price' => $size ? $size['price_adjustment'] : $variation['price_adjustment'],
        ];
    }

    private function addToCart($product, $options) {
        $_POST['product_id'] = $product['id'];
        $_POST['quantity'] = 1;
        $_POST['material_id'] = $options['material_id'] ?? null;
        $_POST['variation_id'] = $options['variation_id'] ?? null;
        $_POST['size_id'] = $options['size_id'] ?? null;
        $_POST['selected_price'] = $options['selected_price'] ?? $product['price'];
        
        // Mock the session for a guest user
        $_SESSION['cart'] = [];

        // Capture output of the cart script
        ob_start();
        include __DIR__ . '/../api/cart.php';
        $response = ob_get_clean();

        $result = json_decode($response, true);

        if (!$result || !$result['success']) {
            throw new Exception("Failed to add item to cart. Response: " . $response);
        }
        echo "Item added to cart successfully.\n";
    }

    private function verifyCart($product, $options)
    {
        $cart = $_SESSION['cart'];
        $key = $product['id'] . '_' . ($options['material_id'] ?: 'null') . '_' . ($options['variation_id'] ?: 'null') . '_' . ($options['size_id'] ?: 'null');

        if (!isset($cart[$key])) {
            throw new Exception("Item not found in cart session.");
        }

        $cartItem = $cart[$key];
        if ($cartItem['quantity'] != 1) {
            throw new Exception("Cart item quantity is incorrect.");
        }

        if ($cartItem['product_id'] != $product['id']) {
            throw new Exception("Cart item product ID is incorrect.");
        }
        echo "Cart verification successful.\n";
    }

    private function fail($message) {
        echo "Test FAILED: $message\n";
    }
}

// Run the test
$test = new AddToCartTest($pdo);
$test->run();
