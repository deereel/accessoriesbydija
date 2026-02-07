# ADR-0001: PWA Support Implementation

## Status
Accepted

## Context
The project needed offline support and installable web app capabilities for both the storefront and admin panel.

## Decision
Implement PWA support using Service Workers with cache-first strategy.

## Consequences
### Pros
- Offline access to cached pages
- Installable on mobile devices
- Improved perceived performance
- Better mobile experience with standalone display

### Cons
- Additional complexity in cache management
- Need to update cache when content changes

## Implementation
- Main storefront PWA: `/app/sw.js` with manifest at `/app/manifest.json`
- Admin PWA: `/admin/admin-sw.js` with manifest at `/admin/manifest.json`
- Mobile-responsive layout with hamburger menu for admin
- Viewport meta tags with `viewport-fit=cover` for iOS

## Notes
- Admin PWA cache updated to include order-details.php
- Mobile meta tags added for both storefront and admin
- Admin sidebar transforms to slide-out drawer on mobile
