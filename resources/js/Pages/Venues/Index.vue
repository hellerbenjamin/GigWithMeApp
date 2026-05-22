<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Venues' }, () => page),
};
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    venues: { type: Array, default: () => [] },
});

// "Portland, OR · United States" — skip the parts a venue doesn't have.
function location(venue) {
    return [venue.city, venue.state, venue.country].filter(Boolean).join(', ');
}
</script>

<template>
    <Head title="Venues" />

    <!-- Header row -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="font-display text-3xl font-bold tracking-tight">Venues</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Your little black book of rooms — save a venue once and reuse it every time
                you book.
            </p>
        </div>

        <Link
            v-if="venues.length"
            href="/venues/create"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
        >
            <i class="pi pi-plus text-xs" />
            New venue
        </Link>
    </div>

    <!-- Venue list -->
    <ul
        v-if="venues.length"
        class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
    >
        <li
            v-for="venue in venues"
            :key="venue.id"
            class="rounded-2xl border border-surface bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-white/10 dark:bg-riser"
        >
            <span class="grid size-10 place-items-center rounded-xl bg-encore-coral/15 text-encore-coral">
                <i class="pi pi-map-marker" />
            </span>
            <p class="mt-4 font-display text-lg font-semibold tracking-tight">{{ venue.name }}</p>
            <p v-if="location(venue)" class="text-sm text-ink/55 dark:text-canvas/50">
                {{ location(venue) }}
            </p>
            <p
                v-if="venue.contact_person || venue.contact_phone"
                class="mt-3 truncate text-sm text-ink/55 dark:text-canvas/50"
            >
                <i class="pi pi-user text-[10px]" />
                {{ [venue.contact_person, venue.contact_phone].filter(Boolean).join(' · ') }}
            </p>
        </li>
    </ul>

    <!-- Empty state -->
    <div
        v-else
        class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-surface bg-white px-6 py-14 text-center shadow-sm dark:border-white/10 dark:bg-riser"
    >
        <span class="grid size-12 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
            <i class="pi pi-map-marker text-lg" />
        </span>
        <p class="mt-3 text-sm font-medium">No venues yet</p>
        <p class="text-sm text-ink/50 dark:text-canvas/45">Add your first room to start your venue book.</p>
        <Link
            href="/venues/create"
            class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
        >
            <i class="pi pi-plus text-xs" />
            Add a venue
        </Link>
    </div>
</template>
