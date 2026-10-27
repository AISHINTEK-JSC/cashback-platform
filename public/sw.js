/**
 * Service worker của PWA.
 * - Trang HTML luôn lấy từ mạng (có số dư, CSRF token riêng từng người), mất mạng thì hiện /offline.html.
 * - File tĩnh có hash (/build, /fonts) và icon được cache để mở app nhanh hơn.
 * - Bỏ qua trang quản trị, Livewire, đăng nhập mạng xã hội, link chuyển hướng và mọi request không phải GET.
 * Đổi VERSION khi sửa file này hoặc offline.html để xoá cache cũ.
 */
const VERSION = 'v2';
const CACHE = `vht-${VERSION}`;
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/favicon.svg', '/icon-192.png', '/icon-512.png'];
const BYPASS = [/^\/super\/admin/, /^\/livewire/, /^\/auth\//, /^\/go\//, /^\/r\//, /^\/dang-xuat/];
const STATIC = [/^\/build\//, /^\/fonts\//, /^\/favicon/, /^\/icon-/, /^\/apple-touch-icon/];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('vht-') && key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin || BYPASS.some((pattern) => pattern.test(url.pathname))) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));

        return;
    }

    if (STATIC.some((pattern) => pattern.test(url.pathname))) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches.open(CACHE).then((cache) => cache.put(request, copy));
                        }

                        return response;
                    }),
            ),
        );
    }
});
