/**
 * Jambo push service worker.
 *
 * Minimal responsibilities:
 *   - Receive push events, render the notification
 *   - Forward clicks to the URL the server passed in data.url
 *
 * Registered from layouts/master.blade.php when the user opts in to
 * push notifications via the profile hub.
 */

self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {
    let payload = {
        title: 'Jambo',
        body: 'You have a new notification.',
        icon: '/favicon.ico',
        data: {},
    };

    if (event.data) {
        try {
            payload = Object.assign(payload, event.data.json());
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: payload.icon || '/favicon.ico',
        badge: payload.badge || '/favicon.ico',
        data: payload.data || {},
        tag: payload.tag || undefined,
        requireInteraction: false,
    };

    event.waitUntil(self.registration.showNotification(payload.title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clients) {
            for (const client of clients) {
                if ('focus' in client && client.url.includes(url)) {
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
        })
    );
});
