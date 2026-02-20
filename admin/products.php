<?php
require_once '../app/config/database.php';
require_once '../app/config/cache.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle delete before any output
if (isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['delete_product'];
    error_log("Attempting to delete product ID: $product_id");

    try {
        // Check if product exists
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        if (!$stmt->fetch()) {
            error_log("Product not found: $product_id");
            $_SESSION['flash_message'] = "Product not found.";
        } else {
            // Check for orders
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $order_count = $stmt->fetchColumn();
            if ($order_count > 0) {
                error_log("Cannot delete product $product_id: has $order_count order items");
                $_SESSION['flash_message'] = "Cannot delete product that has been ordered.";
            } else {
                // Delete related records
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("DELETE FROM wishlists WHERE product_id = ?");
                $stmt->execute([$product_id]);

                $stmt = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?");
                $stmt->execute([$product_id]);

                $stmt = $pdo->prepare("DELETE FROM product_materials WHERE product_id = ?");
                $stmt->execute([$product_id]);

                // Delete images and files
                $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($images as $image_url) {
                    $file_path = '../' . $image_url;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
                $stmt->execute([$product_id]);

                // Delete variations and sizes
                $stmt = $pdo->prepare("DELETE FROM variation_sizes WHERE variation_id IN (SELECT id FROM product_variations WHERE product_id = ?)");
                $stmt->execute([$product_id]);

                $stmt = $pdo->prepare("DELETE FROM product_variations WHERE product_id = ?");
                $stmt->execute([$product_id]);

                // Delete product
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);

                $pdo->commit();

                error_log("Product deleted successfully: $product_id");
                $_SESSION['flash_message'] = "Product deleted successfully.";
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error deleting product $product_id: " . $e->getMessage());
        $_SESSION['flash_message'] = $e->getMessage();
    }

    // Redirect back
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle toggle feature
if (isset($_POST['toggle_feature'])) {
    $product_id = (int)$_POST['toggle_feature'];
    try {
        $stmt = $pdo->prepare("UPDATE products SET is_featured = 1 - is_featured WHERE id = ?");
        $stmt->execute([$product_id]);

        // Get product slug for cache clearing
        $slug_stmt = $pdo->prepare("SELECT slug FROM products WHERE id = ?");
        $slug_stmt->execute([$product_id]);
        $slug = $slug_stmt->fetchColumn();

        // Clear product cache
        cache_delete('product_' . $slug);

        $_SESSION['flash_message'] = "Product featured status updated.";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle toggle active
if (isset($_POST['toggle_active'])) {
    $product_id = (int)$_POST['toggle_active'];
    try {
        $stmt = $pdo->prepare("UPDATE products SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$product_id]);

        // Get product slug for cache clearing
        $slug_stmt = $pdo->prepare("SELECT slug FROM products WHERE id = ?");
        $slug_stmt->execute([$product_id]);
        $slug = $slug_stmt->fetchColumn();

        // Clear product cache
        cache_delete('product_' . $slug);

        $_SESSION['flash_message'] = "Product active status updated.";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle toggle customized
if (isset($_POST['toggle_customized'])) {
    $product_id = (int)$_POST['toggle_customized'];
    try {
        $stmt = $pdo->prepare("UPDATE products SET is_customized = 1 - is_customized WHERE id = ?");
        $stmt->execute([$product_id]);

        // Get product slug for cache clearing
        $slug_stmt = $pdo->prepare("SELECT slug FROM products WHERE id = ?");
        $slug_stmt->execute([$product_id]);
        $slug = $slug_stmt->fetchColumn();

        // Clear product cache
        cache_delete('product_' . $slug);

        $_SESSION['flash_message'] = "Product customized status updated.";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$page_title = "Products Management";
$active_nav = "products";

if (isset($_SESSION['flash_message'])) {
    echo '<div class="card" style="background: #d4edda; color: #155724;"><div class="card-body">' . $_SESSION['flash_message'] . '</div></div>';
    unset($_SESSION['flash_message']);
}

// Handle form submission
if ($_POST) {
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

    // For AJAX requests, ensure we return JSON
    if ($is_ajax) {
        header('Content-Type: application/json');
    }

    try {
        $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        // Check if product name already exists
        $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ?");
        $stmt->execute([$_POST['product_name']]);
        if ($stmt->fetch()) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'A product with this name already exists. Please choose a different name.']);
                exit(0); // Force exit
            } else {
                $_SESSION['flash_message'] = 'A product with this name already exists. Please choose a different name.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }

        $pdo->beginTransaction();
        try {
            $slug = strtolower(str_replace(' ', '-', $_POST['product_name']));
            $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
            $stmt = $pdo->prepare("INSERT INTO products (name, slug, short_description, description, sku, price, stock_quantity, gender, category_id, weight, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$_POST['product_name'], $slug, $_POST['short_description'], $_POST['description'], $_POST['sku'], $_POST['price'], $_POST['stock'], trim($_POST['gender']), $_POST['category_id'], $weight]);
            $product_id = $pdo->lastInsertId();

            // Insert into product_categories
            $stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            $stmt->execute([$product_id, $_POST['category_id']]);
            
            // Add variations
            if (isset($_POST['variations'])) {
                foreach ($_POST['variations'] as $variation) {
                    $price_adjustment = !empty($variation['price_adjustment']) ? $variation['price_adjustment'] : null;
                    $variation_weight = !empty($variation['weight']) ? $variation['weight'] : null;
                    $stmt = $pdo->prepare("INSERT INTO product_variations (product_id, material_id, tag, color, adornment, price_adjustment, stock_quantity, weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$product_id, $variation['material_id'], $variation['tag'], $variation['color'], $variation['adornment'], $price_adjustment, $variation['stock'], $variation_weight]);
                    $variation_id = $pdo->lastInsertId();

                    // Add sizes for this variation
                    if (isset($variation['sizes'])) {
                        foreach ($variation['sizes'] as $size) {
                            if (!empty($size['size'])) {
                                $size_price_adjustment = !empty($size['price_adjustment']) ? $size['price_adjustment'] : null;
                                $stmt = $pdo->prepare("INSERT INTO variation_sizes (variation_id, size, stock_quantity, price_adjustment) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$variation_id, $size['size'], $size['stock'], $size_price_adjustment]);
                            }
                        }
                    }
                }
            }
            
            // Handle image uploads
            if (isset($_FILES['images'])) {
                $upload_dir = '../assets/images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Process each image field
                for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                    if (!empty($_FILES['images']['name'][$i]['file'])) {
                        $filename = $_FILES['images']['name'][$i]['file'];
                        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                        if (in_array($file_ext, $allowed_exts)) {
                            $new_filename = $product_id . '_' . time() . '_' . $i . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            $image_url = 'assets/images/products/' . $new_filename;

                            if (move_uploaded_file($_FILES['images']['tmp_name'][$i]['file'], $upload_path)) {
                                $tag = $_POST['images'][$i]['tag'] ?? null;
                                $alt_text = $_POST['images'][$i]['alt_text'] ?? '';
                                $is_primary = isset($_POST['images'][$i]['is_primary']) ? 1 : 0;

                                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, alt_text, is_primary, tag) VALUES (?, ?, ?, ?, ?)");
                                $stmt->execute([$product_id, $image_url, $alt_text, $is_primary, $tag]);
                            }
                        }
                    }
                }
            }
            
            $pdo->commit();
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => 'Product added successfully!']);
                exit(0); // Force exit
            } else {
                $_SESSION['flash_message'] = 'Product added successfully!';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            } else {
                $_SESSION['flash_message'] = $e->getMessage();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
    
    if ($action === 'edit_product') {
        $pdo->beginTransaction();
        try {
            $product_id = $_POST['product_id'];
            $slug = strtolower(str_replace(' ', '-', $_POST['product_name']));
            $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;

            // Update product
            $stmt = $pdo->prepare("UPDATE products SET name = ?, slug = ?, short_description = ?, description = ?, sku = ?, price = ?, stock_quantity = ?, gender = ?, category_id = ?, weight = ? WHERE id = ?");
            $stmt->execute([$_POST['product_name'], $slug, $_POST['short_description'], $_POST['description'], $_POST['sku'], $_POST['price'], $_POST['stock'], trim($_POST['gender']), $_POST['category_id'], $weight, $product_id]);

            // Update product_categories
            $stmt = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            $stmt->execute([$product_id, $_POST['category_id']]);
            
            // Delete existing variations and sizes
            $stmt = $pdo->prepare("DELETE FROM variation_sizes WHERE variation_id IN (SELECT id FROM product_variations WHERE product_id = ?)");
            $stmt->execute([$product_id]);
            $stmt = $pdo->prepare("DELETE FROM product_variations WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            // Add new variations
            if (isset($_POST['variations'])) {
                foreach ($_POST['variations'] as $variation) {
                    $price_adjustment = !empty($variation['price_adjustment']) ? $variation['price_adjustment'] : null;
                    $variation_weight = !empty($variation['weight']) ? $variation['weight'] : null;
                    $stmt = $pdo->prepare("INSERT INTO product_variations (product_id, material_id, tag, color, adornment, price_adjustment, stock_quantity, weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$product_id, $variation['material_id'], $variation['tag'], $variation['color'], $variation['adornment'], $price_adjustment, $variation['stock'], $variation_weight]);
                    $variation_id = $pdo->lastInsertId();

                    // Add sizes for this variation
                    if (isset($variation['sizes'])) {
                        foreach ($variation['sizes'] as $size) {
                            if (!empty($size['size'])) {
                                $size_price_adjustment = !empty($size['price_adjustment']) ? $size['price_adjustment'] : null;
                                $stmt = $pdo->prepare("INSERT INTO variation_sizes (variation_id, size, stock_quantity, price_adjustment) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$variation_id, $size['size'], $size['stock'], $size_price_adjustment]);
                            }
                        }
                    }
                }
            }
            
            // Handle image deletion
            if (!empty($_POST['deleted_images'])) {
                $deleted_images = explode(',', rtrim($_POST['deleted_images'], ','));
                foreach ($deleted_images as $deleted_image_id) {
                    // Get the image URL to delete the file
                    $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ?");
                    $stmt->execute([$deleted_image_id]);
                    $image = $stmt->fetch();
                    if ($image) {
                        $file_path = '../' . $image['image_url'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
            
                    // Delete from the database
                    $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
                    $stmt->execute([$deleted_image_id]);
                }
            }
            
            // Handle image updates and uploads
            if (isset($_POST['images']) || isset($_FILES['images'])) {
                $upload_dir = '../assets/images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
            
                foreach ($_POST['images'] as $index => $imageData) {
                    $image_id = $imageData['id'] ?? null;
                    $tag = $imageData['tag'] ?? null;
                    $alt_text = $imageData['alt_text'] ?? '';
                    $is_primary = isset($imageData['is_primary']) ? 1 : 0;
            
                    // Check if a new file is uploaded
                    if (!empty($_FILES['images']['name'][$index]['file'])) {
                        $filename = $_FILES['images']['name'][$index]['file'];
                        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
                        if (in_array($file_ext, $allowed_exts)) {
                            // If it's an existing image, delete the old file
                            if ($image_id) {
                                $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ?");
                                $stmt->execute([$image_id]);
                                $image = $stmt->fetch();
                                if ($image) {
                                    $file_path = '../' . $image['image_url'];
                                    if (file_exists($file_path)) {
                                        unlink($file_path);
                                    }
                                }
                            }
            
                            // Upload the new file
                            $new_filename = $product_id . '_' . time() . '_' . $index . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            $image_url = 'assets/images/products/' . $new_filename;
            
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$index]['file'], $upload_path)) {
                                if ($image_id) {
                                    // Update existing image record
                                    $stmt = $pdo->prepare("UPDATE product_images SET image_url = ?, tag = ?, alt_text = ?, is_primary = ? WHERE id = ?");
                                    $stmt->execute([$image_url, $tag, $alt_text, $is_primary, $image_id]);
                                } else {
                                    // Insert new image record
                                    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, alt_text, is_primary, tag) VALUES (?, ?, ?, ?, ?)");
                                    $stmt->execute([$product_id, $image_url, $alt_text, $is_primary, $tag]);
                                }
                            }
                        }
                    } else {
                        // If no new file is uploaded, just update the text fields for existing images
                        if ($image_id) {
                            $stmt = $pdo->prepare("UPDATE product_images SET tag = ?, alt_text = ?, is_primary = ? WHERE id = ?");
                            $stmt->execute([$tag, $alt_text, $is_primary, $image_id]);
                        }
                    }
                }
            }

            $pdo->commit();

            // Clear product cache
            cache_delete('product_' . $slug);

            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
                exit(0); // Force exit
            } else {
                $_SESSION['flash_message'] = 'Product updated successfully!';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit(0); // Force exit
            } else {
                $_SESSION['flash_message'] = $e->getMessage();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }

    } catch (Exception $e) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
            exit(0); // Force exit
        } else {
            $_SESSION['flash_message'] = 'An error occurred: ' . $e->getMessage();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Fetch products
$stmt = $pdo->query("SELECT p.*, COUNT(pv.id) as variation_count FROM products p LEFT JOIN product_variations pv ON p.id = pv.product_id GROUP BY p.id ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();

// Fetch materials for form from database
try {
    $stmt = $pdo->query("SELECT * FROM materials WHERE is_active = 1 ORDER BY name");
    $db_materials = $stmt->fetchAll();
} catch (Exception $e) {
    $db_materials = [];
}

// Fallback to hardcoded materials if database table is empty
$materialOptions = !empty($db_materials) ? $db_materials : [
    ['id' => 1, 'name' => 'Sterling Silver'],
    ['id' => 2, 'name' => 'Gold'],
    ['id' => 3, 'name' => 'Gold Plated'],
    ['id' => 4, 'name' => 'Rose Gold'],
    ['id' => 5, 'name' => 'White Gold'],
    ['id' => 6, 'name' => 'Stainless Steel'],
    ['id' => 7, 'name' => 'Leather'],
    ['id' => 8, 'name' => 'Fabric'],
    ['id' => 9, 'name' => 'Beads'],
    ['id' => 10, 'name' => 'Wood'],
];

// Fetch categories for form
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Fetch colors from database for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM colors WHERE is_active = 1 ORDER BY name");
    $db_colors = $stmt->fetchAll();
} catch (Exception $e) {
    $db_colors = [];
}

// Fetch adornments from database for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM adornments WHERE is_active = 1 ORDER BY name");
    $db_adornments = $stmt->fetchAll();
} catch (Exception $e) {
    $db_adornments = [];
}

// Fallback to hardcoded values if database tables are empty
$colorOptions = !empty($db_colors) ? array_map(function($c) { return ['value' => $c['name'], 'hex' => $c['hex_code']]; }, $db_colors) : [
    ['value' => 'Gold', 'hex' => '#FFD700'],
    ['value' => 'Silver', 'hex' => '#C0C0C0'],
    ['value' => 'Rose Gold', 'hex' => '#B76E79'],
    ['value' => 'White Gold', 'hex' => '#E8E8E8'],
    ['value' => 'Black', 'hex' => '#000000'],
    ['value' => 'Blue', 'hex' => '#0000FF'],
    ['value' => 'Red', 'hex' => '#FF0000'],
    ['value' => 'Pink', 'hex' => '#FFC0CB'],
    ['value' => 'Green', 'hex' => '#008000'],
    ['value' => 'Purple', 'hex' => '#800080'],
];

$adornmentOptions = !empty($db_adornments) ? array_map(function($a) { return $a['name']; }, $db_adornments) : [
    'Diamond', 'Ruby', 'Emerald', 'Zirconia', 'Sapphire', 'Pearl', 'Moissanite',
    'Blue Gem', 'Pink Gem', 'White Gem', 'Red Gem', 'White Stone', 'Black Stone', 'Red Stone', 'Pink Stone'
];
?>

<?php include '_layout_header.php'; ?>

<style>
    .controls { background: var(--card); padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .products-table { background: var(--card); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .products-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    table { width: 100%; border-collapse: collapse; min-width: 600px; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
    th { background: #f8f8f8; font-weight: 600; }
    .tab-container { display: flex; background: #f8f8f8; border-bottom: 1px solid #ddd; }
    .tab-btn { padding: 15px 20px; background: none; border: none; cursor: pointer; font-weight: 500; }
    .tab-btn.active { background: white; border-bottom: 2px solid var(--accent); }
    .tab-content { display: none; padding: 20px; max-height: 70vh; overflow-y: auto; }
    .tab-content.active { display: block; }
    .image-item { border: 1px solid var(--border); padding: 15px; margin-bottom: 10px; border-radius: 8px; background: #f9f9f9; position: relative; }
    .variation-item { border: 1px solid var(--border); padding: 15px; margin-bottom: 20px; border-radius: 8px; background: #f9f9f9; position: relative; border-left: 4px solid var(--accent); }
    .variation-item:not(:last-child) { border-bottom: 3px solid var(--accent); margin-bottom: 25px; }
    .image-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; margin-right: 10px; }
    .remove-image { position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; }
    .size-item { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
    .customized-badge { background: #6f42c1; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px; }
    .product-name-cell { display: flex; align-items: center; }
</style>

        <?php if (isset($success)): ?>
            <div class="card" style="background: #d4edda; color: #155724;">
                <div class="card-body"><?= $success ?></div>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="card" style="background: #f8d7da; color: #721c24;">
                <div class="card-body"><?= $error ?></div>
            </div>
        <?php endif; ?>

        <div class="controls">
            <h2>Products (<?= count($products) ?>)</h2>
            <button class="btn btn-success" onclick="openAddModal()" id="addProductBtn">+ Add Product</button>
        </div>

        <div class="products-table">
            <div class="products-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Variations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="product-name-cell">
                                <strong><?= htmlspecialchars($product['name']) ?></strong>
                                <?php if (!empty($product['is_customized'])): ?>
                                    <span class="customized-badge">Customized</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($product['sku']) ?></td>
                            <td>£<?= number_format($product['price'], 2) ?></td>
                            <td><?= $product['stock_quantity'] ?></td>
                            <td><?= $product['variation_count'] ?> variations</td>
                            <td>
                                <button class="btn" onclick="editProduct(<?= $product['id'] ?>)" style="margin-right: 5px;">Edit</button>
                                <form method="POST" style="display:inline; margin-right: 5px;">
                                    <input type="hidden" name="toggle_feature" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn"><?= $product['is_featured'] ? 'Unfeature' : 'Feature' ?></button>
                                </form>
                                <form method="POST" style="display:inline; margin-right: 5px;">
                                    <input type="hidden" name="toggle_active" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn"><?= $product['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                                </form>
                                <form method="POST" style="display:inline; margin-right: 5px;">
                                    <input type="hidden" name="toggle_customized" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn" style="background: <?= $product['is_customized'] ? '#6f42c1' : '' ?>; color: <?= $product['is_customized'] ? 'white' : '' ?>;">
                                        <?= $product['is_customized'] ? 'Uncustomize' : 'Customize' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                    <input type="hidden" name="delete_product" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </main>
  </div>
</div>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Add Product</h2>
            
            <div class="tab-container">
                <button class="tab-btn active" onclick="switchTab('basic')">Basic Info</button>
                <button class="tab-btn" onclick="switchTab('variations')">Variations</button>
                <button class="tab-btn" onclick="switchTab('images')">Images</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" onsubmit="return submitProductForm(event)" id="productForm">
                <input type="hidden" name="action" value="add_product">
                <input type="hidden" name="deleted_images" id="deleted_images" value="">
                <div id="form-message" style="display: none; margin-bottom: 15px; padding: 10px; border-radius: 4px;"></div>
                
                <div id="basic-tab" class="tab-content active">                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" required onblur="generateSKU()">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>SKU (Auto-generated)</label>
                            <input type="text" name="sku" required readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label>Base Price (£)</label>
                            <input type="number" name="price" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Short Description</label>
                        <textarea name="short_description" rows="2" placeholder="Brief description for product cards and listings"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Detailed Description</label>
                        <textarea name="description" rows="4" placeholder="Full product description for detail pages"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="U">Unisex</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Total Stock</label>
                            <input type="number" name="stock" required>
                        </div>
                        <div class="form-group">
                            <label>Base Weight (g)</label>
                            <input type="number" name="weight" step="0.1" placeholder="Weight in grams">
                        </div>
                    </div>
                </div>
                
                <div id="variations-tab" class="tab-content">
                    <h3>Product Variations</h3>
                    <div id="variations-container"></div>
                    <button type="button" onclick="addVariation()" class="btn">+ Add Variation</button>
                </div>
                
                <div id="images-tab" class="tab-content">
                    <h3>Product Images</h3>
                    <div id="images-container">
                        <div class="image-item">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Image File</label>
                                    <input type="file" name="images[0][file]" accept="image/*" multiple>
                                </div>
                                <div class="form-group">
                                    <label>Or Choose from Cloud Storage</label>
                                    <div class="drive-buttons">
                                        <button type="button" class="btn btn-sm" onclick="openGoogleDrivePicker()" style="background: #4285F4; color: white;">
                                            <i class="fab fa-google-drive"></i> Google Drive
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openDropboxPicker()" style="background: #0061FF; color: white;">
                                            <i class="fab fa-dropbox"></i> Dropbox
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openOneDrivePicker()" style="background: #0078D4; color: white;">
                                            <i class="fab fa-microsoft"></i> OneDrive
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openICloudPicker()" style="background: #007AFF; color: white;">
                                            <i class="fas fa-cloud"></i> iCloud
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tag (Optional)</label>
                                    <select name="images[0][tag]">
                                        <option value="">General Product Image</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Alt Text</label>
                                    <input type="text" name="images[0][alt_text]" placeholder="Image description">
                                </div>
                                <div class="form-group">
                                    <label>Primary Image</label>
                                    <input type="checkbox" name="images[0][is_primary]" value="1" class="primary-image-checkbox" onclick="handlePrimaryImageChange(this)">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addImageField()" class="btn">+ Add Image</button>
                </div>
                
                <div style="text-align: right; margin-top: 20px; padding: 20px; border-top: 1px solid #ddd; background: white;">
                    <div id="stockError" class="error" style="display: none; text-align: left; margin-bottom: 10px;"></div>
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let variationCount = 1;
        let imageCount = 1;
        
        function updateImageTagOptions() {
    const variations = document.querySelectorAll('.variation-item');
    const tagOptions = ['<option value="">General Product Image</option>'];
    
    variations.forEach(variation => {
        const tagInput = variation.querySelector('input[name*="[tag]"]');
        if (tagInput && tagInput.value) {
            tagOptions.push(`<option value="${tagInput.value}">${tagInput.value}</option>`);
        }
    });
    
    document.querySelectorAll('select[name*="[tag]"]').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = tagOptions.join('');
        select.value = currentValue;
    });
}        
        function handlePrimaryImageChange(checkbox) {
    const checkboxes = document.querySelectorAll('.primary-image-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    checkbox.checked = true;
}

function addImageField() {
            const container = document.getElementById('images-container');
            const imageHtml = `
                <div class="image-item">
                    <button type="button" class="remove-image" onclick="removeImage(this)">&times;</button>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Image File</label>
                                    <input type="file" name="images[${imageCount}][file]" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <label>Or Choose from Cloud Storage</label>
                                    <div class="drive-buttons">
                                        <button type="button" class="btn btn-sm" onclick="openGoogleDrivePicker()" style="background: #4285F4; color: white;">
                                            <i class="fab fa-google-drive"></i> Google Drive
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openDropboxPicker()" style="background: #0061FF; color: white;">
                                            <i class="fab fa-dropbox"></i> Dropbox
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openOneDrivePicker()" style="background: #0078D4; color: white;">
                                            <i class="fab fa-microsoft"></i> OneDrive
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openICloudPicker()" style="background: #007AFF; color: white;">
                                            <i class="fas fa-cloud"></i> iCloud
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tag (Optional)</label>
                                    <select name="images[${imageCount}][tag]">
                                        <option value="">General Product Image</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Alt Text</label>
                                    <input type="text" name="images[${imageCount}][alt_text]" placeholder="Image description">
                                </div>
                                <div class="form-group">
                                    <label>Primary Image</label>
                                    <input type="checkbox" name="images[${imageCount}][is_primary]" value="1" class="primary-image-checkbox" onclick="handlePrimaryImageChange(this)">
                                </div>
                            </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', imageHtml);
            imageCount++;
            updateImageTagOptions();
        }
        
        function addImageFieldWithData(imageData, index) {
            const container = document.getElementById('images-container');
            const imageSrc = imageData.image_url.startsWith('assets/') ? '../' + imageData.image_url : imageData.image_url;
            const imageHtml = `
                <div class="image-item" data-image-id="${imageData.id}">
                    <input type="hidden" name="images[${index}][id]" value="${imageData.id}">
                    <button type="button" class="remove-image" onclick="removeImage(this)">&times;</button>
                    <div class="existing-image" style="margin-bottom: 10px;">
                        <img src="${imageSrc}" alt="Current image" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                        <p style="margin: 5px 0; font-size: 12px; color: #666;">Current: ${imageData.image_url}</p>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Replace Image (Optional)</label>
                            <input type="file" name="images[${index}][file]" accept="image/*">
                        </div>
                                <div class="form-group">
                                    <label>Or Choose from Cloud Storage</label>
                                    <div class="drive-buttons">
                                        <button type="button" class="btn btn-sm" onclick="openGoogleDrivePicker()" style="background: #4285F4; color: white;">
                                            <i class="fab fa-google-drive"></i> Google Drive
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openDropboxPicker()" style="background: #0061FF; color: white;">
                                            <i class="fab fa-dropbox"></i> Dropbox
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openOneDrivePicker()" style="background: #0078D4; color: white;">
                                            <i class="fab fa-microsoft"></i> OneDrive
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openICloudPicker()" style="background: #007AFF; color: white;">
                                            <i class="fas fa-cloud"></i> iCloud
                                        </button>
                                    </div>
                                </div>
                        <div class="form-group">
                            <label>Tag (Optional)</label>
                            <select name="images[${index}][tag]">
                                <option value="">General Product Image</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Alt Text</label>
                            <input type="text" name="images[${index}][alt_text]" value="${imageData.alt_text || ''}" placeholder="Image description">
                        </div>
                        <div class="form-group">
                            <label>Primary Image</label>
                            <input type="checkbox" name="images[${index}][is_primary]" value="1" ${imageData.is_primary ? 'checked' : ''} class="primary-image-checkbox" onclick="handlePrimaryImageChange(this)">
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', imageHtml);
            updateImageTagOptions();
            
            // Set the tag value after insertion
            const tagSelect = container.querySelector(`select[name="images[${index}][tag]"]`);
            if (tagSelect && imageData.tag) {
                tagSelect.value = imageData.tag;
            }
        }        function removeImage(button) {
            const imageItem = button.closest('.image-item');
            const imageId = imageItem.dataset.imageId;
            if (imageId) {
                const deletedImagesInput = document.getElementById('deleted_images');
                deletedImagesInput.value += imageId + ',';
            }
            imageItem.remove();
        }        
        function generateSKU() {
            const productName = document.querySelector('input[name="product_name"]').value;
            if (!productName) return;

            // For editing existing products, only generate new SKU if name actually changed
            if (window.originalProductName && window.originalProductName.trim() === productName.trim()) {
                // Name hasn't changed, don't regenerate SKU
                generateVariationTags();
                updateImageTagOptions();
                return;
            }

            // Generate SKU from first letters of each word
            const words = productName.trim().split(/\s+/);
            const letters = words.map(word => word.charAt(0).toUpperCase()).join('');

            // Check for existing SKUs and increment
            fetch('check_sku.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({base: letters})
            })
            .then(response => response.json())
            .then(data => {
                document.querySelector('input[name="sku"]').value = data.sku;
                generateVariationTags();
                updateImageTagOptions();
            });
        }
        
        function generateVariationTags() {
            const sku = document.querySelector('input[name="sku"]').value;
            if (!sku) return;
            
            const variations = document.querySelectorAll('.variation-item');
            variations.forEach((variation, index) => {
                const tagInput = variation.querySelector('input[name*="[tag]"]');
                if (tagInput) {
                    tagInput.value = `${sku}-${index + 1}`;
                }
            });
        }
        
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.getElementById(`${tabName}-tab`).classList.add('active');
        }
        
        function openAddModal() {
            console.log('openAddModal called');
            // Reset form for adding new product
            document.querySelector('#productModal form').reset();
            document.querySelector('input[name="action"]').value = 'add_product';

            // Remove product ID if exists
            const productIdInput = document.querySelector('input[name="product_id"]');
            if (productIdInput) {
                productIdInput.remove();
            }

            // Update modal title
            document.querySelector('#productModal h2').textContent = 'Add Product';

            // Reset variations to default
            const variationsContainer = document.getElementById('variations-container');
            variationsContainer.innerHTML = '';
            addVariationToForm({}, 0);
            variationCount = 1;

            // Reset images to default
            const imagesContainer = document.getElementById('images-container');
            imagesContainer.innerHTML = `
                <div class="image-item">
                    <button type="button" class="remove-image" onclick="removeImage(this)">&times;</button>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Image File</label>
                            <input type="file" name="images[0][file]" accept="image/*">
                        </div>
                                <div class="form-group">
                                    <label>Or Choose from Cloud Storage</label>
                                    <div class="drive-buttons">
                                        <button type="button" class="btn btn-sm" onclick="openGoogleDrivePicker()" style="background: #4285F4; color: white;">
                                            <i class="fab fa-google-drive"></i> Google Drive
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openDropboxPicker()" style="background: #0061FF; color: white;">
                                            <i class="fab fa-dropbox"></i> Dropbox
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openOneDrivePicker()" style="background: #0078D4; color: white;">
                                            <i class="fab fa-microsoft"></i> OneDrive
                                        </button>
                                        <button type="button" class="btn btn-sm" onclick="openICloudPicker()" style="background: #007AFF; color: white;">
                                            <i class="fas fa-cloud"></i> iCloud
                                        </button>
                                    </div>
                                </div>
                        <div class="form-group">
                            <label>Tag (Optional)</label>
                            <select name="images[0][tag]">
                                <option value="">General Product Image</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Alt Text</label>
                            <input type="text" name="images[0][alt_text]" placeholder="Image description">
                        </div>
                        <div class="form-group">
                            <label>Primary Image</label>
                            <input type="checkbox" name="images[0][is_primary]" value="1" class="primary-image-checkbox" onclick="handlePrimaryImageChange(this)">
                        </div>
                    </div>
                </div>
            `;
            imageCount = 1;
            updateImageTagOptions();

            // Reset to first tab
            switchTab('basic');

            // Clear original name for new products
            window.originalProductName = '';

            document.getElementById('productModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        function addVariation() {
            addVariationToForm({}, variationCount);
            variationCount++;
            generateVariationTags();
            updateImageTagOptions();
        }
        
        function addSize(button) {
            const sizesContainer = button.closest('.sizes-container');
            const sizeItems = sizesContainer.querySelectorAll('.size-item');
            const variationIndex = button.closest('.variation-item').querySelector('select').name.match(/\[(\d+)\]/)[1];

            // Find the highest existing size index
            let maxIndex = -1;
            sizeItems.forEach(item => {
                const input = item.querySelector('input[name*="[size]"]');
                if (input && input.name) {
                    const match = input.name.match(/\[sizes\]\[(\d+)\]/);
                    if (match) {
                        maxIndex = Math.max(maxIndex, parseInt(match[1]));
                    }
                }
            });

            const nextIndex = maxIndex + 1;

            const newSize = document.createElement('div');
            newSize.className = 'size-item';
            newSize.innerHTML = `
                <label for="size-${variationIndex}-${nextIndex}">Size</label>
                <input type="text" id="size-${variationIndex}-${nextIndex}" name="variations[${variationIndex}][sizes][${nextIndex}][size]" placeholder="Size" style="width: 150px;" required>
                <label for="stock-${variationIndex}-${nextIndex}">Stock</label>
                <input type="number" id="stock-${variationIndex}-${nextIndex}" name="variations[${variationIndex}][sizes][${nextIndex}][stock]" placeholder="Stock" style="width: 100px;">
                <label for="price-${variationIndex}-${nextIndex}">Price Adjustment</label>
                <input type="number" id="price-${variationIndex}-${nextIndex}" name="variations[${variationIndex}][sizes][${nextIndex}][price_adjustment]" placeholder="New Price" step="0.01" style="width: 120px;">
                <button type="button" onclick="removeSize(this)" class="btn btn-danger">Remove</button>
            `;

            // Insert before the add button row
            const addButtonRow = button.parentElement;
            sizesContainer.insertBefore(newSize, addButtonRow);
        }
        
        function removeSize(button) {
            button.closest('.size-item').remove();
        }
        
        function editProduct(id) {
            console.log('editProduct called with id:', id);
            fetch(`get_product.php?id=${id}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(product => {
                    console.log('Product data:', product);
                    if (product.error) {
                        alert('Error: ' + product.error);
                        return;
                    }
                    
                    // Populate form with product data
                    document.querySelector('input[name="product_name"]').value = product.name || '';
                    document.querySelector('input[name="sku"]').value = product.sku || '';
                    document.querySelector('input[name="price"]').value = product.price || '';
                    document.querySelector('textarea[name="short_description"]').value = product.short_description || '';
                    document.querySelector('textarea[name="description"]').value = product.description || '';
                    document.querySelector('input[name="stock"]').value = product.stock_quantity || '';
                    document.querySelector('select[name="gender"]').value = product.gender || 'Unisex';
                    document.querySelector('select[name="category_id"]').value = product.category_id || '';
                    document.querySelector('input[name="weight"]').value = product.weight || '';

                    // Store original name for SKU generation logic
                    window.originalProductName = product.name || '';
                    
                    // Update form action for editing
                    const form = document.querySelector('#productModal form');
                    form.querySelector('input[name="action"]').value = 'edit_product';
                    
                    // Add product ID for editing
                    let productIdInput = form.querySelector('input[name="product_id"]');
                    if (!productIdInput) {
                        productIdInput = document.createElement('input');
                        productIdInput.type = 'hidden';
                        productIdInput.name = 'product_id';
                        form.appendChild(productIdInput);
                    }
                    productIdInput.value = id;
                    
                    // Update modal title
                    document.querySelector('#productModal h2').textContent = 'Edit Product';
                    
                    // Clear existing variations
                    const variationsContainer = document.getElementById('variations-container');
                    variationsContainer.innerHTML = '';
                    
                    // Add variations
                    if (product.variations && product.variations.length > 0) {
                        product.variations.forEach((variation, index) => {
                            addVariationToForm(variation, index);
                        });
                        variationCount = product.variations.length;
                    } else {
                        addVariationToForm({}, 0);
                        variationCount = 1;
                    }
                    
                    // Reset images container and add existing images
                    const imagesContainer = document.getElementById('images-container');
                    imagesContainer.innerHTML = '';
                    imageCount = 0;                    
                    // Add existing images if any
                    if (product.images && product.images.length > 0) {
                        product.images.forEach((image, index) => {
                            addImageFieldWithData(image, index);
                        });
                        imageCount = product.images.length;
                    }                    
                    // Add one empty image field
                    addImageField();
                    
                    // Show modal
                    switchTab('basic');
                    document.getElementById('productModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching product details');
                });
        }
        
        function addVariationToForm(variation, index) {
            const container = document.getElementById('variations-container');
            
            // Generate material options from database values
            const materialOptions = [
                <?php foreach ($materialOptions as $material): ?>
                { id: '<?= $material['id'] ?>', name: '<?= htmlspecialchars($material['name']) ?>' },
                <?php endforeach; ?>
            ];
            
            // Generate color options from database values
            const colorOptions = [
                <?php foreach ($colorOptions as $color): ?>
                { value: '<?= htmlspecialchars($color['value'] ?? $color['name']) ?>', hex: '<?= htmlspecialchars($color['hex'] ?? '') ?>' },
                <?php endforeach; ?>
            ];
            
            // Generate adornment options from database values
            const adornmentOptions = [
                <?php foreach ($adornmentOptions as $adornment): ?>
                '<?= htmlspecialchars(is_array($adornment) ? ($adornment['name'] ?? '') : $adornment) ?>',
                <?php endforeach; ?>
            ];
            
            const variationHtml = `
                <div class="variation-item">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tag (Auto-generated)</label>
                            <input type="text" name="variations[${index}][tag]" value="${variation.tag || ''}" readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label>Material</label>
                            <select name="variations[${index}][material_id]" required>
                                <option value="">Select Material</option>
                                ${materialOptions.map(m => `<option value="${m.id}" ${String(variation.material_id) === m.id ? 'selected' : ''}>${m.name}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Color</label>
                            <select name="variations[${index}][color]">
                                <option value="">Select Color</option>
                                ${colorOptions.map(c => `<option value="${c.value}" ${variation.color === c.value ? 'selected' : ''}>${c.value}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Adornments</label>
                            <select name="variations[${index}][adornment]">
                                <option value="">Select Adornment</option>
                                ${adornmentOptions.map(a => `<option value="${a}" ${variation.adornment === a ? 'selected' : ''}>${a}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Price (£)</label>
                            <input type="number" name="variations[${index}][price_adjustment]" step="0.01" value="${variation.price_adjustment || 0}" placeholder="Enter new price">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="variations[${index}][stock]" value="${variation.stock_quantity || ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Weight (g)</label>
                        <input type="number" name="variations[${index}][weight]" step="0.1" value="${variation.weight || ''}" placeholder="Weight in grams">
                    </div>
                    
                    <h4>Sizes for this variation</h4>
                    <div class="sizes-container">
                        ${variation.sizes && variation.sizes.length > 0 ?
                            variation.sizes.map((size, sizeIndex) => `
                                <div class="size-item">
                                    <label for="size-${index}-${sizeIndex}">Size</label>
                                    <input type="text" id="size-${index}-${sizeIndex}" name="variations[${index}][sizes][${sizeIndex}][size]" value="${size.size || ''}" placeholder="Size" style="width: 150px;" required>
                                    <label for="stock-${index}-${sizeIndex}">Stock</label>
                                    <input type="number" id="stock-${index}-${sizeIndex}" name="variations[${index}][sizes][${sizeIndex}][stock]" value="${size.stock_quantity || ''}" placeholder="Stock" style="width: 100px;">
                                    <label for="price-${index}-${sizeIndex}">Price Adjustment</label>
                                    <input type="number" id="price-${index}-${sizeIndex}" name="variations[${index}][sizes][${sizeIndex}][price_adjustment]" value="${size.price_adjustment || 0}" placeholder="New Price" step="0.01" style="width: 120px;">
                                    <button type="button" onclick="removeSize(this)" class="btn btn-danger">Remove</button>
                                </div>
                            `).join('') : `
                                <div class="size-item">
                                    <label for="size-${index}-0">Size</label>
                                    <input type="text" id="size-${index}-0" name="variations[${index}][sizes][0][size]" placeholder="Size" style="width: 150px;" required>
                                    <label for="stock-${index}-0">Stock</label>
                                    <input type="number" id="stock-${index}-0" name="variations[${index}][sizes][0][stock]" placeholder="Stock" style="width: 100px;">
                                    <label for="price-${index}-0">Price Adjustment</label>
                                    <input type="number" id="price-${index}-0" name="variations[${index}][sizes][0][price_adjustment]" placeholder="New Price" step="0.01" style="width: 120px;">
                                    <button type="button" onclick="removeSize(this)" class="btn btn-danger">Remove</button>
                                </div>
                            `
                        }
                        <!-- Placeholder row removed to avoid nameless inputs that aren't submitted -->
                        <div style="margin-top: 10px;">
                            <button type="button" onclick="addSize(this)" class="btn">+ Size</button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', variationHtml);
        }
        
        
        function submitProductForm(event) {
            event.preventDefault();

            // Validate stock first
            if (!validateStock()) {
                return false;
            }

            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            const action = form.querySelector('input[name="action"]').value;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = action === 'add_product' ? 'Adding...' : 'Updating...';

            // Clear any previous messages
            const messageDiv = document.getElementById('form-message');
            messageDiv.style.display = 'none';

            // Submit form via AJAX
            const formData = new FormData(form);

            fetch('', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            // read response as text first so we can handle HTML error pages
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response from server:', text);
                    // show server response (HTML or plain text) in the form message for debugging
                    messageDiv.style.display = 'block';
                    messageDiv.style.background = '#f8d7da';
                    messageDiv.style.color = '#721c24';
                    messageDiv.style.border = '1px solid #f5c6cb';
                    // truncate long responses
                    messageDiv.textContent = text.length > 1000 ? text.slice(0, 1000) + '... (truncated)' : text;
                    throw new Error('Invalid JSON response');
                }

                if (data.success) {
                    // Success - show message and close modal
                    messageDiv.style.display = 'block';
                    messageDiv.style.background = '#d4edda';
                    messageDiv.style.color = '#155724';
                    messageDiv.style.border = '1px solid #c3e6cb';
                    messageDiv.textContent = data.message;

                    // Close modal after a short delay
                    setTimeout(() => {
                        closeModal();
                        location.reload(); // Refresh the page to show the changes
                    }, 1500);
                } else {
                    // Error - show message in modal
                    messageDiv.style.display = 'block';
                    messageDiv.style.background = '#f8d7da';
                    messageDiv.style.color = '#721c24';
                    messageDiv.style.border = '1px solid #f5c6cb';
                    messageDiv.textContent = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (!messageDiv.style.display || messageDiv.style.display === 'none') {
                    messageDiv.style.display = 'block';
                    messageDiv.style.background = '#f8d7da';
                    messageDiv.style.color = '#721c24';
                    messageDiv.style.border = '1px solid #f5c6cb';
                    messageDiv.textContent = 'An unexpected error occurred. Please try again.';
                }
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });

            return false;
        }

        function validateStock() {
            const baseStock = parseInt(document.querySelector('input[name="stock"]').value) || 0;
            const variations = document.querySelectorAll('.variation-item');
            let totalVariationStock = 0;
            const errors = [];

            variations.forEach((variation, vIndex) => {
                // Find the variation-level stock input (name ending with [stock] but not containing [sizes])
                const variationStockInput = variation.querySelector('input[name$="[stock]"]:not([name*="[sizes]"])');
                const variationStock = parseInt(variationStockInput ? variationStockInput.value : 0, 10) || 0;

                const sizeItems = variation.querySelectorAll('.size-item:not([data-placeholder])');
                let totalSizeStock = 0;

                // Debugging: list detected size inputs and values
                const sizeDebug = [];

                sizeItems.forEach(sizeItem => {
                    const sizeInput = sizeItem.querySelector('input[name*="[size]"]');
                    const stockInput = sizeItem.querySelector('input[name*="[stock]"]');
                    const stockValRaw = stockInput ? (stockInput.value || '').toString().trim() : '';
                    const stockVal = stockValRaw === '' ? null : parseInt(stockValRaw, 10);

                    if (stockVal !== null && !isNaN(stockVal)) {
                        if (!sizeInput || sizeInput.value.trim() === '') {
                            errors.push(`Variation ${vIndex + 1}: Size is required when stock is specified`);
                        } else {
                            totalSizeStock += stockVal;
                            sizeDebug.push({ size: sizeInput.value.trim(), stock: stockVal });
                        }
                    }
                });

                console.log(`Variation ${vIndex + 1} - variationStock: ${variationStock}, sizes:`, sizeDebug);

                if (sizeItems.length > 0 && totalSizeStock !== variationStock) {
                    errors.push(`Variation ${vIndex + 1}: Size stocks (${totalSizeStock}) don't match variation stock (${variationStock})`);
                }

                totalVariationStock += variationStock;
            });

            if (totalVariationStock !== baseStock) {
                errors.push(`Total variation stocks (${totalVariationStock}) don't match base stock (${baseStock})`);
            }

            if (errors.length > 0) {
                document.getElementById('stockError').innerHTML = errors.join('<br>');
                document.getElementById('stockError').style.display = 'block';
                return false;
            }

            document.getElementById('stockError').style.display = 'none';
            return true;
        }
        
        // Drive Picker functions
        function openGoogleDrivePicker() {
            // Google Drive integration requires API key and OAuth 2.0 setup
            // This would need to be implemented with Google Picker API
            alert('Google Drive integration requires OAuth 2.0 setup. Please use the file upload option for now.');
        }
        
        function openDropboxPicker() {
            // Dropbox integration requires app key and Chooser API setup
            // This would need to be implemented with Dropbox Chooser API
            alert('Dropbox integration requires app key setup. Please use the file upload option for now.');
        }
        
        function openOneDrivePicker() {
            // OneDrive integration requires app registration and API setup
            // This would need to be implemented with OneDrive File Picker API
            alert('OneDrive integration requires app registration. Please use the file upload option for now.');
        }
        
        function openICloudPicker() {
            // iCloud integration requires Apple ID authentication and API setup
            // This would need to be implemented with iCloud Drive API
            alert('iCloud integration requires Apple ID setup. Please use the file upload option for now.');
        }

        // Drive buttons styling
        const style = document.createElement('style');
        style.textContent = `
            .drive-buttons {
                display: flex;
                gap: 5px;
                flex-wrap: wrap;
            }
            
            .drive-buttons .btn {
                padding: 5px 10px;
                font-size: 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                transition: opacity 0.2s;
            }
            
            .drive-buttons .btn:hover {
                opacity: 0.9;
            }
            
            .drive-buttons .btn i {
                font-size: 12px;
            }
        `;
        document.head.appendChild(style);

        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

<?php include '_layout_footer.php'; ?>
