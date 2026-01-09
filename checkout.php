<?php
session_start();
require_once 'config/database.php';
require_once 'includes/shipping-calculator.php';

// Checkout access: do not force-redirect from server — client-side will load guest cart from localStorage.

$customer_id = $_SESSION['customer_id'] ?? null;
$addresses = [];
$customer_email = '';
$customer_name = '';
$customer_phone = '';

// Fetch customer addresses if logged in
if ($customer_id) {
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch();
        if ($customer) {
            $customer_name = $customer['first_name'] . ' ' . $customer['last_name'];
            $customer_email = $customer['email'];
            $customer_phone = $customer['phone'] ?? '';
        }

        $stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC");
        $stmt->execute([$customer_id]);
        $addresses = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Address fetch error - continue with empty addresses
    }
}

// Fetch cart items for order summary
$cart_items = [];
$subtotal = 0;

if ($customer_id) {
    // Get cart from database
    try {
                $stmt = $pdo->prepare("SELECT c.product_id, c.quantity, c.material_id, c.variation_id, c.size_id,
                                       p.name, COALESCE(c.selected_price, pv.price_adjustment, p.price) as price, p.slug,
                                       m.name as material_name, pv.tag as variation_tag, pv.color, pv.adornment, vs.size
                                       FROM cart c
                                       JOIN products p ON c.product_id = p.id
                                       LEFT JOIN materials m ON m.id = c.material_id
                                       LEFT JOIN product_variations pv ON pv.id = c.variation_id
                                       LEFT JOIN variation_sizes vs ON vs.id = c.size_id
                                       WHERE c.customer_id = ?");        $stmt->execute([$customer_id]);
        $cart_items = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Cart fetch error
    }
} else {
    // Get cart from session/localStorage (will be passed via AJAX)
    // For now, we'll fetch via JavaScript
}

// Calculate subtotal from cart items
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Allow page to render for both guests and logged-in users; client-side will handle empty-cart UX.

// Default shipping cost (will be calculated dynamically on client side based on country)
$shipping_cost = 5.00;

// Check for promo code validation (via AJAX)
$discount = 0;
$discount_code = '';

$total = $subtotal + $shipping_cost - $discount;

$page_title = 'Checkout - Dija Accessories'; 
$page_description = 'Complete your order and choose shipping/payment options.'; 
include 'includes/header.php';
?>

    <style>
        .checkout-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .checkout-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        @media (max-width: 768px) { .checkout-grid { grid-template-columns: 1fr; } }
        
        .checkout-section { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
        .section-title { font-size: 1.3rem; font-weight: 600; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #c487a5; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #c487a5; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        
        .payment-methods { display: grid; gap: 1rem; }
        .payment-option { border: 2px solid #ddd; padding: 1rem; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
        .payment-option:hover { border-color: #c487a5; background: #fafafa; }
        .payment-option input[type="radio"] { margin-right: 0.75rem; }
        .payment-option.selected { border-color: #c487a5; background: #fff5f8; }
        
        .order-summary { position: sticky; top: 2rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #eee; }
        .summary-row.total { font-weight: 700; font-size: 1.2rem; color: #c487a5; border: none; margin-top: 0.5rem; }
        .summary-row.discount { color: #28a745; }
        .progress { position: relative; width: 100%; height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; margin-top:8px; }
        .progress-bar { height: 100%; width: 0%; background: #ef4444; transition: width 0.3s ease; }
        .bg-green { background: #10b981 !important; }
        .bg-yellow { background: #f59e0b !important; }
        .bg-red { background: #ef4444 !important; }
        .progress-label { font-size: 13px; color: #666; margin-bottom: 8px; }
        .progress-note { font-size: 12px; color: #666; margin-top: 6px; }
        
        .promo-input-group { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
        .promo-input-group input { flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; }
        .promo-input-group button { padding: 0.75rem 1rem; background: #c487a5; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .promo-input-group button:hover { background: #a66889; }
        
        .btn-checkout { display: block; width: 100%; text-align: center; background: #c487a5; color: #fff; border: none; padding: 14px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem; margin-top: 1.5rem; }
        .btn-checkout:hover { background: #a66889; }
        .btn-checkout:disabled { background: #ccc; cursor: not-allowed; }
        
        .address-card { border: 1px solid #ddd; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; cursor: pointer; transition: all 0.2s; }
        .address-card:hover { border-color: #c487a5; background: #fafafa; }
        .address-card input[type="radio"] { margin-right: 0.75rem; }
        .address-card.selected { border: 2px solid #c487a5; background: #fff5f8; }
        
        .error-message { color: #dc3545; font-size: 0.9rem; margin-top: 0.25rem; }
        .success-message { color: #28a745; font-size: 0.9rem; margin-top: 0.25rem; }
        
        .cart-item { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #eee; }
        .cart-item-name { font-weight: 500; }
        .cart-item-qty { color: #666; font-size: 0.9rem; }
    </style>

    <div class="checkout-container">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:2rem;">
            <h1 style="margin:0; display:inline-flex; align-items:center;"><i class="fas fa-shopping-cart" style="margin-right:8px;"></i> Checkout</h1>
            <a href="products.php" class="btn-outline" style="padding:8px 12px; border-radius:6px; border:1px solid #ddd; color:#222; text-decoration:none;">Continue Shopping</a>
        </div>

        <div class="checkout-grid">
            <!-- Left Column: Checkout Form -->
            <div>
                <!-- Contact Information -->
                <div class="checkout-section">
                    <div class="section-title">Contact Information</div>
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="contact_full_name" name="contact_full_name" value="<?php echo htmlspecialchars($customer_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Phone Number *</label>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($customer_phone); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer_email); ?>" required>
                    </div>
                    <?php if (!$customer_id): ?>
                    <p style="color: #666; font-size: 0.9rem;">Already have an account? <a href="login.php" style="color: #c487a5;">Sign in</a></p>
                    <?php endif; ?>
                </div>

                <!-- Shipping Address -->
                <div class="checkout-section">
                    <div class="section-title">Shipping Address</div>

                    <?php if (!empty($addresses)): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <label for="address-select-dropdown" style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Select Address</label>
                        <select id="address-select-dropdown" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                            <option value="">-- Select an address --</option>
                            <?php foreach ($addresses as $addr): ?>
                                <?php
                                    $full_name = $addr['full_name'] ?? trim((($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? '')));
                                    $line1 = $addr['address_line_1'] ?? '';
                                    $city = $addr['city'] ?? '';
                                    $state = $addr['state'] ?? '';
                                    $country = $addr['country'] ?? '';
                                    $label = htmlspecialchars($full_name . ' - ' . $line1 . ', ' . $city . ', ' . $state . ', ' . $country);
                                ?>
                                <option value="<?php echo $addr['id']; ?>" <?php echo (!empty($addr['is_default']) ? 'selected' : ''); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr style="margin: 1.5rem 0;">
                    <?php endif; ?>

                    <div style="margin-bottom: 1rem;">
                        <a href="#" onclick="toggleAddressForm(); return false;" style="color: #c487a5; text-decoration: none;">
                            <i class="fas fa-plus"></i> <?php echo !empty($addresses) ? 'Add new address' : 'Enter shipping address'; ?>
                        </a>
                    </div>

                    <form id="address-form" style="display: <?php echo empty($addresses) ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($customer_name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>

                        <div class="form-group">
                            <label for="address_line_1">Address Line 1 *</label>
                            <input type="text" id="address_line_1" name="address_line_1" required>
                        </div>

                        <div class="form-group">
                            <label for="address_line_2">Address Line 2 (Optional)</label>
                            <input type="text" id="address_line_2" name="address_line_2">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="state">State/Province *</label>
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
                        <?php if ($customer_id): ?>
                        <button type="button" id="save-address-btn-checkout" class="btn-primary" style="margin-top:8px;">Save Address</button>
                        <?php else: ?>
                        <button type="button" id="use-address-btn-checkout" class="btn-primary" style="margin-top:8px;">Use this address</button>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Payment Method -->
                <div class="checkout-section">
                    <div class="section-title">Payment Method</div>
                    <div class="payment-methods">
                        <label class="payment-option" onclick="selectPaymentMethod('paystack')">
                            <input type="radio" name="payment_method" value="paystack" onchange="selectPaymentMethod('paystack')">
                            <i class="fas fa-credit-card"></i> Paystack
                            <small style="display: block; color: #666; margin-left: 1.75rem;">Card, Mobile Money, Bank Transfer</small>
                        </label>
                        <label class="payment-option selected" onclick="selectPaymentMethod('stripe')">
                            <input type="radio" name="payment_method" value="stripe" checked onchange="selectPaymentMethod('stripe')">
                            <i class="fab fa-stripe"></i> Stripe
                            <small style="display: block; color: #666; margin-left: 1.75rem;">Card Payments</small>
                        </label>
                        <label class="payment-option" onclick="selectPaymentMethod('remita')">
                            <input type="radio" name="payment_method" value="remita" onchange="selectPaymentMethod('remita')">
                            <i class="fas fa-money-bill"></i> Remita
                            <small style="display: block; color: #666; margin-left: 1.75rem;">Bank Transfer, Card, Wallet</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Summary -->
            <div>
                <!-- Free Shipping Progress (moved from cart) -->
                <div class="checkout-section" id="shipping-progress-card" style="margin-bottom:16px;">
                    <div class="section-title"><i class="fas fa-truck" style="margin-right:8px;"></i>Free Shipping</div>
                    <div>
                        <div class="progress-label" id="shipping-progress-text">Add more to qualify for free shipping</div>
                        <div class="progress"><div id="shipping-progress-bar" class="progress-bar"></div></div>
                        <div class="progress-note" id="shipping-info-text">Free shipping thresholds apply based on destination.</div>
                    </div>
                </div>

                <div class="checkout-section order-summary">
                    <div class="section-title">Order Summary</div>

                    <!-- Cart Items -->
                    <div id="cart-items-summary" style="margin-bottom: 1.5rem;">
                        <!-- Populated via JavaScript -->
                    </div>

                    <!-- Promo Code -->
                    <div class="promo-input-group">
                        <input type="text" id="promo_code" name="promo_code" placeholder="Enter promo code">
                        <button type="button" onclick="applyPromoCode()">Apply</button>
                    </div>
                    <div id="promo-message" style="margin-bottom: 1rem;"></div>

                    <!-- Summary Totals -->
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal-display">£0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span id="shipping-display">£<?php echo number_format($shipping_cost, 2); ?></span>
                    </div>
                    <div class="summary-row discount" id="discount-row" style="display: none;">
                        <span>Discount</span>
                        <span id="discount-display">-£0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="total-display">£0.00</span>
                    </div>

                    <!-- Place Order Button -->
                    <button class="btn-checkout" id="place-order-btn" onclick="placeOrder()">
                        Place Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        let SHIPPING_COST = <?php echo $shipping_cost; ?>; // Start with default, will be updated dynamically
        let currentDiscountPercent = 0;
        let currentDiscountAbsolute = 0; // GBP absolute discount

        // Load cart items from database or localStorage
        async function loadCartItems() {
            try {
                const response = await fetch('/api/cart.php');
                const data = await response.json();
                
                if (data.success && data.items) {
                    const itemsHtml = data.items.map(item => {
                        let details = [];
                        if (item.material_name) details.push(item.material_name);
                        if (item.color) details.push(item.color);
                        if (item.adornment) details.push(item.adornment);
                        if (item.size) details.push(item.size);
                        if (item.variation_tag) details.push(item.variation_tag);
                        const detailStr = details.length > 0 ? ` (${details.join(', ')})` : '';
                        return `
                        <div class="cart-item">
                            <span class="cart-item-name">${escapeHtml(item.name || item.product_name || ('Product #' + (item.product_id || '')))}${detailStr} x${item.quantity}</span>
                            <span>£${(item.price * item.quantity).toFixed(2)}</span>
                        </div>
                    `}).join('');
                    
                        document.getElementById('cart-items-summary').innerHTML = itemsHtml;
                        // Store current items for promo re-validation
                        __currentCartItems = data.items.map(it => ({ name: it.name || it.product_name || ('Product #' + (it.product_id||'')), quantity: it.quantity, price: parseFloat(it.price||0), product_id: it.product_id }));
                        updateOrderTotal(__currentCartItems);
                } else {
                        loadCartFromLocalStorage();
                }
            } catch (error) {
                console.error('Error loading cart:', error);
                loadCartFromLocalStorage();
            }
        }

        function loadCartFromLocalStorage() {
            const cartStr = localStorage.getItem('DIJACart') || localStorage.getItem('cart');
            if (!cartStr) return;

            const cart = JSON.parse(cartStr);
            // Build items array with price if available and render
            const items = cart.map((item) => {
                return {
                    product_id: item.product_id || item.id,
                    name: item.product_name || `Product #${item.product_id || item.id}`,
                    quantity: item.quantity || 1,
                    price: parseFloat(item.price || 0),
                    sku: item.sku || 'N/A'  // Will be overridden by API response
                };
            });

            const itemsHtml = items.map(it => `
                <div class="cart-item">
                    <span class="cart-item-name">${escapeHtml(it.name)} x${it.quantity}</span>
                    <span>£${(it.price * it.quantity).toFixed(2)}</span>
                </div>
            `).join('');

            document.getElementById('cart-items-summary').innerHTML = itemsHtml;
            __currentCartItems = items;
            updateOrderTotal(__currentCartItems);
        }

        function updateOrderTotal(items = []) {
            let subtotal = 0;
            items.forEach(item => {
                subtotal += parseFloat(item.price || 0) * (item.quantity || 1);
            });

                // Determine discount amount: prefer absolute value if available, else percent
                let discountAmount = 0;
                if (currentDiscountAbsolute && currentDiscountAbsolute > 0) {
                    discountAmount = parseFloat(currentDiscountAbsolute) || 0;
                } else if (currentDiscountPercent && currentDiscountPercent > 0) {
                    discountAmount = subtotal * (parseFloat(currentDiscountPercent) / 100);
                }

            // Cap discount to subtotal
            discountAmount = Math.min(discountAmount, subtotal);

            const total = subtotal + SHIPPING_COST - discountAmount;

            // Update displays and data attributes
            document.getElementById('subtotal-display').textContent = '£' + subtotal.toFixed(2);
            document.getElementById('subtotal-display').setAttribute('data-price', String(subtotal));
            document.getElementById('discount-display').textContent = '-£' + discountAmount.toFixed(2);
            document.getElementById('discount-display').setAttribute('data-price', String(-discountAmount));
            document.getElementById('total-display').textContent = '£' + total.toFixed(2);
            document.getElementById('total-display').setAttribute('data-price', String(total));

            // Show or hide discount row
            const discRow = document.getElementById('discount-row');
            if (discountAmount > 0) {
                discRow.style.display = 'flex';
            } else {
                discRow.style.display = 'none';
            }
        }

        // Calculate shipping based on country and cart items
        async function updateShippingCost() {
            const country = document.getElementById('country')?.value || 'United Kingdom';
            const subtotalEl = document.getElementById('subtotal-display');
            const subtotal = subtotalEl ? parseFloat(subtotalEl.getAttribute('data-price') || '0') : 0;
            
            // Build cart items array for weight calculation
            const cartItems = __currentCartItems.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity
            }));
            
            try {
                const response = await fetch('/api/shipping/calculate.php?' + new URLSearchParams({
                    country: country,
                    subtotal: subtotal,
                    cart_items: JSON.stringify(cartItems)
                }));
                
                const data = await response.json();
                
                if (data.success && data.fee !== null) {
                    SHIPPING_COST = parseFloat(data.fee);
                    if (data.fee === 0) {
                        document.getElementById('shipping-display').textContent = 'FREE';
                    } else {
                        document.getElementById('shipping-display').textContent = '£' + SHIPPING_COST.toFixed(2);
                    }
                } else {
                    // Country not found, show message
                    SHIPPING_COST = 5.00; // Default
                    document.getElementById('shipping-display').textContent = 'Depends on location';
                }
                
                // Recalculate order total with new shipping cost
                updateOrderTotal(__currentCartItems);
            } catch (error) {
                console.error('Error calculating shipping:', error);
                // Keep current shipping cost on error
            }
        }

        function toggleAddressForm() {
            const form = document.getElementById('address-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function getSelectedAddressId() {
            const dropdown = document.getElementById('address-select-dropdown');
            return dropdown ? dropdown.value : null;
        }

        function selectPaymentMethod(method) {
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            event.target.closest('.payment-option').classList.add('selected');
        }

        // Keep the current cart items in scope so promo re-calculation uses them
        let __currentCartItems = [];

        async function applyPromoCode() {
            const code = document.getElementById('promo_code').value.trim();
            if (!code) return;
            
            try {
                const response = await fetch('/api/promo/validate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code, subtotal: parseFloat((document.getElementById('subtotal-display')?.textContent || '£0').replace(/[^0-9.]/g,'')) || 0 })
                });
                
                const data = await response.json();
                const messageDiv = document.getElementById('promo-message');
                
                if (data.success) {
                    // API returns 'type' ('percent'|'amount'), 'value' and 'discount' (absolute GBP)
                    if (data.type === 'percent') {
                        currentDiscountPercent = parseFloat(data.value) || 0;
                        currentDiscountAbsolute = parseFloat(data.discount) || 0;
                        messageDiv.innerHTML = `<div class="success-message">✓ Promo code applied! ${currentDiscountPercent}% off</div>`;
                    } else {
                        currentDiscountPercent = 0;
                        currentDiscountAbsolute = parseFloat(data.discount) || 0;
                        messageDiv.innerHTML = `<div class="success-message">✓ Promo code applied! -£${currentDiscountAbsolute.toFixed(2)}</div>`;
                    }
                    // Store promo absolute/percent and recalc using current items
                    currentDiscountPercent = data.type === 'percent' ? parseFloat(data.value) || 0 : 0;
                    currentDiscountAbsolute = parseFloat(data.discount) || 0;
                    // Recalculate using stored items
                    updateOrderTotal(__currentCartItems.length ? __currentCartItems : undefined);
                } else {
                    messageDiv.innerHTML = `<div class="error-message">✗ ${data.message || 'Invalid promo code'}</div>`;
                    currentDiscountPercent = 0;
                    currentDiscountAbsolute = 0;
                    updateOrderTotal(__currentCartItems.length ? __currentCartItems : undefined);
                }
            } catch (error) {
                console.error('Error validating promo:', error);
            }
        }

        async function placeOrder() {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const email = document.getElementById('email').value;
            const contactName = document.getElementById('contact_full_name').value;
            const contactPhone = document.getElementById('contact_phone').value;
            const addressId = getSelectedAddressId();
            const isLoggedIn = <?php echo $customer_id ? 'true' : 'false'; ?>;

            if (!contactName || !contactPhone || !email) {
                alert('Please fill in all contact information fields');
                return;
            }

            if (!isLoggedIn && !validateAddressForm()) {
                alert('Please fill in all required address fields for guest checkout');
                return;
            }

            if (isLoggedIn && !addressId) {
                alert('Please select or add a shipping address');
                return;
            }

            document.getElementById('place-order-btn').disabled = true;
            document.getElementById('place-order-btn').textContent = 'Processing...';

            try {
                // Calculate client-side discount for server verification
                const subtotal = parseFloat((document.getElementById('subtotal-display')?.getAttribute('data-price') || '0')) || 0;
                let clientDiscount = 0;
                if (currentDiscountAbsolute && currentDiscountAbsolute > 0) {
                    clientDiscount = parseFloat(currentDiscountAbsolute) || 0;
                } else if (currentDiscountPercent && currentDiscountPercent > 0) {
                    clientDiscount = subtotal * (parseFloat(currentDiscountPercent) / 100);
                }
                clientDiscount = Math.min(clientDiscount, subtotal);

                // Build order payload
                const orderPayload = {
                    email,
                    contact_name: contactName,
                    contact_phone: contactPhone,
                    payment_method: paymentMethod,
                    promo_code: document.getElementById('promo_code').value || null,
                    client_discount: Math.round(clientDiscount * 100) / 100,  // Round to 2 decimals
                    shipping_cost: SHIPPING_COST  // Include the calculated shipping cost
                };
                
                // For logged-in customers
                if (isLoggedIn) {
                    orderPayload.address_id = addressId;
                } else {
                    // For guest checkout, collect cart items and address
                    orderPayload.cart_items = __currentCartItems.map(item => ({
                        product_id: item.product_id,
                        product_name: item.name || item.product_name,
                        quantity: item.quantity,
                        price: item.price,
                        sku: item.sku || 'N/A'
                    }));
                    
                    // Extract address from form
                    const fullName = document.getElementById('full_name')?.value || '';
                    const nameParts = fullName.trim().split(/\s+/);
                    const firstName = nameParts[0] || '';
                    const lastName = nameParts.slice(1).join(' ') || '';
                    
                    orderPayload.guest_address = {
                        first_name: firstName,
                        last_name: lastName,
                        address_line_1: document.getElementById('address_line_1')?.value || '',
                        address_line_2: document.getElementById('address_line_2')?.value || '',
                        city: document.getElementById('city')?.value || '',
                        state: document.getElementById('state')?.value || '',
                        postal_code: document.getElementById('postal_code')?.value || '',
                        country: document.getElementById('country')?.value || ''
                    };
                }
                
                const response = await fetch('/api/orders/create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderPayload)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirect to payment gateway
                    if (paymentMethod === 'paystack') {
                        window.location.href = `/api/payments/paystack/initialize.php?order_id=${data.order_id}`;
                    } else if (paymentMethod === 'stripe') {
                        window.location.href = `/api/payments/stripe/create-session.php?order_id=${data.order_id}`;
                    } else if (paymentMethod === 'remita') {
                        window.location.href = `/api/payments/remita/initialize.php?order_id=${data.order_id}`;
                    }
                } else {
                    alert('Error creating order: ' + (data.message || 'Unknown error'));
                    document.getElementById('place-order-btn').disabled = false;
                    document.getElementById('place-order-btn').textContent = 'Place Order';
                }
            } catch (error) {
                console.error('Error placing order:', error);
                alert('Error placing order. Please try again.');
                document.getElementById('place-order-btn').disabled = false;
                document.getElementById('place-order-btn').textContent = 'Place Order';
            }
        }

        function validateAddressForm() {
            const required = ['full_name', 'phone', 'address_line_1', 'city', 'state', 'postal_code', 'country'];
            return required.every(id => document.getElementById(id).value.trim() !== '');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load cart on page load
        document.addEventListener('DOMContentLoaded', function(){
            loadCartItems();
            bindSaveAddressCheckout();
               // Update shipping progress after cart loads
               setTimeout(() => { if (typeof updateShippingProgress === 'function') updateShippingProgress(); }, 200);
               // Calculate initial shipping based on country
               setTimeout(() => { updateShippingCost(); }, 200);
        });

            // Shipping meter implementation - server-backed thresholds
            async function updateShippingProgress(){
                const bar = document.getElementById('shipping-progress-bar');
                const text = document.getElementById('shipping-progress-text');
                const info = document.getElementById('shipping-info-text');
                const subtotalEl = document.getElementById('subtotal-display');
                if (!bar || !text || !subtotalEl) return;

                const subtotalGBP = parseFloat(subtotalEl.getAttribute('data-price') || '0') || 0;
                const country = (document.getElementById('country')?.value) || 'United Kingdom';

                // Build cart items for weight calculation
                const cartItems = (typeof __currentCartItems !== 'undefined' && Array.isArray(__currentCartItems)) ? __currentCartItems.map(i => ({ product_id: i.product_id, quantity: i.quantity })) : [];

                try {
                    const params = new URLSearchParams({
                        country: country,
                        subtotal: String(subtotalGBP),
                        cart_items: JSON.stringify(cartItems)
                    });

                    const res = await fetch('/api/shipping/calculate.php?' + params);
                    const data = await res.json();

                    if (!data || !data.success) {
                        // Fallback messaging
                        bar.style.width = '0%';
                        bar.className = 'progress-bar bg-red';
                        text.innerHTML = 'Free shipping not available';
                        info.innerHTML = 'Free shipping thresholds apply based on destination.';
                        return;
                    }

                    const threshold = data.free_shipping_threshold; // null means not applicable
                    const isFree = data.is_free_shipping === true;
                    const isFirstTime = data.is_first_time_customer === true;

                    if (isFree) {
                        bar.style.width = '100%';
                        bar.className = 'progress-bar bg-green';
                        text.innerHTML = '🎉 You qualify for free shipping!';
                    } else if (threshold && threshold > 0) {
                        const pct = Math.min((subtotalGBP / threshold) * 100, 100);
                        bar.style.width = pct + '%';
                        bar.className = 'progress-bar ' + (pct >= 100 ? 'bg-green' : pct >= 50 ? 'bg-yellow' : 'bg-red');
                        const remaining = Math.max(threshold - subtotalGBP, 0);
                        text.innerHTML = `Add £${remaining.toFixed(2)} more for free shipping`;
                    } else {
                        bar.style.width = '0%';
                        bar.className = 'progress-bar bg-red';
                        text.innerHTML = `Free shipping not available in ${country}`;
                    }

                    if (info) {
                        if (isFirstTime) {
                            info.innerHTML = '📍 First-time customer • Free shipping: UK only at £100+';
                        } else {
                            info.innerHTML = '📍 Returning customer • Free shipping: All supported countries at £300+';
                        }
                    }

                    // Sync shipping cost so total shows correctly if threshold crossed
                    if (typeof updateShippingCost === 'function') updateShippingCost();

                } catch (err) {
                    console.error('updateShippingProgress error', err);
                }
            }

            // Recompute when address fields change
            document.addEventListener('change', function(e){
                if (e.target && (e.target.id === 'country' || e.target.id === 'state')) {
                    updateShippingProgress();
                    // Also update shipping cost when country changes
                    if (e.target.id === 'country') {
                        updateShippingCost();
                    }
                }
            });

        // Save address button handler for checkout
        function bindSaveAddressCheckout() {
            const saveBtn = document.getElementById('save-address-btn-checkout');
            const useBtn = document.getElementById('use-address-btn-checkout');
            const isLoggedIn = <?php echo $customer_id ? 'true' : 'false'; ?>;

            if (saveBtn) {
                saveBtn.addEventListener('click', async function(){
                    const payload = {
                        type: 'shipping',
                        first_name: (document.getElementById('full_name')?.value || '').split(' ')[0] || '',
                        last_name: (document.getElementById('full_name')?.value || '').split(' ').slice(1).join(' ') || '',
                        company: '',
                        address_line_1: document.getElementById('address_line_1')?.value || '',
                        address_line_2: document.getElementById('address_line_2')?.value || '',
                        city: document.getElementById('city')?.value || '',
                        state: document.getElementById('state')?.value || '',
                        postal_code: document.getElementById('postal_code')?.value || '',
                        country: document.getElementById('country')?.value || '',
                        is_default: false
                    };
                    try {
                        const res = await fetch('/api/account/save_address.php', {
                            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert('Address saved');
                            // Optionally reload page to refresh dropdown
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to save address');
                        }
                    } catch (e) {
                        console.error('Save address error', e);
                        alert('Error saving address');
                    }
                });
            }

            if (useBtn) {
                useBtn.addEventListener('click', function(){
                    // Hide form and proceed — the form values will be used when placing order
                    document.getElementById('address-form').style.display = 'none';
                });
            }
        }
    </script>
</body>
</html>
