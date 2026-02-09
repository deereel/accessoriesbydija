# Project Context

## Overview
Accessories By Dija is an e-commerce platform for jewelry and accessories, built with PHP/MySQL. The project includes both a customer-facing storefront and an admin panel.

## Tech Stack
- **Backend**: PHP 8.x, MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **PWA**: Service Workers for offline caching
- **Admin**: Separate admin panel with role-based access control

## Project Structure
```
accessoriesbydija/
├── admin/          # Admin panel (PWA-enabled)
├── api/            # API endpoints
├── app/            # Main storefront (PWA-enabled)
├── assets/         # CSS, JS, images
├── plans/          # Planning documents
├── scripts/        # Utility scripts
└── sql/            # Database schemas
```

## Key Features
- Product catalog with categories and filtering
- Shopping cart and checkout
- Order management
- Customer accounts
- Admin panel with role-based access (admin, superadmin, staff)
- PWA support for both storefront and admin

---

## PWA Implementation Details

### Service Worker (`app/sw.js`)
- **Cache Version**: v23
- **Caching Strategy**: Network-first for dynamic content, cache-first for static assets
- **Excluded from Cache** (always network-only):
  - `/account.php` - Customer account page
  - `/login.php` - Login page
  - `/signup.php` - Registration page
  - `/checkout.php` - Checkout page
  - `/cart.php` - Shopping cart
  - `/order-confirmation.php` - Order confirmation
  - `/auth/*` - Authentication endpoints
  - `/api/*` - API endpoints

### PWA Install Prompt (`app/includes/pwa-install.js`)
- Automatically captures `beforeinstallprompt` event
- Shows install banner at bottom of screen
- Respects user dismissal (7-day cooldown)
- Detects standalone mode to prevent showing after installation
- Uses localStorage to track installation status
- Version-based cache clearing for updates

### Manifest (`app/manifest.json`)
- Name: "Dija Accessories"
- Short name: "Dija"
- Display mode: standalone
- Theme color: #C27BA0
- Icons: 16x16, 32x32, 192x192, 512x512 (maskable)

### Admin PWA (`admin/`)
- **Manifest**: `/admin/manifest.json` - "Dija Admin" PWA
- **Service Worker**: `/admin/admin-sw.js` - Separate SW for admin panel
- **Install Script**: `/admin/admin-pwa-install.js` - Admin-specific install banner
- Uses separate localStorage keys (`admin-pwa-installed`, `admin-pwa-install-dismissed`) to avoid conflicts with storefront PWA

---

## Known Issues & Fixes

### Path Issues (CRITICAL for PWA)
- **Issue**: JavaScript files using relative paths (e.g., `assets/js/custom-cta.js`) fail in PWA context
- **Fix**: Always use absolute paths with leading slash (e.g., `/assets/js/custom-cta.js`)
- **Files Fixed**: `app/includes/footer.php`

### Account Page Plain Text
- **Issue**: Account page showing as plain text without styling in PWA
- **Root Cause**: Relative CSS/JS paths resolving incorrectly
- **Fix**: Changed `assets/css/account.css` to `/assets/css/account.css` in `app/account.php`

### Custom CTA Section Not Displaying
- **Issue**: Custom CTA section invisible in PWA
- **Fix**: Added animation fallback to `assets/js/custom-cta.js` with 500ms timeout
- **Fix**: Changed relative paths to absolute paths in `app/includes/footer.php`

---

## Memory Bank System

### Overview
The project uses a dual-memory system:
1. **`.qodo/`** - MCP standard context folder (this file)
2. **`app/memory/`** - Application runtime memory bank (PHP-based)

### App Memory Bank Structure
```
app/memory/
├── memory.php        # MemoryBank class with save(), get(), searchByTag() methods
├── initialize.php    # Pre-loads known issues, fixes, and project context
└── memory.json       # Stored memory data (auto-generated)
```

### MemoryBank Class Methods
- `save(string $key, $value, array $tags = [])` - Save a memory entry
- `get(string $key)` - Retrieve entry by key
- `getEntry(string $key)` - Get entry with metadata (value + tags + timestamp)
- `delete(string $key)` - Delete entry
- `searchByTag(string $tag)` - Find all entries with a specific tag
- `listKeys()` - List all memory keys

