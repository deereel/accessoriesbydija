<?php
$page_title = "Shopping Cart";
$page_description = "Review your selected jewelry items and proceed to checkout.";
include 'includes/header.php';
?>

<style>
.cart-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
.cart-header { text-align: center; margin-bottom: 40px; }
.cart-items { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.cart-item { display: flex; align-items: center; padding: 20px; border-bottom: 1px solid #f0f0f0; }
.item-image { width: 80px; height: 80px; background: #f5f5f5; border-radius: 8px; margin-right: 20px; }
.item-details { flex: 1; }
.item-name { font-weight: 600; margin-bottom: 5px; }
.item-price { color: #C27BA0; font-weight: 600; }
.quantity-controls { display: flex; align-items: center; gap: 10px; margin: 0 20px; }
.qty-btn { background: #f0f0f0; border: none; width: 30px; height: 30px; border-radius: 4px; cursor: pointer; }
.remove-btn { background: #ff4444; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
.cart-summary { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; margin-top: 30px; }
.checkout-btn { background: #C27BA0; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; cursor: pointer; width: 100%; }
.empty-cart { text-align: center; padding: 60px 20px; }
</style>

<main>
    <div class="cart-container">
        <div class="cart-header">
            <h1>Shopping Cart</h1>
            <p>Review your selected items</p>
        </div>

        <div class="empty-cart">
            <i class="fas fa-shopping-bag" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
            <h2>Your cart is empty</h2>
            <p>Add some beautiful jewelry to get started</p>
            <a href="products.php" style="background: #C27BA0; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 20px;">Shop Now</a>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>