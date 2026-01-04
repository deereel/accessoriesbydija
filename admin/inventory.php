<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Inventory Management';
$active_nav = 'inventory';

require_once '../config/database.php';

// --- Data Fetching & Filtering ---

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$where = ["1=1"];
$params = [];

if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    array_push($params, $searchTerm, $searchTerm);
}

if (!empty($_GET['stock_status'])) {
    if ($_GET['stock_status'] === 'in_stock') {
        $where[] = "p.stock_quantity > 10";
    } elseif ($_GET['stock_status'] === 'low_stock') {
        $where[] = "p.stock_quantity > 0 AND p.stock_quantity <= 10";
    } elseif ($_GET['stock_status'] === 'out_of_stock') {
        $where[] = "p.stock_quantity <= 0";
    }
}

$where_clause = implode(" AND ", $where);

// Main Query for Products
$sql = "SELECT
            p.id, p.name, p.sku, p.price, p.stock_quantity, p.is_active,
            (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image,
            COALESCE((SELECT SUM(oi.quantity) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)), 0) as units_sold_30d,
            COALESCE((SELECT SUM(pv.stock_quantity) FROM product_variations pv WHERE pv.product_id = p.id), 0) as variation_stock,
            COALESCE((SELECT SUM(vs.stock_quantity) FROM variation_sizes vs JOIN product_variations pv ON vs.variation_id = pv.id WHERE pv.product_id = p.id), 0) as size_stock
        FROM products p
        WHERE $where_clause
        ORDER BY p.stock_quantity ASC, p.name ASC
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);

// Bind filter parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}
// Bind LIMIT and OFFSET as integers
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total Count for Pagination
$count_sql = "SELECT COUNT(*) FROM products p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_products_count / $limit);

// Dashboard Stats
$stats_sql = "SELECT 
                SUM(stock_quantity * price) as total_value,
                SUM(stock_quantity) as total_units,
                SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock_count
             FROM products";
$stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

