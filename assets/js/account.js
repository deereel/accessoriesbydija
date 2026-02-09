// Account Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Tab Navigation
    const navItems = document.querySelectorAll('.account-nav-item:not(.logout)');
    const tabs = document.querySelectorAll('.account-tab');
    
    // Restore saved tab from sessionStorage
    const savedTab = sessionStorage.getItem('accountActiveTab');
    if (savedTab) {
        const targetTab = document.querySelector(`.account-nav-item[data-tab="${savedTab}"]`);
        if (targetTab) {
            // Update active nav item
            navItems.forEach(nav => nav.classList.remove('active'));
            targetTab.classList.add('active');
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            const targetTabContent = document.getElementById(savedTab + '-tab');
            if (targetTabContent) {
                targetTabContent.classList.add('active');
                // Load tab content
                loadTabContent(savedTab);
            }
        }
    } else {
        // Load initial dashboard data
        loadDashboardData();
    }
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.dataset.tab;
            
            // Save active tab to sessionStorage
            sessionStorage.setItem('accountActiveTab', tabId);
            
            // Update active nav item
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabId + '-tab').classList.add('active');
            
            // Load tab content
            loadTabContent(tabId);
        });
    });
    
    // Load initial dashboard data (only if no saved tab)
    if (!sessionStorage.getItem('accountActiveTab')) {
        loadDashboardData();
    }
    
    // Save tab before leaving page
    window.addEventListener('beforeunload', function() {
        const activeTab = document.querySelector('.account-nav-item.active');
        if (activeTab) {
            sessionStorage.setItem('accountActiveTab', activeTab.dataset.tab);
        }
    });
    
    // Profile Form
    document.getElementById('profile-form').addEventListener('submit', handleProfileUpdate);
    
    // Password Form
    document.getElementById('password-form').addEventListener('submit', handlePasswordChange);
    
    // Address Modal
    const addressModal = document.getElementById('address-modal');
    const addAddressBtn = document.getElementById('add-address-btn');
    const cancelAddressBtn = document.getElementById('cancel-address-btn');
    const closeBtn = addressModal.querySelector('.close');
    
    addAddressBtn.addEventListener('click', () => openAddressModal());
    cancelAddressBtn.addEventListener('click', () => closeAddressModal());
    closeBtn.addEventListener('click', () => closeAddressModal());
    
    document.getElementById('address-form').addEventListener('submit', handleAddressSave);
    
    // Delete Account
    const deleteModal = document.getElementById('delete-modal');
    const deleteAccountBtn = document.getElementById('delete-account-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    
    deleteAccountBtn.addEventListener('click', () => {
        deleteModal.classList.add('active');
    });
    
    cancelDeleteBtn.addEventListener('click', () => {
        deleteModal.classList.remove('active');
        document.getElementById('delete-confirm').value = '';
    });
    
    confirmDeleteBtn.addEventListener('click', handleAccountDelete);
});

function loadTabContent(tabId) {
    switch(tabId) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'orders':
            loadOrders();
            break;
        case 'addresses':
            loadAddresses();
            break;
        case 'wishlist':
            loadWishlist();
            break;
    }
}

