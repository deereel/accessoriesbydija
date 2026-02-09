const CACHE_NAME = 'dija-admin-v6';
const urlsToCache = [
  '/admin/',
  '/admin/index.php',
  '/admin/products.php',
  '/admin/order-detail.php',
  '/admin/orders.php',
  '/admin/customers.php',
  '/admin/reports.php',
  '/admin/inventory.php',
  '/admin/shipping.php',
  '/admin/categories.php',
  '/admin/banners.php',
  '/admin/testimonials.php',
  '/admin/settings.php',
  '/admin/support-tickets.php',
  '/admin/users.php',
  '/admin/database.php',
  '/admin/logs.php',
  '/admin/promos.php',
  '/admin/custom-orders.php',
  '/admin/run-migrations.php',
  '/assets/css/all.min.css',
  '/assets/css/style.css',
  '/assets/css/header.css',
  '/assets/images/admin-icon-192.png',
  '/assets/images/admin-icon-512.png',
  '/assets/images/apple-touch-icon.png',
  '/assets/images/placeholder.jpg',
  '/favicon.ico',
  '/admin/manifest.json'
];

// Install event - cache resources
self.addEventListener('install', event => {
  console.log('Admin Service Worker v6 installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
      .then(() => self.skipWaiting())
  );
});

// Fetch event - use network-first for navigation/API and CSS; cache-first for other resources
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Network-first for product images with better error handling
  if (event.request.url.includes('/uploads/products/') || 
      event.request.url.includes('/uploads/') ||
      event.request.url.includes('/assets/images/products/') ||
      event.request.url.includes('/assets/images/') ||
      event.request.destination === 'image') {
    event.respondWith(
      fetch(event.request)
        .then(networkResponse => {
          // Only cache successful responses
          if (networkResponse.status === 200) {
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, networkResponse.clone());
            });
          }
          return networkResponse;
        })
        .catch(() => {
          // Fallback to cache, then to placeholder
          return caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // Return placeholder for missing images
            return fetch('/assets/images/placeholder.jpg').catch(() => {
              return new Response('', { status: 404 });
            });
          });
        })
    );
    return;
  }

  // Network-first for API requests - never cache these
  if (event.request.url.includes('/api/') || event.request.url.includes('/admin/api_')) {
    event.respondWith(
      fetch(event.request)
        .then(networkResponse => {
          // Update cache with fresh page/response
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, networkResponse.clone()));
          return networkResponse;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // Network-first for stylesheets
  if (event.request.destination === 'style' || event.request.url.endsWith('.css')) {
    event.respondWith(
      fetch(event.request)
        .then(networkResponse => {
          // Update cache with fresh CSS
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, networkResponse.clone()));
          return networkResponse;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // For navigations (page loads) and explicit auth actions (logout/login query params), prefer network so server-side state changes are applied
  if (event.request.mode === 'navigate' || url.searchParams.has('logout') || url.searchParams.has('login')) {
    event.respondWith(
      fetch(event.request)
        .then(networkResponse => {
          // Avoid caching logout responses
          if (!url.searchParams.has('logout')) {
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, networkResponse.clone()));
          }
          return networkResponse;
        })
        .catch(() => caches.match(event.request).then(cacheResp => cacheResp || caches.match('/admin/index.php')))
    );
    return;
  }

  // Default: cache-first, fall back to network
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker v6 activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      console.log('Existing admin caches:', cacheNames);
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old admin cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('Service Worker v6 activated and claiming clients');
      return self.clients.claim();
    })
  );
});
