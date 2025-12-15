# Shipping Fee Calculation Implementation

## Overview
Implemented dynamic shipping fee calculation based on customer location and total order weight.

## Pricing Structure

### UK Standard Shipping
- 0-1kg: £3.50
- 1-4kg: £5.20
- 5kg+: £7.00

### Canada International Tracked
- 0-1kg: £19.30
- 1.25-2kg: £23.35
- 3kg+: £28.00

### USA Tracked
- 0-1kg: £16.50
- 1.25-2kg: £20.20
- 3kg+: £30.90

### Ireland Tracked
- 0-1kg: £8.65
- 1-2kg: £10.10
- 3kg+: £11.10

### Other Locations
For countries not listed above: "Shipping fee depends on location"

## Implementation Files

### 1. `includes/shipping-calculator.php` (NEW)
Helper functions for shipping calculations:
- `calculateShippingFee($country, $total_weight_grams)`: Returns shipping fee in GBP or null if country not found
- `getShippingFeeWithDescription($country, $total_weight_grams)`: Returns detailed shipping info with description
- `calculateTotalWeight($cart_items, $pdo)`: Calculates total weight from cart items using product weights

**Key Features:**
- Accurate weight calculations using product weight field (in grams)
- Automatic tier selection based on total weight
- Handles unknown countries gracefully

### 2. `api/orders/create.php` (UPDATED)
- Now imports `shipping-calculator.php`
- Extracts country from customer address (logged-in) or guest address
- Calculates total weight from cart items
- Calls `calculateShippingFee()` to determine shipping cost
- Falls back to £5.00 if country not found
- Stores calculated shipping in orders table

**Changes:**
- Line 6: Added require for shipping-calculator
- Lines 77-129: Updated cart fetching to extract country
- Lines 225-230: Dynamic shipping calculation instead of fixed £5.00

### 3. `api/shipping/calculate.php` (NEW)
REST API endpoint for frontend shipping calculation:
- **Endpoint**: GET `/api/shipping/calculate.php`
- **Parameters**:
  - `country`: Country name (required)
  - `cart_items`: JSON array of cart items (optional, for weight calc)
- **Returns**: JSON with fee, description, and country_found flag

**Usage Example:**
```javascript
const response = await fetch('/api/shipping/calculate.php?' + new URLSearchParams({
    country: 'United Kingdom',
    cart_items: JSON.stringify([{product_id: 1, quantity: 2}])
}));
const data = await response.json();
// Returns: { success: true, fee: 5.20, description: "£5.20 (United Kingdom, 2.5kg)", country_found: true, weight_kg: 2.5 }
```

### 4. `checkout.php` (UPDATED)
Frontend checkout page with dynamic shipping:
- Imports `shipping-calculator.php` on backend
- Changed SHIPPING_COST from const to let variable
- Added `updateShippingCost()` function to fetch shipping from API
- Calls `updateShippingCost()` when country field changes
- Calls initial shipping calculation after cart loads

**Key JavaScript Changes:**
- Line 309: `let SHIPPING_COST` instead of `const`
- Lines 345-375: New `updateShippingCost()` async function
- Line 674: Call `updateShippingCost()` when country changes
- Line 690: Initial shipping calculation on page load

## Data Flow

### Logged-in Customer Checkout:
1. Customer selects/adds address with country
2. `country` field change triggers `updateShippingCost()`
3. Frontend calls `/api/shipping/calculate.php` with country and cart items
4. API calculates weight from cart items, determines shipping tier
5. Shipping fee updated in order summary (real-time)
6. On order creation: `api/orders/create.php` extracts country from `address_id`, recalculates shipping server-side for verification

### Guest Checkout:
1. Customer enters country in address form
2. `country` field change triggers `updateShippingCost()`
3. Same process as logged-in customer
4. On order creation: guest address passed includes country, shipping calculated server-side

### Order Creation API:
1. Receives order payload with cart items and address
2. Extracts country from address
3. Calls `calculateTotalWeight()` to sum all item weights
4. Calls `calculateShippingFee()` to determine shipping
5. Stores final shipping amount in orders table
6. Amount verified by payment gateway on completion

## Weight Data

Products must have `weight` field populated (in grams):
- Jewelry items typically 5-50g
- Stored in `products.weight` as DECIMAL(8,2)
- NULL or 0 means no shipping weight contribution

## Testing Checklist

- [ ] UK customer checkout: verify correct tier pricing (0-1kg, 1-4kg, 5kg+)
- [ ] Canada checkout: verify international rates and tier boundaries
- [ ] USA checkout: verify tier pricing
- [ ] Ireland checkout: verify rates
- [ ] Unknown country: verify "shipping fee depends on location" message
- [ ] Weight calculation: verify multiple items sum correctly
- [ ] Server-side verification: confirm order creation uses calculated shipping
- [ ] Payment gateway: confirm order amount includes calculated shipping
- [ ] Guest checkout: verify shipping calculated before order placement
- [ ] Address change: verify shipping updates in real-time

## Error Handling

- If country is not recognized: Returns null from `calculateShippingFee()`
- Frontend fallback: Uses £5.00 default and displays "Depends on location"
- Server fallback: Uses £5.00 if API returns null
- Weight calculation errors: Safely handles products with no weight data (treats as 0g)

## Currency

All shipping fees are in GBP (£). System uses GBP as base currency throughout.

## Future Enhancements

- [ ] Support multiple carrier options with different rates
- [ ] Free shipping above order threshold (by country)
- [ ] Volumetric weight calculation for large items
- [ ] Real-time rates from shipping API (FedEx, DHL, etc.)
- [ ] Packaging weight included in calculation
