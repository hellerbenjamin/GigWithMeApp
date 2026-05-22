<script setup>
import UserMenu from './UserMenu.vue';
import { useDarkMode } from '../composables/useDarkMode';

defineProps({
    title: { type: String, default: '' },
    user: { type: Object, default: null },
});

defineEmits(['toggle-sidebar']);

const { isDark, toggle } = useDarkMode();
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-surface bg-canvas/80 px-4 backdrop-blur-md dark:border-white/10 dark:bg-backstage/80 lg:px-8"
    >
        <button
            type="button"
            class="grid size-9 place-items-center rounded-lg text-muted transition-colors hover:bg-surface dark:text-canvas/60 dark:hover:bg-white/5 lg:hidden"
            aria-label="Open menu"
            @click="$emit('toggle-sidebar')"
        >
            <i class="pi pi-bars" />
        </button>

        <h1 class="flex-1 truncate font-display text-lg font-semibold tracking-tight">
            {{ title }}
        </h1>

        <button
            type="button"
            class="grid size-9 place-items-center rounded-lg text-muted transition-colors hover:bg-surface dark:text-canvas/60 dark:hover:bg-white/5"
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggle"
        >
            <i :class="isDark ? 'pi pi-sun' : 'pi pi-moon'" />
        </button>

        <UserMenu :user="user" />
    </header>
</template>
