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
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem; }
        .dashboard-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .dashboard-card h3 { color: #333; margin-bottom: 1rem; }
        .dashboard-card .number { font-size: 2rem; font-weight: bold; color: #c487a5; }
        .recent-section { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
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
                <h3>Total Products</h3>
                <div class="number">156</div>
                <p>Active products in catalog</p>
            </div>
            <div class="dashboard-card">
                <h3>Total Orders</h3>
                <div class="number">89</div>
                <p>Orders this month</p>
            </div>
            <div class="dashboard-card">
                <h3>Revenue</h3>
                <div class="number">$12,450</div>
                <p>This month's revenue</p>
            </div>
            <div class="dashboard-card">
                <h3>Customers</h3>
                <div class="number">234</div>
                <p>Registered customers</p>
            </div>
        </div>

        <div class="recent-section">
            <h2>Recent Orders</h2>
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="background: #f8f8f8;">
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Order ID</th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Customer</th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Product</th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Amount</th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">#1001</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">Sarah Johnson</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">Diamond Solitaire Ring</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">$299</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;"><span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Completed</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">#1002</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">Michael Brown</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">Gold Chain Necklace</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">$159</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;"><span style="background: #ffc107; color: black; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Processing</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">#1003</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">Emma Davis</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">Pearl Drop Earrings</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;">$89</td>
                        <td style="padding: 1rem; border-bottom: 1px solid #eee;"><span style="background: #17a2b8; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Shipped</span></td>
                    </tr>
                </tbody>
            </table>
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