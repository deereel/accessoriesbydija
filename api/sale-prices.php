<?php
/**
 * API for managing product sale prices (Admin)
 */

require_once '../app/config/database.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Check if sale columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_on_sale'");
    $columns_exist = $stmt->fetch();
    
    if (!$columns_exist) {
        // Return empty list if columns don't exist yet
        echo json_encode(['success' => true, 'products' => []]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => true, 'products' => []]);
    exit;
}

if ($action === 'list') {
    // Get all products with sale status
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT p.id, p.name, p.price, p.is_on_sale, p.sale_price, p.sale_percentage, p.sale_end_date 
            FROM products p 
            WHERE p.is_active = 1";
    
    $params = [];
    
    if ($search) {
        $sql .= " AND p.name LIKE ?";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY p.name ASC LIMIT 100";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate percentages for products with fixed sale price
        foreach ($products as &$product) {
            if ($product['is_on_sale'] && $product['sale_price'] && !$product['sale_percentage']) {
                $product['sale_percentage'] = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
            }
            
            // Check if sale has expired
            if ($product['sale_end_date'] && strtotime($product['sale_end_date']) < time()) {
                $product['sale_expired'] = true;
            } else {
                $product['sale_expired'] = false;
            }
        }
        
        echo json_encode(['success' => true, 'products' => $products]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} elseif ($action === 'set') {
    // Set sale price for a product
    $product_id = (int)($_POST['product_id'] ?? 0);
    $is_on_sale = isset($_POST['is_on_sale']) ? (int)$_POST['is_on_sale'] : 0;
    $sale_price = isset($_POST['sale_price']) && $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
    $sale_percentage = isset($_POST['sale_percentage']) && $_POST['sale_percentage'] !== '' ? (int)$_POST['sale_percentage'] : null;
    $sale_end_date = isset($_POST['sale_end_date']) && $_POST['sale_end_date'] !== '' ? $_POST['sale_end_date'] : null;
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    // Validate sale price
    if ($is_on_sale) {
        // Get current price
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        // Calculate sale price from percentage if provided
        if ($sale_percentage !== null && $sale_percentage > 0) {
            $sale_price = round($product['price'] * (1 - $sale_percentage / 100), 2);
        }
        
        // Validate sale price is lower than original
        if ($sale_price !== null && $sale_price >= $product['price']) {
            echo json_encode(['success' => false, 'message' => 'Sale price must be lower than original price']);
            exit;
        }
        
        // Validate percentage
        if ($sale_percentage !== null && ($sale_percentage <= 0 || $sale_percentage > 100)) {
            echo json_encode(['success' => false, 'message' => 'Percentage must be between 1 and 100']);
            exit;
        }
    } else {
        // Turning off sale
        $sale_price = null;
        $sale_percentage = null;
        $sale_end_date = null;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET is_on_sale = ?, sale_price = ?, sale_percentage = ?, sale_end_date = ? WHERE id = ?");
        $stmt->execute([$is_on_sale, $sale_price, $sale_percentage, $sale_end_date, $product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Sale price updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} elseif ($action === 'bulk') {
    // Bulk update sale prices
    $updates = json_decode($_POST['updates'] ?? '[]', true);
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No updates provided']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        foreach ($updates as $update) {
            $product_id = (int)$update['product_id'];
            $is_on_sale = isset($update['is_on_sale']) ? (int)$update['is_on_sale'] : 0;
            
            if ($product_id <= 0) continue;
            
            if ($is_on_sale) {
                $sale_price = isset($update['sale_price']) && $update['sale_price'] !== '' ? (float)$update['sale_price'] : null;
                $sale_percentage = isset($update['sale_percentage']) && $update['sale_percentage'] !== '' ? (int)$update['sale_percentage'] : null;
                $sale_end_date = isset($update['sale_end_date']) && $update['sale_end_date'] !== '' ? $update['sale_end_date'] : null;
                
                // Get current price for percentage calculation
                $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product && $sale_percentage !== null && $sale_percentage > 0) {
                    $sale_price = round($product['price'] * (1 - $sale_percentage / 100), 2);
                }
                
                $stmt = $pdo->prepare("UPDATE products SET is_on_sale = 1, sale_price = ?, sale_percentage = ?, sale_end_date = ? WHERE id = ?");
                $stmt->execute([$sale_price, $sale_percentage, $sale_end_date, $product_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET is_on_sale = 0, sale_price = NULL, sale_percentage = NULL, sale_end_date = NULL WHERE id = ?");
                $stmt->execute([$product_id]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Bulk update completed']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Bulk update failed: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
