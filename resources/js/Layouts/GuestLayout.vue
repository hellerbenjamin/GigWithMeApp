<script setup>
import GigWithMeLogo from '../components/GigWithMeLogo.vue';
import { useDarkMode } from '../composables/useDarkMode';

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});

// Lighten the cable when the page background is dark.
const { isDark } = useDarkMode();
</script>

<template>
    <div class="grid min-h-full place-items-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 flex flex-col items-center text-center">
                <GigWithMeLogo
                    class="h-9 text-stage-indigo dark:text-canvas"
                    :variant="isDark ? 'dark' : 'light'"
                />
                <h1 v-if="title" class="mt-6 font-display text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="mt-1 text-sm text-muted dark:text-canvas/60">
                    {{ subtitle }}
                </p>
            </div>

            <div
                class="rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
