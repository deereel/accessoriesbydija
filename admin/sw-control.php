<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
// Restrict access to Superadmin only; redirect others to Settings
if (!in_array($_SESSION['admin_role'] ?? '', ['superadmin'])) {
    header('Location: settings.php');
    exit;
}

$page_title = 'Service Worker Control';
$active_nav = 'sw';

include '_layout_header.php';
?>

<div class="card">
  <div class="card-header"><i class="fas fa-wrench"></i> Service Worker Control</div>
  <div class="card-body">
    <p>Quick tools for testing and managing service workers and cached assets for development/testing.</p>

    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
      <button id="unregister-admin" class="btn">Unregister Admin SW</button>
      <button id="force-update-admin" class="btn">Force Update Admin SW</button>
      <button id="unregister-all" class="btn">Unregister All SWs</button>
      <button id="clear-sw-caches" class="btn">Clear SW Caches</button>
      <button id="reload-clients" class="btn">Reload Admin Page</button>
    </div>

    <div id="sw-log" style="background:#fff; border:1px solid var(--border); padding:12px; border-radius:8px; max-height:320px; overflow:auto; white-space:pre-wrap;"></div>
  </div>
</div>

<?php include '_layout_footer.php'; ?>

<script>
function log(msg) {
  const el = document.getElementById('sw-log');
  const time = new Date().toLocaleTimeString();
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

document.getElementById('unregister-admin').addEventListener('click', function(){ if(confirm('Unregister admin SW?')) unregisterAdmin(); });
document.getElementById('force-update-admin').addEventListener('click', function(){ forceUpdateAdmin(); });
document.getElementById('unregister-all').addEventListener('click', function(){ if(confirm('Unregister ALL service workers?')) unregisterAll(); });
document.getElementById('clear-sw-caches').addEventListener('click', function(){ if(confirm('Delete all caches? This may affect offline behavior.')) clearSWCaches(); });
document.getElementById('reload-clients').addEventListener('click', function(){ location.reload(); });
</script>