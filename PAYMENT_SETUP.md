# Checkout & Payment Gateway Integration Guide

## Overview
This guide provides complete setup instructions for the Dija Accessories checkout system with Paystack, Stripe, and Remita payment gateway integrations.

---

## File Structure

```
checkout.php                                    # Main checkout page
├── api/
│   ├── cart.php                               # Cart API (existing)
│   ├── orders/
│   │   └── create.php                         # Create pending order
│   ├── promo/
│   │   └── validate.php                       # Validate promo codes
│   ├── payments/
│   │   ├── paystack/
│   │   │   ├── initialize.php                 # Init Paystack transaction
│   │   │   └── verify.php                     # Verify payment & webhook
│   │   ├── stripe/
│   │   │   ├── create-session.php             # Create Stripe session
│   │   │   └── webhook.php                    # Webhook handler
│   │   └── remita/
│   │       ├── initialize.php                 # Generate RRR & init
│   │       └── verify.php                     # Verify & webhook
│   └── account/
│       └── get_addresses.php                  # Get customer addresses
└── includes/
    ├── header.php                             # Page header (existing)
    └── footer.php                             # Page footer (existing)
```

---

## Database Schema Requirements

### `orders` Table
```sql
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
```

### `order_items` Table (for detailed order records)
```sql
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
```

### `customer_addresses` Table (if not exists)
```sql
CREATE TABLE customer_addresses (
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

## Checkout Flow

### 1. **User Access Checkout**
- Navigate to `/checkout.php`
- Must have items in cart (session/localStorage/DB)
- If not logged in, prompted to log in or continue as guest

### 2. **Enter Contact & Shipping Info**
- Email address
- Select existing address or enter new one
- Phone number & address details

### 3. **Apply Promo Code**
- Optional promo code entry
- `/api/promo/validate.php` validates code
- Returns discount percentage or fixed amount
- Updates order summary

### 4. **Select Payment Method**
- Paystack
- Stripe
- Remita

### 5. **Place Order**
- Calls `/api/orders/create.php`
- Creates pending order in database
- Clears cart
- Redirects to payment gateway

### 6. **Complete Payment**
- Customer enters payment details at gateway
- Gateway processes payment
- Webhook OR redirect verifies payment
- Order status updated to "paid"

### 7. **Order Confirmation**
- Redirect to `/order-confirmation.php` with order ID
- Display order details
- Send confirmation email

---

## Paystack Integration

### Setup Steps

1. **Create Account**
   - Visit https://paystack.com
   - Sign up and complete business verification
   - Access dashboard

2. **Get API Keys**
   - Dashboard → Settings → API Keys & Webhooks
   - Copy **Public Key** and **Secret Key**

3. **Configure Environment**
   - Add to `.env` or server environment:
   ```
   PAYSTACK_PUBLIC_KEY=pk_test_xxxxx (testing)
   PAYSTACK_SECRET_KEY=sk_test_xxxxx (testing)
   ```
   - For production: use `pk_live_` and `sk_live_` keys

4. **Setup Webhook**
   - Dashboard → Settings → API Keys & Webhooks
   - Webhook URL: `https://yourdomain.com/api/payments/paystack/verify.php`
   - Events to subscribe: `charge.success`, `charge.failed`, `charge.dispute`

5. **Verify File Configuration**
   - Edit `/api/payments/paystack/initialize.php`
   - Ensure `getenv('PAYSTACK_SECRET_KEY')` matches your setup
   - Test with test keys first

### Flow
1. User selects Paystack → clicks "Place Order"
2. `/api/orders/create.php` creates pending order
3. Redirects to `/api/payments/paystack/initialize.php?order_id=123`
4. Initialize endpoint creates Paystack transaction (kobo conversion)
5. User redirected to Paystack checkout page
6. After payment, Paystack redirects back to verify endpoint OR sends webhook
7. Verify endpoint confirms payment and updates order status

### Testing
- Use test keys from Paystack dashboard
- Test card: 4084 0343 0000 9010
- Test mobile: 0803 456 7890
- Any future expiry date
- Any 3-digit CVV

---

## Stripe Integration

### Setup Steps

1. **Create Account**
   - Visit https://stripe.com
   - Sign up and verify email
   - Access dashboard

2. **Get API Keys**
   - Dashboard → Developers → API Keys
   - Copy **Publishable Key** and **Secret Key**
   - Save test keys first

3. **Install Stripe PHP Library**
   ```bash
   composer require stripe/stripe-php
   ```

4. **Configure Environment**
   ```
   STRIPE_PUBLISHABLE_KEY=pk_test_xxxxx
   STRIPE_SECRET_KEY=sk_test_xxxxx
   STRIPE_WEBHOOK_SECRET=whsec_test_xxxxx
   ```

5. **Setup Webhook**
   - Dashboard → Developers → Webhooks
   - Add endpoint: `https://yourdomain.com/api/payments/stripe/webhook.php`
   - Events: `checkout.session.completed`, `payment_intent.succeeded`, `charge.failed`
   - Copy signing secret for `STRIPE_WEBHOOK_SECRET`

6. **Update Configuration Files**
   - Uncomment Stripe library require in `/api/payments/stripe/create-session.php`
   - Ensure `getenv()` calls match your setup

### Flow
1. User selects Stripe → clicks "Place Order"
2. `/api/orders/create.php` creates pending order
3. Redirects to `/api/payments/stripe/create-session.php?order_id=123`
4. Creates Stripe Checkout Session (pence conversion for GBP)
5. User redirected to Stripe Checkout page
6. After payment, Stripe sends webhook to verify endpoint
7. Webhook handler updates order status to "paid"

