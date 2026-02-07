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

## Recent Changes
- Added mobile PWA support with responsive admin layout
- Added order-details.php to admin PWA cache
- Mobile meta tags for iOS/Android PWA compatibility
