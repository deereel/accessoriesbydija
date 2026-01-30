<?php
require_once '../app/config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle delete
if (isset($_POST['delete_rate'])) {
    $rate_id = (int)$_POST['delete_rate'];
    try {
        $stmt = $pdo->prepare("DELETE FROM shipping_rates WHERE id = ?");
        $stmt->execute([$rate_id]);
        $_SESSION['flash_message'] = "Shipping rate deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle toggle active
if (isset($_POST['toggle_active'])) {
    $rate_id = (int)$_POST['toggle_active'];
    try {
        $stmt = $pdo->prepare("UPDATE shipping_rates SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$rate_id]);
        $_SESSION['flash_message'] = "Shipping rate status updated.";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$page_title = "Shipping Rates Management";
$active_nav = "shipping";
include '_layout_header.php';

if (isset($_SESSION['flash_message'])) {
    echo '<div class="card" style="background: #d4edda; color: #155724;"><div class="card-body">' . $_SESSION['flash_message'] . '</div></div>';
    unset($_SESSION['flash_message']);
}

// Handle form submission
if ($_POST) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_rate') {
        try {
            $stmt = $pdo->prepare("INSERT INTO shipping_rates (country, weight_min, weight_max, fee, currency, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['country'],
                floatval($_POST['weight_min']),
                floatval($_POST['weight_max']),
                floatval($_POST['fee']),
                $_POST['currency'] ?? 'GBP',
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $success = "Shipping rate added successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
        }
    }

    if ($action === 'edit_rate') {
        try {
            $stmt = $pdo->prepare("UPDATE shipping_rates SET country = ?, weight_min = ?, weight_max = ?, fee = ?, currency = ?, is_active = ? WHERE id = ?");
            $stmt->execute([
                $_POST['country'],
                floatval($_POST['weight_min']),
                floatval($_POST['weight_max']),
                floatval($_POST['fee']),
                $_POST['currency'] ?? 'GBP',
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['rate_id']
            ]);
            $success = "Shipping rate updated successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
        }
    }
}

// Fetch shipping rates
$stmt = $pdo->query("SELECT * FROM shipping_rates ORDER BY country, weight_min");
$shipping_rates = $stmt->fetchAll();

// Group by country
$rates_by_country = [];
foreach ($shipping_rates as $rate) {
    $rates_by_country[$rate['country']][] = $rate;
}
?>

<style>
    .controls { background: var(--card); padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .shipping-table { background: var(--card); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
    th { background: #f8f8f8; font-weight: 600; }
    .country-section { margin-bottom: 30px; }
    .country-header { background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 10px; font-weight: bold; }
</style>

<?php if (isset($success)): ?>
    <div class="card" style="background: #d4edda; color: #155724;">
        <div class="card-body"><?= $success ?></div>
    </div>
<?php endif; ?>

<div class="controls">
    <h2>Shipping Rates Management</h2>
    <button class="btn btn-success" onclick="openAddModal()">+ Add Shipping Rate</button>
</div>

<?php foreach ($rates_by_country as $country => $rates): ?>
<div class="country-section">
    <div class="country-header">
        <?= htmlspecialchars($country) ?> (<?= count($rates) ?> rate<?= count($rates) > 1 ? 's' : '' ?>)
    </div>

    <div class="shipping-table">
        <table>
            <thead>
                <tr>
                    <th>Weight Range</th>
                    <th>Fee</th>
                    <th>Currency</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rates as $rate): ?>
                <tr>
                    <td>
                        <?php
                        if ($rate['weight_min'] == 0) {
                            echo "0 - {$rate['weight_max']}kg";
                        } elseif ($rate['weight_max'] >= 999999) {
                            echo "{$rate['weight_min']}kg+";
                        } else {
                            echo "{$rate['weight_min']} - {$rate['weight_max']}kg";
                        }
                        ?>
                    </td>
                    <td>£<?= number_format($rate['fee'], 2) ?></td>
                    <td><?= htmlspecialchars($rate['currency']) ?></td>
                    <td>
                        <span style="color: <?= $rate['is_active'] ? 'green' : 'red' ?>">
                            <?= $rate['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn" onclick="editRate(<?= $rate['id'] ?>)" style="margin-right: 5px;">Edit</button>
                        <form method="POST" style="display:inline; margin-right: 5px;">
                            <input type="hidden" name="toggle_active" value="<?= $rate['id'] ?>">
                            <button type="submit" class="btn">
                                <?= $rate['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this shipping rate?')">
                            <input type="hidden" name="delete_rate" value="<?= $rate['id'] ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<!-- Add/Edit Modal -->
<div id="rateModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Add Shipping Rate</h2>

        <form method="POST">
            <input type="hidden" name="action" value="add_rate">
            <input type="hidden" name="rate_id" id="rate_id">

            <div class="form-group">
                <label>Country</label>
                <select name="country" id="country" required>
                    <option value="">Select Country</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="Canada">Canada</option>
                    <option value="United States">United States</option>
                    <option value="Ireland">Ireland</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Weight Min (kg)</label>
                    <input type="number" name="weight_min" id="weight_min" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Weight Max (kg)</label>
                    <input type="number" name="weight_max" id="weight_max" step="0.01" min="0" placeholder="999999 for unlimited" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Fee (£)</label>
                    <input type="number" name="fee" id="fee" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Currency</label>
                    <select name="currency" id="currency">
                        <option value="GBP">GBP (£)</option>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" id="is_active" checked> Active
                </label>
            </div>

            <div style="text-align: right; margin-top: 20px; padding: 20px; border-top: 1px solid #ddd; background: white;">
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Save Rate</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Shipping Rate';
    document.querySelector('input[name="action"]').value = 'add_rate';
    document.getElementById('rate_id').value = '';
    document.getElementById('rateModal').querySelector('form').reset();
    document.getElementById('is_active').checked = true;
    document.getElementById('rateModal').style.display = 'block';
}

function editRate(id) {
    fetch(`get_shipping_rate.php?id=${id}`)
        .then(response => response.json())
        .then(rate => {
            if (rate.error) {
                alert('Error: ' + rate.error);
                return;
            }

            document.getElementById('modalTitle').textContent = 'Edit Shipping Rate';
            document.querySelector('input[name="action"]').value = 'edit_rate';
            document.getElementById('rate_id').value = rate.id;
            document.getElementById('country').value = rate.country;
            document.getElementById('weight_min').value = rate.weight_min;
            document.getElementById('weight_max').value = rate.weight_max;
            document.getElementById('fee').value = rate.fee;
            document.getElementById('currency').value = rate.currency;
            document.getElementById('is_active').checked = rate.is_active == 1;

            document.getElementById('rateModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching shipping rate details');
        });
}

function closeModal() {
    document.getElementById('rateModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('rateModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include '_layout_footer.php'; ?>
