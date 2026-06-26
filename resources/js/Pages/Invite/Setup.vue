<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

export default {
    layout: (_h, page) =>
        h(GuestLayout, { title: 'Set up alerts' }, () => page),
};
</script>

<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePushNotifications } from '../../composables/usePushNotifications';

const props = defineProps({
    pushToken: { type: String, required: true },
});

const vapidKey = usePage().props.webpushKey ?? null;

const { supported, subscribed, busy, denied, toggle } = usePushNotifications({
    vapidKey,
    pushToken: props.pushToken,
});

const isIos = ref(false);
const isInstalled = ref(false);
const isMobile = ref(false);
const isFirefoxAndroid = ref(false);
const installPrompt = ref(null);
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
            window.__pwaInstallPrompt = null;
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

    isIos.value = /iPad|iPhone|iPod/.test(ua) ||
        (ua.includes('Mac') && navigator.maxTouchPoints > 1);

    const isAndroid = /Android/.test(ua);
    isMobile.value = isIos.value || isAndroid;

    // Firefox on Android dropped PWA install support — users need Chrome.
    isFirefoxAndroid.value = isAndroid && /Firefox\//.test(ua);

    isInstalled.value =
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    // Captured early in app.js before Vue mounts.
    installPrompt.value = window.__pwaInstallPrompt ?? null;

    window.addEventListener('appinstalled', () => {
        isInstalled.value = true;
        installPrompt.value = null;
        window.__pwaInstallPrompt = null;
    });
});
</script>

<template>
    <Head title="Set up alerts · GigWithMe" />

    <!-- ─── Installed PWA: just the push toggle ─── -->
    <template v-if="isInstalled">
        <div class="space-y-1.5">
            <h2 class="text-lg font-semibold">You're in.</h2>
            <p class="text-sm text-ink/60 dark:text-canvas/55">
                Turn on push alerts so you never miss a gig poll or confirmation.
            </p>
        </div>

        <div class="mt-6 space-y-3">
            <div v-if="denied && !subscribed" class="rounded-xl bg-surface/60 p-4 text-sm dark:bg-white/5">
                <p class="font-medium">Notifications are blocked</p>
                <p class="mt-1 text-ink/60 dark:text-canvas/55">
                    Re-enable notifications for this site in your browser settings.
                </p>
            </div>

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
                                : "Get instant alerts for polls and confirmations."
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

    <!-- ─── iOS, not installed ─── -->
    <template v-else-if="isIos">
        <div class="space-y-1.5">
            <h2 class="text-lg font-semibold">Install GigWithMe to get alerts</h2>
            <p class="text-sm text-ink/60 dark:text-canvas/55">
                Push notifications on iPhone require the app to be added to your
                Home Screen. Open this page in Safari, then:
            </p>
        </div>

        <div class="mt-6 space-y-3">
            <div class="rounded-xl bg-surface/60 p-4 dark:bg-white/5">
                <ol class="space-y-3 text-sm text-ink/75 dark:text-canvas/70">
                    <li class="flex gap-3">
                        <span class="font-semibold text-amp-violet">1</span>
                        Tap the <i class="pi pi-upload mx-0.5 align-middle" /> Share button
                        at the bottom of Safari.
                    </li>
                    <li class="flex gap-3">
                        <span class="font-semibold text-amp-violet">2</span>
                        Choose <strong>Add to Home Screen</strong> and tap Add.
                    </li>
                    <li class="flex gap-3">
                        <span class="font-semibold text-amp-violet">3</span>
                        Open GigWithMe from your Home Screen. It will ask to
                        enable notifications.
                    </li>
                </ol>
            </div>

            <div class="rounded-xl bg-surface/60 p-4 dark:bg-white/5">
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink/40 dark:text-canvas/35">
                    Not in Safari? Copy this link and paste it there
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
                Skip for now, take me to sign in
            </a>
        </div>
    </template>

    <!-- ─── Firefox on Android: no PWA support, needs Chrome ─── -->
    <template v-else-if="isFirefoxAndroid">
        <div class="space-y-1.5">
            <h2 class="text-lg font-semibold">Open in Chrome to install</h2>
            <p class="text-sm text-ink/60 dark:text-canvas/55">
                Firefox doesn't support installing web apps. Copy this link and
                open it in Chrome to set up GigWithMe on your Home Screen.
            </p>
        </div>

        <div class="mt-6 space-y-3">
            <div class="rounded-xl bg-surface/60 p-4 dark:bg-white/5">
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink/40 dark:text-canvas/35">
                    Your setup link
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
                Skip for now, take me to sign in
            </a>
        </div>
    </template>

    <!-- ─── Android / desktop, not installed ─── -->
    <template v-else-if="isMobile">
        <div class="space-y-1.5">
            <h2 class="text-lg font-semibold">Install GigWithMe</h2>
            <p class="text-sm text-ink/60 dark:text-canvas/55">
                Add it to your Home Screen for push alerts and quick access.
            </p>
        </div>

        <div class="mt-6 space-y-3">
            <!-- Chrome offers a one-tap install when it's ready -->
            <Button
                v-if="installPrompt"
                label="Install GigWithMe"
                fluid
                :loading="installing"
                @click="install"
            />

            <!-- Always show manual fallback — Chrome's prompt has its own timing -->
            <div class="rounded-xl bg-surface/60 p-4 dark:bg-white/5">
                <p class="mb-3 text-sm font-medium">Install manually in Chrome</p>
                <ol class="space-y-2 text-sm text-ink/75 dark:text-canvas/70">
                    <li class="flex gap-3">
                        <span class="font-semibold text-amp-violet">1</span>
                        Tap the menu <strong>&#8942;</strong> in the top-right of Chrome.
                    </li>
                    <li class="flex gap-3">
                        <span class="font-semibold text-amp-violet">2</span>
                        Choose <strong>Add to Home screen</strong>.
                    </li>
                    <li class="flex gap-3">
                        <span class="font-semibold text-amp-violet">3</span>
                        Open GigWithMe from your Home Screen. It will ask to
                        enable notifications.
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

    <!-- ─── Desktop: skip straight to push toggle ─── -->
    <template v-else>
        <div class="space-y-1.5">
            <h2 class="text-lg font-semibold">You're in.</h2>
            <p class="text-sm text-ink/60 dark:text-canvas/55">
                For push alerts on your phone, open this link there and follow
                the install steps. On desktop you can sign in now.
            </p>
        </div>
        <div class="mt-6">
            <a href="/login" class="block">
                <Button label="Continue to sign in" fluid />
            </a>
        </div>
    </template>
</template>
