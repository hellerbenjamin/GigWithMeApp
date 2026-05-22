<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    href: { type: String, required: true },
    icon: { type: String, required: true }, // primeicon class, e.g. 'pi pi-home'
    label: { type: String, required: true },
});

const page = usePage();

// Active when the current path is the link or sits beneath it (so /gigs/3
// keeps the "Gigs" item lit). The root path only matches exactly.
const isActive = computed(() => {
    const current = page.url.split('?')[0];
    if (props.href === '/') return current === '/';
    return current === props.href || current.startsWith(props.href + '/');
});
</script>

<template>
    <Link
        :href="href"
        class="group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
        :class="
            isActive
                ? 'bg-white/10 text-white'
                : 'text-canvas/65 hover:bg-white/5 hover:text-white'
        "
    >
        <!-- Soundcheck-teal accent bar marks the active destination — teal reads
             "live / you are here" against the indigo without competing with the
             coral CTAs. -->
        <span
            class="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-soundcheck-teal transition-opacity"
            :class="isActive ? 'opacity-100' : 'opacity-0'"
        />
        <i :class="icon" class="text-base shrink-0" />
        <span>{{ label }}</span>
    </Link>
</template>
