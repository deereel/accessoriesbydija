# System Architecture

## Directory Structure

### `/app/` - Customer Storefront
Main storefront files with PWA support.
- `index.php` - Homepage
- `products.php` - Product listing
- `product.php` - Product detail
- `cart.php` - Shopping cart
- `checkout.php` - Checkout
- `account.php` - Customer account
- `sw.js` - Service Worker for PWA
- `manifest.json` - PWA manifest
- `includes/` - Shared components (header, footer, init)

### `/admin/` - Admin Panel
Admin panel with role-based access and PWA support.
- `index.php` - Login page
- `dashboard.php` - Admin dashboard
- `products.php` - Product management
- `orders.php` - Order management
- `inventory.php` - Inventory management
- `customers.php` - Customer management
- `admin-sw.js` - Service Worker for admin PWA
- `manifest.json` - Admin PWA manifest
- `_layout_header.php` - Admin layout header with responsive design

### `/api/` - API Endpoints
RESTful API endpoints for frontend.
- `products.php` - Product data
- `filtered-products.php` - Filtered product search
- `new-products.php` - New arrivals
- `check-stock-levels.php` - Stock checking
- `testimonials.php` - Testimonials
- Analytics, newsletter, and other endpoints

### `/assets/` - Static Assets
- `/css/` - Stylesheets
- `/js/` - JavaScript files
- `/images/` - Images and icons

## Database Schema
- `products` - Product catalog
- `categories` - Product categories
- `orders` - Customer orders
- `order_items` - Order line items
- `customers` - Customer accounts
- `cart_items` - Shopping cart
- `promo_codes` - Promo codes
- `shipping_rates` - Shipping rates
- `testimonials` - Customer testimonials
- `banners` - Home page banners
- `admin_users` - Admin user accounts
- `newsletter_subscribers` - Newsletter subscribers

## Design Patterns
- **Model-View-Controller (MVC)** - Loosely applied
- **Service Worker Pattern** - Cache-first for PWA
- **Role-Based Access Control (RBAC)** - Admin permissions
- **Session-Based Authentication** - PHP sessions

## PWA Configuration
- **Main PWA**: `/app/sw.js` caches storefront pages
- **Admin PWA**: `/admin/admin-sw.js` caches admin pages
- Both use cache-first strategy with network fallback
- Manifest files define app metadata and icons
