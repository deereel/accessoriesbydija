<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
// Restrict access to settings page to admins and superadmins
if (!in_array($_SESSION['admin_role'], ['admin', 'superadmin'])) {
    exit('Access Denied: You do not have permission to access this page.');
}


$page_title = 'Settings';
$active_nav = 'settings';

require_once '../config/database.php';
?>

<?php include '_layout_header.php'; ?>

<style>
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}
.setting-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.setting-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.setting-card .icon {
    font-size: 48px;
    color: var(--accent);
    margin-bottom: 15px;
}
.setting-card h3 {
    margin: 0 0 10px 0;
    font-size: 1.2rem;
}
.setting-card p {
    color: #666;
    font-size: 14px;
    margin-bottom: 20px;
}
.setting-card .btn {
    background: var(--accent);
    color: #fff;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: bold;
}
</style>

<div class="card">
    <div class="card-header"><i class="fas fa-cogs"></i> Site Settings</div>
    <div class="card-body">
        <p>Manage administrative settings, users, and other site configurations from this hub.</p>
        <div class="settings-grid">
            
            <!-- Promo Codes -->
            <div class="setting-card">
                <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                <h3>Promo Codes</h3>
                <p>Create and manage discount and promotional codes for your store.</p>
                <a href="promos.php" class="btn">Manage Promos</a>
            </div>

            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin'): ?>
                <!-- User Management -->
                <div class="setting-card">
                    <div class="icon"><i class="fas fa-users-cog"></i></div>
                    <h3>User Management</h3>
                    <p>Add or remove administrators and manage their permission levels.</p>
                    <a href="users.php" class="btn">Manage Users</a>
                </div>

                <!-- Database Management -->
                <div class="setting-card">
                    <div class="icon"><i class="fas fa-database"></i></div>
                    <h3>Database Tools</h3>
                    <p>View table data and create backups of the site database.</p>
                    <a href="database.php" class="btn">Manage Database</a>
                </div>

                <!-- API & Webhook Management -->
                <div class="setting-card">
                    <div class="icon"><i class="fas fa-plug"></i></div>
                    <h3>API & Webhook Management</h3>
                    <p>Manage API keys, webhooks, and external integrations.</p>
                    <a href="api_webhooks.php" class="btn">Manage APIs & Webhooks</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '_layout_footer.php'; ?>