### Testing
- Use test keys
- Test card: 4242 4242 4242 4242
- Expiry: Any future date (MM/YY)
- CVC: Any 3 digits
- Billing ZIP: Any 5 digits

---

## Remita Integration

### Setup Steps

1. **Create Account**
   - Visit https://remita.net
   - Register as merchant
   - Complete business verification

2. **Get Credentials**
   - Dashboard → Settings
   - Copy **Merchant ID**
   - Copy **API Key**
   - Create service for "Jewelry/E-Commerce" to get **Service ID**
   - Optional: Get **Public Key** for client-side features

3. **Configure Environment**
   ```
   REMITA_MERCHANT_ID=123456
   REMITA_API_KEY=your_api_key_here
   REMITA_SERVICE_ID=service_id_here
   REMITA_PUBLIC_KEY=pk_test_xxxxx (optional)
   ```

4. **Setup Webhook**
   - Remita Dashboard → Integration → Webhooks
   - POST Webhook URL: `https://yourdomain.com/api/payments/remita/verify.php`
   - Configure for transaction notifications

5. **Test Configuration**
   - Use sandbox API first: https://remita.net/sandbox
   - Use test merchant ID and API keys
   - Switch to live after testing

### Flow
1. User selects Remita → clicks "Place Order"
2. `/api/orders/create.php` creates pending order
3. Redirects to `/api/payments/remita/initialize.php?order_id=123`
4. Generates unique RRR (Remita Reference Number)
5. Initiates payment at Remita API
6. User redirected to Remita payment page
7. After payment, Remita redirects or webhooks to verify endpoint
8. Verify endpoint confirms via RRR and updates order

### Testing
- Use sandbox credentials
- Test reference numbers provided by Remita
- Test different payment methods (bank, card, wallet)

---

## Security Considerations

### 1. **Amount Verification**
- ALWAYS verify amount on server side
- Prevent price manipulation attacks
- Compare exact amounts (accounting for currency)
- Examples:
  - Paystack: amount in kobo must match (GBP * 100)
  - Stripe: amount in pence must match (GBP * 100)
  - Remita: amount in GBP must match

### 2. **Reference/Order Number Verification**
- Ensure unique order numbers
- Prevent duplicate order processing
- Use idempotent checks (e.g., `AND status = 'pending'`)
- Store reference for audit trail

### 3. **Signature Verification**
- **Paystack**: Verify webhook signature with secret key
- **Stripe**: Always validate webhook signature (`hash_hmac` with secret)
- **Remita**: Verify request hash if available

### 4. **HTTPS Only**
- All payment pages must use HTTPS
- Webhooks must be HTTPS only
- Never send payment data over HTTP

### 5. **Environment Variables**
- Never hardcode API keys
- Use `.env` file (not in version control)
- Use different keys for test/production
- Rotate keys periodically

### 6. **Error Handling**
- Don't expose sensitive error messages to users
- Log errors securely
- Never log full payment details
- Handle failed payments gracefully

---

## Production Checklist

- [ ] Database tables created (orders, order_items, customer_addresses)
- [ ] HTTPS certificate installed
- [ ] Environment variables configured with LIVE keys
- [ ] Webhook endpoints accessible from internet
- [ ] Webhook signing secrets configured
- [ ] Error logging setup (do not expose to users)
- [ ] Email confirmations implemented
- [ ] Inventory tracking on order completion
- [ ] Refund process documented
- [ ] Payment reconciliation reports setup
- [ ] Test with live keys (small amounts)
- [ ] Monitor webhook delivery logs

---

## Troubleshooting

### Payment Gateway Not Initializing
- Check API keys are correct and active
- Verify environment variables are loaded (`getenv()`)
- Check order exists in database with 'pending' status
- Verify cURL is enabled on server

### Webhooks Not Received
- Check webhook URL is HTTPS and accessible
- Verify webhook is configured in gateway dashboard
- Check server logs for incoming requests
- Ensure webhook handler is not throwing errors

### Amount Mismatch Errors
- Verify currency conversion (GBP * 100 for kobo/pence)
- Check discount calculations
- Ensure shipping is included in total
- Verify order total is calculated consistently

### Orders Not Updating After Payment
- Check webhook handler logs
- Verify order exists in database before payment
- Ensure database connection works in webhook context
- Check order status is 'pending' before updating

---

## Email Notifications (TODO)

Implement email confirmations:
- Order confirmation with details
- Payment received notification
- Shipping notification
- Delivery confirmation

---

## Refund Processing (TODO)

Create refund API:
- `/api/refunds/create.php` - Initiate refund
- `/api/refunds/process.php` - Handle refund webhook
- Gateway-specific refund logic (Paystack, Stripe, Remita)

---

## Testing Checklist

### Paystack
- [ ] Test with test keys
- [ ] Successful payment flow
- [ ] Failed payment handling
- [ ] Webhook delivery
- [ ] Order status update

### Stripe
- [ ] Create Checkout Session
- [ ] Successful payment
- [ ] Failed payment
- [ ] Webhook signature validation
- [ ] Order status update

### Remita
- [ ] RRR generation
- [ ] Payment initialization
- [ ] Payment verification
- [ ] Webhook handling
- [ ] Order status update

### All Gateways
- [ ] Promo code application
- [ ] Shipping address validation
- [ ] Order number uniqueness
- [ ] Cart clearing after payment
- [ ] Order confirmation page display

---

## Support Resources

- **Paystack**: https://paystack.com/support
- **Stripe**: https://stripe.com/docs
- **Remita**: https://remita.net/help

---

## Notes

- All endpoints expect POST/GET as specified
- All endpoints return JSON responses
- Status codes: 200 (success), 400 (bad request), 404 (not found), 500 (error)
- Store all payment metadata for audit/reconciliation
- Keep detailed logs of all payment transactions
