<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Gigs' }, () => page),
};
</script>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    gigs: { type: Array, default: () => [] },
});

// The gig queued for deletion — drives the confirmation dialog. Null when
// nothing is pending.
const pendingDeletion = ref(null);
const deleting = ref(false);

// --- Filters -------------------------------------------------------------
// All gigs are already loaded, so filtering happens client-side for an instant
// response — no round-trip. Null / 'all' mean "no filter".
const currentYear = new Date().getFullYear();

// Collapsed by default on mobile to keep the toolbar out of the way; the toggle
// below reveals it. On sm+ the bar is always shown regardless of this flag.
const showFilters = ref(false);

const search = ref('');
const statusFilter = ref(null);
const typeFilter = ref(null);
// Open on this year's gigs by default — but only when there are some, so a band
// with nothing booked this year doesn't land on an empty, pre-filtered list.
const yearFilter = ref(
    props.gigs.some((gig) => Number(gig.date.slice(0, 4)) === currentYear) ? currentYear : null,
);
const timeFilter = ref('all');

const timeOptions = [
    { label: 'All', value: 'all' },
    { label: 'Upcoming', value: 'upcoming' },
    { label: 'Past', value: 'past' },
];

// Offer only the statuses / types actually present, so the dropdowns never list
// a value that would match nothing. Status labels ride along on each gig; type
// has no label server-side, so capitalize its value.
const statusOptions = computed(() => {
    const seen = new Map();
    for (const gig of props.gigs) {
        if (!seen.has(gig.status)) {
            seen.set(gig.status, { value: gig.status, label: gig.statusLabel });
        }
    }
    return [...seen.values()];
});

const typeOptions = computed(() => {
    const seen = new Map();
    for (const gig of props.gigs) {
        if (!seen.has(gig.type)) {
            seen.set(gig.type, {
                value: gig.type,
                label: gig.type.charAt(0).toUpperCase() + gig.type.slice(1),
            });
        }
    }
    return [...seen.values()];
});

// Years present across all gigs, newest first.
const yearOptions = computed(() => {
    const years = new Set(props.gigs.map((gig) => Number(gig.date.slice(0, 4))));
    return [...years].sort((a, b) => b - a);
});

// Local midnight today — gigs on or after it count as upcoming.
const startOfToday = new Date();
startOfToday.setHours(0, 0, 0, 0);

const filteredGigs = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.gigs.filter((gig) => {
        if (statusFilter.value && gig.status !== statusFilter.value) return false;
        if (typeFilter.value && gig.type !== typeFilter.value) return false;
        if (yearFilter.value && Number(gig.date.slice(0, 4)) !== yearFilter.value) return false;

        if (timeFilter.value !== 'all') {
            const isPast = gigDate(gig.date) < startOfToday;
            if (timeFilter.value === 'upcoming' && isPast) return false;
            if (timeFilter.value === 'past' && !isPast) return false;
        }

        if (q && !`${gig.name ?? ''} ${gig.venue ?? ''}`.toLowerCase().includes(q)) {
            return false;
        }

        return true;
    });
});

const hasActiveFilters = computed(
    () => !!search.value
        || !!statusFilter.value
        || !!typeFilter.value
        || !!yearFilter.value
        || timeFilter.value !== 'all',
);

function clearFilters() {
    search.value = '';
    statusFilter.value = null;
    typeFilter.value = null;
    yearFilter.value = null;
    timeFilter.value = 'all';
}

// A human label for the gig in the confirmation copy.
function gigLabel(gig) {
    return gig.name || (gig.type === 'gig' ? 'this gig' : gig.type);
}

function confirmDeletion() {
    if (!pendingDeletion.value) {
        return;
    }

    router.delete(`/gigs/${pendingDeletion.value.id}`, {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            pendingDeletion.value = null;
        },
    });
}

// Parse the plain Y-m-d string as a local date (no timezone shift) for display.
function gigDate(date) {
    const [y, m, d] = date.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function dateParts(date) {
    const d = gigDate(date);
    return {
        month: d.toLocaleDateString(undefined, { month: 'short' }),
        day: d.getDate(),
        weekday: d.toLocaleDateString(undefined, { weekday: 'short' }),
    };
}

function timeRange(gig) {
    if (!gig.startTime) return null;
    return gig.endTime ? `${gig.startTime}–${gig.endTime}` : gig.startTime;
}

function formatFee(gig) {
    if (gig.fee === null || gig.fee === undefined) return null;
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: gig.currency || 'USD',
        maximumFractionDigits: 0,
    }).format(gig.fee);
}
</script>

