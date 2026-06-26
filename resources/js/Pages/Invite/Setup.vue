<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

export default {
    layout: (_h, page) =>
        h(GuestLayout, { title: 'Set up alerts' }, () => page),
};
</script>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePushNotifications } from '../../composables/usePushNotifications';

const props = defineProps({
    pushToken: { type: String, required: true },
});

const page = usePage();
const vapidKey = computed(() => page.props.webpushKey ?? null);

const { supported, subscribed, busy, denied, toggle } = usePushNotifications({
    vapidKey: vapidKey.value,
    pushToken: props.pushToken,
});

// Resolved on mount — avoids SSR window access.
const envState = ref('loading'); // 'loading' | 'in-app-ios' | 'in-app-android' | 'ios-browser' | 'ready'
const isIos = ref(false);
const installPrompt = ref(null);
const isInstalled = ref(false);
const installing = ref(false);
const copyDone = ref(false);

const currentUrl = typeof window !== 'undefined' ? window.location.href : '';

async function install() {
    if (!installPrompt.value) return;
    installing.value = true;
    try {
        installPrompt.value.prompt();
        const { outcome } = await installPrompt.value.userChoice;
        if (outcome === 'accepted') {
            isInstalled.value = true;
            installPrompt.value = null;
        }
    } finally {
        installing.value = false;
    }
}

async function copyLink() {
    await navigator.clipboard.writeText(currentUrl);
    copyDone.value = true;
    setTimeout(() => { copyDone.value = false; }, 2000);
}

onMounted(() => {
    const ua = navigator.userAgent;
    const ios = /iPad|iPhone|iPod/.test(ua) || (ua.includes('Mac') && navigator.maxTouchPoints > 1);
    isIos.value = ios;

    const isStandalone =
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    if (isStandalone) isInstalled.value = true;

    // iOS WebView: Safari always defines navigator.standalone; WebViews don't.
    const isIosWebView = ios && typeof window.navigator.standalone === 'undefined';
    // Android WebView: most add "wv)" to the UA.
    const isAndroidWebView = /Android/.test(ua) && /; wv\)/.test(ua);
    // Social/email in-app browsers that embed their own renderer.
    const isKnownInApp = /FBAN|FBAV|Instagram|Twitter\/|Line\/|Snapchat/.test(ua);

    if (isIosWebView || (isKnownInApp && ios)) {
        envState.value = 'in-app-ios';
    } else if (isAndroidWebView || (isKnownInApp && !ios)) {
        envState.value = 'in-app-android';
    } else if (ios && !isStandalone) {
        envState.value = 'ios-browser';
    } else {
        envState.value = 'ready';
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        installPrompt.value = e;
    });
    window.addEventListener('appinstalled', () => {
        isInstalled.value = true;
        installPrompt.value = null;
    });
});
</script>

