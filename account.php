<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "My Account";
$page_description = "Manage your account, orders, and preferences.";
include 'includes/header.php';

$customerId = $_SESSION['customer_id'];
$profile = null;
$addresses = [];
$orders = [];

try {
    // Database connection is already included in header.php, but we need the credentials.
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Fetch profile
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone, date_of_birth, gender FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch addresses
    $stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch orders
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$customerId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $key => $order) {
        $stmt = $pdo->prepare("SELECT oi.*, p.slug, (SELECT image_url FROM product_images WHERE product_id = oi.product_id AND is_primary = 1 LIMIT 1) as image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $orders[$key]['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // It's better to log errors than to display them to the user.
    error_log("Database Error: " . $e->getMessage());
    // You could show a generic error message to the user if you want.
}
?>

<link rel="stylesheet" href="assets/css/account.css">

<main>
    <div class="account-container">
        <div class="account-header">
            <h1>My Account</h1>
            <p>Welcome back, <?php echo htmlspecialchars($profile['first_name'] ?? 'Guest'); ?>!</p>
        </div>

        <div class="account-tabs">
            <button class="tab-btn active" data-tab="profile">Profile</button>
            <button class="tab-btn" data-tab="orders">Orders</button>
            <button class="tab-btn" data-tab="addresses">Addresses</button>
            <a href="auth/logout.php" class="logout-btn">Logout</a>
        </div>

        <div id="profile" class="tab-content active">
            <h2>Profile Information</h2>
            <form id="profile-form">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                </div>
                <button type="submit" class="btn-primary">Update Profile</button>
            </form>
            <div id="profile-message" class="message"></div>
        </div>

        <div id="orders" class="tab-content">
            <h2>Order History</h2>
            <?php if (empty($orders)): ?>
                <p>You have not placed any orders yet.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-item">
                        <div class="order-summary">
                            <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                            <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($order['created_at'])); ?></p>
                            <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($order['status'])); ?></p>
                            <p><strong>Total:</strong> £<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></p>
                        </div>
                        <div class="order-details">
                            <h4>Items:</h4>
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-product">
                                    <a href="product.php?slug=<?php echo $item['slug']; ?>">
                                        <img src="assets/images/<?php echo htmlspecialchars($item['image_url'] ?? 'placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                                    </a>
                                    <div class="product-info">
                                        <a href="product.php?slug=<?php echo $item['slug']; ?>"><?php echo htmlspecialchars($item['product_name']); ?></a>
                                        <span>Quantity: <?php echo $item['quantity']; ?></span>
                                        <span>Price: £<?php echo htmlspecialchars(number_format($item['unit_price'], 2)); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="addresses" class="tab-content">
            <h2>My Addresses</h2>
            <div id="address-list">
                <?php if (empty($addresses)): ?>
                    <p>No addresses saved yet.</p>
                <?php else: ?>
                    <?php foreach ($addresses as $address): ?>
                        <div class="address-item" data-id="<?php echo $address['id']; ?>">
                            <p>
                                <strong><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></strong><br>
                                <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                <?php if($address['address_line_2']) echo htmlspecialchars($address['address_line_2']) . '<br>'; ?>
                                <?php echo htmlspecialchars($address['city'] . ', ' . $address['postal_code']); ?><br>
                                <?php echo htmlspecialchars($address['country']); ?>
                            </p>
                            <button class="btn-secondary btn-edit-address">Edit</button>
                            <button class="btn-danger btn-delete-address">Delete</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button id="btn-add-new-address" class="btn-primary">Add New Address</button>
            
            <!-- Add/Edit Address Form (hidden by default) -->
            <div id="address-form-container" style="display:none;">
                <form id="address-form">
                    <input type="hidden" name="address_id" id="address_id">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Address Line 1</label>
                        <input type="text" name="address_line_1" required>
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" required>
                    </div>
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" required>
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" required>
                    </div>
                    <button type="submit" class="btn-primary">Save Address</button>
                    <button type="button" id="btn-cancel-address" class="btn-secondary">Cancel</button>
                </form>
            </div>
            <div id="address-message" class="message"></div>
        </div>
    </div>
</main>

<script src="assets/js/account.js"></script>

<?php include 'includes/footer.php'; ?>
