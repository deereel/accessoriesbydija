<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Order Details';
$active_nav = 'orders';

require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int) $_GET['id'];

// Fetch order + customer basic info
$stmt = $pdo->prepare(
    "SELECT o.*,
            c.email AS customer_email,
            c.first_name,
            c.last_name,
            ca.type AS address_type,
            ca.first_name AS addr_first_name,
            ca.last_name AS addr_last_name,
            ca.company,
            ca.address_line_1,
            ca.address_line_2,
            ca.city,
            ca.state,
            ca.postal_code,
            ca.country
     FROM orders o
     LEFT JOIN customers c ON o.customer_id = c.id
     LEFT JOIN customer_addresses ca ON o.address_id = ca.id
     WHERE o.id = ?"
);
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Fetch items
$itemsStmt = $pdo->prepare(
    "SELECT oi.*, p.slug AS product_slug, oi.product_name, p.description as product_description,
     COALESCE(
        (SELECT image_url FROM product_images WHERE variant_id = oi.variation_id LIMIT 1),
        (SELECT image_url FROM product_images WHERE product_id = oi.product_id AND is_primary = 1 LIMIT 1)
     ) as product_image
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC"
);
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$order['items'] = $items;

include '_layout_header.php';
?>

<style>
    .order-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .order-header {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .order-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    .card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .card-header {
        font-weight: bold;
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        text-transform: capitalize;
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
    }
    .items-table th, .items-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }
    .items-table th {
        background: #f5f5f5;
    }
    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
    .product-link {
        color: #007bff;
        text-decoration: none;
    }
    .product-link:hover {
        text-decoration: underline;
    }
    .product-description {
        color: #666;
        font-size: 12px;
        margin-top: 5px;
    }
    .form-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
    }
    .form-group {
        flex: 1;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .form-group select, .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .btn {
        background: #007bff;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn:hover {
        background: #0056b3;
    }
    .btn-success {
        background: #28a745;
    }
    .btn-success:hover {
        background: #218838;
    }
    .alert {
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
        display: none;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        border-radius: 8px;
        position: relative;
    }
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .close:hover {
        color: black;
    }
    .product-link {
        color: #007bff;
        cursor: pointer;
        text-decoration: none;
    }
    .product-link:hover {
        text-decoration: underline;
    }
</style>

<div class="order-detail-container">
    <div class="order-header">
        <div>
            <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
            <p>Placed on <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
        </div>
        <div>
            <a href="orders.php" class="btn">← Back to Orders</a>
        </div>
    </div>

    <div id="alert" class="alert"></div>

    <div class="order-info">
        <div class="card">
            <div class="card-header">Summary</div>
            <div class="card-body">
                <div><strong>Order #:</strong> <?php echo htmlspecialchars($order['order_number']); ?></div>
                <div><strong>Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></div>
                <div><strong>Status:</strong>
                    <span class="status-badge" style="background: <?php
                        $status_colors = [
                            'delivered' => '#d4edda',
                            'processing' => '#d1ecf1',
                            'shipped' => '#cce5ff',
                            'pending' => '#fff3cd',
                            'cancelled' => '#f8d7da'
                        ];
                        echo $status_colors[$order['status']] ?? '#e2e3e5';
                    ?>; color: <?php
                        $text_colors = [
                            'delivered' => '#155724',
                            'processing' => '#0c5460',
                            'shipped' => '#004085',
                            'pending' => '#856404',
                            'cancelled' => '#721c24'
                        ];
                        echo $text_colors[$order['status']] ?? '#383d41';
                    ?>;">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                </div>
                <div><strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_status']); ?> (<?php echo htmlspecialchars($order['payment_method'] ?: '-'); ?>)</div>
                <hr>
                <div><strong>Subtotal:</strong> £<?php echo number_format($order['total_amount'] - ($order['shipping_amount'] ?: 0) + ($order['discount_amount'] ?: 0), 2); ?></div>
                <div><strong>Shipping:</strong> £<?php echo number_format($order['shipping_amount'] ?: 0, 2); ?></div>
                <div><strong>Discount:</strong> £<?php echo number_format($order['discount_amount'] ?: 0, 2); ?></div>
                <div><strong>Total:</strong> <strong>£<?php echo number_format($order['total_amount'], 2); ?></strong></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Customer / Shipping</div>
            <div class="card-body">
                <div><strong>Customer:</strong> <?php
                    $customerName = ($order['first_name'] ? ($order['first_name'] . ' ' . ($order['last_name'] ?: '')) : ($order['contact_name'] ?: 'Guest'));
                    echo htmlspecialchars($customerName);
                ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email'] ?: $order['email'] ?: '-'); ?></div>
                <div><strong>Phone:</strong> <?php echo htmlspecialchars($order['contact_phone'] ?: '-'); ?></div>
                <hr>
                <div><strong>Shipping Address:</strong></div>
                <?php if ($order['address_line_1']): ?>
                    <div><?php echo htmlspecialchars($order['addr_first_name'] ?: ''); ?> <?php echo htmlspecialchars($order['addr_last_name'] ?: ''); ?></div>
                    <?php if ($order['company']): ?><div><?php echo htmlspecialchars($order['company']); ?></div><?php endif; ?>
                    <div><?php echo htmlspecialchars($order['address_line_1']); ?></div>
                    <?php if ($order['address_line_2']): ?><div><?php echo htmlspecialchars($order['address_line_2']); ?></div><?php endif; ?>
                    <div><?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['postal_code']); ?></div>
                    <div><?php echo htmlspecialchars($order['country']); ?></div>
                <?php else: ?>
                    <div style="color:#666; font-size:12px;">No saved address linked to this order (guest address may be stored in Notes).</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Items</div>
        <div class="card-body">
            <?php if (empty($items)): ?>
                <div style="color:#666;">No items found.</div>
            <?php else: ?>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th style="text-align:right;">Qty</th>
                            <th style="text-align:right;">Unit</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['product_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="product-link" data-item='<?php echo htmlspecialchars(json_encode($item)); ?>' onclick="openProductModal(this)">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                        <?php if ($item['variation_tag'] || $item['material_name'] || $item['color'] || $item['adornment'] || $item['size']): ?>
                                            (<?php
                                            $vars = [];
                                            if ($item['variation_tag']) $vars[] = $item['variation_tag'];
                                            if ($item['material_name']) $vars[] = $item['material_name'];
                                            if ($item['color']) $vars[] = $item['color'];
                                            if ($item['adornment']) $vars[] = $item['adornment'];
                                            if ($item['size']) $vars[] = $item['size'];
                                            echo htmlspecialchars(implode(', ', $vars));
                                            ?>)
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($item['product_description']): ?>
                                        <div class="product-description"><?php echo htmlspecialchars($item['product_description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['product_sku']); ?></td>
                                <td style="text-align:right;"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td style="text-align:right;">£<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td style="text-align:right;">£<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Actions</div>
        <div class="card-body">
            <form id="orderUpdateForm" onsubmit="return submitOrderUpdate(event)">
                <input type="hidden" id="order_id" name="order_id" value="<?php echo (int)$order['id']; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Order Status</label>
                        <select id="status" name="status">
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payment_status">Payment Status</label>
                        <select id="payment_status" name="payment_status">
                            <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Internal notes / shipping notes..."><?php echo htmlspecialchars($order['notes'] ?: ''); ?></textarea>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; align-items:center;">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeProductModal()">&times;</span>
            <div id="productModalContent">
                <div style="text-align: center; margin-bottom: 20px;">
                    <img id="productModalImage" src="" alt="" style="max-width: 100%; max-height: 400px; object-fit: contain;">
                </div>
                <h3 id="productModalName"></h3>
                <div id="productModalVariations" style="margin: 10px 0; color: #666;"></div>
                <div id="productModalDescription"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function showAlert(type, message) {
        const el = document.getElementById('alert');
        el.style.display = 'block';
        el.className = 'alert alert-' + type;
        el.textContent = message;
        setTimeout(() => el.style.display = 'none', 5000);
    }

    function submitOrderUpdate(event) {
        event.preventDefault();
        const form = event.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const formData = new FormData(form);

        fetch('update_order.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Order updated successfully!');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('error', data.error || 'Failed to update order.');
            }
        }).catch(err => {
            showAlert('error', 'An unexpected error occurred.');
            console.error(err);
        }).finally(() => {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
        });

        return false;
    }

    function openProductModal(el) {
        const item = JSON.parse(el.dataset.item);
        console.log('Product item data:', item);
        console.log('Product image URL:', item.product_image);
        console.log('Variation tag:', item.variation_tag);
        document.getElementById('productModalImage').src = '../' + (item.product_image || 'assets/images/placeholder.jpg');
        document.getElementById('productModalImage').alt = item.product_name;
        document.getElementById('productModalName').textContent = item.product_name;
        const vars = [];
        if (item.variation_tag) vars.push(item.variation_tag);
        if (item.material_name) vars.push(item.material_name);
        if (item.color) vars.push(item.color);
        if (item.adornment) vars.push(item.adornment);
        if (item.size) vars.push(item.size);
        document.getElementById('productModalVariations').textContent = vars.join(', ') || 'No variations';
        document.getElementById('productModalDescription').textContent = item.product_description || 'No description available.';
        document.getElementById('productModal').style.display = 'block';
    }

    function closeProductModal() {
        document.getElementById('productModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('productModal');
        if (event.target === modal) {
            closeProductModal();
        }
    });
</script>

<?php include '_layout_footer.php'; ?>
