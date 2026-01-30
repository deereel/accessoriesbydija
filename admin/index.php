<?php
session_start();

// Database-driven admin authentication
if (!isset($_SESSION['admin_logged_in']) && isset($_POST['username'])) {
    require_once __DIR__ . '/../app/config/database.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    $error = null;

    try {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username OR email = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!isset($_POST['captcha']) || strtoupper($_POST['captcha']) !== $_SESSION['admin_captcha']) {
                $error = 'Invalid verification code.';
            } elseif ($user['is_active']) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_full_name'] = $user['full_name'];
                $_SESSION['admin_role'] = $user['role'];

                // Update last login timestamp
                $update_stmt = $pdo->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->execute([$user['id']]);

                header('Location: index.php');
                exit;
            } else {
                $error = 'Your account is inactive.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    } catch (PDOException $e) {
        $error = 'A database error occurred. Please try again later.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    if(session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['admin_logged_in'])):
// Generate CAPTCHA
$captcha_code = strtoupper(substr(md5(rand()), 0, 5));
$_SESSION['admin_captcha'] = $captcha_code;
?>
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
      <div style="margin: 1rem 0; text-align: center;">
        <div style="display: inline-block; padding: 0.5rem; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 1.2rem; letter-spacing: 0.2rem;">
          <?php echo $captcha_code; ?>
        </div>
      </div>
      <input type="text" name="captcha" placeholder="Enter the code above" required style="text-transform: uppercase;">
      <button type="submit">Login</button>
    </form>
    <p style="text-align:center; color:#666; margin-top:1rem; font-size:12px;">Please use your assigned administrator credentials to log in.</p>
  </div>
</body>
</html>
<?php exit; endif; ?>

<?php
require_once __DIR__ . '/../app/config/database.php';

$page_title = 'Dashboard';
$active_nav = 'dashboard';

// Fetch dashboard statistics
$total_products = 0;
$total_orders = 0;
$total_customers = 0;
$monthly_revenue = 0;
$recent_orders = [];

try {
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $total_products = $stmt->fetch()['count'] ?? 0;
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $total_orders = $stmt->fetch()['count'] ?? 0;
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
    $total_customers = $stmt->fetch()['count'] ?? 0;
    
    // Monthly revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $monthly_revenue = $stmt->fetch()['revenue'] ?? 0;
    
    // Recent orders
    $stmt = $pdo->query("SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at, c.first_name, c.last_name 
                         FROM orders o 
                         LEFT JOIN customers c ON o.customer_id = c.id 
                         ORDER BY o.created_at DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Database error - values will remain 0
}

include __DIR__ . '/_layout_header.php';
?>

<div class="card">
  <div class="card-header">Overview</div>
  <div class="card-body">
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
      <div class="card" style="border-left:4px solid #c487a5;">
        <div class="card-body">
          <div style="font-size:24px; color:#c487a5;"><i class="fas fa-gem"></i></div>
          <h3 style="margin:8px 0 4px;">Total Products</h3>
          <div style="font-size:28px; font-weight:700; color:#c487a5;"><?php echo intval($total_products); ?></div>
          <p style="color:#666;">Active products in catalog</p>
          <a href="/admin/products.php" class="btn">Manage Products</a>
        </div>
      </div>
      <div class="card" style="border-left:4px solid #c487a5;">
        <div class="card-body">
          <div style="font-size:24px; color:#c487a5;"><i class="fas fa-shopping-cart"></i></div>
          <h3 style="margin:8px 0 4px;">Total Orders</h3>
          <div style="font-size:28px; font-weight:700; color:#c487a5;"><?php echo intval($total_orders); ?></div>
          <p style="color:#666;">All orders</p>
          <a href="/admin/orders.php" class="btn">View Orders</a>
        </div>
      </div>
      <div class="card" style="border-left:4px solid #c487a5;">
        <div class="card-body">
          <div style="font-size:24px; color:#c487a5;"><i class="fas fa-pound-sign"></i></div>
          <h3 style="margin:8px 0 4px;">Revenue</h3>
          <div style="font-size:28px; font-weight:700; color:#c487a5;">£<?php echo number_format(floatval($monthly_revenue), 2); ?></div>
          <p style="color:#666;">This month's revenue</p>
          <a href="/admin/reports.php" class="btn">View Reports</a>
        </div>
      </div>
      <div class="card" style="border-left:4px solid #c487a5;">
        <div class="card-body">
          <div style="font-size:24px; color:#c487a5;"><i class="fas fa-users"></i></div>
          <h3 style="margin:8px 0 4px;">Customers</h3>
          <div style="font-size:28px; font-weight:700; color:#c487a5;"><?php echo intval($total_customers); ?></div>
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
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Amount</th>
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Status</th>
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Date</th>
            <th style="padding:10px; border-bottom:1px solid #eee; text-align:left;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recent_orders)): ?>
            <tr><td colspan="6" style="padding:10px; text-align:center; color:#999;">No orders yet</td></tr>
          <?php else: foreach ($recent_orders as $order): ?>
            <tr>
              <td style="padding:10px;"><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
              <td style="padding:10px;">
                <?php 
                $customer_name = (!empty($order['first_name']) || !empty($order['last_name'])) 
                    ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) 
                    : 'Guest';
                echo $customer_name;
                ?>
              </td>
              <td style="padding:10px;"><strong>£<?php echo number_format(floatval($order['total_amount']), 2); ?></strong></td>
              <td style="padding:10px;">
                <?php
                $status = htmlspecialchars($order['status']);
                $status_colors = [
                    'completed' => '#d4edda',
                    'processing' => '#fff3cd',
                    'shipped' => '#d1ecf1',
                    'pending' => '#fff3cd',
                    'cancelled' => '#f8d7da'
                ];
                $status_text_colors = [
                    'completed' => '#155724',
                    'processing' => '#856404',
                    'shipped' => '#0c5460',
                    'pending' => '#856404',
                    'cancelled' => '#721c24'
                ];
                $bg = $status_colors[$status] ?? '#f0f0f0';
                $txt = $status_text_colors[$status] ?? '#666';
                $display_status = ucfirst($status);
                ?>
                <span style="padding:4px 8px; border-radius:12px; background:<?php echo $bg; ?>; color:<?php echo $txt; ?>; font-size:12px;">
                  <?php echo $display_status; ?>
                </span>
              </td>
              <td style="padding:10px;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
              <td style="padding:10px;"><a href="#" class="btn" style="padding:6px 10px; font-size:12px;">View</a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_footer.php'; ?>
