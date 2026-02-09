<?php
header('Content-Type: application/json');
// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../app/config/database.php';

// Get filter parameters from URL
$selected_genders = [];
if (isset($_GET['gender'])) {
    if (is_array($_GET['gender'])) {
        $selected_genders = $_GET['gender'];
    } elseif (is_string($_GET['gender']) && !empty($_GET['gender'])) {
        $selected_genders = [$_GET['gender']];
    }
}

$selected_categories = [];
if (isset($_GET['category'])) {
    if (is_array($_GET['category'])) {
        $selected_categories = $_GET['category'];
    } elseif (is_string($_GET['category']) && !empty($_GET['category'])) {
        $selected_categories = [$_GET['category']];
    }
}

$selected_prices = isset($_GET['price']) && is_array($_GET['price']) ? $_GET['price'] : [];

$selected_materials = [];
if (isset($_GET['material'])) {
    if (is_array($_GET['material'])) {
        $selected_materials = $_GET['material'];
    } elseif (is_string($_GET['material']) && !empty($_GET['material'])) {
        $selected_materials = [$_GET['material']];
    }
}

$selected_colors = [];
if (isset($_GET['color'])) {
    if (is_array($_GET['color'])) {
        $selected_colors = $_GET['color'];
    } elseif (is_string($_GET['color']) && !empty($_GET['color'])) {
        $selected_colors = [$_GET['color']];
    }
}

$selected_adornments = [];
if (isset($_GET['adornment'])) {
    if (is_array($_GET['adornment'])) {
        $selected_adornments = $_GET['adornment'];
    } elseif (is_string($_GET['adornment']) && !empty($_GET['adornment'])) {
        $selected_adornments = [$_GET['adornment']];
    }
}

$is_new_filter = isset($_GET['new']) && $_GET['new'] == '1';
$current_sort = $_GET['sort'] ?? '';

try {
    // Base query with all necessary joins
    $sql = "SELECT p.*, 
                   GROUP_CONCAT(DISTINCT c.name) as categories,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image, 
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 0 ORDER BY sort_order ASC LIMIT 1) as hover_image 
            FROM products p
            LEFT JOIN product_categories pc ON p.id = pc.product_id
            LEFT JOIN categories c ON pc.category_id = c.id";

    $where = ["p.is_active = 1"];
    $params = [];

    if ($is_new_filter) {
        $where[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    // Handle gender filter
    if (!empty($selected_genders)) {
        $gender_conditions = [];
        foreach ($selected_genders as $gender) {
            if (strtolower($gender) == 'men') {
                $gender_conditions[] = "(p.gender IN ('M', 'U'))";
            } elseif (strtolower($gender) == 'women') {
                $gender_conditions[] = "(p.gender IN ('F', 'W', 'U'))";
            }
        }
        if (!empty($gender_conditions)) {
            $where[] = '(' . implode(' OR ', $gender_conditions) . ')';
        }
    }

    // Handle category filter
    if (!empty($selected_categories)) {
        $category_placeholders = implode(',', array_fill(0, count($selected_categories), '?'));
        $where[] = "p.id IN (SELECT product_id FROM product_categories pc_cat JOIN categories c_cat ON pc_cat.category_id = c_cat.id WHERE LOWER(c_cat.name) IN (" . $category_placeholders . "))";
        foreach ($selected_categories as $category) {
            $params[] = strtolower($category);
        }
    }

    // Handle price filters
    if (!empty($selected_prices)) {
        $price_conditions = [];
        foreach ($selected_prices as $price_range) {
            list($min, $max) = explode('-', $price_range);
            $price_conditions[] = "(p.price >= ? AND p.price <= ?)";
            $params[] = (float)$min;
            $params[] = (float)$max;
        }
        if (!empty($price_conditions)) {
            $where[] = '(' . implode(' OR ', $price_conditions) . ')';
        }
    }

    // Handle material filter
    if (!empty($selected_materials)) {
        $material_placeholders = implode(',', array_fill(0, count($selected_materials), '?'));
        $where[] = "p.id IN (SELECT product_id FROM product_materials pm_mat JOIN materials m_mat ON pm_mat.material_id = m_mat.id WHERE LOWER(m_mat.name) IN (" . $material_placeholders . "))";
        foreach ($selected_materials as $material) {
            $params[] = strtolower($material);
        }
    }

    // Handle color filter
    if (!empty($selected_colors)) {
        $color_placeholders = implode(',', array_fill(0, count($selected_colors), '?'));
        $where[] = "p.id IN (SELECT product_id FROM product_colors pc_col JOIN colors c_col ON pc_col.color_id = c_col.id WHERE LOWER(c_col.name) IN (" . $color_placeholders . "))";
        foreach ($selected_colors as $color) {
            $params[] = strtolower($color);
        }
    }

    // Handle adornment filter
    if (!empty($selected_adornments)) {
        $adornment_placeholders = implode(',', array_fill(0, count($selected_adornments), '?'));
        $where[] = "p.id IN (SELECT product_id FROM product_adornments pa_ador JOIN adornments a_ador ON pa_ador.adornment_id = a_ador.id WHERE LOWER(a_ador.name) IN (" . $adornment_placeholders . "))";
        foreach ($selected_adornments as $adornment) {
            $params[] = strtolower($adornment);
        }
    }

    // Combine WHERE clauses
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    // Add GROUP BY to handle duplicate rows from joins
    $sql .= " GROUP BY p.id";

    // Handle sorting
    if ($current_sort === 'price-low') {
        $sql .= " ORDER BY p.price ASC";
    } elseif ($current_sort === 'price-high') {
        $sql .= " ORDER BY p.price DESC";
    } else {
        $sql .= " ORDER BY p.created_at DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format products for JSON response
    $formatted_products = [];
    foreach ($products as $product) {
        $main_image = $product['main_image'];
        $hover_image = $product['hover_image'];
        
        // Ensure image URLs are absolute paths (start with /)
        if ($main_image && strpos($main_image, '/') !== 0) {
            $main_image = '/' . $main_image;
        }
        if ($hover_image && strpos($hover_image, '/') !== 0) {
            $hover_image = '/' . $hover_image;
        }
        
        $formatted_products[] = [
            'id' => $product['id'],
            'name' => htmlspecialchars($product['name']),
            'slug' => $product['slug'],
            'price' => (float)$product['price'],
            'is_on_sale' => (bool)$product['is_on_sale'],
            'sale_price' => $product['sale_price'] ? (float)$product['sale_price'] : null,
            'sale_percentage' => $product['sale_percentage'] ? (int)$product['sale_percentage'] : null,
            'sale_end_date' => $product['sale_end_date'],
            'short_description' => htmlspecialchars(substr($product['short_description'] ?: $product['description'] ?: '', 0, 50)),
            'weight' => $product['weight'],
            'stock_quantity' => (int)$product['stock_quantity'],
            'main_image' => $main_image,
            'hover_image' => $hover_image
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($products),
        'products' => $formatted_products
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
    exit;
}