<template>
    <Head title="Gigs" />

    <!-- Header row -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="font-display text-3xl font-bold tracking-tight">Gigs</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Every show, rehearsal, and date on the books — GigWithMe keeps the schedule
                straight.
            </p>
        </div>

        <Link
            v-if="gigs.length"
            href="/gigs/create"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
        >
            <i class="pi pi-plus text-xs" />
            New gig
        </Link>
    </div>

    <!-- Mobile filter toggle — hidden on sm+, where the bar is always shown. -->
    <button
        v-if="gigs.length"
        type="button"
        class="mt-6 flex w-full items-center justify-between rounded-xl border border-surface bg-white px-4 py-2.5 text-sm font-medium shadow-sm dark:border-white/10 dark:bg-riser sm:hidden"
        :aria-expanded="showFilters"
        @click="showFilters = !showFilters"
    >
        <span class="flex items-center gap-2">
            <i class="pi pi-filter text-xs" />
            Filters
            <span
                v-if="hasActiveFilters"
                class="grid size-2 place-items-center rounded-full bg-amp-violet dark:bg-primary-400"
            />
        </span>
        <i :class="showFilters ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" class="text-xs text-muted dark:text-canvas/45" />
    </button>

    <!-- Filters -->
    <div
        v-if="gigs.length"
        class="mt-3 flex-col gap-3 sm:mt-6 sm:flex sm:flex-row sm:flex-wrap sm:items-center"
        :class="showFilters ? 'flex' : 'hidden'"
    >
        <IconField class="w-full sm:flex-1 sm:min-w-52">
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" placeholder="Search by title or venue" fluid />
        </IconField>

        <Select
            v-model="statusFilter"
            :options="statusOptions"
            option-label="label"
            option-value="value"
            show-clear
            placeholder="All statuses"
            class="w-full sm:w-44"
        />

        <Select
            v-model="typeFilter"
            :options="typeOptions"
            option-label="label"
            option-value="value"
            show-clear
            placeholder="All types"
            class="w-full sm:w-40"
        />

        <Select
            v-model="yearFilter"
            :options="yearOptions"
            show-clear
            placeholder="All years"
            class="w-full sm:w-36"
        />

        <SelectButton
            v-model="timeFilter"
            :options="timeOptions"
            option-label="label"
            option-value="value"
            :allow-empty="false"
            aria-label="Filter by when"
        />

        <Button
            v-if="hasActiveFilters"
            label="Clear"
            icon="pi pi-times"
            text
            severity="secondary"
            @click="clearFilters"
        />
    </div>

    <!-- Gig list -->
    <ul v-if="filteredGigs.length" class="mt-6 space-y-3">
        <li
            v-for="gig in filteredGigs"
            :key="gig.id"
            class="relative flex items-stretch overflow-hidden rounded-2xl border border-surface bg-white shadow-sm transition-shadow hover:shadow-md dark:border-white/10 dark:bg-riser"
        >
            <!-- Date block — flush against the card's top, left and bottom edges -->
            <div
                class="flex w-16 shrink-0 flex-col items-center justify-center bg-amp-violet/10 text-amp-violet dark:bg-primary-500/15 dark:text-primary-300"
            >
                <span class="text-[10px] font-semibold uppercase tracking-wide">{{ dateParts(gig.date).weekday }}</span>
                <span class="font-display text-xl font-bold leading-none">{{ dateParts(gig.date).day }}</span>
                <span class="text-[10px] font-medium uppercase">{{ dateParts(gig.date).month }}</span>
            </div>

            <!-- Status — flush in the top-right corner; the card's overflow-hidden
                 clips its outer corner to match the rounding, and the inner corner
                 is tucked with a small radius. -->
            <Tag
                :value="gig.statusLabel"
                :severity="gig.statusSeverity"
                class="!absolute right-0 top-0 !rounded-none !rounded-bl-lg !px-2.5 !py-1 text-xs"
            />

            <!-- Content — carries the padding the card used to have. The fee and
                 actions are vertically centred, so they tuck just under the
                 corner status chip without needing extra right clearance. -->
            <div class="flex min-w-0 flex-1 items-center gap-4 p-4 sm:gap-5 sm:p-5">
                <!-- Details -->
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="truncate font-display text-lg font-semibold tracking-tight">
                            {{ gig.name || (gig.type === 'gig' ? 'Untitled gig' : gig.type) }}
                        </p>
                        <Tag
                            v-if="gig.type !== 'gig'"
                            :value="gig.type"
                            severity="secondary"
                            class="shrink-0 capitalize"
                        />
                    </div>
                    <p class="mt-0.5 truncate text-sm text-ink/55 dark:text-canvas/50">
                        <i class="pi pi-map-marker text-[10px]" />
                        {{ gig.venue || 'Venue TBD' }}
                        <span v-if="timeRange(gig)"> · {{ timeRange(gig) }}</span>
                    </p>
                </div>

                <!-- Fee + actions, grouped into one right-aligned cluster so they
                     read as a unit rather than floating across the row. -->
                <div class="flex shrink-0 items-center gap-3">
                    <span v-if="formatFee(gig)" class="hidden text-sm font-semibold tabular-nums text-ink dark:text-canvas sm:inline">
                        {{ formatFee(gig) }}
                    </span>

                    <div class="flex items-center gap-0.5">
                        <!-- Edit -->
                        <Link
                            :href="`/gigs/${gig.id}/edit`"
                            :aria-label="`Edit ${gigLabel(gig)}`"
                            class="grid size-8 place-items-center rounded-lg text-ink/50 transition-colors hover:bg-amp-violet/10 hover:text-amp-violet dark:text-canvas/50 dark:hover:text-primary-300"
                        >
                            <i class="pi pi-pencil text-sm" />
                        </Link>

                        <!-- Remove -->
                        <button
                            type="button"
                            :aria-label="`Delete ${gigLabel(gig)}`"
                            class="grid size-8 place-items-center rounded-lg text-ink/50 transition-colors hover:bg-cancelled/10 hover:text-cancelled dark:text-canvas/50"
                            @click="pendingDeletion = gig"
                        >
                            <i class="pi pi-trash text-sm" />
                        </button>
                    </div>
                </div>
            </div>
        </li>
    </ul>

    <!-- No matches: there are gigs, but none survive the active filters. -->
    <div
        v-else-if="gigs.length"
        class="mt-6 flex flex-col items-center justify-center rounded-2xl border border-surface bg-white px-6 py-14 text-center shadow-sm dark:border-white/10 dark:bg-riser"
    >
        <span class="grid size-12 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
            <i class="pi pi-filter-slash text-lg" />
        </span>
        <p class="mt-3 text-sm font-medium">No gigs match your filters</p>
        <p class="text-sm text-ink/50 dark:text-canvas/45">Try widening your search or clearing the filters.</p>
        <Button label="Clear filters" text class="mt-4" @click="clearFilters" />
    </div>

    <!-- Empty state -->
    <div
        v-else
        class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-surface bg-white px-6 py-14 text-center shadow-sm dark:border-white/10 dark:bg-riser"
    >
        <span class="grid size-12 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
            <i class="pi pi-calendar text-lg" />
        </span>
        <p class="mt-3 text-sm font-medium">No gigs yet</p>
        <p class="text-sm text-ink/50 dark:text-canvas/45">Book your first show to start the calendar.</p>
        <Link
            href="/gigs/create"
            class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
        >
            <i class="pi pi-plus text-xs" />
            Book a gig
        </Link>
    </div>

    <!-- Delete confirmation -->
    <Dialog
        :visible="!!pendingDeletion"
        modal
        dismissable-mask
        header="Delete gig?"
        :style="{ width: '26rem' }"
        @update:visible="(open) => { if (!open) pendingDeletion = null; }"
    >
        <p class="text-sm text-ink/70 dark:text-canvas/65">
            <span class="font-medium text-ink dark:text-canvas">{{ pendingDeletion ? gigLabel(pendingDeletion) : '' }}</span>
            will be removed from the calendar. This can't be undone.
        </p>
        <template #footer>
            <Button label="Cancel" text severity="secondary" :disabled="deleting" @click="pendingDeletion = null" />
            <Button label="Delete" severity="danger" :loading="deleting" @click="confirmDeletion" />
        </template>
    </Dialog>
</template>
