<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Booking' }, () => page),
};
</script>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    seasons: { type: Array, default: () => [] },
});

const showCreateForm = ref(false);

const form = useForm({
    name: '',
    starts_on: '',
    ends_on: '',
    notes: '',
});

function submit() {
    form.post('/booking/seasons', {
        onSuccess: () => {
            form.reset();
            showCreateForm.value = false;
        },
    });
}

function formatDate(iso) {
    if (!iso) return null;
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
}

function dateRange(season) {
    if (season.starts_on && season.ends_on) {
        return `${formatDate(season.starts_on)} – ${formatDate(season.ends_on)}`;
    }
    if (season.starts_on) return `From ${formatDate(season.starts_on)}`;
    if (season.ends_on) return `Through ${formatDate(season.ends_on)}`;
    return null;
}

const statusOrder = ['targeting', 'contacted', 'in_discussion', 'booked', 'declined', 'no_response'];

const statusColors = {
    targeting:     'bg-amp-violet/15 text-amp-violet dark:text-primary-300',
    contacted:     'bg-stage-indigo/15 text-stage-indigo dark:text-stage-indigo',
    in_discussion: 'bg-pending/15 text-pending-text dark:text-pending',
    booked:        'bg-confirmed/15 text-confirmed-text dark:text-soundcheck-teal',
    declined:      'bg-cancelled/15 text-cancelled-text dark:text-cancelled',
    no_response:   'bg-surface text-ink/50 dark:bg-white/5 dark:text-canvas/50',
};

const statusLabels = {
    targeting:     'Targeting',
    contacted:     'Contacted',
    in_discussion: 'In Discussion',
    booked:        'Booked',
    declined:      'Declined',
    no_response:   'No Response',
};

function seasonStats(season) {
    return statusOrder
        .filter(s => season.statusCounts?.[s] > 0)
        .map(s => ({ status: s, count: season.statusCounts[s] }));
}
</script>

<template>
    <Head title="Booking" />

    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="font-display text-3xl font-bold tracking-tight">Booking Roadmap</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Track venue outreach season by season.
            </p>
        </div>
        <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
            @click="showCreateForm = !showCreateForm"
        >
            <i class="pi pi-plus text-xs" />
            New Season
        </button>
    </div>

    <!-- Create form -->
    <div
        v-if="showCreateForm"
        class="mt-6 rounded-2xl border border-amp-violet/30 bg-white p-6 shadow-sm dark:border-primary-500/30 dark:bg-riser"
    >
        <h3 class="font-display text-lg font-semibold tracking-tight">New Season</h3>
        <form class="mt-4 space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">
                    Season name
                </label>
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="e.g. Summer 2026, Fall Run, Holiday Shows"
                    class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                    required
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-cancelled-text">{{ form.errors.name }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Start date (optional)</label>
                    <input
                        v-model="form.starts_on"
                        type="date"
                        class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">End date (optional)</label>
                    <input
                        v-model="form.ends_on"
                        type="date"
                        class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Notes (optional)</label>
                <textarea
                    v-model="form.notes"
                    rows="2"
                    class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                />
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-xl bg-amp-violet px-4 py-2 text-sm font-semibold text-white hover:brightness-105 disabled:opacity-50"
                >
                    Create Season
                </button>
                <button
                    type="button"
                    class="rounded-xl border border-surface px-4 py-2 text-sm font-medium text-ink/70 hover:bg-surface dark:border-white/15 dark:text-canvas/65 dark:hover:bg-white/5"
                    @click="showCreateForm = false"
                >
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Season list -->
    <div v-if="seasons.length" class="mt-8 space-y-4">
        <Link
            v-for="season in seasons"
            :key="season.id"
            :href="`/booking/seasons/${season.id}`"
            class="block rounded-2xl border border-surface bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-white/10 dark:bg-riser"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h3 class="font-display text-lg font-semibold tracking-tight truncate">{{ season.name }}</h3>
                    <p v-if="dateRange(season)" class="mt-0.5 text-xs text-ink/50 dark:text-canvas/45">
                        {{ dateRange(season) }}
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="font-display text-2xl font-bold tabular-nums">{{ season.total }}</p>
                    <p class="text-xs text-ink/50 dark:text-canvas/45">{{ season.total === 1 ? 'venue' : 'venues' }}</p>
                </div>
            </div>

            <!-- Status breakdown chips -->
            <div v-if="seasonStats(season).length" class="mt-3 flex flex-wrap gap-2">
                <span
                    v-for="stat in seasonStats(season)"
                    :key="stat.status"
                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="statusColors[stat.status]"
                >
                    {{ stat.count }} {{ statusLabels[stat.status] }}
                </span>
            </div>

            <div v-else class="mt-3 text-xs text-ink/40 dark:text-canvas/35">
                No venues in pipeline yet
            </div>
        </Link>
    </div>

    <!-- Empty state -->
    <div
        v-else
        class="mt-16 flex flex-col items-center justify-center text-center"
    >
        <span class="grid size-16 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
            <i class="pi pi-map text-2xl" />
        </span>
        <h3 class="mt-4 font-display text-lg font-semibold">No booking seasons yet</h3>
        <p class="mt-1 text-sm text-ink/50 dark:text-canvas/45">
            Create a season to start tracking venue outreach.
        </p>
        <button
            type="button"
            class="mt-6 inline-flex items-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white hover:brightness-105"
            @click="showCreateForm = true"
        >
            <i class="pi pi-plus text-xs" />
            New Season
        </button>
    </div>
</template>
