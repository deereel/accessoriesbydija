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
                <div class="setting-card" style="position:relative;">
                    <?php if (($_SESSION['admin_role'] ?? '') === 'superadmin'): ?>
                    <div style="position:absolute; top:10px; right:10px;">
                        <button id="run-migrations-btn" title="Run database migrations" style="border:none; background:transparent; cursor:pointer; font-size:18px; color:var(--accent);">🔄</button>
                    </div>
                    <?php endif; ?>
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
   
                <!-- Log Viewer -->
                <div class="setting-card">
                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                    <h3>Log Viewer</h3>
                    <p>View application logs for debugging and monitoring.</p>
                    <a href="logs.php" class="btn">View Logs</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '_layout_footer.php'; ?>

<!-- Migrations Modal -->
<div id="migrations-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1200; align-items:center; justify-content:center;">
    <div style="background:#fff; width:90%; max-width:820px; padding:18px; border-radius:8px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <h3>Run Database Migrations</h3>
            <button onclick="closeMigrations()" class="btn">Close</button>
        </div>
        <div id="migrations-body"><p style="color:#666;">Click "Run" to apply migrations from the SQL sheet. Results will be listed below.</p></div>
        <div style="text-align:right; margin-top:12px;"><button class="btn" onclick="closeMigrations()">Cancel</button> <button class="btn" id="migrations-run">Run</button></div>
    </div>
</div>

<script>
function closeMigrations(){ document.getElementById('migrations-modal').style.display='none'; }
document.getElementById('run-migrations-btn') && document.getElementById('run-migrations-btn').addEventListener('click', function(){ document.getElementById('migrations-modal').style.display='flex'; });
document.getElementById('migrations-run') && document.getElementById('migrations-run').addEventListener('click', function(){
    var btn = this; btn.disabled = true; btn.textContent = 'Running...';
    fetch('/admin/run-migrations.php',{method:'POST'}).then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.textContent = 'Run';
        if(j.success){
            var html = '<div style="background:#e6ffed; border:1px solid #86efac; padding:12px; border-radius:6px; margin-bottom:12px;">';
            html += '<p style="color:#166534; margin:0;"><strong>✓ Migration Completed Successfully!</strong></p></div>';
            if(j.results && j.results.length > 0) {
                var created = j.results.filter(r => r.action === 'created').map(r => r.table);
                var altered = j.results.filter(r => r.action === 'altered').map(r => r.table);
                var executed = j.results.filter(r => r.action === 'executed').length;
                var errors = j.results.filter(r => r.action === 'error');
                if(created.length > 0) html += '<p><strong>Tables Created:</strong> ' + created.join(', ') + '</p>';
                if(altered.length > 0) html += '<p><strong>Tables Altered:</strong> ' + altered.join(', ') + '</p>';
                if(executed > 0) html += '<p><strong>Statements Executed:</strong> ' + executed + '</p>';
                if(errors.length > 0) {
                    html += '<p><strong>Errors:</strong></p><ul>';
                    errors.forEach(e => html += '<li>' + (e.statement ? e.statement.substring(0,50) + '...' : 'Unknown') + ': ' + e.error + '</li>');
                    html += '</ul>';
                }
            }
            document.getElementById('migrations-body').innerHTML = html;
        } else {
            document.getElementById('migrations-body').innerHTML = '<div style="color:#b33; background:#ffe6e6; border:1px solid #fca5a5; padding:12px; border-radius:6px;">Error: '+(j.message||'Unknown')+'</div>';
        }
    }).catch(e=>{ btn.disabled=false; btn.textContent='Run'; document.getElementById('migrations-body').innerHTML = '<div style="color:#b33;">Network error</div>'; });
});
</script>
