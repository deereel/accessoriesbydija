<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

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
                // unique code check
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
        // Ajax code generation
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
    table { width:100%; border-collapse:collapse; }
    th, td { padding:10px; border-bottom:1px solid #eee; text-align:left; }
    th { background:#fafafa; }
    .alert { padding:10px 14px; border-radius:6px; margin-bottom:12px; }
    .alert.error { background:#fee2e2; color:#991b1b; }
    .alert.success { background:#dcfce7; color:#166534; }
</style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h2>Admin</h2>
        <a class="nav-link" href="index.php"><i class="fas fa-gauge"></i> Dashboard</a>
        <a class="nav-link" href="products.php"><i class="fas fa-gem"></i> Products</a>
        <a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a class="nav-link" href="customers.php"><i class="fas fa-users"></i> Customers</a>
        <a class="nav-link" href="banners.php"><i class="fas fa-image"></i> Banners</a>
        <a class="nav-link" href="testimonials.php"><i class="fas fa-comment"></i> Testimonials</a>
        <a class="nav-link active" href="promos.php"><i class="fas fa-ticket"></i> Promo Codes</a>
        <div style="margin-top:12px;"><a class="nav-link" href="?logout=1"><i class="fas fa-sign-out"></i> Logout</a></div>
    </aside>
    <main class="content">
        <div class="card">
            <div class="card-header">Create / Edit Promo</div>
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
    </main>
</div>

<script>
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
</body>
</html>
