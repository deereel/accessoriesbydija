<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Orders Management';
$active_nav = 'orders';

require_once '../app/config/database.php';

// Build query based on filters
$where = [];
$params = [];

if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $where[] = "(o.order_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

if (!empty($_GET['status'])) {
    $where[] = "o.status = ?";
    $params[] = $_GET['status'];
}

$sql = "SELECT o.*, c.email as customer_email, c.first_name, c.last_name 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY o.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '_layout_header.php'; ?>
<style>
    .filter-badge { background: #e9ecef; color: #495057; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-right: 5px; }
</style>

<div class="card">
    <div class="card-header">
        <i class="fas fa-shopping-cart"></i> Orders Management
        <a href="custom-orders.php" class="btn btn-sm" style="float:right;">Custom Orders</a>
    </div>
    <div class="card-body">
        <div style="overflow-x:auto;">
            <form method="GET" style="margin-bottom:16px; display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" placeholder="Search by Order #, Customer..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <select name="status" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo ($_GET['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo ($_GET['status'] ?? '') === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo ($_GET['status'] ?? '') === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn">Filter</button>
                <a href="orders.php" class="btn" style="background:#6c757d;">Clear</a>
            </form>

            <div style="margin-bottom: 1rem;">
                <?php if (!empty($_GET['search'])): ?>
                    <span class="filter-badge">Search: "<?php echo htmlspecialchars($_GET['search']); ?>"</span>
                <?php endif; ?>
                <?php if (!empty($_GET['status'])): ?>
                    <span class="filter-badge">Status: <?php echo htmlspecialchars(ucfirst($_GET['status'])); ?></span>
                <?php endif; ?>
            </div>

            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Order #</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Customer</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Status</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Total</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#666;">No orders found matching your criteria.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="order-row" data-order-id="<?php echo (int)$order['id']; ?>" style="border-bottom:1px solid #eee; cursor:pointer;">
                            <td style="padding:10px;"><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td style="padding:10px;">
                                <?php 
                                if ($order['first_name']) {
                                    echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                } else {
                                    echo 'Guest';
                                }
                                ?>
                            </td>
                            <td style="padding:10px;">
                                <?php
                                $status = htmlspecialchars($order['status']);
                                $status_colors = [
                                    'delivered' => ['bg' => '#d4edda', 'text' => '#155724'],
                                    'processing' => ['bg' => '#d1ecf1', 'text' => '#0c5460'],
                                    'shipped' => ['bg' => '#cce5ff', 'text' => '#004085'],
                                    'pending' => ['bg' => '#fff3cd', 'text' => '#856404'],
                                    'cancelled' => ['bg' => '#f8d7da', 'text' => '#721c24']
                                ];
                                $color_set = $status_colors[$status] ?? ['bg' => '#e2e3e5', 'text' => '#383d41'];
                                ?>
                                <span style="background:<?php echo $color_set['bg']; ?>; color:<?php echo $color_set['text']; ?>; padding:4px 8px; border-radius:4px; font-size:12px; text-transform:capitalize;">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td style="padding:10px;"><strong>£<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <td style="padding:10px;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Make rows clickable
        document.querySelectorAll('.order-row').forEach(row => {
            row.addEventListener('click', () => {
                const id = row.getAttribute('data-order-id');
                window.location.href = 'order-detail.php?id=' + id;
            });
        });
    </script>
</div>

