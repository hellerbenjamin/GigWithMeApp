/**
 * GigWithMe service worker — web push delivery + notification actions.
 *
 * The push payload is JSON shaped server-side by the Gig* notifications'
 * toWebPush() (the laravel-notification-channels/webpush envelope):
 *   { title, body, icon, badge, tag, renotify, actions: [...],
 *     data: { url, replyToken? } }
 * When the poll notification carries "I'm in" / "Can't make it" actions, the
 * reply is recorded straight from the notification via the token-authorized
 * /rsvp/{token}/reply endpoint — no app open, no login.
 */

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (e) {
        payload = { title: 'GigWithMe', body: event.data ? event.data.text() : '' };
    }

    const title = payload.title || 'GigWithMe';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/icons/icon-192.png',
        badge: payload.badge || '/icons/badge-96.png',
        // Coalesce repeat notifications about the same gig.
        tag: payload.tag,
        renotify: !!payload.renotify,
        actions: payload.actions || [],
        data: payload.data || {},
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    const { url, replyToken } = event.notification.data || {};
    event.notification.close();

    // Quick RSVP from the notification action — record it in the background and
    // skip opening the app.
    if (replyToken && (event.action === 'rsvp-yes' || event.action === 'rsvp-no')) {
        const available = event.action === 'rsvp-yes';
        event.waitUntil(
            fetch(`/rsvp/${replyToken}/reply`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ available }),
                credentials: 'same-origin',
            }).catch(() => {
                // Network failed — fall back to opening the RSVP page so the
                // member can still reply.
                return self.clients.openWindow(`/rsvp/${replyToken}`);
            }),
        );
        return;
    }

    // Plain click (or body tap): focus an existing tab on this URL or open one.
    const target = url || '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if (client.url.includes(target) && 'focus' in client) {
                    return client.focus();
                }
            }
            return self.clients.openWindow(target);
        }),
    );
});
