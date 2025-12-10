<?php
$page_title = "My Account";
$page_description = "Manage your account, orders, and preferences.";
include 'includes/header.php';
?>

<style>
.account-container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
.account-header { text-align: center; margin-bottom: 40px; }
.account-tabs { display: flex; border-bottom: 1px solid #e0e0e0; margin-bottom: 30px; }
.tab-btn { background: none; border: none; padding: 15px 30px; cursor: pointer; border-bottom: 2px solid transparent; }
.tab-btn.active { border-bottom-color: #C27BA0; color: #C27BA0; }
.tab-content { display: none; }
.tab-content.active { display: block; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
.form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; }
.btn-primary { background: #C27BA0; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; }
.order-item { background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 15px; }
</style>

<main>
    <div class="account-container">
        <div class="account-header">
            <h1>My Account</h1>
            <p>Manage your profile and orders</p>
        </div>

        <div class="account-tabs">
            <button class="tab-btn active" onclick="showTab('profile')">Profile</button>
            <button class="tab-btn" onclick="showTab('orders')">Orders</button>
            <button class="tab-btn" onclick="showTab('addresses')">Addresses</button>
        </div>

        <div id="profile" class="tab-content active">
            <h2>Profile Information</h2>
            <form>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" value="John">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" value="Doe">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="john@example.com">
                </div>
                <button type="submit" class="btn-primary">Update Profile</button>
            </form>
        </div>

        <div id="orders" class="tab-content">
            <h2>Order History</h2>
            <div class="order-item">
                <h3>Order #12345</h3>
                <p>Date: March 15, 2024</p>
                <p>Status: Delivered</p>
                <p>Total: £299.00</p>
            </div>
        </div>

        <div id="addresses" class="tab-content">
            <h2>Shipping Addresses</h2>
            <p>No addresses saved yet.</p>
            <button class="btn-primary">Add New Address</button>
        </div>
    </div>
</main>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>