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

main { background: var(--bg); }

.cart-container { max-width: 1200px; margin: 0 auto; padding: 32px 16px; }
.cart-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
@media (min-width: 992px) { .cart-grid { grid-template-columns: 2fr 1fr; } }

.card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); font-weight: 600; color: var(--text); }
.card-body { padding: 20px; }

/* Left column */
.cart-title { margin-bottom: 16px; }
.empty-cart { text-align: center; padding: 40px 20px; color: var(--muted); }

/* Cart item */
.cart-item { display: grid; grid-template-columns: 96px 1fr; gap: 16px; padding: 16px 0; border-bottom: 1px solid var(--border); align-items: center; }
.cart-item:last-child { border-bottom: none; }
.cart-item .thumb { width: 96px; height: 96px; border-radius: 8px; overflow: hidden; background: #f3f4f6; }
.cart-item .thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cart-item .meta { display: flex; flex-direction: column; gap: 8px; }
.cart-item .name { font-weight: 600; color: var(--text); }
.cart-item .variant { font-size: 12px; color: var(--muted); }
.cart-item .row-bottom { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.qty-control { display: inline-flex; align-items: center; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
.qty-control button { width: 36px; height: 36px; border: none; background: #f3f4f6; cursor: pointer; }
.qty-control input { width: 56px; height: 36px; text-align: center; border: none; border-left: 1px solid var(--border); border-right: 1px solid var(--border); }
.price-line { font-weight: 600; color: var(--text); white-space: nowrap; }
.remove-from-cart { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 8px 12px; border-radius: 8px; cursor: pointer; }

/* Right column */
.progress { position: relative; width: 100%; height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
.progress-bar { height: 100%; width: 0%; background: #ef4444; transition: width 0.3s ease; }
.bg-green { background: #10b981 !important; }
.bg-yellow { background: #f59e0b !important; }
.bg-red { background: #ef4444 !important; }
.progress-label { font-size: 13px; color: var(--muted); margin-bottom: 8px; }
.progress-note { font-size: 12px; color: var(--muted); margin-top: 6px; }

.summary-list { list-style: none; padding: 0; margin: 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.summary-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border); }
.summary-item:last-child { border-bottom: none; }
.summary-strong { font-weight: 700; }

.btn-primary { display: block; width: 100%; text-align: center; background: var(--accent); color: #fff; border: none; padding: 14px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; }
.btn-outline { display: inline-block; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; color: var(--text); background: #fff; cursor: pointer; }

/* Address form */
.form-group { margin-bottom: 12px; }
.form-group label { display: block; margin-bottom: 6px; color: var(--muted); font-size: 13px; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; }
.form-row { display: grid; grid-template-columns: 1fr; gap: 12px; }
@media (min-width: 640px) { .form-row { grid-template-columns: 1fr 1fr; } }

.loading { text-align: center; padding: 24px; color: var(--muted); }
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
        <!-- Free Shipping Progress -->
        <div class="card" style="margin-bottom:16px;">
          <div class="card-header"><i class="fas fa-truck" style="margin-right:8px;"></i>Free Shipping</div>
          <div class="card-body">
            <div class="progress-label" id="shipping-progress-text">Add more to qualify for free shipping</div>
            <div class="progress"><div id="shipping-progress-bar" class="progress-bar"></div></div>
            <div class="progress-note" id="shipping-info-text">Free shipping: ₦150k+ (Lagos) • ₦250k+ (Other Nigerian states) • ₦600k+ (African countries) • ₦800k+ (Other countries) • £300+ (United Kingdom)</div>
          </div>
        </div>

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
            <div class="form-row" style="margin-top:10px;">
              <div class="form-group">
                <label for="promo-code">Promo Code</label>
                <input type="text" id="promo-code" placeholder="Enter promo code" />
              </div>
              <div class="form-group" style="display:flex; align-items:flex-end; gap:8px;">
                <button type="button" class="btn-outline" id="apply-promo">Apply</button>
                <button type="button" class="btn-outline" id="remove-promo">Remove</button>
              </div>
            </div>
            <div id="promo-message" style="color:#b91c1c; font-size:13px; margin-top:6px;"></div>
            <button class="btn-primary" id="checkout-btn" style="margin-top:12px;">Proceed to Checkout</button>
          </div>
        </div>

        <?php if ($is_logged_in): ?>
        <!-- Saved Addresses for Logged-in Users -->
        <div class="card" id="addresses-card" style="margin-bottom:16px;">
          <div class="card-header">Saved Addresses</div>
          <div class="card-body">
            <div class="form-group">
              <label for="address-select">Select Address</label>
              <select id="address-select">
                <option value="">Loading addresses...</option>
              </select>
            </div>
            <button type="button" class="btn-outline" id="add-address-btn" style="margin-top:8px;">Add New Address</button>
          </div>
        </div>
        <?php endif; ?>

        <!-- Customer Information -->
        <div class="card" id="customer-info-card" <?php echo $is_logged_in ? 'style="display:none;"' : ''; ?> >
          <div class="card-header">Customer Information</div>
          <div class="card-body">
            <?php if ($is_logged_in): ?>
            <div class="form-group">
              <label for="address-name">Address Name</label>
              <input type="text" id="address-name" placeholder="Home, Work, etc." />
            </div>
            <?php endif; ?>
            <div class="form-group">
              <label for="client-name">Full Name</label>
              <input type="text" id="client-name" placeholder="Enter your full name" />
            </div>
            <div class="form-group">
              <label for="client-phone">Phone Number</label>
              <input type="tel" id="client-phone" placeholder="Enter your phone number" />
            </div>
            <div class="form-group">
              <label for="shipping-address">Street Address</label>
              <textarea id="shipping-address" rows="3" placeholder="Enter your street address"></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="city-input">City</label>
                <input type="text" id="city-input" placeholder="Enter your city" />
              </div>
              <div class="form-group">
                <label for="country-select">Country</label>
                <select id="country-select">
                  <option value="Nigeria" selected>Nigeria</option>
                  <option value="United Kingdom">United Kingdom</option>
                  <option value="United States">United States</option>
                  <option value="Canada">Canada</option>
                  <option value="Ghana">Ghana</option>
                  <option value="Kenya">Kenya</option>
                  <option value="South Africa">South Africa</option>
                  <option value="Germany">Germany</option>
                  <option value="France">France</option>
                  <option value="Australia">Australia</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="state-select">State/Province</label>
              <select id="state-select">
                <option value="">Select State</option>
              </select>
            </div>
            <?php if ($is_logged_in): ?>
            <button type="button" class="btn-primary" id="save-address-btn" style="margin-top:8px;">Save Address</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
// Free shipping meter based on DeeReel thresholds; adapts to selected country/state.
(function(){
  const AFRICAN_COUNTRIES = [
    'Algeria','Angola','Benin','Botswana','Burkina Faso','Burundi','Cameroon','Cape Verde','Central African Republic','Chad','Comoros','Congo','Democratic Republic of the Congo','Djibouti','Egypt','Equatorial Guinea','Eritrea','Eswatini','Ethiopia','Gabon','Gambia','Ghana','Guinea','Guinea-Bissau','Ivory Coast','Kenya','Lesotho','Liberia','Libya','Madagascar','Malawi','Mali','Mauritania','Mauritius','Morocco','Mozambique','Namibia','Niger','Nigeria','Rwanda','Sao Tome and Principe','Senegal','Seychelles','Sierra Leone','Somalia','South Africa','South Sudan','Sudan','Tanzania','Togo','Tunisia','Uganda','Zambia','Zimbabwe'
  ];

  function getShippingThresholdNGN(country, state){
    if (country === 'Nigeria' && state === 'Lagos') return 150000;
    if (country === 'Nigeria') return 250000;
    if (AFRICAN_COUNTRIES.includes(country)) return 600000;
    return 800000;
  }

  // Exported for cart-handler.js to call
  window.updateShippingProgress = function(){
    const bar = document.getElementById('shipping-progress-bar');
    const text = document.getElementById('shipping-progress-text');
    const info = document.getElementById('shipping-info-text');
    const subtotalEl = document.getElementById('subtotal');
    if (!bar || !text || !subtotalEl) return;

    // Subtotal in GBP base stored in data-price by cart-handler
    const subtotalGBP = parseFloat(subtotalEl.getAttribute('data-price') || '0') || 0;

    const country = (document.getElementById('country-select')?.value) || 'Nigeria';
    const state = (document.getElementById('state-select')?.value) || 'Lagos';

    // Convert NGN thresholds to GBP using currency rates if available
    let rateNGN = 0;
    try {
      rateNGN = window.currencyConverter?.rates?.NGN || 0;
    } catch(e) { rateNGN = 0; }

    let thresholdGBP = 0;
    if (country === 'United Kingdom') {
      thresholdGBP = 300; // £300 free shipping threshold in UK
    } else {
      const thresholdNGN = getShippingThresholdNGN(country, state);
      thresholdGBP = rateNGN > 0 ? (thresholdNGN / rateNGN) : 0; // Convert NGN thresholds to GBP base
    }

    let percent = 0;
    if (thresholdGBP > 0) percent = Math.min((subtotalGBP / thresholdGBP) * 100, 100);
    bar.style.width = percent + '%';
    bar.className = 'progress-bar ' + (percent >= 100 ? 'bg-green' : percent >= 50 ? 'bg-yellow' : 'bg-red');

    // Remaining amount in current currency with symbol
    let symbol = '£';
    let rateCurrent = 1;
    const cc = window.currencyConverter;
    if (cc && cc.currentCurrency) {
      symbol = cc.symbols?.[cc.currentCurrency] || symbol;
      rateCurrent = cc.rates?.[cc.currentCurrency] || 1;
    }

    const remainingGBP = Math.max(thresholdGBP - subtotalGBP, 0);
    const remainingDisp = Math.round(remainingGBP * rateCurrent).toLocaleString();

    let locationText = country;
    if (country === 'Nigeria') {
      locationText = (state === 'Lagos') ? 'Lagos' : 'other Nigerian states';
    } else if (AFRICAN_COUNTRIES.includes(country)) {
      locationText = 'other African countries';
    } else if (country === 'United Kingdom') {
      locationText = 'the United Kingdom';
    } else {
      locationText = 'international delivery';
    }

    if (percent >= 100) {
      text.innerHTML = '🎉 You qualify for free shipping!';
      document.getElementById('shipping')?.classList.add('text-success');
      document.getElementById('shipping') && (document.getElementById('shipping').textContent = 'Free');
    } else {
      text.innerHTML = `Add ${symbol} ${remainingDisp} more for free shipping to ${locationText}`;
      document.getElementById('shipping') && (document.getElementById('shipping').textContent = 'Depends on location');
    }

    if (info) {
      const thresholdDisp = Math.round(thresholdGBP * rateCurrent).toLocaleString();
      info.innerHTML = `📍 Current location: ${country === 'Nigeria' ? (state || 'Nigeria') : country} • Free shipping: ${symbol} ${thresholdDisp}+`;
    }
  };

  // Recompute when address fields change
  document.addEventListener('change', function(e){
    if (e.target && (e.target.id === 'country-select' || e.target.id === 'state-select')) {
      window.updateShippingProgress();
    }
  });
})();
</script>

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
    if (typeof updateShippingProgress === 'function') updateShippingProgress();
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

<?php include 'includes/footer.php'; ?>
