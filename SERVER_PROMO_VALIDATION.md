# Server-Side Promo Validation Implementation

**Date:** December 14, 2025  
**File Modified:** `api/orders/create.php`  
**Status:** ✅ Complete and syntax-validated

## Overview

The order creation endpoint now enforces comprehensive server-side promo code validation, preventing fraudulent discount manipulation and ensuring data integrity. This document details the implementation.

---

## Key Features Implemented

### 1. **Server-Side Discount Calculation & Verification**
- Server recalculates promo discount using identical logic to `/api/promo/validate.php`
- Verifies client-provided discount against server calculation (allows 0.01 tolerance for rounding)
- Logs suspicious discrepancies but allows order (non-blocking to prevent UX issues from timing races)

**Logic:**
```php
// Percent-based discount
if ($type === 'percent') {
    $discount = ($subtotal * ($value / 100.0));
    if ($max_discount && $discount > $max_discount) {
        $discount = $max_discount;
    }
}

// Fixed amount discount
else {
    $discount = min($value, $subtotal);
}

// Always cap to subtotal
$discount = round(min($discount, $subtotal), 2);
```

### 2. **Promo Validation Checks**
✅ Code exists and is active (`is_active = 1`)  
✅ Current date/time is within promo date range  
✅ Order subtotal meets minimum order amount (`min_order_amount`)  
✅ Promo has not exceeded usage limit (`usage_count < usage_limit`)  
✅ Client-provided discount approximately matches server calculation  

### 3. **Guest Checkout Support**
- Accepts `cart_items` array for guests (bypassing DB lookup)
- Validates guest address fields:
  - `first_name`, `last_name`, `address_line_1`, `city`, `state`, `postal_code`, `country`
- Stores guest address as JSON in order notes for reference
- No separate guest address table needed (can be added later)

### 4. **Order Items Persistence**
- Inserts all cart items into `order_items` table
- Records: `product_id`, `product_name`, `product_sku`, `quantity`, `unit_price`, `total_price`
- Maintains referential integrity with `orders` table

### 5. **Enhanced Request Validation**
- Email validation (`filter_var(..., FILTER_VALIDATE_EMAIL)`)
- Payment method validation (must be `paystack`, `stripe`, or `remita`)
- Cart item structure validation (required: `product_id`, `quantity`, `price`)
- Numeric range checks (`quantity > 0`, `price >= 0`)
- Address validation for both logged-in (via ID) and guest customers

### 6. **Transaction Management**
- All database operations wrapped in `BEGIN TRANSACTION ... COMMIT/ROLLBACK`
- Ensures atomicity: order + items + promo update all succeed or all fail
- Prevents partial order records on insert errors

### 7. **Detailed Error Responses**
Returns HTTP `400` with descriptive messages for:
- Missing required fields
- Invalid email/payment method
- Cart empty or invalid items
- Invalid address data
- Promo not found or expired
- Usage limit exceeded
- Subtotal below minimum
- Invalid address for customer

---

## Request/Response Examples

### Request (Logged-in Customer with Promo)
```json
{
  "email": "customer@example.com",
  "payment_method": "paystack",
  "address_id": 1,
  "promo_code": "SAVE10",
  "client_discount": 5.25
}
```

### Request (Guest Checkout)
```json
{
  "email": "guest@example.com",
  "payment_method": "stripe",
  "cart_items": [
    {
      "product_id": 1,
      "product_name": "Gold Necklace",
      "quantity": 1,
      "price": 45.99,
      "sku": "GN-001"
    }
  ],
  "guest_address": {
    "first_name": "Jane",
    "last_name": "Smith",
    "address_line_1": "456 Oxford St",
    "address_line_2": "Apt 3B",
    "city": "London",
    "state": "Greater London",
    "postal_code": "SW1A 2AA",
    "country": "United Kingdom"
  },
  "promo_code": "WELCOME5",
  "client_discount": 2.30
}
```

### Success Response
```json
{
  "success": true,
  "message": "Order created successfully",
  "order_id": 42,
  "order_number": "ORD-20251214-A1B2C3",
  "subtotal": 45.99,
  "discount": 5.25,
  "shipping": 5.00,
  "total_amount": 45.74,
  "payment_method": "paystack"
}
```

