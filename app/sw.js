const CACHE_NAME = 'accessories-by-dija-v23';

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
  '/assets/css/account.css',

  // Images & icons
  '/assets/images/logo.webp',
  '/assets/images/android-chrome-192x192.png',
  '/assets/images/android-chrome-512x512.png',
  '/assets/images/apple-touch-icon.png',
  '/favicon.ico',

  // Manifest
  '/app/manifest.json',

  // PWA Install Script
  '/app/includes/pwa-install.js'
];

/* =========================
   INSTALL
========================= */
self.addEventListener('install', event => {
  console.log('Service Worker v19 installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
      .then(() => self.skipWaiting())
  );
});

/* ============================
   FETCH - Advanced Strategies
   ============================= */

// Pages that should NEVER be cached (authenticated/dynamic)
const noCachePatterns = [
    /\/account\.php/,
    /\/login\.php/,
    /\/signup\.php/,
    /\/checkout\.php/,
    /\/cart\.php/,
    /\/order-confirmation\.php/,
    /\/auth\//,
    /\/api\//
];

function shouldNotCache(url) {
    return noCachePatterns.some(pattern => pattern.test(url));
}

self.addEventListener('fetch', event => {
  const url = event.request.url;

  // Skip if should not be cached (authenticated pages)
  if (shouldNotCache(url)) {
    event.respondWith(
      fetch(event.request).catch(() =>
        new Response('', { status: 503, statusText: 'Service Unavailable' })
      )
    );
    return;
  }

  /* ---------- IMAGES (network-first, cached) ---------- */
  if (
    event.request.destination === 'image' ||
    url.includes('/uploads/') ||
    url.includes('/assets/images/')
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
  if (url.includes('/api/')) {
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
  if (url.includes('products.php')) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
    return;
  }

  /* ---------- JAVASCRIPT (network-first – CRITICAL) ---------- */
  if (
    event.request.destination === 'script' ||
    url.endsWith('.js')
  ) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
    return;
  }

  /* ---------- CSS (network-first, always update) ---------- */
  if (
    event.request.destination === 'style' ||
    url.endsWith('.css')
  ) {
    event.respondWith(
      fetch(event.request)
        .then(networkResponse => {
          // Always cache/update CSS from network
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
  console.log('Service Worker v19 activating...');
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
