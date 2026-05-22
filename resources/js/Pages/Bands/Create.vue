<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

// Persistent layout with a per-page title for the topbar.
export default {
    layout: (_h, page) => h(AppLayout, { title: 'New band' }, () => page),
};
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="New band" />

    <div class="mx-auto max-w-lg">
        <div
            class="flex flex-col items-center rounded-2xl border border-surface bg-white px-6 py-12 text-center shadow-sm dark:border-white/10 dark:bg-riser"
        >
            <span
                class="grid size-12 place-items-center rounded-full bg-amp-violet/15 text-amp-violet dark:text-primary-300"
            >
                <i class="pi pi-plus text-lg" />
            </span>
            <h2 class="mt-4 font-display text-2xl font-bold tracking-tight">Start a band</h2>
            <p class="mt-2 max-w-sm text-sm text-ink/60 dark:text-canvas/55">
                Band creation is coming soon. Once it's here, you'll name your band,
                pick a genre, and Roadie will set it as your active band automatically.
            </p>
        </div>
    </div>
</template>