### Error Response (Promo Invalid)
```json
{
  "success": false,
  "message": "Promo code not found or expired"
}
```

---

## Database Tables Used

| Table | Fields Referenced |
|-------|-------------------|
| `cart` | `customer_id`, `product_id`, `quantity` |
| `products` | `price`, `name`, `sku` |
| `customer_addresses` | `id`, `customer_id` |
| `promo_codes` | `code`, `is_active`, `start_date`, `end_date`, `min_order_amount`, `max_discount`, `type`, `value`, `usage_count`, `usage_limit` |
| `orders` | ✅ All columns used (see schema) |
| `order_items` | ✅ All columns populated (see schema) |

---

## Session & Pending Order

After successful order creation, the following is stored in `$_SESSION`:

```php
$_SESSION['pending_order'] = [
    'order_id' => 42,
    'order_number' => 'ORD-20251214-A1B2C3',
    'total_amount' => 45.74,
    'currency' => 'GBP',
    'promo_code' => 'SAVE10',
    'discount' => 5.25
];
```

This is used by payment gateways (`paystack/verify.php`, `stripe/webhook.php`, etc.) to confirm payment and finalize the order.

---

## Security Considerations

1. **Discount Verification:** Client discount is checked against server calculation. Mismatches are logged but don't block order (prevents race conditions from client-side recomputation delays).

2. **Promo Usage Increment:** Updated atomically within transaction to prevent double-counting via race conditions.

3. **Address Validation:** Required fields validated; guest addresses stored as JSON (not executed as code).

4. **Email Validation:** Uses `filter_var(..., FILTER_VALIDATE_EMAIL)` to reject malformed addresses.

5. **Transaction Rollback:** Database errors trigger rollback, preventing orphaned orders without items.

---

## Testing Checklist

- [ ] **Test 1:** Logged-in user places order with valid promo → order created, discount applied correctly
- [ ] **Test 2:** Logged-in user places order without promo → order created with `discount = 0`
- [ ] **Test 3:** Guest places order with valid promo and address → order and guest address stored
- [ ] **Test 4:** Guest places order with invalid address (missing field) → error returned
- [ ] **Test 5:** Expired promo code used → error returned
- [ ] **Test 6:** Client provides incorrect discount amount → order created, warning logged
- [ ] **Test 7:** Order items correctly inserted in `order_items` table for both guest and logged-in
- [ ] **Test 8:** Order subtotal < promo min_order_amount → error returned
- [ ] **Test 9:** Promo usage limit exceeded → error returned
- [ ] **Test 10:** Payment method invalid → error returned

---

## Future Enhancements

1. **Guest Order Addresses Table:** Create separate `guest_order_addresses` table instead of storing as JSON.
2. **Webhook Signature Verification:** Implement payment provider signature checks for Paystack/Stripe/Remita.
3. **Promo Per-Customer Limits:** Add `usage_per_customer` field to promo table.
4. **Shipping Calculation:** Replace fixed £5 with dynamic calculation based on address/weight.
5. **Tax Calculation:** Add tax computation based on customer location and product type.
6. **Order Modification Lock:** Prevent cart changes during order creation (short lock).

---

## Related Files

- **API Endpoint:** [`api/orders/create.php`](api/orders/create.php) (this file)
- **Promo Validation:** [`api/promo/validate.php`](api/promo/validate.php) (frontend calls this; server recalculates)
- **Payment Verification:** `api/payments/*/verify.php` (should call this endpoint first)
- **Order Confirmation:** [`order-confirmation.php`](order-confirmation.php) (displays created order)
- **Database Schema:** [`database.sql`](database.sql) (table definitions)

---

## Rollout Checklist

- ✅ Server-side validation implemented
- ✅ Guest checkout support added
- ✅ Order items persistence enabled
- ✅ Transaction management configured
- ✅ PHP syntax validated
- ⏳ Frontend checkout.php to pass `client_discount` in POST body
- ⏳ Payment gateways updated to verify order before processing payment
- ⏳ Manual smoke test on staging
- ⏳ Production deployment
