<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config/security.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Access Control
$user_role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : '';
$current_page = basename($_SERVER['PHP_SELF']);

// Pages inaccessible to certain roles
$restricted_pages = [
    'staff' => ['inventory.php', 'testimonials.php', 'settings.php', 'banners.php', 'categories.php'],
    'admin' => ['users.php', 'database.php'],
];

if (isset($restricted_pages[$user_role]) && in_array($current_page, $restricted_pages[$user_role])) {
    // For the current user role, if the page is in their restricted list, redirect.
    header('Location: index.php');
    exit;
}

// Expected: $page_title (optional), $active_nav (dashboard|inventory|reports|products|categories|orders|customers|banners|testimonials|settings)
$page_title = isset($page_title) ? $page_title : 'Admin - Dija Accessories';
$active_nav = isset($active_nav) ? $active_nav : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="manifest" href="/admin-manifest.json">
  <meta name="theme-color" content="#c487a5">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --sidebar-bg:#333; --sidebar-txt:#fff; --sidebar-link:#ddd; --sidebar-link-hover:#fff;
      --accent:#c487a5; --bg:#f4f4f4; --card:#fff; --border:#eee; --txt:#111;
    }
    * { box-sizing:border-box; }
    body { margin:0; font-family: Arial, sans-serif; background:var(--bg); color:var(--txt); }
    a { text-decoration:none; }
    .admin-layout { display:flex; min-height:100vh; }
    .sidebar { width:240px; background:var(--sidebar-bg); color:var(--sidebar-txt); padding:16px; position:sticky; top:0; height:100vh; }
    .sidebar h2 { font-size:18px; margin:0 0 12px; color:#fff; }
    .nav-link { display:flex; align-items:center; gap:10px; color:var(--sidebar-link); padding:10px 12px; border-radius:6px; margin-bottom:6px; }
    .nav-link:hover, .nav-link.active { background:#444; color:var(--sidebar-link-hover); }
    .content { flex:1; display:flex; flex-direction:column; }
    .admin-header { background:#222; color:#fff; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; }
    .admin-header h1 { font-size:18px; margin:0; }
    .btn { padding:8px 12px; background:var(--accent); color:#fff; border:none; border-radius:6px; cursor:pointer; display:inline-block; }
    .btn.logout { background:#dc3545; }
    .main { padding:20px; }
    .card { background:var(--card); border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:16px; border:1px solid var(--border); }
    .card-header { padding:14px 18px; border-bottom:1px solid var(--border); font-weight:600; }
    .card-body { padding:18px; }
    
    /* Modal Styles */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
    .modal-content { background: var(--card); margin: 5% auto; padding: 20px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
    .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 20px; }
    .close:hover, .close:focus { color: #000; }
    
    /* Form Styles */
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; margin-bottom: 6px; color: var(--txt); font-weight: 500; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; font-family: Arial, sans-serif; }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--accent); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-row .form-group { margin-bottom: 0; }
    @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
    
    /* Button Styles */
    .btn-success { background: #28a745; }
    .btn-success:hover { background: #218838; }
    /* Scroll-to-top for admin */
    .scroll-top { position: fixed; right: 18px; bottom: 22px; width: 44px; height: 44px; border-radius: 50%; background: var(--accent); color: #fff; border: none; display: none; align-items: center; justify-content: center; cursor: pointer; z-index: 2000; box-shadow: 0 6px 18px rgba(0,0,0,0.15); font-size: 18px; }
    .scroll-top.show { display: flex; }
  </style>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/admin-sw.js', { scope: '/admin/' })
      .then(registration => {
        console.log('Admin Service Worker registered successfully:', registration);
      })
      .catch(error => {
        console.log('Admin Service Worker registration failed:', error);
      });
  }
  </script>
</head>
<body>
<div class="admin-layout">
  <aside class="sidebar">
    <h2>Dija Admin</h2>
    <a class="nav-link <?php echo $active_nav==='dashboard'?'active':''; ?>" href="/admin/index.php"><i class="fas fa-gauge"></i> <span>Dashboard</span></a>
    <a class="nav-link <?php echo $active_nav==='reports'?'active':''; ?>" href="/admin/reports.php"><i class="fas fa-chart-pie"></i> <span>Reports</span></a>
    
    <?php if (isset($user_role) && in_array($user_role, ['admin', 'superadmin'])): ?>
    <a class="nav-link <?php echo $active_nav==='inventory'?'active':''; ?>" href="/admin/inventory.php"><i class="fas fa-boxes"></i> <span>Inventory</span></a>
    <?php endif; ?>
    <a class="nav-link <?php echo $active_nav==='products'?'active':''; ?>" href="/admin/products.php"><i class="fas fa-gem"></i> <span>Products</span></a>

    <?php if (isset($user_role) && in_array($user_role, ['admin', 'superadmin'])): ?>
    <a class="nav-link <?php echo $active_nav==='shipping'?'active':''; ?>" href="/admin/shipping.php"><i class="fas fa-truck"></i> <span>Shipping Rates</span></a>
    <a class="nav-link <?php echo $active_nav==='categories'?'active':''; ?>" href="/admin/categories.php"><i class="fas fa-list"></i> <span>Categories</span></a>
    <?php endif; ?>

    <a class="nav-link <?php echo $active_nav==='orders'?'active':''; ?>" href="/admin/orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a>
    <a class="nav-link <?php echo $active_nav==='customers'?'active':''; ?>" href="/admin/customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
    <a class="nav-link <?php echo $active_nav==='support_tickets'?'active':''; ?>" href="/admin/support-tickets.php"><i class="fas fa-headset"></i> <span>Support Tickets</span></a>
    
    <?php if (isset($user_role) && in_array($user_role, ['admin', 'superadmin'])): ?>
    <a class="nav-link <?php echo $active_nav==='banners'?'active':''; ?>" href="/admin/banners.php"><i class="fas fa-image"></i> <span>Banners</span></a>
    <a class="nav-link <?php echo $active_nav==='testimonials'?'active':''; ?>" href="/admin/testimonials.php"><i class="fas fa-comment"></i> <span>Testimonials</span></a>
    <a class="nav-link <?php echo $active_nav==='settings'?'active':''; ?>" href="/admin/settings.php"><i class="fas fa-cogs"></i> <span>Settings</span></a>
    <?php endif; ?>
    
    <div style="margin-top:12px;"><a class="nav-link" href="/admin/index.php?logout=1"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a></div>
  </aside>
  <div class="content">
    <header class="admin-header">
      <h1><?php echo htmlspecialchars($page_title); ?></h1>
      <a href="/admin/index.php?logout=1" class="btn logout">Logout</a>
    </header>
    <main class="main">
    <button class="scroll-top" id="adminScrollTopBtn" aria-label="Scroll to top">↑</button>
