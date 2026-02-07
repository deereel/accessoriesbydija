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

require_once '../app/config/database.php';
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
        <?php if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin','superadmin'])): ?>
        <div style="margin-bottom:12px; color:#444; background:#fff; padding:10px; border-radius:8px; border:1px solid var(--border);">
            <strong>Admin Note:</strong> Use the Service Worker tools to unregister or force-update service workers and clear caches when testing deployments or troubleshooting PWA/CSS issues. These actions affect cached site files and should be used carefully. <em>Service Worker controls are restricted to <strong>Superadmin</strong> users.</em>
        </div>
        <?php endif; ?>
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

                <!-- Service Worker Controls (Superadmin only) -->
                <div class="setting-card" id="sw-control-card">
                    <div class="icon"><i class="fas fa-wrench"></i></div>
                    <h3>Service Worker Controls</h3>
                    <p>Unregister, force-update, and clear caches for admin service worker. For testing & deployments.</p>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:center; margin-top:8px;">
                        <button id="sw-unregister-admin" class="btn">Unregister Admin SW</button>
                        <button id="sw-force-update-admin" class="btn">Force Update Admin SW</button>
                        <button id="sw-unregister-all" class="btn">Unregister All SWs</button>
                        <button id="sw-clear-caches" class="btn">Clear SW Caches</button>
                    </div>
                    <div id="sw-control-log" style="margin-top:12px; background:#fff; border:1px solid var(--border); padding:8px; border-radius:6px; max-height:160px; overflow:auto; font-size:13px;"></div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
(function(){
  function log(msg) {
    const el = document.getElementById('sw-control-log');
    const time = new Date().toLocaleTimeString();
    if (!el) return console.log(msg);
    el.textContent = `[${time}] ${msg}\n` + el.textContent;
  }

  async function unregisterAdmin() {
    try {
      const regs = await navigator.serviceWorker.getRegistrations();
      const adminRegs = regs.filter(r => r.scope && r.scope.includes('/admin/'));
      if (!adminRegs.length) { log('No admin service worker registrations found.'); return; }
      for (const r of adminRegs) {
        const ok = await r.unregister();
        log('Unregistered admin SW at ' + r.scope + (ok ? ' (success)' : ' (failed)'));
      }
    } catch (e) { log('Error while unregistering admin SW: ' + e); }
  }

  async function forceUpdateAdmin() {
    try {
      const regs = await navigator.serviceWorker.getRegistrations();
      const adminRegs = regs.filter(r => r.scope && r.scope.includes('/admin/'));
      if (!adminRegs.length) { log('No admin service worker registrations found.'); return; }
      for (const r of adminRegs) {
        try { await r.update(); log('Update checked for admin SW at ' + r.scope); } catch(e){ log('Update failed for ' + r.scope + ': ' + e); }
      }
    } catch (e) { log('Error while updating admin SW: ' + e); }
  }

  async function unregisterAll() {
    try {
      const regs = await navigator.serviceWorker.getRegistrations();
      if (!regs.length) { log('No service worker registrations found.'); return; }
      for (const r of regs) {
        const ok = await r.unregister();
        log('Unregistered SW at ' + r.scope + (ok ? ' (success)' : ' (failed)'));
      }
    } catch (e) { log('Error while unregistering all SWs: ' + e); }
  }

  async function clearSWCaches() {
    try {
      const keys = await caches.keys();
      if (!keys.length) { log('No cache keys found.'); return; }
      const removed = [];
      await Promise.all(keys.map(k => caches.delete(k).then(ok => { if (ok) removed.push(k); } )));
      if (removed.length) log('Deleted caches: ' + removed.join(', '));
      else log('No caches were deleted.');
    } catch (e) { log('Error while clearing caches: ' + e); }
  }

  const btnUnreg = document.getElementById('sw-unregister-admin');
  const btnUpdate = document.getElementById('sw-force-update-admin');
  const btnUnregAll = document.getElementById('sw-unregister-all');
  const btnClear = document.getElementById('sw-clear-caches');

  btnUnreg && btnUnreg.addEventListener('click', function(){ if(confirm('Unregister admin SW?')) unregisterAdmin(); });
  btnUpdate && btnUpdate.addEventListener('click', function(){ forceUpdateAdmin(); });
  btnUnregAll && btnUnregAll.addEventListener('click', function(){ if(confirm('Unregister ALL service workers?')) unregisterAll(); });
  btnClear && btnClear.addEventListener('click', function(){ if(confirm('Delete all caches? This may affect offline behavior.')) clearSWCaches(); });
})();
</script>

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
