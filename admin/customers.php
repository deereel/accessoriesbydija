<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Customers Management';
$active_nav = 'customers';

require_once '../config/database.php';

// Fetch customers
$stmt = $pdo->query("SELECT id, first_name, last_name, email, phone, created_at FROM customers ORDER BY created_at DESC LIMIT 100");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '_layout_header.php'; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-users"></i> Customers Management
    </div>
    <div class="card-body">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Name</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Email</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Phone</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Joined</th>
                        <th style="padding:10px; border-bottom:1px solid #ddd; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px;"><strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                            <td style="padding:10px;"><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                            <td style="padding:10px;">
                                <div style="display:flex; gap:8px; align-items:center;">
                                    <a href="customer-profile.php?id=<?php echo intval($customer['id']); ?>" class="btn" style="font-size:12px;">View Profile</a>
                                    <?php if (!isset($user_role) || $user_role !== 'staff'): ?>
                                    <button class="btn force-reset-btn" data-customer-id="<?php echo intval($customer['id']); ?>" style="font-size:12px;">Force Reset</button>
                                    <?php endif; ?>
                                    <span class="reset-status" style="font-size:12px; color:green; display:none;">Reset!</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '_layout_footer.php'; ?>
<script>
document.querySelectorAll('.force-reset-btn').forEach(function(btn){
    btn.addEventListener('click', function(e){
        e.preventDefault();
        var id = this.dataset.customerId;
        var statusSpan = this.parentNode.querySelector('.reset-status');
        if(!confirm('Force password reset for this customer?')) return;
        this.disabled = true;
        statusSpan.style.display = 'none';
        fetch('/admin/force-password-reset.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ customer_id: id, action: 'set' })
        }).then(r=>r.json()).then(j=>{
            if(j.success){
                statusSpan.textContent = 'Reset!';
                statusSpan.style.color = 'green';
                statusSpan.style.display = '';
            } else {
                statusSpan.textContent = 'Error!';
                statusSpan.style.color = 'red';
                statusSpan.style.display = '';
                alert('Error: ' + (j.message||'failed'));
            }
        }).catch(e=>{
            statusSpan.textContent = 'Network error!';
            statusSpan.style.color = 'red';
            statusSpan.style.display = '';
            alert('Network error');
        }).finally(()=>{
            this.disabled = false;
        });
    });
});
</script>