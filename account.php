<?php
session_start();
$page_title = "My Account - Dija Accessories";
$page_description = "Manage your account, orders, and preferences";

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php?redirect=account.php');
    exit;
}

require_once 'config/database.php';

// Get customer data
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database error");
}

include 'includes/header.php';
?>

<main class="account-page">
    <div class="container">
        <div class="account-header">
            <h1>My Account</h1>
            <p>Welcome back, <?php echo htmlspecialchars($customer['first_name']); ?>!</p>
        </div>
        
        <div class="account-layout">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar">
                <nav class="account-nav">
                    <a href="#" class="account-nav-item active" data-tab="dashboard">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#" class="account-nav-item" data-tab="orders">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Orders</span>
                    </a>
                    <a href="#" class="account-nav-item" data-tab="addresses">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Addresses</span>
                    </a>
                    <a href="#" class="account-nav-item" data-tab="profile">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                    <a href="#" class="account-nav-item" data-tab="settings">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="auth/logout.php" class="account-nav-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </nav>
            </aside>
            
            <!-- Main Content -->
            <div class="account-content">
                <!-- Dashboard Tab -->
                <div class="account-tab active" id="dashboard-tab">
                    <h2>Dashboard</h2>
                    
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="total-orders">0</h3>
                                <p>Total Orders</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="total-addresses">0</h3>
                                <p>Saved Addresses</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo date('M Y', strtotime($customer['created_at'])); ?></h3>
                                <p>Member Since</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recent-orders">
                        <h3>Recent Orders</h3>
                        <div id="recent-orders-list">
                            <p class="text-muted">Loading orders...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <div class="account-tab" id="orders-tab">
                    <div class="tab-header">
                        <h2>Order History</h2>
                        <a href="products.php" class="btn-primary">
                            <i class="fas fa-shopping-bag"></i> Order Now
                        </a>
                    </div>
                    <div id="orders-list">
                        <p class="text-muted">Loading orders...</p>
                    </div>
                </div>
                
                <!-- Addresses Tab -->
                <div class="account-tab" id="addresses-tab">
                    <div class="tab-header">
                        <h2>Saved Addresses</h2>
                        <button class="btn-primary" id="add-address-btn">
                            <i class="fas fa-plus"></i> Add Address
                        </button>
                    </div>
                    <div id="addresses-list">
                        <p class="text-muted">Loading addresses...</p>
                    </div>
                </div>
                
                <!-- Profile Tab -->
                <div class="account-tab" id="profile-tab">
                    <h2>Profile Information</h2>
                    
                    <form id="profile-form" class="account-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($customer['date_of_birth'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select</option>
                                    <option value="male" <?php echo $customer['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $customer['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $customer['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="profile-message" class="message" style="display: none;"></div>
                        
                        <button type="submit" class="btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <!-- Settings Tab -->
                <div class="account-tab" id="settings-tab">
                    <h2>Account Settings</h2>
                    
                    <!-- Change Password -->
                    <div class="settings-section">
                        <h3>Change Password</h3>
                        <form id="password-form" class="account-form">
                            <div class="form-group">
                                <label for="current_password">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password *</label>
                                <input type="password" id="new_password" name="new_password" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_new_password">Confirm New Password *</label>
                                <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                            </div>
                            
                            <div id="password-message" class="message" style="display: none;"></div>
                            
                            <button type="submit" class="btn-primary">Change Password</button>
                        </form>
                    </div>
                    
                    <!-- Delete Account -->
                    <div class="settings-section danger-zone">
                        <h3>Danger Zone</h3>
                        <p>Once you delete your account, there is no going back. Please be certain.</p>
                        <button class="btn-danger" id="delete-account-btn">Delete Account</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Address Modal -->
<div id="address-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="address-modal-title">Add Address</h2>
        
        <form id="address-form">
            <input type="hidden" id="address_id" name="address_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="address_first_name">First Name *</label>
                    <input type="text" id="address_first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="address_last_name">Last Name *</label>
                    <input type="text" id="address_last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="address_type">Address Type *</label>
                <select id="address_type" name="type" required>
                    <option value="shipping">Shipping</option>
                    <option value="billing">Billing</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="company">Company (Optional)</label>
                <input type="text" id="company" name="company">
            </div>
            
            <div class="form-group">
                <label for="address_line_1">Address Line 1 *</label>
                <input type="text" id="address_line_1" name="address_line_1" required>
            </div>
            
            <div class="form-group">
                <label for="address_line_2">Address Line 2</label>
                <input type="text" id="address_line_2" name="address_line_2">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="city">City *</label>
                    <input type="text" id="city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="state">State *</label>
                    <input type="text" id="state" name="state" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="postal_code">Postal Code *</label>
                    <input type="text" id="postal_code" name="postal_code" required>
                </div>
                <div class="form-group">
                    <label for="country">Country *</label>
                    <input type="text" id="country" name="country" value="United Kingdom" required>
                </div>
            </div>
            
            <div class="form-check">
                <input type="checkbox" id="is_default" name="is_default">
                <label for="is_default">Set as default address</label>
            </div>
            
            <div id="address-message" class="message" style="display: none;"></div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="cancel-address-btn">Cancel</button>
                <button type="submit" class="btn-primary">Save Address</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal">
    <div class="modal-content">
        <h2>Delete Account</h2>
        <p>Are you sure you want to delete your account? This action cannot be undone.</p>
        <p>Please type <strong>DELETE</strong> to confirm:</p>
        
        <input type="text" id="delete-confirm" class="form-control" placeholder="Type DELETE">
        
        <div class="modal-actions">
            <button type="button" class="btn-secondary" id="cancel-delete-btn">Cancel</button>
            <button type="button" class="btn-danger" id="confirm-delete-btn">Delete Account</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="assets/css/account.css">
<script src="assets/js/account.js"></script>

<?php include 'includes/footer.php'; ?>