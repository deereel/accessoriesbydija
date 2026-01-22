<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Customer Profile';
$active_nav = 'customers';

require_once '../config/database.php';

$customer_id = intval($_GET['id'] ?? 0);
if (!$customer_id) {
    header('Location: customers.php');
    exit;
}

// Fetch customer details
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header('Location: customers.php');
    exit;
}

// Fetch customer orders
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.customer_id = ? GROUP BY o.id ORDER BY o.created_at DESC");
    $stmt->execute([$customer_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Orders table might not exist or query error
    $orders = [];
}

// Fetch customer addresses
$addresses = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$customer_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Addresses table might not exist
    $addresses = [];
}

// Fetch wishlist
$wishlist = [];
try {
    $stmt = $pdo->prepare("SELECT w.*, p.name, p.price FROM wishlists w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.created_at DESC");
    $stmt->execute([$customer_id]);
    $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Wishlist table might not exist or join error
    $wishlist = [];
}

include '_layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user"></i> Customer Profile: <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
        <a href="customers.php" class="btn" style="float:right;">Back to Customers</a>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Customer Details -->
            <div>
                <h3>Personal Information</h3>
                <table style="width:100%; border-collapse:collapse;">
                    <tr><td style="padding:8px; border-bottom:1px solid #eee;"><strong>Name:</strong></td><td style="padding:8px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td></tr>
                    <tr><td style="padding:8px; border-bottom:1px solid #eee;"><strong>Email:</strong></td><td style="padding:8px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($customer['email']); ?></td></tr>
                    <tr><td style="padding:8px; border-bottom:1px solid #eee;"><strong>Phone:</strong></td><td style="padding:8px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td></tr>
                    <tr><td style="padding:8px; border-bottom:1px solid #eee;"><strong>Date of Birth:</strong></td><td style="padding:8px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($customer['date_of_birth'] ?? 'N/A'); ?></td></tr>
                    <tr><td style="padding:8px; border-bottom:1px solid #eee;"><strong>Gender:</strong></td><td style="padding:8px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($customer['gender'] ?? 'N/A'); ?></td></tr>
                    <tr><td style="padding:8px; border-bottom:1px solid #eee;"><strong>Member Since:</strong></td><td style="padding:8px; border-bottom:1px solid #eee;"><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td></tr>
                    <tr><td style="padding:8px; border-bottom:1px solid #eee;"><strong>Last Login:</strong></td><td style="padding:8px; border-bottom:1px solid #eee;"><?php echo $customer['last_login'] ? date('M d, Y H:i', strtotime($customer['last_login'])) : 'Never'; ?></td></tr>
                </table>
            </div>

            <!-- Statistics -->
            <div>
                <h3>Statistics</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div style="background:#f5f5f5; padding:15px; border-radius:8px; text-align:center;">
                        <div style="font-size:24px; font-weight:bold; color:#c487a5;"><?php echo count($orders); ?></div>
                        <div>Total Orders</div>
                    </div>
                    <div style="background:#f5f5f5; padding:15px; border-radius:8px; text-align:center;">
                        <div style="font-size:24px; font-weight:bold; color:#c487a5;"><?php echo count($addresses); ?></div>
                        <div>Saved Addresses</div>
                    </div>
                    <div style="background:#f5f5f5; padding:15px; border-radius:8px; text-align:center;">
                        <div style="font-size:24px; font-weight:bold; color:#c487a5;"><?php echo count($wishlist); ?></div>
                        <div>Wishlist Items</div>
                    </div>
                    <div style="background:#f5f5f5; padding:15px; border-radius:8px; text-align:center;">
                        <div style="font-size:24px; font-weight:bold; color:#c487a5;">£<?php echo number_format(array_sum(array_column($orders, 'total_amount')), 2); ?></div>
                        <div>Total Spent</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orders History -->
<div class="card">
    <div class="card-header"><i class="fas fa-shopping-cart"></i> Order History</div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Order #</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Date</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Items</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Total</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Status</th>
                            <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:10px;"><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td style="padding:10px;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td style="padding:10px;"><?php echo intval($order['item_count']); ?> items</td>
                                <td style="padding:10px;"><strong>£<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td style="padding:10px;">
                                    <span style="padding:4px 8px; border-radius:12px; background:<?php
                                        $colors = ['completed'=>'#d4edda', 'processing'=>'#fff3cd', 'shipped'=>'#d1ecf1', 'pending'=>'#fff3cd', 'cancelled'=>'#f8d7da'];
                                        echo $colors[$order['status']] ?? '#f0f0f0';
                                    ?>; color:<?php
                                        $text_colors = ['completed'=>'#155724', 'processing'=>'#856404', 'shipped'=>'#0c5460', 'pending'=>'#856404', 'cancelled'=>'#721c24'];
                                        echo $text_colors[$order['status']] ?? '#666';
                                    ?>; font-size:12px;">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td style="padding:10px;"><a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn" style="font-size:12px;">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Addresses -->
<div class="card">
    <div class="card-header"><i class="fas fa-map-marker-alt"></i> Saved Addresses</div>
    <div class="card-body">
        <?php if (empty($addresses)): ?>
            <p>No addresses found.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                <?php foreach ($addresses as $address): ?>
                    <div style="border: 2px solid <?php echo $address['is_default'] ? '#c487a5' : '#eee'; ?>; border-radius: 8px; padding: 15px; position: relative;">
                        <?php if ($address['is_default']): ?>
                            <div style="position:absolute; top:10px; right:10px; background:#c487a5; color:white; padding:4px 8px; border-radius:12px; font-size:12px;">Default</div>
                        <?php endif; ?>
                        <div style="font-weight:600; margin-bottom:8px;"><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></div>
                        <div style="color:#666; line-height:1.5;">
                            <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                            <?php if ($address['address_line_2']): echo htmlspecialchars($address['address_line_2']) . '<br>'; endif; ?>
                            <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?><br>
                            <?php echo htmlspecialchars($address['country']); ?><br>
                            Phone: <?php echo htmlspecialchars($address['phone']); ?>
                        </div>
                        <div style="margin-top:10px; font-size:14px; color:#666;"><em><?php echo ucfirst($address['type']); ?> Address</em></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Wishlist -->
<div class="card">
    <div class="card-header"><i class="fas fa-heart"></i> Wishlist</div>
    <div class="card-body">
        <?php if (empty($wishlist)): ?>
            <p>No wishlist items.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                <?php foreach ($wishlist as $item): ?>
                    <div style="border:1px solid #eee; border-radius:8px; padding:15px; text-align:center;">
                        <div style="font-weight:600; margin-bottom:8px;"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div style="color:#c487a5; font-weight:600;">£<?php echo number_format($item['price'], 2); ?></div>
                        <div style="color:#666; font-size:14px; margin-top:5px;">Added <?php echo date('M d, Y', strtotime($item['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '_layout_footer.php'; ?>