async function loadDashboardData() {
    try {
        // Load stats
        const ordersResponse = await fetch('/api/account/get_orders.php');
        const ordersData = await ordersResponse.json();
        
        if (ordersData.success) {
            document.getElementById('total-orders').textContent = ordersData.orders.length;
            
            // Display recent orders
            const recentOrders = ordersData.orders.slice(0, 3);
            displayRecentOrders(recentOrders);
        }
        
        const addressesResponse = await fetch('/api/account/get_addresses.php');
        const addressesData = await addressesResponse.json();
        
        if (addressesData.success) {
            document.getElementById('total-addresses').textContent = addressesData.addresses.length;
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

function displayRecentOrders(orders) {
    const container = document.getElementById('recent-orders-list');
    
    if (orders.length === 0) {
        container.innerHTML = '<p class="text-muted">No orders yet</p>';
        return;
    }
    
    container.innerHTML = orders.map(order => `
        <div class="order-card">
            <div class="order-header">
                <span class="order-number">#${order.order_number}</span>
                <span class="order-status ${order.status}">${order.status}</span>
            </div>
            <div class="order-details">
                <div class="order-detail">
                    <label>Date</label>
                    <span>${order.created_at_human ? order.created_at_human : formatDate(order.created_at)}</span>
                </div>
                <div class="order-detail">
                    <label>Total</label>
                    <span>£${parseFloat(order.total_amount).toFixed(2)}</span>
                </div>
                <div class="order-detail">
                    <label>Items</label>
                    <span>${order.item_count || 0} items</span>
                </div>
            </div>
        </div>
    `).join('');
}

async function loadOrders() {
    const container = document.getElementById('orders-list');
    
    try {
        const response = await fetch('/api/account/get_orders.php');
        const data = await response.json();
        
        if (data.success) {
            if (data.orders.length === 0) {
                container.innerHTML = '<p class="text-muted">No orders yet</p>';
                return;
            }
            
            container.innerHTML = data.orders.map(order => `
                <div class="order-card" data-order-id="${order.id}">
                    <div class="order-header">
                        <span class="order-number">#${order.order_number}</span>
                        <span class="order-status ${order.status}">${order.status}</span>
                    </div>
                    <div class="order-details">
                        <div class="order-detail">
                            <label>Date</label>
                            <span>${order.created_at_human ? order.created_at_human : formatDate(order.created_at)}</span>
                        </div>
                        <div class="order-detail">
                            <label>Total</label>
                            <span>£${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                        <div class="order-detail">
                            <label>Payment</label>
                            <span>${order.payment_status}</span>
                        </div>
                    </div>
                </div>
            `).join('');

            // Attach click handlers to open order modal
            document.querySelectorAll('.order-card[data-order-id]').forEach(card => {
                card.addEventListener('click', () => {
                    const orderId = card.getAttribute('data-order-id');
                    openOrderModal(orderId);
                });
            });
        }
    } catch (error) {
        container.innerHTML = '<p class="text-muted">Error loading orders</p>';
    }
}

// Fetch and display order details in modal
async function openOrderModal(orderId) {
    const modal = document.getElementById('order-modal');
    const body = document.getElementById('order-modal-body');
    body.innerHTML = '<p class="text-muted">Loading...</p>';
    modal.classList.add('active');

    try {
        const response = await fetch(`/api/account/get_order.php?order_id=${orderId}`);
        const data = await response.json();
        if (!data.success) {
            body.innerHTML = `<p class="text-muted">${data.message}</p>`;
            return;
        }

        const order = data.order;
        const items = data.items || [];

        body.innerHTML = `
            <p><strong>Order #${order.order_number}</strong></p>
            <p>Status: ${order.status} | Payment: ${order.payment_status}</p>
            <p>Date: ${formatDate(order.created_at)}</p>
            <h4>Items</h4>
            <table class="items-table" style="width:100%;border-collapse:collapse;">
                <thead><tr><th>Product</th><th style="text-align:right">Qty</th><th style="text-align:right">Unit</th><th style="text-align:right">Total</th></tr></thead>
                <tbody>
                    ${items.map(i => `
                        <tr>
                            <td>${i.name || i.product_name}</td>
                            <td style="text-align:right">${i.quantity}</td>
                            <td style="text-align:right">£${(parseFloat(i.unit_price)||0).toFixed(2)}</td>
                            <td style="text-align:right">£${(parseFloat(i.unit_price||0)*parseInt(i.quantity||0)).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            <div style="margin-top:1rem;">
                <strong>Total: £${parseFloat(order.total_amount).toFixed(2)}</strong>
            </div>
        `;
    } catch (err) {
        body.innerHTML = '<p class="text-muted">Error loading order details</p>';
    }
}

// Order modal close handlers
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('order-modal');
    const close = document.getElementById('order-modal-close');
    const closeBtn = document.getElementById('order-modal-close-btn');
    if (close) close.addEventListener('click', () => modal.classList.remove('active'));
    if (closeBtn) closeBtn.addEventListener('click', () => modal.classList.remove('active'));
});

async function loadAddresses() {
    const container = document.getElementById('addresses-list');

    try {
        const response = await fetch('/api/account/get_addresses.php');
        const data = await response.json();

        if (data.success) {
            if (data.addresses.length === 0) {
                container.innerHTML = '<p class="text-muted">No saved addresses</p>';
                return;
            }

            container.innerHTML = '<div class="addresses-grid">' + data.addresses.map(address => `
                <div class="address-card ${address.is_default ? 'default' : ''}">
                    ${address.is_default ? '<span class="address-badge">Default</span>' : ''}
                    <div class="address-type">${address.type}</div>
                    <div class="address-name">${address.first_name} ${address.last_name}</div>
                    <div class="address-details">
                        ${address.company ? address.company + '<br>' : ''}
                        ${address.address_line_1}<br>
                        ${address.address_line_2 ? address.address_line_2 + '<br>' : ''}
                        ${address.city}, ${address.state} ${address.postal_code}<br>
                        ${address.country}<br>
                        ${address.phone ? 'Phone: ' + address.phone : ''}
                    </div>
                    <div class="address-actions">
                        <button class="btn-small btn-edit" onclick="editAddress(${address.id})">Edit</button>
                        <button class="btn-small btn-delete" onclick="deleteAddress(${address.id})">Delete</button>
                    </div>
                </div>
            `).join('') + '</div>';
        }
    } catch (error) {
        container.innerHTML = '<p class="text-muted">Error loading addresses</p>';
    }
}

async function loadWishlist() {
    const container = document.getElementById('wishlist-list');

    try {
        const response = await fetch('/api/wishlist.php');
        const data = await response.json();

        if (data.success) {
            if (data.items.length === 0) {
                container.innerHTML = '<p class="text-muted">Your wishlist is empty</p>';
                return;
            }

            container.innerHTML = '<div class="wishlist-grid">' + data.items.map(item => `
                <div class="product-card wishlist-item" data-product-id="${item.product_id}">
                    <button class="remove-wishlist-btn" onclick="removeFromWishlist(${item.product_id})">
                        <i class="fas fa-times"></i>
                    </button>
                    <a href="product.php?slug=${item.slug}" class="product-image">
                        ${item.image_url ? `<img src="${item.image_url}" alt="${item.product_name}">` : '<div class="placeholder">No Image</div>'}
                    </a>
                    <div class="product-info">
                        <h3><a href="product.php?slug=${item.slug}">${item.product_name}</a></h3>
                        <div class="product-footer">
                            <span class="product-price">£${parseFloat(item.price).toFixed(2)}</span>
                            <button class="cart-btn add-to-cart-btn" onclick="addToCartFromWishlist(${item.product_id}, '${item.product_name.replace(/'/g, "\\'")}', ${item.price}, '${item.image_url || ''}')">Add to Cart</button>
                        </div>
                    </div>
                </div>
            `).join('') + '</div>';
        } else {
            container.innerHTML = '<p class="text-muted">Error loading wishlist</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="text-muted">Error loading wishlist</p>';
    }
}

async function removeFromWishlist(productId) {
    if (!confirm('Remove this item from your wishlist?')) {
        return;
    }

    try {
        const response = await fetch(`/api/wishlist.php?product_id=${productId}`, {
            method: 'DELETE'
        });
        const data = await response.json();

        if (data.success) {
            loadWishlist(); // Reload the wishlist
        } else {
            alert('Error removing item from wishlist');
        }
    } catch (error) {
        alert('Error removing item from wishlist');
    }
}

async function addToCartFromWishlist(productId, productName, price, image) {
    if (window.cartHandler) {
        const productData = {
            product_id: productId,
            product_name: productName,
            price: price,
            image: image,
            quantity: 1
        };
        await window.cartHandler.addToCart(productData);
    } else {
        alert('Cart handler not available');
    }
}

async function handleProfileUpdate(e) {
    e.preventDefault();
    
    const messageEl = document.getElementById('profile-message');
    const formData = {
        first_name: document.getElementById('first_name').value,
        last_name: document.getElementById('last_name').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        date_of_birth: document.getElementById('date_of_birth').value,
        gender: document.getElementById('gender').value
    };
    
    try {
        const response = await fetch('/api/account/update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        messageEl.textContent = data.message;
        messageEl.className = 'message ' + (data.success ? 'success' : 'error');
        messageEl.style.display = 'block';
        
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 3000);
    } catch (error) {
        messageEl.textContent = 'An error occurred';
        messageEl.className = 'message error';
        messageEl.style.display = 'block';
    }
}

async function handlePasswordChange(e) {
    e.preventDefault();
    
    const messageEl = document.getElementById('password-message');
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_new_password').value;
    
    if (newPassword !== confirmPassword) {
        messageEl.textContent = 'Passwords do not match';
        messageEl.className = 'message error';
        messageEl.style.display = 'block';
        return;
    }
    
    const formData = {
        current_password: document.getElementById('current_password').value,
        new_password: newPassword
    };
    
    try {
        const response = await fetch('/api/account/change_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        messageEl.textContent = data.message;
        messageEl.className = 'message ' + (data.success ? 'success' : 'error');
        messageEl.style.display = 'block';
        
        if (data.success) {
            e.target.reset();
        }
        
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 3000);
    } catch (error) {
        messageEl.textContent = 'An error occurred';
        messageEl.className = 'message error';
        messageEl.style.display = 'block';
    }
}

function openAddressModal(addressId = null) {
    const modal = document.getElementById('address-modal');
    const form = document.getElementById('address-form');
    const title = document.getElementById('address-modal-title');
    
    if (addressId) {
        title.textContent = 'Edit Address';
        // Load address data
        loadAddressData(addressId);
    } else {
        title.textContent = 'Add Address';
        form.reset();
    }
    
    modal.classList.add('active');
}

function closeAddressModal() {
    document.getElementById('address-modal').classList.remove('active');
}

async function loadAddressData(addressId) {
    try {
        const response = await fetch(`/api/account/get_addresses.php?id=${addressId}`);
        const data = await response.json();
        
        if (data.success && data.address) {
            const address = data.address;
            document.getElementById('address_id').value = address.id;
            document.getElementById('address_first_name').value = address.first_name;
            document.getElementById('address_last_name').value = address.last_name;
            document.getElementById('phone').value = address.phone || '';
            document.getElementById('address_type').value = address.type;
            document.getElementById('company').value = address.company || '';
            document.getElementById('address_line_1').value = address.address_line_1;
            document.getElementById('address_line_2').value = address.address_line_2 || '';
            document.getElementById('city').value = address.city;
            document.getElementById('state').value = address.state;
            document.getElementById('postal_code').value = address.postal_code;
            document.getElementById('country').value = address.country;
            document.getElementById('is_default').checked = address.is_default == 1;
        }
    } catch (error) {
        console.error('Error loading address:', error);
    }
}

async function handleAddressSave(e) {
    e.preventDefault();

    const messageEl = document.getElementById('address-message');
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.is_default = document.getElementById('is_default').checked ? 1 : 0;
    data.csrf_token = document.getElementById('csrf_token').value;

    try {
        const response = await fetch('/api/account/save_address.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeAddressModal();
            loadAddresses();
        } else {
            messageEl.textContent = result.message;
            messageEl.className = 'message error';
            messageEl.style.display = 'block';
        }
    } catch (error) {
        messageEl.textContent = 'An error occurred';
        messageEl.className = 'message error';
        messageEl.style.display = 'block';
    }
}

async function editAddress(addressId) {
    openAddressModal(addressId);
}

async function deleteAddress(addressId) {
    if (!confirm('Are you sure you want to delete this address?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/account/delete_address.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ address_id: addressId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadAddresses();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('An error occurred');
    }
}

async function handleAccountDelete() {
    const confirmText = document.getElementById('delete-confirm').value;
    
    if (confirmText !== 'DELETE') {
        alert('Please type DELETE to confirm');
        return;
    }
    
    try {
        const response = await fetch('/api/account/delete_account.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'index.php';
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('An error occurred');
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}