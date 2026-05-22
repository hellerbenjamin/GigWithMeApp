<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Shared "coming soon" stub for sidebar destinations whose features aren't
// built yet. Pass an icon (matching the nav item), a title, and copy — or use
// the default slot for richer content.
defineProps({
    icon: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, default: '' },
});

const page = usePage();
const bandName = computed(() => page.props.activeBand?.name ?? null);
</script>

<template>
    <div class="mx-auto max-w-lg">
        <div
            class="relative flex flex-col items-center overflow-hidden rounded-2xl border border-surface bg-white px-6 py-14 text-center shadow-sm dark:border-white/10 dark:bg-riser"
        >
            <!-- Soft stage glow behind the icon. -->
            <div
                class="pointer-events-none absolute -top-16 left-1/2 size-48 -translate-x-1/2 rounded-full bg-amp-violet/10 blur-3xl"
            />

            <p
                class="relative text-xs font-semibold uppercase tracking-wider text-amp-violet dark:text-primary-300"
            >
                <span v-if="bandName">{{ bandName }} · </span>Coming soon
            </p>

            <span
                class="relative mt-4 grid size-14 place-items-center rounded-2xl bg-amp-violet/15 text-amp-violet ring-1 ring-amp-violet/20 dark:text-primary-300"
            >
                <i :class="icon" class="text-xl" />
            </span>

            <h2 class="relative mt-5 font-display text-2xl font-bold tracking-tight">{{ title }}</h2>
            <p class="relative mt-2 max-w-sm text-sm text-ink/60 dark:text-canvas/55">
                <slot>{{ description }}</slot>
            </p>
        </div>
    </div>
</template>
