<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePushNotifications } from '../composables/usePushNotifications';

const props = defineProps({
    // A member's durable push token for the login-free endpoints. Omit (null)
    // on logged-in surfaces to use the session-user endpoints.
    pushToken: { type: String, default: null },
    // Compact variant for tucking under an RSVP reply.
    compact: { type: Boolean, default: false },
});

const page = usePage();
const vapidKey = computed(() => page.props.webpushKey ?? null);

const { supported, subscribed, busy, denied, toggle } = usePushNotifications({
    vapidKey: vapidKey.value,
    pushToken: props.pushToken,
});

// iOS only delivers push to an installed PWA. Detect Safari-on-iOS that isn't
// running standalone, so we can nudge "Add to Home Screen" instead of a toggle
// that can't work yet.
const isIosBrowser = computed(() => {
    if (typeof navigator === 'undefined') return false;
    const ua = navigator.userAgent;
    const iOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const standalone = window.navigator.standalone === true ||
        window.matchMedia('(display-mode: standalone)').matches;
    return iOS && !standalone;
});
</script>

<template>
    <!-- iOS Safari (not installed): push needs Add to Home Screen first. -->
    <div
        v-if="isIosBrowser"
        class="rounded-xl bg-surface/60 p-4 text-sm dark:bg-white/5"
    >
        <p class="font-medium">Get gig alerts on your phone</p>
        <p class="mt-1 text-ink/60 dark:text-canvas/55">
            Tap the <i class="pi pi-upload mx-0.5" /> Share button below, then
            <strong>Add to Home Screen</strong>. Open GigWithMe from your home screen
            and you can turn on alerts.
        </p>
    </div>

    <!-- Unsupported browser, or VAPID not configured: say nothing loud. -->
    <div
        v-else-if="!supported"
        v-show="false"
    />

    <!-- Permission was blocked at the browser level. -->
    <div
        v-else-if="denied && !subscribed"
        class="rounded-xl bg-surface/60 p-4 text-sm dark:bg-white/5"
    >
        <p class="font-medium">Notifications are blocked</p>
        <p class="mt-1 text-ink/60 dark:text-canvas/55">
            Re-enable notifications for this site in your browser settings to get
            gig alerts.
        </p>
    </div>

    <!-- The toggle. -->
    <div
        v-else
        class="flex items-center gap-4 rounded-xl bg-surface/60 p-4 dark:bg-white/5"
        :class="compact ? '' : 'sm:p-5'"
    >
        <div class="grid size-10 shrink-0 place-items-center rounded-full bg-amp-violet/10 text-amp-violet dark:bg-amp-violet/15">
            <i class="pi pi-bell text-lg" />
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium">
                {{ subscribed ? 'Push alerts are on' : 'Get push alerts for gigs' }}
            </p>
            <p class="mt-0.5 text-sm text-ink/55 dark:text-canvas/50">
                {{
                    subscribed
                        ? 'You\'ll get a tap on this device for new gigs and confirmations.'
                        : 'Skip the texts — get instant alerts on this device instead.'
                }}
            </p>
        </div>
        <Button
            :label="subscribed ? 'Turn off' : 'Turn on'"
            :severity="subscribed ? 'secondary' : undefined"
            :outlined="subscribed"
            size="small"
            :loading="busy"
            @click="toggle"
        />
    </div>
</template>
