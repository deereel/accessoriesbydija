<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Expected: $page_title (optional), $active_nav (dashboard|products|categories|orders|customers|banners|testimonials|promos)
$page_title = isset($page_title) ? $page_title : 'Admin - Dija Accessories';
$active_nav = isset($active_nav) ? $active_nav : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
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
  </style>
</head>
<body>
<div class="admin-layout">
  <aside class="sidebar">
    <h2>Dija Admin</h2>
    <a class="nav-link <?php echo $active_nav==='dashboard'?'active':''; ?>" href="/admin/index.php"><i class="fas fa-gauge"></i> <span>Dashboard</span></a>
    <a class="nav-link <?php echo $active_nav==='products'?'active':''; ?>" href="/admin/products.php"><i class="fas fa-gem"></i> <span>Products</span></a>
    <a class="nav-link <?php echo $active_nav==='categories'?'active':''; ?>" href="/admin/categories.php"><i class="fas fa-list"></i> <span>Categories</span></a>
    <a class="nav-link <?php echo $active_nav==='orders'?'active':''; ?>" href="/admin/orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a>
    <a class="nav-link <?php echo $active_nav==='customers'?'active':''; ?>" href="/admin/customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
    <a class="nav-link <?php echo $active_nav==='banners'?'active':''; ?>" href="/admin/banners.php"><i class="fas fa-image"></i> <span>Banners</span></a>
    <a class="nav-link <?php echo $active_nav==='testimonials'?'active':''; ?>" href="/admin/testimonials.php"><i class="fas fa-comment"></i> <span>Testimonials</span></a>
    <a class="nav-link <?php echo $active_nav==='promos'?'active':''; ?>" href="/admin/promos.php"><i class="fas fa-ticket"></i> <span>Promo Codes</span></a>
    <div style="margin-top:12px;"><a class="nav-link" href="/admin/index.php?logout=1"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a></div>
  </aside>
  <div class="content">
    <header class="admin-header">
      <h1><?php echo htmlspecialchars($page_title); ?></h1>
      <a href="/admin/index.php?logout=1" class="btn logout">Logout</a>
    </header>
    <main class="main">
