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

defineProps({
    venues: { type: Array, default: () => [] },
});

// The venue queued for deletion — drives the confirmation dialog. Null when
// nothing is pending.
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

        <div v-if="venues.length" class="flex items-center gap-2">
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

    <!-- Venue list -->
    <ul
        v-if="venues.length"
        class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
    >
        <li
            v-for="venue in venues"
            :key="venue.id"
            class="relative rounded-2xl border border-surface bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-white/10 dark:bg-riser"
        >
            <div class="flex items-start justify-between">
                <span class="grid size-10 place-items-center rounded-xl bg-encore-coral/15 text-encore-coral">
                    <i class="pi pi-map-marker" />
                </span>
                <div class="flex items-center gap-0.5">
                    <Link
                        :href="`/venues/${venue.id}/edit`"
                        :aria-label="`Edit ${venue.name}`"
                        class="grid size-8 place-items-center rounded-lg text-ink/50 transition-colors hover:bg-amp-violet/10 hover:text-amp-violet dark:text-canvas/50 dark:hover:text-primary-300"
                    >
                        <i class="pi pi-pencil text-sm" />
                    </Link>
                    <button
                        type="button"
                        :aria-label="`Delete ${venue.name}`"
                        class="grid size-8 place-items-center rounded-lg text-ink/50 transition-colors hover:bg-cancelled/10 hover:text-cancelled dark:text-canvas/50"
                        @click="pendingDeletion = venue"
                    >
                        <i class="pi pi-trash text-sm" />
                    </button>
                </div>
            </div>
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
