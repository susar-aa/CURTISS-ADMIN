/**
 * Curtiss Client Manager - Service Worker
 */

const CACHE_NAME = 'curtiss-cm-v1';
const ASSETS_TO_CACHE = [
  './assets/css/style.css',
  './assets/js/app.js',
  './assets/images/logo.png',
  './manifest.json'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Only cache static assets (css, js, fonts)
  if (event.request.method === 'GET' && 
     (event.request.url.includes('/assets/') || event.request.url.includes('manifest.json'))) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        return cachedResponse || fetch(event.request);
      })
    );
  }
});
