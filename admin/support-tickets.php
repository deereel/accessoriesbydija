<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php'); exit;
}

$page_title = 'Support Tickets';
$active_nav = 'support_tickets';
require_once __DIR__ . '/../app/config/database.php';

// Get filter values from URL
$filter_status = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_assigned = $_GET['assigned'] ?? '';

// Build query with filters
$where = [];
$params = [];

if ($filter_status) {
    $where[] = "t.status = ?";
    $params[] = $filter_status;
}
if ($filter_priority) {
    $where[] = "t.priority = ?";
    $params[] = $filter_priority;
}
if ($filter_category) {
    $where[] = "t.category = ?";
    $params[] = $filter_category;
}
if ($filter_assigned !== '') {
    if ($filter_assigned === 'unassigned') {
        $where[] = "t.assigned_to IS NULL";
    } else {
        $where[] = "t.assigned_to = ?";
        $params[] = (int)$filter_assigned;
    }
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Fetch tickets
$tickets = [];
try {
    $sql = "SELECT t.*, u.username as assigned_username FROM support_tickets t LEFT JOIN admin_users u ON t.assigned_to = u.id $whereClause ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tickets = [];
}

// Get all admin users for assign dropdown
$admins = [];
try {
    $stmt = $pdo->query("SELECT id, username FROM admin_users WHERE is_active = 1 ORDER BY username");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admins = [];
}

include __DIR__ . '/_layout_header.php';
?>
<div class="card">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
    <span>Support Tickets</span>
    <a href="/admin/export-tickets.php?status=<?php echo urlencode($filter_status); ?>&priority=<?php echo urlencode($filter_priority); ?>&category=<?php echo urlencode($filter_category); ?>&assigned=<?php echo urlencode($filter_assigned); ?>" class="btn" style="font-size:12px;">📥 Export CSV</a>
  </div>
  <div class="card-body">
    <p>View, assign, and manage support tickets from customers.</p>
    
    <!-- Filters -->
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid #eee;">
      <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <select name="status" style="padding:6px; border:1px solid #ddd; border-radius:4px;">
          <option value="">All Statuses</option>
          <option value="open" <?php echo $filter_status === 'open' ? 'selected' : ''; ?>>Open</option>
          <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
          <option value="resolved" <?php echo $filter_status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
          <option value="closed" <?php echo $filter_status === 'closed' ? 'selected' : ''; ?>>Closed</option>
        </select>

        <select name="priority" style="padding:6px; border:1px solid #ddd; border-radius:4px;">
          <option value="">All Priorities</option>
          <option value="low" <?php echo $filter_priority === 'low' ? 'selected' : ''; ?>>Low</option>
          <option value="medium" <?php echo $filter_priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
          <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>High</option>
        </select>

        <select name="category" style="padding:6px; border:1px solid #ddd; border-radius:4px;">
          <option value="">All Categories</option>
          <option value="password-reset" <?php echo $filter_category === 'password-reset' ? 'selected' : ''; ?>>Password Reset</option>
          <option value="product-issue" <?php echo $filter_category === 'product-issue' ? 'selected' : ''; ?>>Product Issue</option>
          <option value="order-issue" <?php echo $filter_category === 'order-issue' ? 'selected' : ''; ?>>Order Issue</option>
          <option value="payment-issue" <?php echo $filter_category === 'payment-issue' ? 'selected' : ''; ?>>Payment Issue</option>
          <option value="general" <?php echo $filter_category === 'general' ? 'selected' : ''; ?>>General</option>
          <option value="other" <?php echo $filter_category === 'other' ? 'selected' : ''; ?>>Other</option>
        </select>

        <select name="assigned" style="padding:6px; border:1px solid #ddd; border-radius:4px;">
          <option value="">All Assigned</option>
          <option value="unassigned" <?php echo $filter_assigned === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
          <?php foreach ($admins as $a): ?>
            <option value="<?php echo intval($a['id']); ?>" <?php echo $filter_assigned == $a['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['username']); ?></option>
          <?php endforeach; ?>
        </select>

        <button type="submit" class="btn" style="padding:6px 12px; font-size:12px;">Filter</button>
        <a href="/admin/support-tickets.php" class="btn" style="padding:6px 12px; font-size:12px; background:#999;">Clear</a>
      </form>
    </div>

    <!-- Tickets Table -->
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="background:#f5f5f5;">
            <th style="padding:8px;">ID</th>
            <th style="padding:8px;">Customer</th>
            <th style="padding:8px;">Subject</th>
            <th style="padding:8px;">Category</th>
            <th style="padding:8px;">Priority</th>
            <th style="padding:8px;">Status</th>
            <th style="padding:8px;">Assigned To</th>
            <th style="padding:8px;">Created</th>
            <th style="padding:8px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="9" style="padding:12px; text-align:center; color:#666;">No tickets match your filters</td></tr>
        <?php else: foreach ($tickets as $t): ?>
          <tr style="border-bottom:1px solid #f0f0f0;">
            <td style="padding:8px; font-weight:bold;">#<?php echo intval($t['id']); ?></td>
            <td style="padding:8px;"><strong><?php echo htmlspecialchars($t['customer_name'] ?: $t['customer_email']); ?></strong><br><small style="color:#666;"><?php echo htmlspecialchars($t['customer_email']); ?></small></td>
            <td style="padding:8px; max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($t['subject']); ?>"><?php echo htmlspecialchars($t['subject']); ?></td>
            <td style="padding:8px;"><span style="background:#f0f0f0; padding:3px 6px; border-radius:3px; font-size:12px;"><?php echo htmlspecialchars($t['category']); ?></span></td>
            <td style="padding:8px;">
              <?php 
              $colors = ['low'=>'#cce5ff', 'medium'=>'#ffe5cc', 'high'=>'#ffcccc'];
              $textcolors = ['low'=>'#004085', 'medium'=>'#856404', 'high'=>'#721c24'];
              $bg = $colors[$t['priority']] ?? '#f0f0f0';
              $tc = $textcolors[$t['priority']] ?? '#333';
              ?>
              <span style="background:<?php echo $bg; ?>; color:<?php echo $tc; ?>; padding:3px 6px; border-radius:3px; font-size:12px;"><?php echo htmlspecialchars($t['priority']); ?></span>
            </td>
            <td style="padding:8px;">
              <?php
              $statusBg = ['open'=>'#fff3cd', 'in_progress'=>'#cfe2ff', 'resolved'=>'#d1e7dd', 'closed'=>'#e2e3e5'];
              $statusTxt = ['open'=>'#856404', 'in_progress'=>'#084298', 'resolved'=>'#0f5132', 'closed'=>'#383d41'];
              $sbg = $statusBg[$t['status']] ?? '#f0f0f0';
              $stxt = $statusTxt[$t['status']] ?? '#333';
              ?>
              <span style="background:<?php echo $sbg; ?>; color:<?php echo $stxt; ?>; padding:3px 6px; border-radius:3px; font-size:12px;"><?php echo htmlspecialchars($t['status']); ?></span>
            </td>
            <td style="padding:8px;">
              <select class="assign-select" data-ticket-id="<?php echo intval($t['id']); ?>" style="padding:4px; font-size:11px; border:1px solid #ddd; border-radius:4px;">
                <option value="">Unassigned</option>
                <?php foreach ($admins as $a): ?>
                  <option value="<?php echo intval($a['id']); ?>" <?php echo $t['assigned_to'] == $a['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['username']); ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td style="padding:8px; font-size:12px;"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
            <td style="padding:8px; font-size:12px;">
              <div style="display:flex; gap:4px; flex-wrap:wrap;">
                <button class="btn" onclick="viewTicket(<?php echo intval($t['id']); ?>)" style="padding:4px 8px;">View</button>
                <button class="btn" onclick="openRespond(<?php echo intval($t['id']); ?>)" style="padding:4px 8px;">Reply</button>
                <?php if ($t['status'] !== 'closed'): ?>
                  <button class="btn close-btn" data-ticket-id="<?php echo intval($t['id']); ?>" style="padding:4px 8px;">Close</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Ticket modal -->
<div id="ticket-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1200; align-items:center; justify-content:center;">
  <div style="background:#fff; width:90%; max-width:720px; padding:18px; border-radius:8px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;"><h3 id="ticket-title">Ticket</h3><button onclick="closeTicketModal()" class="btn">Close</button></div>
    <div id="ticket-body" style="max-height:400px; overflow:auto;"></div>
  </div>
</div>

<!-- Respond modal -->
<div id="respond-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1200; align-items:center; justify-content:center;">
  <div style="background:#fff; width:90%; max-width:680px; padding:18px; border-radius:8px;">
    <h3>Respond to Ticket <span id="respond-ticket-id"></span></h3>
    <div style="margin:8px 0 12px;" id="respond-alert"></div>
    <label style="display:block; margin-bottom:8px; font-weight:600;">Response:</label>
    <textarea id="response-text" rows="6" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;"></textarea>
    <label style="display:block; margin-top:12px; margin-bottom:8px; font-weight:600;">Mark as:</label>
    <select id="response-status" style="padding:6px; border:1px solid #ddd; border-radius:4px; margin-bottom:12px;">
      <option value="in_progress">In Progress</option>
      <option value="resolved">Resolved</option>
      <option value="closed">Closed</option>
    </select>
    <div style="margin-top:8px; text-align:right;"><button class="btn" onclick="closeRespond()">Cancel</button> <button class="btn" onclick="submitResponse()">Send Response</button></div>
  </div>
</div>

<?php include __DIR__ . '/_layout_footer.php'; ?>

<script>
function viewTicket(id){
  fetch('/admin/get-ticket.php?id='+encodeURIComponent(id)).then(r=>r.json()).then(j=>{
    if(j.success){
      var t = j.ticket;
      var html = '<p><strong>From:</strong> '+(t.customer_name||t.customer_email)+' ('+t.customer_email+')</p>';
      html += '<p><strong>Subject:</strong> '+t.subject+'</p>';
      html += '<p><strong>Category:</strong> '+t.category+'</p>';
      html += '<p><strong>Priority:</strong> '+t.priority+'</p>';
      html += '<p><strong>Status:</strong> '+t.status+'</p>';
      html += '<p><strong>Message:</strong><br/>'+t.message.replace(/\n/g,'<br/>')+'</p>';
      if(t.response_text){ html += '<hr/><p><strong>Response:</strong><br/>'+t.response_text.replace(/\n/g,'<br/>')+'</p>'; }
      document.getElementById('ticket-title').innerText = 'Ticket #'+t.id;
      document.getElementById('ticket-body').innerHTML = html;
      document.getElementById('ticket-modal').style.display = 'flex';
    } else alert('Failed to load ticket');
  });
}
function closeTicketModal(){ document.getElementById('ticket-modal').style.display='none'; }
function openRespond(id){ 
  document.getElementById('respond-ticket-id').innerText = '#' + id; 
  document.getElementById('respond-modal').style.display='flex'; 
  document.getElementById('respond-modal').dataset.ticketId = id; 
  document.getElementById('response-text').value = ''; 
  document.getElementById('respond-alert').innerText=''; 
}
function closeRespond(){ document.getElementById('respond-modal').style.display='none'; }
function submitResponse(){ 
  var id = document.getElementById('respond-modal').dataset.ticketId; 
  var text = document.getElementById('response-text').value.trim(); 
  var status = document.getElementById('response-status').value;
  if(!text){ document.getElementById('respond-alert').innerText='Please enter a response'; return; } 
  fetch('/admin/respond-ticket.php',{
    method:'POST', 
    headers:{'Content-Type':'application/json'}, 
    body:JSON.stringify({ticket_id:id, response:text, status:status})
  }).then(r=>r.json()).then(j=>{ 
    if(j.success){ 
      document.getElementById('respond-alert').innerText='Response sent'; 
      setTimeout(()=>location.reload(),800); 
    } else { 
      document.getElementById('respond-alert').innerText = j.message || 'Failed'; 
    } 
  }).catch(e=>{ 
    document.getElementById('respond-alert').innerText='Network error'; 
  }); 
}

// Assign ticket handler
document.querySelectorAll('.assign-select').forEach(function(sel){
  sel.addEventListener('change', function(){
    var ticketId = this.dataset.ticketId;
    var adminId = this.value === '' ? null : parseInt(this.value);
    fetch('/admin/assign-ticket.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ticket_id: ticketId, admin_id: adminId})
    }).then(r=>r.json()).then(j=>{
      if(!j.success) alert('Failed: ' + (j.message||'unknown error'));
    }).catch(e=>{ alert('Network error'); });
  });
});

// Close ticket handler
document.querySelectorAll('.close-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    if(!confirm('Close this ticket?')) return;
    var ticketId = this.dataset.ticketId;
    fetch('/admin/close-ticket.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ticket_id: ticketId, status: 'closed'})
    }).then(r=>r.json()).then(j=>{
      if(j.success){ alert('Ticket closed'); location.reload(); } 
      else { alert('Error: ' + (j.message||'failed')); }
    }).catch(e=>{ alert('Network error'); });
  });
});
</script>
