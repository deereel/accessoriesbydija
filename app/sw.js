const CACHE_NAME = 'accessories-by-dija-v18';

/**
 * ONLY cache static, non-dynamic assets
 * ❌ NO JavaScript logic files
 * ❌ NO product pages
 */
const urlsToCache = [
  '/app/',
  '/app/index.php',

  // CSS (safe to cache)
  '/assets/css/all.min.css',
  '/assets/css/header.css',
  '/assets/css/style.css',
  '/assets/css/hero.css',
  '/assets/css/footer.css',

  // Images & icons
  '/assets/images/logo.webp',
  '/assets/images/android-chrome-192x192.png',
  '/assets/images/android-chrome-512x512.png',
  '/assets/images/apple-touch-icon.png',
  '/favicon.ico',

  // Manifest
  '/app/manifest.json'
];

/* =========================
   INSTALL
========================= */
self.addEventListener('install', event => {
  console.log('Service Worker v18 installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
      .then(() => self.skipWaiting())
  );
});

/* =========================
   FETCH
========================= */
self.addEventListener('fetch', event => {

  /* ---------- IMAGES (network-first, cached) ---------- */
  if (
    event.request.destination === 'image' ||
    event.request.url.includes('/uploads/') ||
    event.request.url.includes('/assets/images/')
  ) {
    event.respondWith(
      fetch(event.request)
        .then(networkResponse => {
          if (networkResponse.status === 200) {
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, networkResponse.clone());
            });
          }
          return networkResponse;
        })
        .catch(() =>
          caches.match(event.request).then(
            cached => cached || fetch('/assets/images/placeholder.jpg')
          )
        )
    );
    return;
  }

  /* ---------- API (network-only) ---------- */
  if (event.request.url.includes('/api/')) {
    event.respondWith(
      fetch(event.request, { cache: 'no-store' }).catch(() =>
        new Response(JSON.stringify({
          success: false,
          message: 'Network error'
        }), {
          headers: { 'Content-Type': 'application/json' }
        })
      )
    );
    return;
  }

  /* ---------- PRODUCTS PAGE (network-first) ---------- */
  if (event.request.url.includes('products.php')) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
    return;
  }

  /* ---------- JAVASCRIPT (network-first – CRITICAL) ---------- */
  if (
    event.request.destination === 'script' ||
    event.request.url.endsWith('.js')
  ) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
    return;
  }

  /* ---------- CSS (network-first) ---------- */
  if (
    event.request.destination === 'style' ||
    event.request.url.endsWith('.css')
  ) {
    event.respondWith(
      fetch(event.request)
        .then(networkResponse => {
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, networkResponse.clone());
          });
          return networkResponse;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  /* ---------- DEFAULT (cache-first) ---------- */
  event.respondWith(
    caches.match(event.request).then(
      response => response || fetch(event.request)
    )
  );
});

/* =========================
   ACTIVATE
========================= */
self.addEventListener('activate', event => {
  console.log('Service Worker v18 activating...');
  event.waitUntil(
    caches.keys().then(cacheNames =>
      Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      )
    ).then(() => self.clients.claim())
  );
});
