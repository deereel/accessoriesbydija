<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$page_title = "Shopping Cart";
$page_description = "Review your selected jewelry items and proceed to checkout.";
include 'includes/header.php';
$is_logged_in = isset($_SESSION['customer_id']);
?>

<style>
:root {
  --accent: #C27BA0;
  --bg: #f9fafb;
  --card-bg: #ffffff;
  --text: #111827;
  --muted: #6b7280;
  --border: #e5e7eb;
}

/* Prevent accidental horizontal scrolling on small devices */
html, body { overflow-x: hidden; }

main { background: var(--bg); }

.cart-container { max-width: 1200px; margin: 0 auto; padding: 20px 12px; box-sizing: border-box; }
.cart-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
@media (min-width: 992px) { .cart-grid { grid-template-columns: 2fr 1fr; } }

.card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.card-header { padding: 14px 16px; border-bottom: 1px solid var(--border); font-weight: 600; color: var(--text); }
.card-body { padding: 16px; }

/* Left column */
.cart-title { margin-bottom: 12px; font-size: 1.25rem; }
.empty-cart { text-align: center; padding: 30px 12px; color: var(--muted); }

/* Cart item */
.cart-item { display: grid; grid-template-columns: 80px 1fr; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); align-items: center; }
.cart-item:last-child { border-bottom: none; }
.cart-item .thumb { width: 80px; height: 80px; border-radius: 8px; overflow: hidden; background: #f3f4f6; }
.cart-item .thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cart-item .meta { display: flex; flex-direction: column; gap: 6px; }
.cart-item .name { font-weight: 600; color: var(--text); font-size: 0.95rem; word-break: break-word; }
.cart-item .variant { font-size: 12px; color: var(--muted); }
.cart-item .row-bottom { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; }
.qty-control { display: inline-flex; align-items: center; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
.qty-control button { width: 32px; height: 32px; border: none; background: #f3f4f6; cursor: pointer; }
.qty-control input { width: 48px; height: 32px; text-align: center; border: none; border-left: 1px solid var(--border); border-right: 1px solid var(--border); }
.price-line { font-weight: 600; color: var(--text); white-space: normal; word-break: break-word; }
.remove-from-cart { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 0.9rem; }

/* Right column */
.progress { position: relative; width: 100%; height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
.progress-bar { height: 100%; width: 0%; background: #ef4444; transition: width 0.3s ease; }
.bg-green { background: #10b981 !important; }
.bg-yellow { background: #f59e0b !important; }
.bg-red { background: #ef4444 !important; }
.progress-label { font-size: 13px; color: var(--muted); margin-bottom: 8px; }
.progress-note { font-size: 12px; color: var(--muted); margin-top: 6px; }

.summary-list { list-style: none; padding: 0; margin: 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.summary-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 8px; }
.summary-item:last-child { border-bottom: none; }
.summary-strong { font-weight: 700; }

.btn-primary { display: block; width: 100%; text-align: center; background: var(--accent); color: #fff; border: none; padding: 12px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
.btn-outline { display: inline-block; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; color: var(--text); background: #fff; cursor: pointer; }

/* Address form */
.form-group { margin-bottom: 12px; }
.form-group label { display: block; margin-bottom: 6px; color: var(--muted); font-size: 13px; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; }
.form-row { display: grid; grid-template-columns: 1fr; gap: 12px; }
@media (min-width: 640px) { .form-row { grid-template-columns: 1fr 1fr; } }

.loading { text-align: center; padding: 24px; color: var(--muted); }

/* Extra small screens adjustments */
@media (max-width: 420px) {
  .cart-item { grid-template-columns: 64px 1fr; gap: 8px; }
  .cart-item .thumb { width: 64px; height: 64px; }
  .qty-control input { width: 40px; }
  .card-header { padding: 12px; }
  .card-body { padding: 12px; }
}
</style>

<main>
  <div class="cart-container">
    <h1 class="cart-title">Shopping Cart</h1>
    <div class="cart-grid">
      <!-- Left: Cart items -->
      <div>
        <div id="empty-cart" class="empty-cart" style="display:none">
          <p>Your cart is empty.</p>
          <a href="/products.php" class="btn-outline">Continue Shopping</a>
        </div>
        <div id="cart-items">
          <div class="loading">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--accent);"></i>
            <p>Loading your cart...</p>
          </div>
        </div>
      </div>

      <!-- Right: Summary -->
      <div>
        <!-- Free Shipping Progress moved to checkout.php -->

        <!-- Order Summary -->
        <div class="card" id="cart-summary" style="margin-bottom:16px;">
          <div class="card-header">Order Summary</div>
          <div class="card-body">
            <ul class="summary-list">
              <li class="summary-item"><span>Subtotal</span><strong id="subtotal" class="summary-strong product-price" data-price="0">£0.00</strong></li>
              <li class="summary-item"><span>Shipping</span><strong id="shipping" class="summary-strong">Depends on location</strong></li>
              <li class="summary-item" id="promo-row" style="display:none;"><span>Discount</span><strong id="discount" class="summary-strong product-price" data-price="0">-£0.00</strong></li>
              <li class="summary-item"><span>Total</span><strong id="total" class="summary-strong product-price" data-price="0">£0.00</strong></li>
            </ul>
            <div style="background:#f0f0f0; padding:12px; border-radius:6px; margin-top:12px; margin-bottom:12px; font-size:13px; color:#666;">
              <i class="fas fa-tag" style="margin-right:8px;"></i>Promo codes can be applied at checkout
            </div>
            <a href="products.php" class="btn-outline" style="display:block; text-align:center; margin-bottom:8px;">Continue Shopping</a>
            <button class="btn-primary" id="checkout-btn" style="margin-top:12px;">Proceed to Checkout</button>
          </div>
        </div>

        <!-- Address selection and creation moved to checkout.php -->
      </div>
    </div>
  </div>
</main>

<!-- Free shipping meter moved to checkout.php -->

<script>
// Promo code handling on cart page
(function(){
  let appliedPromo = null; // { code, type, value, discountGBP }

  function gbpSubtotal() {
    const subEl = document.getElementById('subtotal');
    return subEl ? parseFloat(subEl.getAttribute('data-price') || '0') || 0 : 0;
  }

  function setDiscountGBP(amountGBP) {
    const discEl = document.getElementById('discount');
    const row = document.getElementById('promo-row');
    const totalEl = document.getElementById('total');
    const sub = gbpSubtotal();
    const newTotal = Math.max(0, sub - (amountGBP || 0));
    if (!discEl || !row || !totalEl) return;

    if (amountGBP && amountGBP > 0) {
      row.style.display = '';
      discEl.setAttribute('data-price', String(-amountGBP));
      discEl.textContent = `-£${amountGBP.toFixed(2)}`;
    } else {
      row.style.display = 'none';
      discEl.setAttribute('data-price', '0');
      discEl.textContent = '-£0.00';
    }

    totalEl.classList.add('product-price');
    totalEl.setAttribute('data-price', String(newTotal));
    totalEl.textContent = `£${newTotal.toFixed(2)}`;

    if (window.currencyConverter) window.currencyConverter.convertAllPrices();
  }

  async function validateAndApply(code) {
    const msg = document.getElementById('promo-message');
    if (msg) msg.textContent = '';
    if (!code) { if (msg) msg.textContent = 'Enter a promo code.'; return; }
    const subtotal = gbpSubtotal();
    if (subtotal <= 0) { if (msg) msg.textContent = 'Add items to cart before applying a promo.'; return; }
    try {
      const res = await fetch('/api/promos/validate.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: code.trim(), subtotal })
      });
      const data = await res.json();
      if (!data.success) { if (msg) msg.textContent = data.message || 'Invalid promo code.'; appliedPromo = null; setDiscountGBP(0); return; }
      appliedPromo = { code: code.trim(), type: data.type, value: parseFloat(data.value), discountGBP: parseFloat(data.discount) };
      setDiscountGBP(appliedPromo.discountGBP);
      if (msg) msg.style.color = '#065f46', msg.textContent = 'Promo applied.';
    } catch (e) {
      if (msg) msg.textContent = 'Unable to validate promo. Try again later.';
    }
  }

  function removePromo() {
    appliedPromo = null;
    setDiscountGBP(0);
    const msg = document.getElementById('promo-message');
    if (msg) msg.textContent = '';
    const input = document.getElementById('promo-code');
    if (input) input.value = '';
  }

  // Observe subtotal changes to re-validate promo with new subtotal
  document.addEventListener('DOMContentLoaded', function(){
    const applyBtn = document.getElementById('apply-promo');
    const removeBtn = document.getElementById('remove-promo');
    applyBtn && applyBtn.addEventListener('click', function(){
      const code = (document.getElementById('promo-code')?.value || '').trim();
      validateAndApply(code);
    });
    removeBtn && removeBtn.addEventListener('click', removePromo);

    const subEl = document.getElementById('subtotal');
    if (subEl && 'MutationObserver' in window) {
      const obs = new MutationObserver(() => {
        if (appliedPromo && appliedPromo.code) {
          // Re-validate against backend with new subtotal to respect min order, caps, etc.
          validateAndApply(appliedPromo.code);
        } else {
          setDiscountGBP(0);
        }
      });
      obs.observe(subEl, { attributes: true, attributeFilter: ['data-price'] });
    }
  });
})();
</script>

<script>
// States/provinces for supported countries and dynamic population
(function(){
  const statesByCountry = {
    'Nigeria': [
      'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'
    ],
    'United States': [
      'Alabama','Alaska','Arizona','Arkansas','California','Colorado','Connecticut','Delaware','District of Columbia','Florida','Georgia','Hawaii','Idaho','Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota','Mississippi','Missouri','Montana','Nebraska','Nevada','New Hampshire','New Jersey','New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee','Texas','Utah','Vermont','Virginia','Washington','West Virginia','Wisconsin','Wyoming'
    ],
    'Canada': [
      'Alberta','British Columbia','Manitoba','New Brunswick','Newfoundland and Labrador','Nova Scotia','Ontario','Prince Edward Island','Quebec','Saskatchewan','Northwest Territories','Nunavut','Yukon'
    ],
    'United Kingdom': [
      'England','Scotland','Wales','Northern Ireland'
    ]
  };

  function populateStates(country, selected){
    const stateSelect = document.getElementById('state-select');
    if (!stateSelect) return;
    stateSelect.innerHTML = '<option value="">Select State</option>';
    const list = statesByCountry[country] || [];
    list.forEach(st => {
      const opt = document.createElement('option');
      opt.value = st; opt.textContent = st;
      if (selected && selected === st) opt.selected = true;
      stateSelect.appendChild(opt);
    });
  }
  window.populateStates = populateStates;

  document.addEventListener('DOMContentLoaded', function(){
    const countrySel = document.getElementById('country-select');
    if (countrySel) {
      populateStates(countrySel.value);
      countrySel.addEventListener('change', function(){
        populateStates(this.value);
        if (typeof updateShippingProgress === 'function') updateShippingProgress();
      });
    }
  });
})();

// Addresses handling for logged-in users
(function(){
  async function loadAddresses(){
    const sel = document.getElementById('address-select');
    if (!sel) return; // not logged in
    try {
      sel.innerHTML = '<option value="">Loading...</option>';
      const res = await fetch('/api/account/get_addresses.php');
      const data = await res.json();
      if (!data.success) {
        sel.innerHTML = '<option value="">No addresses found</option>';
        return;
      }
      const addresses = data.addresses || [];
      if (!addresses.length) {
        sel.innerHTML = '<option value="">No saved addresses. Add a new one.</option>';
        return;
      }
      sel.innerHTML = '';
      let defaultId = null;
      addresses.forEach(a => {
        const opt = document.createElement('option');
        const id = String(a.id || a.address_id);
        const first = a.first_name || '';
        const last = a.last_name || '';
        const full = a.full_name || `${first} ${last}`.trim();
        const line1 = a.address_line_1 || a.street_address || '';
        const label = `${(a.type || a.address_name || 'Address')} - ${full}, ${line1}, ${a.city || ''}, ${a.state || ''}, ${a.country || ''}`;
        opt.value = id;
        opt.textContent = label;
        if (a.is_default && defaultId === null) { defaultId = id; }
        sel.appendChild(opt);
      });
      if (defaultId !== null) sel.value = defaultId;
      // Prefill form and shipping meter
      applySelectedAddress(addresses.find(a => String(a.id || a.address_id) === sel.value));
      sel.addEventListener('change', function(){
        const match = addresses.find(a => String(a.id || a.address_id) === this.value);
        applySelectedAddress(match);
      });
    } catch (e) {
      console.error('Failed to load addresses', e);
    }
  }

  function applySelectedAddress(addr){
    if (!addr) return;
    const full = addr.full_name || `${(addr.first_name||'')} ${(addr.last_name||'')}`.trim();
    document.getElementById('client-name') && (document.getElementById('client-name').value = full);
    document.getElementById('client-phone') && (document.getElementById('client-phone').value = addr.phone || '');
    document.getElementById('shipping-address') && (document.getElementById('shipping-address').value = addr.address_line_1 || addr.street_address || '');
    document.getElementById('city-input') && (document.getElementById('city-input').value = addr.city || '');
    const countrySel = document.getElementById('country-select');
    if (countrySel) {
      countrySel.value = addr.country || 'Nigeria';
      if (window.populateStates) window.populateStates(countrySel.value, addr.state || '');
    }
    if (typeof updateShippingProgress === 'function') updateShippingProgress();
  }

  async function bindSaveAddress(){
    const btn = document.getElementById('save-address-btn');
    if (!btn) return;
    btn.addEventListener('click', async function(){
      const fullName = (document.getElementById('client-name')?.value || '').trim();
      const firstSpace = fullName.indexOf(' ');
      const first_name = firstSpace > 0 ? fullName.substring(0, firstSpace) : fullName;
      const last_name = firstSpace > 0 ? fullName.substring(firstSpace+1) : '';
      const payload = {
        type: 'shipping',
        first_name,
        last_name,
        company: '',
        address_line_1: (document.getElementById('shipping-address')?.value || '').trim(),
        address_line_2: '',
        city: (document.getElementById('city-input')?.value || '').trim(),
        state: (document.getElementById('state-select')?.value || ''),
        postal_code: '',
        country: (document.getElementById('country-select')?.value || 'Nigeria'),
        is_default: false
      };
      if (!payload.first_name || !payload.address_line_1 || !payload.city || !payload.state) {
        alert('Please fill all required fields');
        return;
      }
      try {
        const res = await fetch('/api/account/save_address.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          alert('Address saved');
          // Hide form and reload addresses
          const infoCard = document.getElementById('customer-info-card');
          if (infoCard) infoCard.style.display = 'none';
          await loadAddresses();
        } else {
          alert(data.message || 'Failed to save address');
        }
      } catch(err){
        console.error('Save address error', err);
        alert('Error saving address');
      }
    });
  }

  function bindAddNew(){
    const addBtn = document.getElementById('add-address-btn');
    if (!addBtn) return;
    addBtn.addEventListener('click', function(){
      const infoCard = document.getElementById('customer-info-card');
      if (infoCard) infoCard.style.display = 'block';
      // Reset fields
      const name = document.getElementById('client-name'); if (name) name.value='';
      const phone = document.getElementById('client-phone'); if (phone) phone.value='';
      const addr = document.getElementById('shipping-address'); if (addr) addr.value='';
      const city = document.getElementById('city-input'); if (city) city.value='';
      const countrySel = document.getElementById('country-select'); if (countrySel) { countrySel.value='Nigeria'; if (window.populateStates) window.populateStates('Nigeria'); }
      const stateSel = document.getElementById('state-select'); if (stateSel) stateSel.value='';
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    loadAddresses();
    bindSaveAddress();
    bindAddNew();
  });
})();
</script>

<script>
// Allow guests to proceed to checkout; simply redirect to checkout page where guest flow is supported
document.addEventListener('DOMContentLoaded', function(){
  const checkoutBtn = document.getElementById('checkout-btn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', function(e){
      // proceed to checkout; cart is handled via server or localStorage on checkout page
      window.location.href = '/checkout.php';
    });
  }
});
</script>

<?php include 'includes/footer.php'; ?>
