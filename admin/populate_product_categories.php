<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../app/config/database.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get all products that have a category_id but no entry in product_categories
        $stmt = $pdo->prepare("
            SELECT p.id, p.category_id
            FROM products p
            LEFT JOIN product_categories pc ON p.id = pc.product_id
            WHERE p.category_id IS NOT NULL
            AND pc.product_id IS NULL
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products)) {
            $message = "No products need to be populated.";
            $success = true;
        } else {
            $message = "Found " . count($products) . " products to populate.\n";

            $pdo->beginTransaction();

            foreach ($products as $product) {
                $insert_stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
                $insert_stmt->execute([$product['id'], $product['category_id']]);
                $message .= "Populated product ID {$product['id']} with category ID {$product['category_id']}<br>";
            }

            $pdo->commit();
            $message .= "<br>Migration completed successfully.";
            $success = true;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Error: " . $e->getMessage();
        $success = false;
    }
}

$page_title = "Populate Product Categories";
include '_layout_header.php';
?>

<div class="container">
    <h1>Populate Product Categories</h1>
    <p>This script will populate the product_categories table for existing products that have a category_id but are missing from the product_categories table.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $success ? 'success' : 'danger' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($_POST['run'])): ?>
        <form method="POST">
            <button type="submit" name="run" class="btn btn-primary">Run Migration</button>
        </form>
    <?php endif; ?>
</div>

<?php include '_layout_footer.php'; ?>