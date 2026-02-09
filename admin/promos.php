<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Promo Codes Management';
$active_nav = 'promos';

require_once __DIR__ . '/../app/config/database.php';

// Ensure promo_codes table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS promo_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        type ENUM('percent','amount') NOT NULL,
        value DECIMAL(10,2) NOT NULL,
        min_order_amount DECIMAL(10,2) DEFAULT 0,
        max_discount DECIMAL(10,2) DEFAULT NULL,
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        usage_limit INT DEFAULT NULL,
        usage_count INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // ignore
}

$errors = [];
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = ($_POST['type'] ?? 'percent') === 'amount' ? 'amount' : 'percent';
        $value = (float)($_POST['value'] ?? 0);
        $min_order_amount = (float)($_POST['min_order_amount'] ?? 0);
        $max_discount = isset($_POST['max_discount']) && $_POST['max_discount'] !== '' ? (float)$_POST['max_discount'] : null;
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $usage_limit = isset($_POST['usage_limit']) && $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($code === '') $errors[] = 'Code is required';
        if ($value <= 0) $errors[] = 'Value must be greater than 0';
        if ($type === 'percent' && ($value <= 0 || $value > 100)) $errors[] = 'Percentage must be between 1 and 100';
        if ($start_date === '' || $end_date === '') $errors[] = 'Start and End date are required';
        if ($start_date !== '' && $end_date !== '' && strtotime($start_date) > strtotime($end_date)) $errors[] = 'Start date must be before End date';

        if (empty($errors)) {
            try {
                $chk = $pdo->prepare('SELECT id FROM promo_codes WHERE code = ?' . ($id ? ' AND id != ?' : ''));
                if ($id) $chk->execute([$code, $id]); else $chk->execute([$code]);
                if ($chk->fetch()) {
                    $errors[] = 'Code already exists';
                } else {
                    if ($id) {
                        $sql = 'UPDATE promo_codes SET code=?, type=?, value=?, min_order_amount=?, max_discount=?, start_date=?, end_date=?, usage_limit=?, is_active=? WHERE id=?';
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$code, $type, $value, $min_order_amount, $max_discount, $start_date, $end_date, $usage_limit, $is_active, $id]);
                        $success = 'Promo updated';
                    } else {
                        $sql = 'INSERT INTO promo_codes (code, type, value, min_order_amount, max_discount, start_date, end_date, usage_limit, is_active) VALUES (?,?,?,?,?,?,?,?,?)';
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$code, $type, $value, $min_order_amount, $max_discount, $start_date, $end_date, $usage_limit, $is_active]);
                        $success = 'Promo created';
                    }
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error while saving';
            }
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            try {
                $del = $pdo->prepare('DELETE FROM promo_codes WHERE id = ?');
                $del->execute([$id]);
                $success = 'Promo deleted';
            } catch (PDOException $e) {
                $errors[] = 'Database error while deleting';
            }
        }
    } elseif ($action === 'generate') {
        header('Content-Type: application/json');
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len = 10; $code = '';
        for ($i=0; $i<$len; $i++) { $code .= $alphabet[random_int(0, strlen($alphabet)-1)]; }
        echo json_encode(['code' => $code]);
        exit;
    }
}

