import { ref, computed, onMounted } from 'vue';

/**
 * Drives a browser's web-push opt-in. Handles capability detection, the
 * permission prompt, PushManager (un)subscription, and syncing the subscription
 * to the backend.
 *
 * Two backends, one composable:
 * - pass a `pushToken` (a member's durable token) → login-free token endpoints,
 *   used on the RSVP page and member home;
 * - omit it → the session-user endpoints, used by logged-in owners/admins.
 *
 * @param {object}   opts
 * @param {string}   opts.vapidKey   VAPID public key (from the shared webpushKey prop)
 * @param {?string}  opts.pushToken  durable member token, or null for the auth endpoints
 */
export function usePushNotifications({ vapidKey, pushToken = null }) {
    const supported =
        typeof window !== 'undefined' &&
        'serviceWorker' in navigator &&
        'PushManager' in window &&
        'Notification' in window &&
        !!vapidKey;

    const permission = ref(supported ? Notification.permission : 'denied');
    const subscribed = ref(false);
    const busy = ref(false);

    const base = pushToken ? `/push/subscribe/${pushToken}` : '/push/subscribe';

    async function refresh() {
        if (!supported) return;
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        subscribed.value = !!sub;
        permission.value = Notification.permission;
    }

    async function subscribe() {
        if (!supported || busy.value) return;
        busy.value = true;
        try {
            permission.value = await Notification.requestPermission();
            if (permission.value !== 'granted') return;

            const reg = await navigator.serviceWorker.ready;
            const sub =
                (await reg.pushManager.getSubscription()) ||
                (await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidKey),
                }));

            const json = sub.toJSON();
            await send(base, 'POST', {
                endpoint: sub.endpoint,
                keys: json.keys,
                contentEncoding:
                    (PushManager.supportedContentEncodings || ['aesgcm'])[0],
            });
            subscribed.value = true;
        } finally {
            busy.value = false;
        }
    }

    async function unsubscribe() {
        if (!supported || busy.value) return;
        busy.value = true;
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                await send(base, 'DELETE', { endpoint: sub.endpoint });
                await sub.unsubscribe();
            }
            subscribed.value = false;
        } finally {
            busy.value = false;
        }
    }

    onMounted(refresh);

    return {
        supported,
        permission: computed(() => permission.value),
        subscribed: computed(() => subscribed.value),
        busy: computed(() => busy.value),
        denied: computed(() => permission.value === 'denied'),
        subscribe,
        unsubscribe,
        toggle: () => (subscribed.value ? unsubscribe() : subscribe()),
    };
}

/** POST/DELETE JSON to a same-origin route, carrying Laravel's XSRF header. */
async function send(url, method, body) {
    const res = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': readCookie('XSRF-TOKEN'),
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });
    if (!res.ok) throw new Error(`push sync failed: ${res.status}`);
}

function readCookie(name) {
    const match = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : '';
}

/** VAPID keys travel base64url; PushManager wants a Uint8Array. */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}
