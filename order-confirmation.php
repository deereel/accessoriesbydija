<?php
session_start();
require_once 'config/database.php';

$order_id = $_GET['order_id'] ?? $_SESSION['order_id'] ?? null;

if (!$order_id) {
    header('Location: index.php');
    exit;
}

try {
    // Fetch order with customer info
    $stmt = $pdo->prepare("SELECT o.*, c.first_name, c.last_name, c.email, a.full_name as address_name, a.address_line_1, a.city, a.postal_code
                           FROM orders o
                           LEFT JOIN customers c ON o.customer_id = c.id
                           LEFT JOIN customer_addresses a ON o.address_id = a.id
                           WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: index.php');
        exit;
    }

    // Fetch order items
    $stmt = $pdo->prepare("SELECT oi.*, p.name, p.sku
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

} catch (PDOException $e) {
    die('Database error');
}

$is_paid = $order['status'] !== 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Dija Accessories</title>
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .confirmation-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .confirmation-card { background: white; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .confirmation-header { background: #f8f8f8; padding: 2rem; text-align: center; border-bottom: 1px solid #eee; }
        .confirmation-header.success { background: linear-gradient(135deg, #d4edda, #c3e6cb); }
        .confirmation-header h1 { margin: 0; color: #155724; }
        .confirmation-header .icon { font-size: 3rem; margin-bottom: 1rem; }
        .confirmation-body { padding: 2rem; }
        
        .order-section { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #eee; }
        .order-section h3 { color: #333; margin-bottom: 1rem; }
        .order-section:last-child { border-bottom: none; }
        
        .order-details { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem; }
        @media (max-width: 600px) { .order-details { grid-template-columns: 1fr; } }
        
        .detail-item { }
        .detail-label { font-weight: 600; color: #666; font-size: 0.9rem; text-transform: uppercase; }
        .detail-value { color: #333; margin-top: 0.25rem; font-size: 1rem; }
        
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { padding: 1rem; text-align: left; border-bottom: 2px solid #eee; font-weight: 600; }
        .items-table td { padding: 1rem; border-bottom: 1px solid #eee; }
        .items-table tr:last-child td { border-bottom: none; }
        
        .order-summary { background: #f8f8f8; padding: 1.5rem; border-radius: 6px; margin-top: 1.5rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; }
        .summary-row.total { font-weight: 700; font-size: 1.2rem; color: #c487a5; border-top: 2px solid #ddd; padding-top: 0.75rem; margin-top: 0.75rem; }
        
        .action-buttons { display: flex; gap: 1rem; margin-top: 2rem; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 500; display: inline-block; cursor: pointer; border: none; }
        .btn-primary { background: #c487a5; color: white; }
        .btn-primary:hover { background: #a66889; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        
        .status-badge { display: inline-block; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="confirmation-header <?php echo $is_paid ? 'success' : ''; ?>">
                <div class="icon">
                    <?php if ($is_paid): ?>
                        <i class="fas fa-check-circle" style="color: #155724;"></i>
                    <?php else: ?>
                        <i class="fas fa-hourglass-end" style="color: #856404;"></i>
                    <?php endif; ?>
                </div>
                <h1>
                    <?php echo $is_paid ? 'Order Confirmed!' : 'Order Pending Payment'; ?>
                </h1>
                <p style="margin: 0.5rem 0 0; color: #666;">
                    <?php echo $is_paid ? 'Thank you for your purchase!' : 'Awaiting payment confirmation...'; ?>
                </p>
            </div>

            <div class="confirmation-body">
                <?php if ($is_paid): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Payment received! Your order is being processed.
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Your order is created but payment is still pending. Please complete payment to confirm your order.
                </div>
                <?php endif; ?>

                <!-- Order Number & Status -->
                <div class="order-section">
                    <h3>Order Information</h3>
                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Order Number</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?php echo date('M d, Y g:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="status-badge <?php echo $is_paid ? 'status-paid' : 'status-pending'; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="order-section">
                    <h3>Shipping Address</h3>
                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['address_name'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['email']); ?></div>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <div class="detail-label">Address</div>
                            <div class="detail-value">
                                <?php 
                                if ($order['address_line_1']) {
                                    echo htmlspecialchars($order['address_line_1']) . '<br>';
                                    echo htmlspecialchars($order['city'] . ', ' . $order['postal_code']);
                                } else {
                                    echo 'Not provided';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="order-section">
                    <h3>Order Items</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th style="text-align: right;">Quantity</th>
                                <th style="text-align: right;">Unit Price</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                <td style="text-align: right;"><?php echo intval($item['quantity']); ?></td>
                                <td style="text-align: right;">£<?php echo number_format($item['price'], 2); ?></td>
                                <td style="text-align: right;">£<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Order Summary -->
                <div class="order-section">
                    <h3>Order Summary</h3>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>£<?php echo number_format($order['total_amount'] + $order['discount_amount'] - $order['shipping_amount'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>£<?php echo number_format($order['shipping_amount'], 2); ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                        <div class="summary-row" style="color: #28a745;">
                            <span>Discount</span>
                            <span>-£<?php echo number_format($order['discount_amount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>£<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="action-buttons">
                    <a href="/" class="btn btn-primary">Continue Shopping</a>
                    <a href="account.php" class="btn btn-secondary">View Orders</a>
                </div>

                <!-- Additional Info -->
                <div style="margin-top: 2rem; padding: 1.5rem; background: #f8f8f8; border-radius: 6px;">
                    <h4 style="margin-top: 0;">What's Next?</h4>
                    <ul style="margin: 0.5rem 0; padding-left: 1.5rem; color: #666;">
                        <li>You will receive a confirmation email shortly</li>
                        <li>Your order will be processed and shipped within 2-3 business days</li>
                        <li>You can track your shipment using the tracking number sent via email</li>
                        <li>For questions, contact us at <a href="mailto:support@dija.com">support@dija.com</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
