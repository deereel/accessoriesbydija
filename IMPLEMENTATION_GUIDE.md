# User Account System Implementation Guide

## Completed Files

### Authentication System
1. ✅ `auth/signup.php` - Handles user registration
2. ✅ `auth/login.php` - Handles user authentication
3. ✅ `auth/logout.php` - Handles user logout
4. ✅ `auth/check_session.php` - Checks if user is logged in
5. ✅ `signup.php` - User-facing signup page
6. ✅ `login.php` - User-facing login page

### Database Updates Needed
Run this SQL to add required tables:

```sql
-- Add remember_token column to customers table
ALTER TABLE customers ADD COLUMN remember_token VARCHAR(64) NULL;

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (customer_id, product_id)
);
```

## Files Still To Create

### Account Dashboard
- `account.php` - Main account dashboard with tabs
- `api/account/get_profile.php` - Get customer profile
- `api/account/update_profile.php` - Update customer profile
- `api/account/delete_account.php` - Delete customer account
- `api/account/get_addresses.php` - Get customer addresses
- `api/account/save_address.php` - Save/update address
- `api/account/delete_address.php` - Delete address
- `api/account/get_orders.php` - Get customer orders

### Cart System
- `cart.php` - Shopping cart page
- `api/cart/get_cart.php` - Get cart items
- `api/cart/add_to_cart.php` - Add item to cart
- `api/cart/update_cart.php` - Update cart item quantity
- `api/cart/remove_from_cart.php` - Remove item from cart
- `api/cart/clear_cart.php` - Clear all cart items
- `assets/js/cart.js` - Cart JavaScript functionality

### Checkout
- `checkout.php` - Checkout page
- `api/checkout/process_order.php` - Process order

## Next Steps

1. Run the SQL commands above to create necessary database tables
2. Implement the account dashboard (account.php)
3. Implement the cart system (cart.php and related APIs)
4. Add "My Account" and "Cart" links to the header navigation
5. Test the complete user flow: signup → login → add to cart → checkout

## Features Implemented

✅ User Registration with validation
✅ User Login with remember me option
✅ Session management
✅ Logout functionality
✅ Password hashing (bcrypt)
✅ Email validation
✅ Error handling

## Features To Implement

⏳ Account Dashboard with tabs
⏳ Profile management
⏳ Address management
⏳ Order history
⏳ Shopping cart
⏳ Checkout process
⏳ Account deletion