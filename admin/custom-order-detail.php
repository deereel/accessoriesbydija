<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Custom Order Details';
$active_nav = 'orders';

require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: custom-orders.php');
    exit;
}

$order_id = (int) $_GET['id'];

// Fetch custom order
$stmt = $pdo->prepare("SELECT * FROM custom_requests WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: custom-orders.php');
    exit;
}

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
    .form-group select, .form-group input, .form-group textarea {
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
</style>

<div class="order-detail-container">
    <div class="order-header">
        <div>
            <h1>Custom Order #<?php echo htmlspecialchars($order['id']); ?></h1>
            <p>Submitted on <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
        </div>
        <div>
            <a href="custom-orders.php" class="btn">← Back to Custom Orders</a>
        </div>
    </div>

    <div id="alert" class="alert"></div>

    <div class="order-info">
        <div class="card">
            <div class="card-header">Customer Information</div>
            <div class="card-body">
                <div><strong>Name:</strong> <?php echo htmlspecialchars($order['name']); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></div>
                <div><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?: '-'); ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Order Summary</div>
            <div class="card-body">
                <div><strong>Jewelry Type:</strong> <?php echo htmlspecialchars($order['jewelry_type']); ?></div>
                <div><strong>Budget Range:</strong> <?php echo htmlspecialchars($order['budget_range']); ?></div>
                <div><strong>Occasion:</strong> <?php echo htmlspecialchars($order['occasion'] ?: '-'); ?></div>
                <div><strong>Timeline:</strong> <?php echo htmlspecialchars($order['timeline'] ?: '-'); ?></div>
                <div><strong>Status:</strong>
                    <span class="status-badge" style="background: <?php
                        $status_colors = [
                            'new' => '#cce5ff',
                            'in_progress' => '#d1ecf1',
                            'completed' => '#d4edda',
                            'cancelled' => '#f8d7da'
                        ];
                        echo $status_colors[$order['status']] ?? '#e2e3e5';
                    ?>; color: <?php
                        $text_colors = [
                            'new' => '#004085',
                            'in_progress' => '#0c5460',
                            'completed' => '#155724',
                            'cancelled' => '#721c24'
                        ];
                        echo $text_colors[$order['status']] ?? '#383d41';
                    ?>;">
                        <?php echo str_replace('_', ' ', htmlspecialchars($order['status'])); ?>
                    </span>
                </div>
                <?php if ($order['estimated_price']): ?>
                <div><strong>Estimated Price:</strong> £<?php echo number_format($order['estimated_price'], 2); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Design Preferences</div>
        <div class="card-body">
            <div><strong>Metals:</strong> <?php
                $metals = json_decode($order['metals'], true);
                echo $metals ? implode(', ', $metals) : '-';
            ?></div>
            <div><strong>Adornments:</strong> <?php
                $stones = json_decode($order['stones'], true);
                echo $stones ? implode(', ', $stones) : '-';
            ?></div>
            <hr>
            <div><strong>Description:</strong></div>
            <div style="margin-top: 10px; white-space: pre-wrap;"><?php echo htmlspecialchars($order['description']); ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Actions</div>
        <div class="card-body">
            <form id="customOrderUpdateForm" onsubmit="return submitCustomOrderUpdate(event)">
                <input type="hidden" id="order_id" name="order_id" value="<?php echo (int)$order['id']; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="new" <?php echo $order['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="in_progress" <?php echo $order['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estimated_price">Estimated Price (£)</label>
                        <input type="number" id="estimated_price" name="estimated_price" step="0.01" value="<?php echo htmlspecialchars($order['estimated_price'] ?: ''); ?>" placeholder="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Internal notes..."><?php echo htmlspecialchars($order['notes'] ?: ''); ?></textarea>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; align-items:center;">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
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

    function submitCustomOrderUpdate(event) {
        event.preventDefault();
        const form = event.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const formData = new FormData(form);

        fetch('update_custom_order.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Custom order updated successfully!');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('error', data.error || 'Failed to update custom order.');
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
</script>

<?php include '_layout_footer.php'; ?>