# Email Documentation

This document outlines all email notifications sent by the Dija Accessories system, including their triggers, content, and scenarios.

## Email Types and Triggers

### 1. Order Confirmation Email
**Function:** `send_order_confirmation_email($pdo, $order_id)`

**Trigger Scenarios:**
- After successful payment via Stripe webhook (`api/payments/stripe/webhook.php`)
- After successful payment verification via Paystack (`api/payments/paystack/verify.php`)
- After successful payment verification via Remita (`api/payments/remita/verify.php`)
- After order creation via API (`api/orders/create.php`)

**Recipient:** Customer (from order record)

**Content:**
- Order number and details
- Itemized list with quantities and prices
- Subtotal, shipping, discount, and total amounts
- Message that another email will be sent when order ships

**Notes:** Logged to `email_logs` table for tracking.

---

### 2. Welcome Email
**Function:** `send_welcome_email($customer_email, $customer_name)`

**Trigger Scenarios:**
- After successful user registration/signup (`auth/signup.php`)

**Recipient:** New customer

**Content:**
- Welcome message with customer name
- List of account benefits (wishlist, addresses, order history, faster checkout, exclusive offers)
- Link to start shopping
- Contact information

---

### 3. Admin Order Notification
**Function:** `send_admin_order_notification($pdo, $order_id)`

**Trigger Scenarios:**
- After order creation via API (`api/orders/create.php`)

**Recipient:** Admin (configured via `ADMIN_EMAIL` env variable, defaults to `admin@accessoriesbydija.uk`)

**Content:**
- New order alert
- Order details (ID, number, customer info, email)
- Total amount
- Itemized list of products

---

### 4. Refund Notification Email
**Function:** `send_refund_notification_email($pdo, $order_id, $amount, $reason)`

**Trigger Scenarios:**
- After successful refund processing via admin panel (`api/refunds/create.php`)
- Supports partial and full refunds

**Recipient:** Customer (from order record)

**Content:**
- Refund confirmation
- Order number
- Refund amount
- Reason for refund
- Timeline for refund appearance in payment method (3-5 business days)
- Contact information

---

### 5. Abandoned Cart Email
**Function:** `send_abandoned_cart_email($email, $cart_items)`

**Trigger Scenarios:**
- Cron job execution of `scripts/process_abandoned_carts.php`
- Cart inactive for 24+ hours
- Customer has email (registered user or guest with email)
- Not previously emailed for this cart

**Recipient:** Customer or guest with cart

**Content:**
- Reminder about abandoned items
- HTML table with cart items, quantities, and prices
- Subtotal calculation
- Call-to-action button to complete order
- Unsubscribe link

**Notes:** Limited to 50 emails per run to prevent spam. Tracks sent emails in `abandoned_carts` table.

---

### 6. Shipping Notification Email
**Function:** `send_shipping_notification_email($pdo, $order_id, $tracking_number = null, $carrier = null)`

**Trigger Scenarios:**
- When order status is changed to 'shipped' via admin panel (`admin/update_order.php`)

**Recipient:** Customer (from order record)

**Content:**
- Shipping confirmation
- Order number
- Tracking information (if provided)
- Expected delivery (3-5 business days)
- Contact information

**Notes:** Logged to `email_logs` table. Optional tracking parameters.

---

### 7. Delivery Confirmation Email
**Function:** `send_delivery_confirmation_email($pdo, $order_id)`

**Trigger Scenarios:**
- When order status is changed to 'delivered' via admin panel (`admin/update_order.php`)

**Recipient:** Customer (from order record)

**Content:**
- Delivery confirmation
- Order number
- Thank you message
- Request for reviews
- Contact information

**Notes:** Logged to `email_logs` table.

---

### 8. Cancelled Order Email
**Function:** `send_cancelled_order_email($pdo, $order_id)`

**Trigger Scenarios:**
- When order status is changed to 'cancelled' via admin panel (`admin/update_order.php`)

**Recipient:** Customer (from order record)

**Content:**
- Order cancellation notification
- Order number
- Apology for inconvenience
- Option to place new order
- Contact information

**Notes:** Logged to `email_logs` table.

---

### 9. Failed Payment Email
**Function:** `send_failed_payment_email($pdo, $order_id)`

**Trigger Scenarios:**
- When payment fails via Stripe webhook (`api/payments/stripe/webhook.php`)
- When payment fails via Paystack verification (`api/payments/paystack/verify.php`)
- When payment status is changed to 'failed' via admin panel (`admin/update_order.php`)

**Recipient:** Customer (from order record)

**Content:**
- Payment failure notification
- Order number
- Possible reasons for failure
- Instructions to retry payment
- Contact information

**Notes:** Logged to `email_logs` table.

---

## Email Infrastructure

- **SMTP Provider:** Configured via environment variables (Brevo by default)
- **Library:** PHPMailer with SMTP authentication
- **Logging:** All transactional emails logged to `email_logs` table
- **Error Handling:** Failed sends logged to PHP error log
- **HTML Support:** Most emails support HTML formatting with plain text fallback

## Environment Variables

Required for email functionality:
- `MAIL_HOST` (default: smtp-relay.brevo.com)
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_PORT` (default: 587)
- `MAIL_FROM_ADDRESS` (default: orders@accessoriesbydija.uk)
- `MAIL_FROM_NAME` (default: Dija Accessories)
- `ADMIN_EMAIL` (default: admin@accessoriesbydija.uk)

## Automation Status

- **Automated:** Order confirmation, welcome, admin notifications, refunds, abandoned carts, shipping notifications, delivery confirmations, cancelled orders, failed payments
- **Manual:** None

## Recommendations

1. ✅ Implemented automated triggers for shipping and delivery emails based on order status updates
2. Add email templates for better maintainability
3. Consider adding email preferences for customers
4. Implement email queue system for high-volume scenarios