<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Orders Management';
$active_nav = 'orders';

require_once '../config/database.php';

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
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#666;">No orders found matching your criteria.</td></tr>
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
                            <td style="padding:10px;">
                                <button type="button" class="btn" style="font-size:12px;" onclick="event.stopPropagation(); openOrderDetails(<?php echo (int)$order['id']; ?>)">View Details</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <span class="close" onclick="closeOrderModal()">&times;</span>
        <h2 style="margin-top:0;">Order Details</h2>

        <div id="orderModalAlert" style="display:none; margin: 10px 0; padding: 10px; border-radius: 6px;"></div>

        <div id="orderDetails" style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="card" style="margin:0; box-shadow:none;">
                <div class="card-header">Summary</div>
                <div class="card-body" id="orderSummary"></div>
            </div>

            <div class="card" style="margin:0; box-shadow:none;">
                <div class="card-header">Customer / Shipping</div>
                <div class="card-body" id="orderCustomer"></div>
            </div>

            <div class="card" style="margin:0; box-shadow:none; grid-column: 1 / -1;">
                <div class="card-header">Items</div>
                <div class="card-body" id="orderItems"></div>
            </div>

            <div class="card" style="margin:0; box-shadow:none; grid-column: 1 / -1;">
                <div class="card-header">Actions</div>
                <div class="card-body">
                    <div id="quickActions" style="margin-bottom:10px;"></div>
                    <form id="orderUpdateForm" onsubmit="return submitOrderUpdate(event)">
                        <input type="hidden" id="order_id" name="order_id" value="">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Order Status</label>
                                <select id="status" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="payment_status">Payment Status</label>
                                <select id="payment_status" name="payment_status">
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="failed">Failed</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="4" placeholder="Internal notes / shipping notes..."></textarea>
                        </div>

                        <div style="display:flex; gap:10px; justify-content:flex-end; align-items:center;">
                            <button type="button" class="btn" style="background:#6c757d;" onclick="closeOrderModal()">Close</button>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replaceAll('&', '&')
            .replaceAll('<', '<')
            .replaceAll('>', '>')
            .replaceAll('"', '"')
            .replaceAll("'", '&#039;');
    }

    function money(amount) {
        const n = parseFloat(amount || 0);
        return '£' + n.toFixed(2);
    }

    function showOrderAlert(type, message) {
        const el = document.getElementById('orderModalAlert');
        el.style.display = 'block';
        el.textContent = message;

        if (type === 'success') {
            el.style.background = '#d4edda';
            el.style.color = '#155724';
        } else {
            el.style.background = '#f8d7da';
            el.style.color = '#721c24';
        }
    }

    function clearOrderAlert() {
        const el = document.getElementById('orderModalAlert');
        el.style.display = 'none';
        el.textContent = '';
    }

    // Make rows clickable
    document.querySelectorAll('.order-row').forEach(row => {
        row.addEventListener('click', () => {
            const id = row.getAttribute('data-order-id');
            openOrderDetails(id);
        });
    });

    function openOrderDetails(orderId) {
        clearOrderAlert();
        document.getElementById('orderModal').style.display = 'block';

        // Reset containers
        document.getElementById('orderSummary').innerHTML = 'Loading...';
        document.getElementById('orderCustomer').innerHTML = '';
        document.getElementById('orderItems').innerHTML = '';
        document.getElementById('quickActions').innerHTML = '';

        fetch(`get_order.php?id=${encodeURIComponent(orderId)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    showOrderAlert('error', data.error || 'Could not load order');
                    return;
                }

                const o = data.order;
                document.getElementById('order_id').value = o.id;
                document.getElementById('status').value = o.status;
                document.getElementById('payment_status').value = o.payment_status;
                document.getElementById('notes').value = o.notes || '';

                const currentStatus = o.status;
                let quickButtons = [];
                if (currentStatus === 'pending') {
                    quickButtons.push('<button type="button" class="btn" onclick="quickUpdate(\'processing\')">Process Order</button>');
                }
                if (currentStatus === 'processing') {
                    quickButtons.push('<button type="button" class="btn" onclick="quickUpdate(\'shipped\')">Ship Order</button>');
                }
                if (currentStatus === 'shipped') {
                    quickButtons.push('<button type="button" class="btn" onclick="quickUpdate(\'delivered\')">Mark Delivered</button>');
                }
                if (currentStatus !== 'delivered' && currentStatus !== 'cancelled') {
                    quickButtons.push('<button type="button" class="btn" style="background:#dc3545;" onclick="quickUpdate(\'cancelled\')">Cancel Order</button>');
                }
                document.getElementById('quickActions').innerHTML = quickButtons.join(' ');

                const subtotal = parseFloat(o.total_amount || 0) - parseFloat(o.shipping_amount || 0) + parseFloat(o.discount_amount || 0);
                document.getElementById('orderSummary').innerHTML = `
                    <div><strong>Order #:</strong> ${escapeHtml(o.order_number)}</div>
                    <div><strong>Date:</strong> ${escapeHtml(o.created_at)}</div>
                    <div><strong>Status:</strong> ${escapeHtml(o.status)}</div>
                    <div><strong>Payment:</strong> ${escapeHtml(o.payment_status)} (${escapeHtml(o.payment_method || '-')})</div>
                    <hr style="border:none; border-top:1px solid #eee; margin:10px 0;">
                    <div><strong>Subtotal:</strong> ${money(subtotal)}</div>
                    <div><strong>Shipping:</strong> ${money(o.shipping_amount)}</div>
                    <div><strong>Discount:</strong> ${money(o.discount_amount)}</div>
                    <div><strong>Total:</strong> <strong>${money(o.total_amount)}</strong></div>
                `;

                const customerName = (o.first_name ? (o.first_name + ' ' + (o.last_name || '')) : (o.contact_name || 'Guest'));
                const email = o.customer_email || o.email || '-';
                const phone = o.contact_phone || '-';

                // Address (for guest checkout address may be embedded in notes as JSON)
                let addressHtml = '';
                if (o.address_line_1) {
                    addressHtml = `
                        <div>${escapeHtml(o.addr_first_name || '')} ${escapeHtml(o.addr_last_name || '')}</div>
                        ${o.company ? `<div>${escapeHtml(o.company)}</div>` : ''}
                        <div>${escapeHtml(o.address_line_1)}</div>
                        ${o.address_line_2 ? `<div>${escapeHtml(o.address_line_2)}</div>` : ''}
                        <div>${escapeHtml(o.city)}, ${escapeHtml(o.state)} ${escapeHtml(o.postal_code)}</div>
                        <div>${escapeHtml(o.country)}</div>
                    `;
                } else {
                    addressHtml = `<div style="color:#666; font-size:12px;">No saved address linked to this order (guest address may be stored in Notes).</div>`;
                }

                document.getElementById('orderCustomer').innerHTML = `
                    <div><strong>Customer:</strong> ${escapeHtml(customerName)}</div>
                    <div><strong>Email:</strong> ${escapeHtml(email)}</div>
                    <div><strong>Phone:</strong> ${escapeHtml(phone)}</div>
                    <hr style="border:none; border-top:1px solid #eee; margin:10px 0;">
                    <div><strong>Shipping Address:</strong></div>
                    ${addressHtml}
                `;

                const items = Array.isArray(o.items) ? o.items : [];
                if (!items.length) {
                    document.getElementById('orderItems').innerHTML = '<div style="color:#666;">No items found.</div>';
                } else {
                    const rows = items.map(i => `
                        <tr>
                            <td style="padding:8px; border-bottom:1px solid #eee;">${escapeHtml(i.product_name)}</td>
                            <td style="padding:8px; border-bottom:1px solid #eee;">${escapeHtml(i.product_sku)}</td>
                            <td style="padding:8px; border-bottom:1px solid #eee; text-align:right;">${escapeHtml(i.quantity)}</td>
                            <td style="padding:8px; border-bottom:1px solid #eee; text-align:right;">${money(i.unit_price)}</td>
                            <td style="padding:8px; border-bottom:1px solid #eee; text-align:right;">${money(i.total_price)}</td>
                        </tr>
                    `).join('');

                    document.getElementById('orderItems').innerHTML = `
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f5f5f5;">
                                    <th style="padding:8px; text-align:left; border-bottom:1px solid #ddd;">Product</th>
                                    <th style="padding:8px; text-align:left; border-bottom:1px solid #ddd;">SKU</th>
                                    <th style="padding:8px; text-align:right; border-bottom:1px solid #ddd;">Qty</th>
                                    <th style="padding:8px; text-align:right; border-bottom:1px solid #ddd;">Unit</th>
                                    <th style="padding:8px; text-align:right; border-bottom:1px solid #ddd;">Total</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    `;
                }
            })
            .catch(err => {
                console.error(err);
                showOrderAlert('error', 'Failed to load order details');
            });
    }

    function closeOrderModal() {
        document.getElementById('orderModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('orderModal');
        if (event.target === modal) {
            closeOrderModal();
        }
    });

    function submitOrderUpdate(event) {
        event.preventDefault();
        clearOrderAlert();

        const form = event.target;
        const formData = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        fetch('update_order.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showOrderAlert('success', 'Order updated successfully!');
                setTimeout(() => location.reload(), 1000); // Reload to see changes
            } else {
                showOrderAlert('error', data.error || 'Failed to update order.');
            }
        }).catch(err => {
            showOrderAlert('error', 'An unexpected error occurred.');
            console.error(err);
        }).finally(() => {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
        });
    }

    function quickUpdate(status) {
        document.getElementById('status').value = status;
        const form = document.getElementById('orderUpdateForm');
        submitOrderUpdate({preventDefault: () => {}, target: form});
    }
</script>