// Load promos
$promos = [];
try {
    $stmt = $pdo->query('SELECT * FROM promo_codes ORDER BY created_at DESC');
    $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Failed to load promos';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Promo Codes - Admin</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    body { font-family: Arial, sans-serif; margin:0; background:#f4f4f4; }
    .layout { display:flex; min-height:100vh; }
    .sidebar { width:240px; background:#333; color:#fff; padding:16px; }
    .sidebar h2 { font-size:18px; margin-bottom:12px; }
    .nav-link { display:block; color:#ddd; text-decoration:none; padding:10px 12px; border-radius:6px; margin-bottom:6px; }
    .nav-link.active, .nav-link:hover { background:#444; color:#fff; }
    .content { flex:1; padding:24px; }
    .card { background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:16px; }
    .card-header { padding:14px 18px; border-bottom:1px solid #eee; font-weight:600; }
    .card-body { padding:18px; }
    .row { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    .row-3 { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; }
    .form-group { display:flex; flex-direction:column; }
    label { font-size:12px; color:#666; margin-bottom:6px; }
    input, select { padding:10px; border:1px solid #ddd; border-radius:6px; }
    .btn { padding:10px 14px; background:#c487a5; color:#fff; border:none; border-radius:6px; cursor:pointer; text-decoration:none; display:inline-block; }
    .btn.secondary { background:#666; }
    .btn.danger { background:#dc3545; }
    .btn.success { background:#28a745; }
    .btn.sm { padding:6px 10px; font-size:12px; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:10px; border-bottom:1px solid #eee; text-align:left; }
    th { background:#fafafa; }
    .alert { padding:10px 14px; border-radius:6px; margin-bottom:12px; }
    .alert.error { background:#fee2e2; color:#991b1b; }
    .alert.success { background:#dcfce7; color:#166534; }
    
    /* Tabs */
    .tabs { display:flex; gap:4px; margin-bottom:16px; border-bottom:2px solid #eee; }
    .tab { padding:12px 20px; background:none; border:none; cursor:pointer; font-size:14px; color:#666; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all 0.2s; }
    .tab:hover { color:#c487a5; }
    .tab.active { color:#c487a5; border-bottom-color:#c487a5; font-weight:600; }
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    
    /* Sale Price Table */
    .sale-product { display:flex; align-items:center; gap:10px; }
    .sale-toggle { position:relative; width:48px; height:24px; }
    .sale-toggle input { opacity:0; width:0; height:0; }
    .sale-slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#ccc; transition:0.3s; border-radius:24px; }
    .sale-slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background-color:white; transition:0.3s; border-radius:50%; }
    .sale-toggle input:checked + .sale-slider { background-color:#28a745; }
    .sale-toggle input:checked + .sale-slider:before { transform:translateX(24px); }
    
    .sale-inputs { display:flex; gap:8px; align-items:center; }
    .sale-inputs input { width:80px; padding:6px; }
    .sale-inputs span { color:#666; font-size:12px; }
    .sale-badge { background:#dc3545; color:white; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
    .sale-expired { opacity:0.6; }
    
    /* Search */
    .search-box { margin-bottom:16px; display:flex; gap:8px; }
    .search-box input { flex:1; padding:10px; border:1px solid #ddd; border-radius:6px; }
    
    /* Modal */
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
    .modal.active { display:flex; }
    .modal-content { background:white; border-radius:12px; padding:20px; max-width:400px; width:90%; }
    .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
    .modal-close { background:none; border:none; font-size:24px; cursor:pointer; }
</style>
</head>
<body>

<?php include '_layout_header.php'; ?>

<div class="content-wrapper" style="padding:20px;">
    <h1 style="margin-bottom:20px;">Promotions</h1>
    
    <!-- Tabs -->
    <div class="tabs">
        <button class="tab active" data-tab="promos">Promo Codes</button>
        <button class="tab" data-tab="sale-prices">Sale Prices</button>
    </div>
    
    <!-- Promo Codes Tab -->
    <div id="tab-promos" class="tab-content active">
        <div class="card">
            <div class="card-header"><i class="fas fa-ticket"></i> Create / Edit Promo</div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="promo-id" value="">
                    <div class="row">
                        <div class="form-group">
                            <label>Code</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" name="code" id="code" placeholder="e.g., SPRING25" required>
                                <button class="btn secondary" type="button" id="gen">Generate</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" id="type">
                                <option value="percent">Percentage</option>
                                <option value="amount">Fixed Amount</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label>Value</label>
                            <input type="number" name="value" id="value" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Min Order Amount</label>
                            <input type="number" name="min_order_amount" id="min_order_amount" step="0.01" min="0" placeholder="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label>Max Discount (cap, for % type)</label>
                            <input type="number" name="max_discount" id="max_discount" step="0.01" min="0" placeholder="optional">
                        </div>
                        <div class="form-group">
                            <label>Usage Limit</label>
                            <input type="number" name="usage_limit" id="usage_limit" min="0" placeholder="optional">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label>Start Date/Time</label>
                            <input type="datetime-local" name="start_date" id="start_date" required>
                        </div>
                        <div class="form-group">
                            <label>End Date/Time</label>
                            <input type="datetime-local" name="end_date" id="end_date" required>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; margin:10px 0;">
                        <label><input type="checkbox" name="is_active" id="is_active" checked> Active</label>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn" type="submit">Save Promo</button>
                        <button class="btn secondary" type="reset" id="reset-form">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Existing Promo Codes</div>
            <div class="card-body">
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Min Order</th>
                                <th>Max Disc</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Usage</th>
                                <th>Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($promos)): ?>
                            <tr><td colspan="11">No promo codes created yet.</td></tr>
                        <?php else: foreach ($promos as $p): ?>
                            <tr>
                                <td><?php echo (int)$p['id']; ?></td>
                                <td><?php echo htmlspecialchars($p['code']); ?></td>
                                <td><?php echo htmlspecialchars($p['type']); ?></td>
                                <td><?php echo htmlspecialchars($p['value']); ?></td>
                                <td><?php echo htmlspecialchars($p['min_order_amount']); ?></td>
                                <td><?php echo htmlspecialchars($p['max_discount']); ?></td>
                                <td><?php echo htmlspecialchars($p['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($p['end_date']); ?></td>
                                <td><?php echo htmlspecialchars($p['usage_count']) . (isset($p['usage_limit']) && $p['usage_limit'] ? ' / ' . htmlspecialchars($p['usage_limit']) : ''); ?></td>
                                <td><?php echo ((int)$p['is_active'] ? 'Yes' : 'No'); ?></td>
                                <td>
                                    <button class="btn secondary" onclick="fillForm(<?php echo (int)$p['id']; ?>,'<?php echo htmlspecialchars($p['code']); ?>','<?php echo htmlspecialchars($p['type']); ?>','<?php echo htmlspecialchars($p['value']); ?>','<?php echo htmlspecialchars($p['min_order_amount']); ?>','<?php echo htmlspecialchars($p['max_discount']); ?>','<?php echo htmlspecialchars(str_replace(' ', 'T', $p['start_date'])); ?>','<?php echo htmlspecialchars(str_replace(' ', 'T', $p['end_date'])); ?>','<?php echo htmlspecialchars($p['usage_limit']); ?>',<?php echo (int)$p['is_active']; ?>)">Edit</button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this promo?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button class="btn danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sale Prices Tab -->
    <div id="tab-sale-prices" class="tab-content">
        <div class="card">
            <div class="card-header"><i class="fas fa-tags"></i> Product Sale Prices</div>
            <div class="card-body">
                <div class="search-box">
                    <input type="text" id="sale-search" placeholder="Search products..." onkeyup="searchSaleProducts()">
                </div>
                <div style="overflow-x:auto;">
                    <table id="sale-prices-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Original Price</th>
                                <th>Sale Price</th>
                                <th>Discount</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sale-products-body">
                            <tr><td colspan="7">Loading products...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sale Price Modal -->
<div id="sale-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Set Sale Price</h3>
            <button class="modal-close" onclick="closeSaleModal()">&times;</button>
        </div>
        <form id="sale-form" onsubmit="saveSalePrice(event)">
            <input type="hidden" id="sale-product-id" name="product_id">
            <div class="form-group" style="margin-bottom:12px;">
                <label>Product</label>
                <div id="sale-product-name" style="font-weight:600;"></div>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Original Price</label>
                <div id="sale-original-price" style="font-size:18px; font-weight:600;"></div>
            </div>
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                <label class="sale-toggle">
                    <input type="checkbox" id="sale-is-on-sale" name="is_on_sale" onchange="toggleSaleInputs()">
                    <span class="sale-slider"></span>
                </label>
                <span>Enable Sale</span>
            </div>
            <div id="sale-inputs" style="display:none;">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Discount Type</label>
                    <select id="sale-discount-type" onchange="calculateSalePrice()">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Price (£)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label id="sale-value-label">Percentage (%)</label>
                    <input type="number" id="sale-value" step="0.01" min="0" oninput="calculateSalePrice()">
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Sale End Date (optional)</label>
                    <input type="datetime-local" id="sale-end-date" name="sale_end_date">
                </div>
                <div style="background:#f8f9fa; padding:12px; border-radius:6px; margin-bottom:12px;">
                    <div style="font-size:12px; color:#666;">Sale Price Preview:</div>
                    <div id="sale-preview" style="font-size:24px; font-weight:600; color:#28a745;">-</div>
                </div>
            </div>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" class="btn secondary" onclick="closeSaleModal()">Cancel</button>
                <button type="submit" class="btn success">Save Sale</button>
            </div>
        </form>
    </div>
</div>

<script>
let saleProducts = [];

// Tab functionality
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        
        if (this.dataset.tab === 'sale-prices') {
            loadSaleProducts();
        }
    });
});

function loadSaleProducts() {
    console.log('[Sale Prices] Loading products...');
    fetch('/api/sale-prices.php?action=list')
        .then(response => {
            console.log('[Sale Prices] Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('[Sale Prices] Response data:', data);
            if (data.success) {
                saleProducts = data.products || [];
                renderSaleProducts(saleProducts);
            } else {
                console.error('[Sale Prices] API error:', data.message);
                document.getElementById('sale-products-body').innerHTML = '<tr><td colspan="7">Error loading products: ' + (data.message || 'Unknown error') + '</td></tr>';
            }
        })
        .catch(error => {
            console.error('[Sale Prices] Fetch error:', error);
            document.getElementById('sale-products-body').innerHTML = '<tr><td colspan="7">Error loading products: ' + error.message + '</td></tr>';
        });
}

function renderSaleProducts(products) {
    const tbody = document.getElementById('sale-products-body');
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7">No products found.</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map(p => {
        const isOnSale = p.is_on_sale && !p.sale_expired;
        let discountPercent = p.sale_percentage;
        if (!discountPercent && p.sale_price && p.price) {
            discountPercent = Math.round((p.price - p.sale_price) / p.price * 100);
        }
        const saleBadge = p.sale_expired ? '<span class="sale-badge" style="background:#666;">Expired</span>' : 
                          (isOnSale && discountPercent ? `<span class="sale-badge">-${discountPercent}%</span>` : '');
        const endDate = p.sale_end_date ? new Date(p.sale_end_date).toLocaleString() : 'No end date';
        const originalPrice = '£' + parseFloat(p.price).toFixed(2);
        const salePrice = isOnSale ? '£' + parseFloat(p.sale_price).toFixed(2) : '-';
        
        return `<tr class="${p.sale_expired ? 'sale-expired' : ''}">
            <td><div class="sale-product">${p.name.substring(0, 40)}${p.name.length > 40 ? '...' : ''}</div></td>
            <td>${originalPrice}</td>
            <td>${salePrice}</td>
            <td>${saleBadge}</td>
            <td>${endDate}</td>
            <td>${isOnSale ? '<span style="color:#28a745;">On Sale</span>' : '<span style="color:#666;">Regular</span>'}</td>
            <td><button class="btn sm" onclick="openSaleModal(${p.id})">${isOnSale ? 'Edit' : 'Set Sale'}</button></td>
        </tr>`;
    }).join('');
}

function searchSaleProducts() {
    const query = document.getElementById('sale-search').value.toLowerCase();
    const filtered = saleProducts.filter(p => p.name.toLowerCase().includes(query));
    renderSaleProducts(filtered);
}

function openSaleModal(productId) {
    const product = saleProducts.find(p => p.id === productId);
    if (!product) return;
    
    document.getElementById('sale-product-id').value = product.id;
    document.getElementById('sale-product-name').textContent = product.name;
    document.getElementById('sale-original-price').textContent = '£' + parseFloat(product.price).toFixed(2);
    document.getElementById('sale-is-on-sale').checked = product.is_on_sale && !product.sale_expired;
    document.getElementById('sale-end-date').value = product.sale_end_date ? product.sale_end_date.substring(0, 16) : '';
    
    if (product.sale_percentage) {
        document.getElementById('sale-discount-type').value = 'percentage';
        document.getElementById('sale-value').value = product.sale_percentage;
    } else if (product.sale_price) {
        document.getElementById('sale-discount-type').value = 'fixed';
        document.getElementById('sale-value').value = product.sale_price;
    } else {
        document.getElementById('sale-discount-type').value = 'percentage';
        document.getElementById('sale-value').value = '';
    }
    
    toggleSaleInputs();
    calculateSalePrice();
    document.getElementById('sale-modal').classList.add('active');
}

function closeSaleModal() {
    document.getElementById('sale-modal').classList.remove('active');
}

function toggleSaleInputs() {
    const inputs = document.getElementById('sale-inputs');
    inputs.style.display = document.getElementById('sale-is-on-sale').checked ? 'block' : 'none';
}

function calculateSalePrice() {
    const type = document.getElementById('sale-discount-type').value;
    const value = parseFloat(document.getElementById('sale-value').value) || 0;
    const originalPrice = parseFloat(document.getElementById('sale-original-price').textContent.replace('£', ''));
    
    document.getElementById('sale-value-label').textContent = type === 'percentage' ? 'Percentage (%)' : 'Fixed Price (£)';
    
    let salePrice = 0;
    if (type === 'percentage') {
        salePrice = originalPrice * (1 - value / 100);
        document.getElementById('sale-preview').textContent = '£' + salePrice.toFixed(2) + ` (${value}% off)`;
    } else {
        salePrice = value;
        const discount = originalPrice > 0 ? Math.round((originalPrice - value) / originalPrice * 100) : 0;
        document.getElementById('sale-preview').textContent = '£' + salePrice.toFixed(2) + ` (${discount}% off)`;
    }
}

function saveSalePrice(e) {
    e.preventDefault();
    
    const productId = document.getElementById('sale-product-id').value;
    const isOnSale = document.getElementById('sale-is-on-sale').checked ? 1 : 0;
    const type = document.getElementById('sale-discount-type').value;
    const value = document.getElementById('sale-value').value;
    const saleEndDate = document.getElementById('sale-end-date').value;
    
    const formData = new FormData();
    formData.append('action', 'set');
    formData.append('product_id', productId);
    formData.append('is_on_sale', isOnSale);
    if (isOnSale) {
        if (type === 'percentage') {
            formData.append('sale_percentage', value);
        } else {
            formData.append('sale_price', value);
        }
        if (saleEndDate) {
            formData.append('sale_end_date', saleEndDate);
        }
    }
    
    fetch('/api/sale-prices.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeSaleModal();
            loadSaleProducts();
        } else {
            alert(data.message || 'Error saving sale price');
        }
    });
}

// Close modal on outside click
document.getElementById('sale-modal').addEventListener('click', function(e) {
    if (e.target === this) closeSaleModal();
});

// Promo code functions
function fillForm(id, code, type, value, minOrder, maxDisc, start, end, usageLimit, isActive) {
    document.getElementById('promo-id').value = id;
    document.getElementById('code').value = code;
    document.getElementById('type').value = type;
    document.getElementById('value').value = value;
    document.getElementById('min_order_amount').value = minOrder || '';
    document.getElementById('max_discount').value = maxDisc || '';
    document.getElementById('start_date').value = start;
    document.getElementById('end_date').value = end;
    document.getElementById('usage_limit').value = usageLimit || '';
    document.getElementById('is_active').checked = !!isActive;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('gen').addEventListener('click', async function(){
    try {
        const res = await fetch('promos.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=generate' });
        const data = await res.json();
        if (data.code) document.getElementById('code').value = data.code;
    } catch (e) {}
});

document.getElementById('reset-form').addEventListener('click', function(){
    document.getElementById('promo-id').value = '';
});
</script>

<?php include '_layout_footer.php'; ?>