### Pre-loaded Memories (from initialize.php)
1. **Project Context** - Name, type, tech stack, features
2. **Bug Fixes** - Hero swiper, account API paths, delete account spacing, checkout country dropdown
3. **Improvements** - Product material guidance, mobile search modal
4. **PWA Fixes** - Image caching, filtering/sorting, path resolution
5. **Recent Changes** - Track all modifications with date, type, description, files affected

### Usage in PHP
```php
require_once __DIR__ . '/memory/initialize.php';

$memory = memory_bank();
$issue = $memory->get('issue_20260205_swiper_not_changing');

// Search by tag
$fixes = $memory->searchByTag('pwa');
```

### Key Memory Keys
- `project_context` - Basic project information
- `recent_changes` - Array of all recent modifications
- `issue_YYYYMMDD_*` - Individual issue records

---

## Recent Changes

### Sale Price Feature
- **Added product sale price functionality** - Admin can now set sale prices on products with percentage or fixed amount discounts
- **Database**: Added columns `is_on_sale`, `sale_price`, `sale_percentage`, `sale_end_date` to products table
- **Admin Interface**: New "Sale Prices" tab in admin promo codes page
- **Storefront**: Products on sale display slashed original price, sale price, and discount badge
- **Files Created/Modified**:
  - `app/memory/sale_price_migration.php` - Database migration
  - `api/sale-prices.php` - Admin API for managing sale prices
  - `admin/promos.php` - Added Sale Prices tab
  - `assets/css/sale-price.css` - Sale price styling
  - `api/filtered-products.php` - Added sale price fields to API response
  - `app/products.php` - Updated product grid to display sale prices
  - `app/product.php` - Updated product detail page to display sale prices

### PWA Fixes
- Fixed CSS/JS path issues in footer.php (added leading slash to all asset paths for PWA compatibility)
- Added custom-cta.js animation fallback for IntersectionObserver
- Service worker cache version bumped to v23
- Fixed manifest.json - removed problematic `id` field, shortened short_name
- Added account.css to service worker cache

### UI Improvements
- Reduced section padding from 4rem to 2rem for featured-products, gift-guide, custom-cta, testimonials
- Removed margin from custom-cta section
- Fixed duplicate/wrong comments in index.php

### Bug Fixes (from app/memory)
- Fixed hero.js swiper not changing slides - undefined variable (`currentSlideIndex` → `heroSlideIndex`)
- Fixed account API database connection paths - wrong require path (`../../config/database.php` → `../../app/config/database.php`)
- Fixed PWA product images not displaying - added absolute path fix for images
- Fixed PWA filtering/sorting requiring refresh - properly handle URL parameters
- Fixed product images not displaying in PWA - added absolute path fix
- Fixed product slug page thumbnail click not changing main image
- Fixed featured products not updating when featured in admin

### API Path Corrections
- Changed all API paths from `/app/api/` to `/api/` for consistency
- Files affected: `products.php`, `product-details.js`, `featured-products.js`, `account.js`, `new-nav.js`, `header.js`

---

## API Endpoints

### Account API (`/api/account/`)
- `get_orders.php` - Fetch customer orders
- `get_addresses.php` - Fetch saved addresses
- `save_address.php` - Save new/edit address
- `delete_address.php` - Delete address
- `update_profile.php` - Update customer profile
- `change_password.php` - Change password
- `delete_account.php` - Delete customer account

### Product API (`/api/`)
- `filtered-products.php` - Filtered product listings
- `featured-products.php` - Featured products
- `wishlist.php` - Wishlist management
- `search.php` - Product search
- `new-products.php` - New arrivals

---

## Database Schema Notes

### Key Tables
- `products` - Main product catalog
- `product_images` - Product images (linked via product_id)
- `customers` - Customer accounts
- `orders` - Order history
- `order_items` - Individual items per order
- `addresses` - Customer addresses
- `wishlist` - Customer wishlist items

### Important Fields
- Products have `is_featured`, `is_active`, `stock_quantity`, `price`, `weight` fields
- Products have `is_on_sale`, `sale_price`, `sale_percentage`, `sale_end_date` fields for sale pricing
- Images use `is_primary` flag to identify primary image
- Customers have `created_at` for membership duration tracking

---

## Development Notes

### Cache Busting
- Service worker cache versions increment with each significant change
- Current: v23 (last updated for path fixes)

### Mobile Detection
- Uses `window.innerWidth <= 1024` for mobile detection
- Mobile viewport forced via JavaScript if cached desktop view detected

### Currency
- Prices displayed in GBP (£)
- Currency conversion available via `currency.js`