// Fetch recent inventory logs
try {
    $log_sql = "
        SELECT 
            il.*,
            p.name as product_name,
            p.sku as product_sku,
            au.username as admin_username
        FROM inventory_logs il
        JOIN products p ON il.product_id = p.id
        LEFT JOIN admin_users au ON il.user_id = au.id
        ORDER BY il.created_at DESC
        LIMIT 25
    ";
    $inventory_logs = $pdo->query($log_sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If the table doesn't exist yet, we can ignore the error for now.
    $inventory_logs = [];
}

?>

<?php include '_layout_header.php'; ?>

<style>
    .stat-card { background: var(--card); border-radius: 8px; padding: 16px; text-align: center; border: 1px solid var(--border); }
    .stat-card h4 { margin: 0 0 8px; color: #666; font-size: 14px; }
    .stat-card .stat-value { font-size: 24px; font-weight: 700; color: var(--accent); }

    .stock-status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
    .stock-in { background: #d4edda; color: #155724; }
    .stock-low { background: #fff3cd; color: #856404; }
    .stock-out { background: #f8d7da; color: #721c24; }

    .inline-stock-update { display: flex; align-items: center; gap: 5px; }
    .inline-stock-update input { width: 60px; text-align: center; padding: 4px; border: 1px solid #ccc; border-radius: 4px; }
    .inline-stock-update button { font-size: 11px; padding: 5px 8px; }
</style>

<!-- Dashboard Stats -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
    <div class="stat-card">
        <h4>Total Inventory Value</h4>
        <div class="stat-value">£<?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
    </div>
    <div class="stat-card">
        <h4>Total Units in Stock</h4>
        <div class="stat-value"><?php echo number_format($stats['total_units'] ?? 0); ?></div>
    </div>
    <div class="stat-card">
        <h4>Out of Stock Items</h4>
        <div class="stat-value"><?php echo number_format($stats['out_of_stock_count'] ?? 0); ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-boxes"></i> Inventory Status
    </div>
    <div class="card-body">
        <form method="GET" style="margin-bottom:16px; display:flex; gap:10px; align-items:center;">
            <input type="text" name="search" placeholder="Search by Name or SKU..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;">
            <select name="stock_status" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                <option value="">All Stock Statuses</option>
                <option value="in_stock" <?php echo ($_GET['stock_status'] ?? '') === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                <option value="low_stock" <?php echo ($_GET['stock_status'] ?? '') === 'low_stock' ? 'selected' : ''; ?>>Low Stock (≤10)</option>
                <option value="out_of_stock" <?php echo ($_GET['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
            </select>
            <button type="submit" class="btn">Filter</button>
            <a href="inventory.php" class="btn" style="background:#6c757d;">Clear</a>
        </form>

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:10px; text-align:left;">Product</th>
                        <th style="padding:10px; text-align:left;">SKU</th>
                        <th style="padding:10px; text-align:center;">Base Stock</th>
                        <th style="padding:10px; text-align:center;">Variant Stock</th>
                        <th style="padding:10px; text-align:center;">Size Stock</th>
                        <th style="padding:10px; text-align:center;">Total Stock</th>
                        <th style="padding:10px; text-align:center;">Units Sold (30d)</th>
                        <th style="padding:10px; text-align:left;">Update Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:20px; color:#666;">No products found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px; display:flex; align-items:center; gap:10px;">
                                <?php if (!empty($product['main_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:40px; height:40px; object-fit:cover; border-radius:4px;">
                                <?php else: ?>
                                    <div style="width:40px; height:40px; background:#f0f0f0; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:12px; color:#666;">Img</div>
                                <?php endif; ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <div style="font-size:12px; color:#666;">Price: £<?php echo number_format($product['price'], 2); ?></div>
                                </div>
                            </td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($product['sku']); ?></td>
                            <td style="padding:10px; text-align:center;">
                                <?php
                                    $stock = (int)$product['stock_quantity'];
                                    if ($stock > 10) {
                                        echo '<span class="stock-status-badge stock-in">' . $stock . '</span>';
                                    } elseif ($stock > 0) {
                                        echo '<span class="stock-status-badge stock-low">' . $stock . '</span>';
                                    } else {
                                        echo '<span class="stock-status-badge stock-out">0</span>';
                                    }
                                ?>
                            </td>
                            <td style="padding:10px; text-align:center;">
                                <?php
                                    $vstock = (int)$product['variation_stock'];
                                    if ($vstock > 10) {
                                        echo '<span class="stock-status-badge stock-in">' . $vstock . '</span>';
                                    } elseif ($vstock > 0) {
                                        echo '<span class="stock-status-badge stock-low">' . $vstock . '</span>';
                                    } else {
                                        echo '<span class="stock-status-badge stock-out">0</span>';
                                    }
                                ?>
                            </td>
                            <td style="padding:10px; text-align:center;">
                                <?php
                                    $sstock = (int)$product['size_stock'];
                                    if ($sstock > 10) {
                                        echo '<span class="stock-status-badge stock-in">' . $sstock . '</span>';
                                    } elseif ($sstock > 0) {
                                        echo '<span class="stock-status-badge stock-low">' . $sstock . '</span>';
                                    } else {
                                        echo '<span class="stock-status-badge stock-out">0</span>';
                                    }
                                ?>
                            </td>
                            <td style="padding:10px; text-align:center; font-weight:bold;">
                                <?php echo (int)$product['stock_quantity'] + (int)$product['variation_stock'] + (int)$product['size_stock']; ?>
                            </td>
                            <td style="padding:10px; text-align:center; font-weight:500;"><?php echo (int)$product['units_sold_30d']; ?></td>
                            <td style="padding:10px;">
                                <form class="inline-stock-update" onsubmit="return updateStock(event, <?php echo (int)$product['id']; ?>)">
                                    <input type="number" name="stock_quantity" placeholder="Enter new quantity" required>
                                    <button type="submit" class="btn btn-success">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top:20px; text-align:center;">
            <?php if ($total_pages > 1): ?>
                <?php
                    // Preserve existing query parameters
                    $query_params = $_GET;
                ?>
                <?php if ($page > 1): ?>
                    <?php $query_params['page'] = $page - 1; ?>
                    <a href="?<?php echo http_build_query($query_params); ?>" class="btn" style="margin-right:5px;">&laquo; Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php $query_params['page'] = $i; ?>
                    <a href="?<?php echo http_build_query($query_params); ?>" class="btn <?php echo ($i === $page) ? 'btn-success' : ''; ?>" style="margin-right:5px;"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <?php $query_params['page'] = $page + 1; ?>
                    <a href="?<?php echo http_build_query($query_params); ?>" class="btn">Next &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <i class="fas fa-history"></i> Recent Inventory Logs
    </div>
    <div class="card-body">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:10px; text-align:left;">Date</th>
                        <th style="padding:10px; text-align:left;">Product</th>
                        <th style="padding:10px; text-align:left;">User</th>
                        <th style="padding:10px; text-align:left;">Action</th>
                        <th style="padding:10px; text-align:center;">Change</th>
                        <th style="padding:10px; text-align:left;">Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory_logs)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#666;">No inventory logs found. Have you run the `create_inventory_log_table.php` script?</td></tr>
                    <?php else: ?>
                        <?php foreach ($inventory_logs as $log): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:10px; font-size:13px; color:#666; white-space:nowrap;"><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                <td style="padding:10px;">
                                    <strong><?php echo htmlspecialchars($log['product_name']); ?></strong>
                                    <div style="font-size:12px; color:#666;">SKU: <?php echo htmlspecialchars($log['product_sku']); ?></div>
                                </td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($log['admin_username'] ?? 'System'); ?></td>
                                <td style="padding:10px;">
                                    <span style="font-size:12px; font-weight:500; text-transform:uppercase;"><?php echo htmlspecialchars(str_replace('_', ' ', $log['action'])); ?></span>
                                    <div style="font-size:12px; color:#666;"><?php echo $log['old_quantity']; ?> &rarr; <?php echo $log['new_quantity']; ?></div>
                                </td>
                                <td style="padding:10px; text-align:center; font-weight:bold; color:<?php echo $log['quantity_change'] > 0 ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo ($log['quantity_change'] > 0 ? '+' : '') . $log['quantity_change']; ?>
                                </td>
                                <td style="padding:10px; font-size:13px; color:#666;"><?php echo htmlspecialchars($log['reason'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateStock(event, productId) {
    event.preventDefault();
    const form = event.target;
    const input = form.querySelector('input[name="stock_quantity"]');
    const button = form.querySelector('button[type="submit"]');
    const newStock = input.value;

    button.disabled = true;
    button.textContent = '...';

    const formData = new FormData();
    formData.append('action', 'update_stock');
    formData.append('product_id', productId);
    formData.append('stock_quantity', newStock);

    fetch('update_inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Briefly show success, then reload to reflect changes everywhere
            button.textContent = '✓';
            button.style.background = '#28a745';
            setTimeout(() => {
                location.reload();
            }, 800);
        } else {
            alert('Error updating stock: ' + (data.error || 'Unknown error'));
            button.disabled = false;
            button.textContent = 'Save';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An unexpected error occurred.');
        button.disabled = false;
        button.textContent = 'Save';
    });

    return false;
}
</script>

<?php include '_layout_footer.php'; ?>