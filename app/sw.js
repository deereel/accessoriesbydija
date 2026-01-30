const CACHE_NAME = 'accessories-by-dija-v3';
const urlsToCache = [
  '/app/',
  '/app/index.php',
  '/assets/css/all.min.css',
  '/assets/css/header.css',
  '/assets/css/hero.css',
  '/assets/css/footer.css',
  '/assets/js/main.js',
  '/assets/js/cart-handler.js',
  '/assets/js/header.js',
  '/assets/js/hero.js',
  '/assets/js/category-section.js',
  '/assets/js/collection-banners.js',
  '/assets/js/currency.js',
  '/assets/js/featured-products.js',
  '/assets/js/custom-cta.js',
  '/assets/js/testimonials.js',
  '/assets/images/logo.webp',
  '/assets/images/android-chrome-192x192.png',
  '/assets/images/android-chrome-512x512.png',
  '/assets/images/apple-touch-icon.png',
  '/favicon.ico',
  '/app/manifest.json'
];

// Install event - cache resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        self.skipWaiting();
      })
  );
});

// Fetch event - serve from cache if available, otherwise fetch from network
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        return response || fetch(event.request);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});