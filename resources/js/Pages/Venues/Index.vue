<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Venues' }, () => page),
};
</script>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import VenueFilters from '../../components/VenueFilters.vue';
import Paginator from '../../components/Paginator.vue';

const props = defineProps({
    venues: { type: Object, required: true }, // Laravel paginator: { data, links, total, … }
    filters: { type: Object, default: () => ({}) },
    filterOptions: { type: Object, default: () => ({ countries: [], states: [] }) },
    hasVenues: { type: Boolean, default: false },
});

// Dim the grid while a filter/page request is in flight.
const loading = ref(false);

// Turn the filter set into a tidy query string and visit. Any filter change
// resets to page 1 (we simply omit `page`). Partial reload keeps it cheap.
function applyFilters(filters) {
    const query = {};
    if (filters.search) query.search = filters.search;
    if (filters.country) query.country = filters.country;
    if (filters.state) query.state = filters.state;
    if (filters.has_contact) query.has_contact = 1;
    if (filters.sort && filters.sort !== 'name') query.sort = filters.sort;

    router.get('/venues', query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['venues', 'filters', 'filterOptions'],
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
}

// The venue queued for deletion — drives the confirmation dialog.
const pendingDeletion = ref(null);
const deleting = ref(false);

// "Portland, OR · United States" — skip the parts a venue doesn't have.
function location(venue) {
    return [venue.city, venue.state, venue.country].filter(Boolean).join(', ');
}

function confirmDeletion() {
    if (!pendingDeletion.value) {
        return;
    }

    router.delete(`/venues/${pendingDeletion.value.id}`, {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            pendingDeletion.value = null;
        },
    });
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

        <div v-if="hasVenues" class="flex items-center gap-2">
            <Link
                href="/venues/import"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-surface px-4 py-2.5 text-sm font-semibold text-ink/70 transition-colors hover:bg-surface dark:border-white/15 dark:text-canvas/70 dark:hover:bg-white/5"
            >
                <i class="pi pi-file-import text-xs" />
                Import CSV
            </Link>
            <Link
                href="/venues/create"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
            >
                <i class="pi pi-plus text-xs" />
                New venue
            </Link>
        </div>
    </div>

    <!-- Search / filters / sort + results -->
    <template v-if="hasVenues">
        <VenueFilters
            class="mt-6"
            :filters="filters"
            :filter-options="filterOptions"
            @change="applyFilters"
        />

        <!-- Venue list -->
        <ul
            v-if="venues.data.length"
            class="mt-6 grid gap-4 transition-opacity sm:grid-cols-2 lg:grid-cols-3"
            :class="{ 'pointer-events-none opacity-60': loading }"
        >
            <li
                v-for="venue in venues.data"
                :key="venue.id"
                class="group relative rounded-2xl border border-surface bg-white p-5 shadow-sm transition-shadow hover:shadow-md focus-within:ring-2 focus-within:ring-amp-violet/50 dark:border-white/10 dark:bg-riser"
            >
                <!-- Stretched link: the whole card opens the editor. Sits above
                     static content to capture clicks; the delete button below
                     layers over it with z-10. -->
                <Link
                    :href="`/venues/${venue.id}/edit`"
                    :aria-label="`Edit ${venue.name}`"
                    class="absolute inset-0 rounded-2xl focus:outline-none"
                />

                <button
                    type="button"
                    :aria-label="`Delete ${venue.name}`"
                    class="absolute right-4 top-4 z-10 grid size-8 place-items-center rounded-lg text-ink/50 transition-colors hover:bg-cancelled/10 hover:text-cancelled dark:text-canvas/50"
                    @click.stop="pendingDeletion = venue"
                >
                    <i class="pi pi-trash text-sm" />
                </button>
                <p class="pr-10 font-display text-lg font-semibold tracking-tight">{{ venue.name }}</p>
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

        <!-- No results for the current filters -->
        <div
            v-else
            class="mt-6 flex flex-col items-center justify-center rounded-2xl border border-surface bg-white px-6 py-14 text-center shadow-sm dark:border-white/10 dark:bg-riser"
        >
            <span class="grid size-12 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
                <i class="pi pi-search text-lg" />
            </span>
            <p class="mt-3 text-sm font-medium">No venues match your search</p>
            <p class="text-sm text-ink/50 dark:text-canvas/45">Try a different term or clear the filters.</p>
            <Link
                href="/venues"
                class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl border border-surface px-4 py-2.5 text-sm font-semibold text-ink/70 transition-colors hover:bg-surface dark:border-white/15 dark:text-canvas/70 dark:hover:bg-white/5"
            >
                <i class="pi pi-times text-xs" />
                Clear filters
            </Link>
        </div>

        <Paginator :paginator="venues" />
    </template>

    <!-- Empty state: band has no venues at all -->
    <div
        v-else
        class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-surface bg-white px-6 py-14 text-center shadow-sm dark:border-white/10 dark:bg-riser"
    >
        <span class="grid size-12 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
            <i class="pi pi-map-marker text-lg" />
        </span>
        <p class="mt-3 text-sm font-medium">No venues yet</p>
        <p class="text-sm text-ink/50 dark:text-canvas/45">Add your first room to start your venue book.</p>
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
            <Link
                href="/venues/create"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
            >
                <i class="pi pi-plus text-xs" />
                Add a venue
            </Link>
            <Link
                href="/venues/import"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-surface px-4 py-2.5 text-sm font-semibold text-ink/70 transition-colors hover:bg-surface dark:border-white/15 dark:text-canvas/70 dark:hover:bg-white/5"
            >
                <i class="pi pi-file-import text-xs" />
                Import from CSV
            </Link>
        </div>
    </div>

    <!-- Delete confirmation -->
    <Dialog
        :visible="!!pendingDeletion"
        modal
        dismissable-mask
        header="Delete venue?"
        :style="{ width: '26rem' }"
        @update:visible="(open) => { if (!open) pendingDeletion = null; }"
    >
        <p class="text-sm text-ink/70 dark:text-canvas/65">
            <span class="font-medium text-ink dark:text-canvas">{{ pendingDeletion?.name }}</span>
            will be removed from your venue book. Gigs booked here keep their history and
            revert to a TBD venue. This can't be undone.
        </p>
        <template #footer>
            <Button label="Cancel" text severity="secondary" :disabled="deleting" @click="pendingDeletion = null" />
            <Button label="Delete" severity="danger" :loading="deleting" @click="confirmDeletion" />
        </template>
    </Dialog>
</template>
