<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

export default {
    layout: (_h, page) =>
        h(GuestLayout, { title: 'You\'re invited' }, () => page),
};
</script>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    memberName: { type: String, required: true },
    bandNames: { type: Array, default: () => [] },
});

const bandLabel = computed(() => {
    const names = props.bandNames;
    if (names.length === 0) return 'your band';
    if (names.length === 1) return names[0];
    if (names.length === 2) return `${names[0]} and ${names[1]}`;
    return `${names.slice(0, -1).join(', ')}, and ${names[names.length - 1]}`;
});
</script>

<template>
    <Head title="You're invited · GigWithMe" />

    <p class="text-sm text-ink/60 dark:text-canvas/55">
        Hi {{ memberName }}, <strong>{{ bandLabel }}</strong> added you on GigWithMe.
        Download the app to accept your invitation and stay up to date on gigs.
    </p>

    <div class="mt-6 space-y-3">
        <a
            href="https://apps.apple.com/app/gigwithme/id0000000000"
            class="flex w-full items-center justify-center gap-3 rounded-xl bg-black px-5 py-3.5 text-white transition-opacity hover:opacity-90"
        >
            <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
            </svg>
            <div class="text-left">
                <div class="text-xs leading-none opacity-75">Download on the</div>
                <div class="text-base font-semibold leading-tight">App Store</div>
            </div>
        </a>

        <a
            href="https://play.google.com/store/apps/details?id=app.gigwithme.member"
            class="flex w-full items-center justify-center gap-3 rounded-xl bg-black px-5 py-3.5 text-white transition-opacity hover:opacity-90"
        >
            <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M3.18 23.76c.3.16.64.19.96.09l.09-.06 11.29-6.52-2.5-2.51zM1.5 2.27C1.19 2.6 1 3.1 1 3.76v16.5c0 .65.19 1.14.51 1.47l.08.07 9.24-9.24v-.22zM20.35 10.5l-2.68-1.55-2.83 2.84 2.84 2.84 2.7-1.56c.77-.44.77-1.16-.03-1.57zM4.14.25l9.87 9.88-2.51 2.5L3.23.32l.09-.06c.29-.1.6-.08.82-.01z"/>
            </svg>
            <div class="text-left">
                <div class="text-xs leading-none opacity-75">Get it on</div>
                <div class="text-base font-semibold leading-tight">Google Play</div>
            </div>
        </a>
    </div>

    <p class="mt-5 text-center text-xs text-ink/40 dark:text-canvas/35">
        Once you've installed the app, open it and sign in with the email address
        this invitation was sent to.
    </p>
</template>
