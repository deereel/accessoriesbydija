<?php
session_start();

// Simple admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_POST['username'] ?? '' === 'admin' && $_POST['password'] ?? '' === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
    } else {
        if ($_POST) {
            $error = "Invalid credentials";
        }
    }
}

if (!isset($_SESSION['admin_logged_in'])):
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
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" class="login-form">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p style="text-align: center; color: #666; margin-top: 1rem;">Default: admin / admin123</p>
    </div>
</body>
</html>
<?php exit; endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Dija Accessories</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .admin-header { background: #333; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .admin-nav { background: #c487a5; padding: 1rem; }
        .admin-nav a { color: white; text-decoration: none; margin-right: 2rem; padding: 0.5rem 1rem; border-radius: 4px; transition: background 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); }
        .admin-content { padding: 2rem; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .dashboard-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center; transition: transform 0.3s, box-shadow 0.3s; border-left: 4px solid #c487a5; }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
        .dashboard-card h3 { color: #333; margin-bottom: 1rem; font-size: 1.1rem; }
        .dashboard-card .number { font-size: 2.5rem; font-weight: 700; color: #c487a5; margin-bottom: 0.5rem; }
        .dashboard-card .icon { font-size: 2rem; color: #c487a5; margin-bottom: 1rem; }
        .dashboard-card p { color: #666; font-size: 0.9rem; }
        .recent-section { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .recent-section h2 { color: #333; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th { padding: 1rem; text-align: left; border-bottom: 2px solid #c487a5; background: #f8f9fa; font-weight: 600; }
        td { padding: 1rem; border-bottom: 1px solid #eee; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-processing { background: #fff3cd; color: #856404; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .btn { padding: 0.5rem 1rem; background: #c487a5; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #b07591; }
        .logout { background: #dc3545; }
        .logout:hover { background: #c82333; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Dija Accessories Admin</h1>
        <a href="?logout=1" class="btn logout">Logout</a>
    </header>

    <nav class="admin-nav">
        <a href="index.php" class="active">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="orders.php">Orders</a>
        <a href="customers.php">Customers</a>
        <a href="banners.php">Banners</a>
        <a href="testimonials.php">Testimonials</a>
    </nav>

    <main class="admin-content">
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="icon"><i class="fas fa-gem"></i></div>
                <h3>Total Products</h3>
                <div class="number">156</div>
                <p>Active products in catalog</p>
                <a href="products.php" class="btn" style="margin-top: 1rem;">Manage Products</a>
            </div>
            <div class="dashboard-card">
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                <h3>Total Orders</h3>
                <div class="number">89</div>
                <p>Orders this month</p>
                <a href="orders.php" class="btn" style="margin-top: 1rem;">View Orders</a>
            </div>
            <div class="dashboard-card">
                <div class="icon"><i class="fas fa-pound-sign"></i></div>
                <h3>Revenue</h3>
                <div class="number">£12,450</div>
                <p>This month's revenue</p>
                <a href="#" class="btn" style="margin-top: 1rem;">View Reports</a>
            </div>
            <div class="dashboard-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3>Customers</h3>
                <div class="number">234</div>
                <p>Registered customers</p>
                <a href="customers.php" class="btn" style="margin-top: 1rem;">View Customers</a>
            </div>
        </div>

        <div class="recent-section">
            <h2><i class="fas fa-clock"></i> Recent Orders</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>#1001</strong></td>
                            <td>Sarah Johnson</td>
                            <td>Diamond Solitaire Ring</td>
                            <td><strong>£299</strong></td>
                            <td><span class="status-badge status-completed">Completed</span></td>
                            <td><a href="#" class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">View</a></td>
                        </tr>
                        <tr>
                            <td><strong>#1002</strong></td>
                            <td>Michael Brown</td>
                            <td>Gold Chain Necklace</td>
                            <td><strong>£159</strong></td>
                            <td><span class="status-badge status-processing">Processing</span></td>
                            <td><a href="#" class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">View</a></td>
                        </tr>
                        <tr>
                            <td><strong>#1003</strong></td>
                            <td>Emma Davis</td>
                            <td>Pearl Drop Earrings</td>
                            <td><strong>£89</strong></td>
                            <td><span class="status-badge status-shipped">Shipped</span></td>
                            <td><a href="#" class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">View</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>

<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>