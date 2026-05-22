<script setup>
import { Link } from '@inertiajs/vue3';
import RoadieLogo from './RoadieLogo.vue';
import BandSwitcher from './BandSwitcher.vue';
import NavLink from './NavLink.vue';

defineProps({
    activeBand: { type: Object, default: null },
    bands: { type: Array, default: () => [] },
    open: { type: Boolean, default: false }, // mobile drawer state
});

const emit = defineEmits(['close']);

// Primary destinations. Hrefs point at their intended URLs; only /dashboard is
// wired server-side so far — the rest land once their controllers exist.
const primaryNav = [
    { href: '/dashboard', icon: 'pi pi-home', label: 'Dashboard' },
    { href: '/gigs', icon: 'pi pi-calendar', label: 'Gigs' },
    { href: '/venues', icon: 'pi pi-map-marker', label: 'Venues' },
    { href: '/band-members', icon: 'pi pi-users', label: 'Band Members' },
    { href: '/music', icon: 'pi pi-volume-up', label: 'Music' },
];
</script>

<template>
    <!-- Backdrop, mobile only -->
    <div
        v-show="open"
        class="fixed inset-0 z-30 bg-ink/40 backdrop-blur-sm lg:hidden"
        @click="emit('close')"
    />

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-stage-indigo transition-transform duration-300 ease-out lg:translate-x-0"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Brand -->
        <div class="flex items-center justify-between px-5 pb-2 pt-6">
            <Link href="/dashboard" class="inline-flex" @click="emit('close')">
                <RoadieLogo class="text-canvas" style="height: 3.25rem" variant="dark" />
            </Link>
            <button
                type="button"
                class="grid size-8 place-items-center rounded-lg text-canvas/60 transition-colors hover:bg-white/10 hover:text-white lg:hidden"
                aria-label="Close menu"
                @click="emit('close')"
            >
                <i class="pi pi-times text-sm" />
            </button>
        </div>

        <!-- Active band -->
        <div class="px-4 pb-4 pt-2">
            <BandSwitcher :active-band="activeBand" :bands="bands" />
        </div>

        <!-- Navigation -->
        <nav class="flex-1 space-y-1 overflow-y-auto px-4 pb-4" @click="emit('close')">
            <NavLink
                v-for="item in primaryNav"
                :key="item.href"
                :href="item.href"
                :icon="item.icon"
                :label="item.label"
            />
        </nav>

        <!-- Footer -->
        <div class="border-t border-white/10 px-4 py-4" @click="emit('close')">
            <NavLink href="/settings" icon="pi pi-cog" label="Settings" />
        </div>
    </aside>
</template>
