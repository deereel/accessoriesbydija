<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Custom Orders Management';
$active_nav = 'orders';

require_once '../config/database.php';

// Build query based on filters
$where = [];
$params = [];

if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $where[] = "(name LIKE ? OR email LIKE ? OR jewelry_type LIKE ? OR description LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

if (!empty($_GET['status'])) {
    $where[] = "status = ?";
    $params[] = $_GET['status'];
}

$sql = "SELECT * FROM custom_requests";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$custom_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '_layout_header.php'; ?>
<style>
    .filter-badge { background: #e9ecef; color: #495057; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-right: 5px; }
</style>

<div class="card">
    <div class="card-header">
        <i class="fas fa-gem"></i> Custom Orders Management
        <a href="orders.php" class="btn btn-sm" style="float:right; margin-left:10px;">Regular Orders</a>
    </div>
    <div class="card-body">
        <div style="overflow-x:auto;">
            <form method="GET" style="margin-bottom:16px; display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" placeholder="Search by Name, Email, Type..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <select name="status" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">All Statuses</option>
                    <option value="new" <?php echo ($_GET['status'] ?? '') === 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="in_progress" <?php echo ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn">Filter</button>
                <a href="custom-orders.php" class="btn" style="background:#6c757d;">Clear</a>
            </form>

            <div style="margin-bottom: 1rem;">
                <?php if (!empty($_GET['search'])): ?>
                    <span class="filter-badge">Search: "<?php echo htmlspecialchars($_GET['search']); ?>"</span>
                <?php endif; ?>
                <?php if (!empty($_GET['status'])): ?>
                    <span class="filter-badge">Status: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $_GET['status']))); ?></span>
                <?php endif; ?>
            </div>

            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Customer</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Jewelry Type</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Budget</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Status</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($custom_orders)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#666;">No custom orders found matching your criteria.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($custom_orders as $order): ?>
                        <tr class="custom-order-row" data-order-id="<?php echo (int)$order['id']; ?>" style="border-bottom:1px solid #eee; cursor:pointer;">
                            <td style="padding:10px;">
                                <strong><?php echo htmlspecialchars($order['name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($order['email']); ?></small>
                            </td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($order['jewelry_type']); ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($order['budget_range']); ?></td>
                            <td style="padding:10px;">
                                <?php
                                $status = htmlspecialchars($order['status']);
                                $status_colors = [
                                    'new' => ['bg' => '#cce5ff', 'text' => '#004085'],
                                    'in_progress' => ['bg' => '#d1ecf1', 'text' => '#0c5460'],
                                    'completed' => ['bg' => '#d4edda', 'text' => '#155724'],
                                    'cancelled' => ['bg' => '#f8d7da', 'text' => '#721c24']
                                ];
                                $color_set = $status_colors[$status] ?? ['bg' => '#e2e3e5', 'text' => '#383d41'];
                                ?>
                                <span style="background:<?php echo $color_set['bg']; ?>; color:<?php echo $color_set['text']; ?>; padding:4px 8px; border-radius:4px; font-size:12px; text-transform:capitalize;">
                                    <?php echo str_replace('_', ' ', $status); ?>
                                </span>
                            </td>
                            <td style="padding:10px;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Make rows clickable
        document.querySelectorAll('.custom-order-row').forEach(row => {
            row.addEventListener('click', () => {
                const id = row.getAttribute('data-order-id');
                window.location.href = 'custom-order-detail.php?id=' + id;
            });
        });
    </script>
</div>

<?php include '_layout_footer.php'; ?>