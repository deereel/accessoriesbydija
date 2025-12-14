<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Orders Management';
$active_nav = 'orders';

require_once '../config/database.php';

// Fetch orders
$stmt = $pdo->query("SELECT o.*, c.email as customer_email, c.first_name, c.last_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id ORDER BY o.created_at DESC LIMIT 50");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '_layout_header.php'; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-shopping-cart"></i> Orders Management
    </div>
    <div class="card-body">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Order #</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Customer</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Status</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Total</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Date</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr style="border-bottom:1px solid #eee;">
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
                                <span style="background:<?php echo $order['status']=='delivered'?'#d4edda':'#fff3cd'; ?>; color:<?php echo $order['status']=='delivered'?'#155724':'#856404'; ?>; padding:4px 8px; border-radius:4px; font-size:12px;">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td style="padding:10px;"><strong>£<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <td style="padding:10px;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td style="padding:10px;">
                                <a href="#" class="btn" style="font-size:12px;">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '_layout_footer.php'; ?>