<template>
    <Head title="Set up alerts · GigWithMe" />

    <!-- ── iOS in-app browser (Gmail, social apps, etc.) ── -->
    <template v-if="envState === 'in-app-ios'">
        <div class="space-y-1.5">
            <h2 class="text-lg font-semibold">Open in Safari to continue</h2>
            <p class="text-sm text-ink/60 dark:text-canvas/55">
                This link opened inside another app. Push notifications need Safari,
                so tap the share button and choose <strong>Open in Safari</strong>.
            </p>
        </div>
        <div class="mt-6 space-y-3">
            <div class="rounded-xl bg-surface/60 p-4 dark:bg-white/5">
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink/40 dark:text-canvas/35">
                    Or copy this link and paste it into Safari
                </p>
                <p class="break-all text-sm text-ink/70 dark:text-canvas/65">{{ currentUrl }}</p>
                <Button
                    class="mt-3"
                    :label="copyDone ? 'Copied!' : 'Copy link'"
                    size="small"
                    severity="secondary"
                    outlined
                    @click="copyLink"
                />
            </div>
            <a
                href="/login"
                class="block text-center text-sm text-ink/40 hover:text-ink/60 dark:text-canvas/35 dark:hover:text-canvas/55"
            >
                Skip for now
            </a>
        </div>
    </template>

    <!-- ── Android in-app browser ── -->
    <template v-else-if="envState === 'in-app-android'">
        <div class="space-y-1.5">
            <h2 class="text-lg font-semibold">Open in Chrome to continue</h2>
            <p class="text-sm text-ink/60 dark:text-canvas/55">
                This link opened inside another app. Tap <strong>&#8942;</strong> and choose
                <strong>Open in Chrome</strong> (or your default browser) to set up
                push notifications.
            </p>
        </div>
        <div class="mt-6 space-y-3">
            <div class="rounded-xl bg-surface/60 p-4 dark:bg-white/5">
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink/40 dark:text-canvas/35">
                    Or copy this link and paste it into Chrome
                </p>
                <p class="break-all text-sm text-ink/70 dark:text-canvas/65">{{ currentUrl }}</p>
                <Button
                    class="mt-3"
                    :label="copyDone ? 'Copied!' : 'Copy link'"
                    size="small"
                    severity="secondary"
                    outlined
                    @click="copyLink"
                />
            </div>
            <a
                href="/login"
                class="block text-center text-sm text-ink/40 hover:text-ink/60 dark:text-canvas/35 dark:hover:text-canvas/55"
            >
                Skip for now
            </a>
        </div>
    </template>

    <!-- ── iOS Safari, not installed: Add to Home Screen first ── -->
    <template v-else-if="envState === 'ios-browser'">
        <div class="space-y-1.5">
            <h2 class="text-lg font-semibold">Add GigWithMe to your Home Screen</h2>
            <p class="text-sm text-ink/60 dark:text-canvas/55">
                Push notifications on iPhone require the app to be installed.
                It only takes a few seconds.
            </p>
        </div>
        <div class="mt-6 space-y-3">
            <div class="rounded-xl bg-surface/60 p-4 dark:bg-white/5">
                <ol class="space-y-2 text-sm text-ink/75 dark:text-canvas/70">
                    <li>
                        <span class="font-medium">1.</span>
                        Tap the <i class="pi pi-upload mx-0.5" /> Share button at the bottom of Safari.
                    </li>
                    <li>
                        <span class="font-medium">2.</span>
                        Choose <strong>Add to Home Screen</strong>.
                    </li>
                    <li>
                        <span class="font-medium">3.</span>
                        Open GigWithMe from your Home Screen and you're all set.
                    </li>
                </ol>
            </div>
            <a
                href="/login"
                class="block text-center text-sm text-ink/40 hover:text-ink/60 dark:text-canvas/35 dark:hover:text-canvas/55"
            >
                Skip for now, take me to sign in
            </a>
        </div>
    </template>

    <!-- ── Ready: real browser, not iOS-gated ── -->
    <template v-else-if="envState === 'ready'">
        <div class="space-y-1.5">
            <h2 class="text-lg font-semibold">You're in.</h2>
            <p class="text-sm text-ink/60 dark:text-canvas/55">
                Turn on push alerts so you never miss a gig poll or confirmation.
            </p>
        </div>

        <div class="mt-6 space-y-3">
            <!-- Android install prompt (shown when browser offers it) -->
            <div
                v-if="installPrompt && !isInstalled"
                class="flex items-center gap-4 rounded-xl bg-amp-violet/10 p-4 dark:bg-amp-violet/15"
            >
                <div class="grid size-10 shrink-0 place-items-center rounded-full bg-amp-violet/20 text-amp-violet">
                    <i class="pi pi-home text-lg" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">Install GigWithMe</p>
                    <p class="mt-0.5 text-sm text-ink/55 dark:text-canvas/50">
                        Add it to your Home Screen for the full app experience.
                    </p>
                </div>
                <Button
                    label="Install"
                    size="small"
                    :loading="installing"
                    @click="install"
                />
            </div>

            <!-- Push notifications are blocked -->
            <div
                v-if="denied && !subscribed"
                class="rounded-xl bg-surface/60 p-4 text-sm dark:bg-white/5"
            >
                <p class="font-medium">Notifications are blocked</p>
                <p class="mt-1 text-ink/60 dark:text-canvas/55">
                    Re-enable notifications for this site in your browser settings to get gig alerts.
                </p>
            </div>

            <!-- Push toggle -->
            <div
                v-else-if="supported"
                class="flex items-center gap-4 rounded-xl bg-surface/60 p-4 dark:bg-white/5"
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
                                ? "You'll get a tap on this device for new gigs and confirmations."
                                : "Get instant alerts on this device for polls and confirmations."
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

            <a
                href="/login"
                class="block text-center text-sm text-ink/40 hover:text-ink/60 dark:text-canvas/35 dark:hover:text-canvas/55"
            >
                {{ subscribed ? 'Continue to sign in' : 'Skip for now, take me to sign in' }}
            </a>
        </div>
    </template>
</template>
