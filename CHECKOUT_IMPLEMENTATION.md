# Checkout & Payment Implementation Checklist

## ✅ Files Created/Updated

### Core Checkout
- [x] `checkout.php` - Main checkout page with address selection, promo codes, payment method selection
- [x] `order-confirmation.php` - Order confirmation and receipt page

### API Endpoints

#### Orders
- [x] `api/orders/create.php` - Create pending order before payment

#### Addresses
- [x] `api/account/get_addresses.php` - Fetch/create customer addresses (already existed, verified working)

#### Promos
- [x] `api/promo/validate.php` - Validate promo codes

#### Payment Gateways - Paystack
- [x] `api/payments/paystack/initialize.php` - Initialize Paystack transaction
- [x] `api/payments/paystack/verify.php` - Verify payment & handle webhook

#### Payment Gateways - Stripe
- [x] `api/payments/stripe/create-session.php` - Create Stripe Checkout Session
- [x] `api/payments/stripe/webhook.php` - Handle Stripe webhooks

#### Payment Gateways - Remita
- [x] `api/payments/remita/initialize.php` - Generate RRR and initialize payment
- [x] `api/payments/remita/verify.php` - Verify payment & handle webhook

### Documentation
- [x] `PAYMENT_SETUP.md` - Comprehensive setup guide for all payment gateways

---

## 📋 Database Setup Required

### Tables to Create
```sql
-- Run these SQL commands in your database:

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_amount DECIMAL(10,2) DEFAULT 5.00,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    address_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (address_id) REFERENCES customer_addresses(id)
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Customer addresses table (if doesn't exist)
CREATE TABLE IF NOT EXISTS customer_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);
```

---

## 🔧 Configuration Steps

### Step 1: Create Environment File
Create `.env` file in project root (or configure via server environment):
```
# Paystack
PAYSTACK_PUBLIC_KEY=pk_test_xxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxx

# Stripe
STRIPE_PUBLISHABLE_KEY=pk_test_xxxxx
STRIPE_SECRET_KEY=sk_test_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_test_xxxxx

# Remita
REMITA_MERCHANT_ID=123456
REMITA_API_KEY=your_api_key
REMITA_SERVICE_ID=service_id
REMITA_PUBLIC_KEY=pk_test_xxxxx (optional)
```

### Step 2: Configure Paystack
1. Visit https://paystack.com
2. Sign up and get test keys
3. Set webhook URL: `https://yourdomain.com/api/payments/paystack/verify.php`
4. Subscribe to webhook events: `charge.success`, `charge.failed`
5. Copy test keys to environment file

### Step 3: Configure Stripe
1. Visit https://stripe.com
2. Get test API keys
3. Install Stripe PHP: `composer require stripe/stripe-php`
4. Uncomment require statement in `/api/payments/stripe/create-session.php`
5. Set webhook URL: `https://yourdomain.com/api/payments/stripe/webhook.php`
6. Copy signing secret to environment
7. Copy keys to environment file

### Step 4: Configure Remita
1. Visit https://remita.net
2. Register as merchant
3. Get Merchant ID, API Key, and Service ID
4. Set webhook URL: `https://yourdomain.com/api/payments/remita/verify.php`
5. Copy credentials to environment file

### Step 5: Navigation Links
Add link to checkout from cart/products pages:
```php
<a href="/checkout.php" class="btn btn-primary">Proceed to Checkout</a>
```

---

## 🧪 Testing Workflow

### Test Paystack
1. Use test API keys
2. Click checkout → select Paystack
3. Enter test card: `4084 0343 0000 9010`
4. Verify order created in database
5. Verify payment verified and order status changed to "paid"

### Test Stripe
1. Use test API keys
2. Click checkout → select Stripe
3. Enter test card: `4242 4242 4242 4242`
4. Complete checkout
5. Verify webhook received (check Stripe dashboard)
6. Verify order status updated to "paid"

### Test Remita
1. Use sandbox credentials
2. Click checkout → select Remita
3. Complete test payment flow
4. Verify RRR generation
5. Verify order status updated

---

## 🔒 Security Checklist (Before Production)

- [ ] All environment variables set with LIVE keys
- [ ] HTTPS enabled on all payment pages
- [ ] Webhook URLs configured in all gateways
- [ ] Database backup strategy in place
- [ ] Error logging implemented (no sensitive data in logs)
- [ ] Payment signature verification enabled (especially Stripe)
- [ ] Amount verification implemented on server
- [ ] Duplicate order prevention implemented
- [ ] Rate limiting on API endpoints
- [ ] CORS headers configured properly
- [ ] Input validation on all forms
- [ ] SQL injection prevention (use prepared statements)
- [ ] XSS prevention (use htmlspecialchars)
- [ ] Test with real small amounts (£0.01-£1.00)
- [ ] Monitor webhook delivery logs

---

## 📧 TODO Items (Not Yet Implemented)

### Email Notifications
- [ ] Create `/api/emails/send-order-confirmation.php`
- [ ] Send confirmation email with order details
- [ ] Send shipping notification when order ships
- [ ] Send delivery confirmation
- [ ] Implement email templates

### Inventory Management
- [ ] Deduct from inventory when order is paid
- [ ] Re-add to inventory if payment fails
- [ ] Alert when stock is low after order

### Refunds
- [ ] Create `/api/refunds/create.php` for admin refunds
- [ ] Handle refunds from Paystack
- [ ] Handle refunds from Stripe
- [ ] Handle refunds from Remita
- [ ] Send refund notification to customer

### Order Management
- [ ] Admin page to view all orders
- [ ] Order status tracking
- [ ] Bulk order actions (mark shipped, etc.)
- [ ] Order search/filtering

### Shipping
- [ ] Implement shipping label generation
- [ ] Shipping carrier integration (if needed)
- [ ] Real-time shipping rate calculation
- [ ] Tracking number management

---

## 🐛 Common Issues & Solutions

### "Paystack configuration incomplete"
**Solution**: Check PAYSTACK_SECRET_KEY environment variable is set correctly

### "Stripe session not created"
**Solution**: Ensure `stripe/stripe-php` package is installed and autoloader required

### "RRR already exists"
**Solution**: Checkout logic already handles duplicate RRR generation - wait a second and retry

### "Webhook not received"
**Solution**: 
- Verify webhook URL is HTTPS and publicly accessible
- Check webhook is subscribed to correct events in gateway dashboard
- Check server logs for incoming POST requests

### "Order not found during verification"
**Solution**: Ensure order is created with 'pending' status before payment gateway redirect

---

## 📞 Support

For gateway-specific issues:
- **Paystack**: https://paystack.com/support
- **Stripe**: https://stripe.com/docs
- **Remita**: https://remita.net/help

---

## Next Steps

1. ✅ Create database tables (see "Database Setup Required")
2. ✅ Configure environment variables (see "Configuration Steps")
3. ✅ Test with test keys for each gateway
4. ✅ Implement TODO items (emails, refunds, etc.)
5. ✅ Switch to live keys and test with small amounts
6. ✅ Monitor logs and webhook delivery
7. ✅ Set up email notifications
8. ✅ Implement inventory management
