<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

export default {
    layout: (_h, page) =>
        h(GuestLayout, { title: 'Set up alerts' }, () => page),
};
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import PushOptIn from '../../components/PushOptIn.vue';

defineProps({
    pushToken: { type: String, required: true },
});
</script>

<template>
    <Head title="Set up alerts · GigWithMe" />

    <div class="space-y-1.5">
        <h2 class="text-lg font-semibold">You're in.</h2>
        <p class="text-sm text-ink/60 dark:text-canvas/55">
            Turn on push alerts so you never miss a gig poll or confirmation.
            It takes one tap, and you can always change it later.
        </p>
    </div>

    <div class="mt-6 space-y-4">
        <PushOptIn :push-token="pushToken" />

        <a
            href="/login"
            class="block text-center text-sm text-ink/50 hover:text-ink/70 dark:text-canvas/45 dark:hover:text-canvas/65"
        >
            Skip for now, take me to sign in
        </a>
    </div>
</template>
