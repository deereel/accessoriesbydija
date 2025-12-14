<?php
session_start();

// Simple admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    if (($_POST['username'] ?? '') === 'admin' && ($_POST['password'] ?? '') === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
    } else {
        if ($_POST) { $error = 'Invalid credentials'; }
    }
}

if (!isset($_SESSION['admin_logged_in'])): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - Dija Accessories</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
    .login-container { max-width: 400px; margin: 100px auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .login-form input { width: 100%; padding: 1rem; margin: 0.5rem 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .login-form button { width: 100%; padding: 1rem; background: #c487a5; color: white; border: none; border-radius: 4px; cursor: pointer; }
    .error { color: red; margin-bottom: 1rem; }
    h2 { text-align: center; color: #333; }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Admin Login</h2>
    <?php if (isset($error)): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <form method="POST" class="login-form">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <p style="text-align:center; color:#666; margin-top:1rem;">Default: admin / admin123</p>
  </div>
</body>
</html>
<?php exit; endif; ?>

<?php $page_title = 'Dashboard'; $active_nav = 'dashboard'; include __DIR__ . '/_layout_header.php'; ?>

<div class="card">
  <div class="card-header">Overview</div>
  <div class="card-body">
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
      <div class="card" style="border-left:4px solid #c487a5;">
        <div class="card-body">
          <div style="font-size:24px; color:#c487a5;"><i class="fas fa-gem"></i></div>
          <h3 style="margin:8px 0 4px;">Total Products</h3>
          <div style="font-size:28px; font-weight:700; color:#c487a5;">156</div>
          <p style="color:#666;">Active products in catalog</p>
          <a href="/admin/products.php" class="btn">Manage Products</a>
        </div>
      </div>
      <div class="card" style="border-left:4px solid #c487a5;">
        <div class="card-body">
          <div style="font-size:24px; color:#c487a5;"><i class="fas fa-shopping-cart"></i></div>
          <h3 style="margin:8px 0 4px;">Total Orders</h3>
          <div style="font-size:28px; font-weight:700; color:#c487a5;">89</div>
          <p style="color:#666;">Orders this month</p>
          <a href="/admin/orders.php" class="btn">View Orders</a>
        </div>
      </div>
      <div class="card" style="border-left:4px solid #c487a5;">
        <div class="card-body">
          <div style="font-size:24px; color:#c487a5;"><i class="fas fa-pound-sign"></i></div>
          <h3 style="margin:8px 0 4px;">Revenue</h3>
          <div style="font-size:28px; font-weight:700; color:#c487a5;">£12,450</div>
          <p style="color:#666;">This month's revenue</p>
          <a href="#" class="btn">View Reports</a>
        </div>
      </div>
      <div class="card" style="border-left:4px solid #c487a5;">
        <div class="card-body">
          <div style="font-size:24px; color:#c487a5;"><i class="fas fa-users"></i></div>
          <h3 style="margin:8px 0 4px;">Customers</h3>
          <div style="font-size:28px; font-weight:700; color:#c487a5;">234</div>
          <p style="color:#666;">Registered customers</p>
          <a href="/admin/customers.php" class="btn">View Customers</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="fas fa-clock"></i> Recent Orders</div>
  <div class="card-body">
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Order ID</th>
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Customer</th>
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Product</th>
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Amount</th>
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Status</th>
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>#1001</strong></td>
            <td>Sarah Johnson</td>
            <td>Diamond Solitaire Ring</td>
            <td><strong>£299</strong></td>
            <td><span style="padding:4px 8px; border-radius:12px; background:#d4edda; color:#155724; font-size:12px;">Completed</span></td>
            <td><a href="#" class="btn" style="padding:6px 10px; font-size:12px;">View</a></td>
          </tr>
          <tr>
            <td><strong>#1002</strong></td>
            <td>Michael Brown</td>
            <td>Gold Chain Necklace</td>
            <td><strong>£159</strong></td>
            <td><span style="padding:4px 8px; border-radius:12px; background:#fff3cd; color:#856404; font-size:12px;">Processing</span></td>
            <td><a href="#" class="btn" style="padding:6px 10px; font-size:12px;">View</a></td>
          </tr>
          <tr>
            <td><strong>#1003</strong></td>
            <td>Emma Davis</td>
            <td>Pearl Drop Earrings</td>
            <td><strong>£89</strong></td>
            <td><span style="padding:4px 8px; border-radius:12px; background:#d1ecf1; color:#0c5460; font-size:12px;">Shipped</span></td>
            <td><a href="#" class="btn" style="padding:6px 10px; font-size:12px;">View</a></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_footer.php'; ?>
