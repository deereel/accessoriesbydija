class CartHandler {
    constructor() {
        this.userId = null;
        this.isLoggedIn = false;
        this.cartCount = 0;
        this.init();
    }

    async init() {
        await this.checkLoginStatus();
        this.updateCartCount();
        this.bindEvents();
    }

    async checkLoginStatus() {
        try {
            const response = await fetch('/auth/check_session.php');
            const data = await response.json();
            this.isLoggedIn = data.logged_in || false;
            this.userId = data.user_id || null;
        } catch (error) {
            console.error('Error checking login status:', error);
            this.isLoggedIn = false;
            this.userId = null;
        }
    }

    bindEvents() {
        // Bind add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.add-to-cart-btn, .add-to-cart')) {
                e.preventDefault();
                this.addToCartFromButton(e.target);
            }
        });

        // Bind quantity update buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.qty-btn, .quantity-btn')) {
                e.preventDefault();
                this.updateQuantityFromButton(e.target);
            }
        });

        // Bind quantity input changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('.quantity-input')) {
                this.updateQuantityFromInput(e.target);
            }
        });

        // Bind remove from cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.remove-from-cart, .remove-item')) {
                e.preventDefault();
                this.removeFromCart(e.target);
            }
        });
    }

    async addToCartFromButton(button) {
        const productCard = button.closest('.product-card, .product-item, [data-product-id]');
        if (!productCard) {
            console.error('Product card not found');
            return;
        }

        const productData = this.extractProductData(productCard);
        if (!productData.product_id) {
            console.error('Product ID not found');
            return;
        }

        await this.addToCart(productData);
    }

    extractProductData(productCard) {
        return {
            product_id: productCard.dataset.productId || productCard.querySelector('[data-product-id]')?.dataset.productId || '',
            product_name: productCard.dataset.productName || productCard.querySelector('.product-name, .product-title')?.textContent?.trim() || '',
            price: parseFloat(productCard.dataset.price || productCard.querySelector('.product-price')?.textContent?.replace(/[^0-9.]/g, '') || 0),
            image: productCard.dataset.image || productCard.querySelector('.product-image img')?.src || '',
            color: productCard.querySelector('.color-select, [name="color"]')?.value || '',
            size: productCard.querySelector('.size-select, [name="size"]')?.value || '',
            width: productCard.querySelector('.width-select, [name="width"]')?.value || '',
            quantity: parseInt(productCard.querySelector('.quantity-input, [name="quantity"]')?.value || 1)
        };
    }

    async addToCart(productData) {
        try {
            const response = await fetch('/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(productData)
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Item added to cart!', 'success');
                this.updateCartCount();

                // If on cart page, reload cart items
                if (window.location.pathname.includes('/cart.php')) {
                    loadCartItems();
                }
            } else {
                this.showNotification(result.message || 'Failed to add item to cart', 'error');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.showNotification('Error adding item to cart', 'error');
        }
    }

    async updateQuantityFromButton(button) {
        const cartItem = button.closest('.cart-item');
        const input = cartItem.querySelector('.quantity-input');
        const currentQty = parseInt(input.value) || 1;

        if (button.classList.contains('plus') || button.textContent.includes('+')) {
            input.value = currentQty + 1;
        } else if (button.classList.contains('minus') || button.textContent.includes('-')) {
            input.value = Math.max(1, currentQty - 1);
        }

        this.updateQuantityFromInput(input);
    }

    async updateQuantityFromInput(input) {
        const cartItem = input.closest('.cart-item');
        const cartItemId = cartItem.dataset.cartItemId;
        const quantity = parseInt(input.value);

        if (!cartItemId || quantity < 1) {
            return;
        }

        try {
            const response = await fetch('/api/cart.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_item_id: cartItemId,
                    quantity: quantity
                })
            });

            const result = await response.json();

            if (result.success) {
                this.updateCartTotals();
                this.updateCartCount();
            } else {
                this.showNotification(result.message || 'Failed to update quantity', 'error');
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
            this.showNotification('Error updating quantity', 'error');
        }
    }

    async removeFromCart(button) {
        const cartItem = button.closest('.cart-item');
        const cartItemId = cartItem.dataset.cartItemId;

        if (!cartItemId) {
            return;
        }

        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            return;
        }

        try {
            const response = await fetch(`/api/cart.php?cart_item_id=${cartItemId}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                cartItem.remove();
                this.updateCartTotals();
                this.updateCartCount();
                this.showNotification('Item removed from cart', 'success');
            } else {
                this.showNotification(result.message || 'Failed to remove item', 'error');
            }
        } catch (error) {
            console.error('Error removing from cart:', error);
            this.showNotification('Error removing item from cart', 'error');
        }
    }

    async updateCartCount() {
        try {
            const cart = await this.getCart();
            this.cartCount = cart.items ? cart.items.reduce((total, item) => total + item.quantity, 0) : 0;

            // Update cart count in header/navigation
            const cartCountElements = document.querySelectorAll('.cart-count, .cart-badge');
            cartCountElements.forEach(element => {
                element.textContent = this.cartCount;
                element.style.display = this.cartCount > 0 ? 'inline-block' : 'none';
            });
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    }

    async getCart() {
        try {
            const response = await fetch('/api/cart.php');
            return await response.json();
        } catch (error) {
            console.error('Error getting cart:', error);
            return { success: false, items: [] };
        }
    }

    updateCartTotals() {
        // Update subtotal, tax, shipping, and total
        const cartItems = document.querySelectorAll('.cart-item');
        let subtotal = 0;

        cartItems.forEach(item => {
            const price = parseFloat(item.dataset.price || 0);
            const quantity = parseInt(item.querySelector('.quantity-input')?.value || 1);
            subtotal += price * quantity;
        });

        // Update subtotal display
        const subtotalElement = document.getElementById('subtotal');
        if (subtotalElement) {
            subtotalElement.textContent = `₦${subtotal.toLocaleString()}`;
        }

        // Update total display
        const totalElement = document.getElementById('total');
        if (totalElement) {
            totalElement.textContent = `₦${subtotal.toLocaleString()}`;
        }

        // Update shipping progress if function exists
        if (typeof updateShippingProgress === 'function') {
            updateShippingProgress();
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} notification-toast`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            background-color: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff'};
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 300px;
            word-wrap: break-word;
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Guest cart functionality using localStorage
    saveGuestCart(cartData) {
        if (!this.isLoggedIn) {
            localStorage.setItem('DIJACart', JSON.stringify(cartData));
        }
    }

    getGuestCart() {
        if (!this.isLoggedIn) {
            const cart = localStorage.getItem('DIJACart');
            return cart ? JSON.parse(cart) : [];
        }
        return [];
    }

    mergeGuestCart() {
        if (this.isLoggedIn) {
            const guestCart = this.getGuestCart();
            if (guestCart.length > 0) {
                // Add guest cart items to user cart
                guestCart.forEach(item => {
                    this.addToCart(item);
                });
                // Clear guest cart
                localStorage.removeItem('DIJACart');
            }
        }
    }
}

// Initialize cart handler when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (!window.cartHandler) {
        window.cartHandler = new CartHandler();
    }
});
