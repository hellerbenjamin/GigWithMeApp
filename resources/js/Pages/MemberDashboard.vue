<script>
import { h } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Dashboard' }, () => page),
};
</script>

<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    upcomingGigs: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const activeBand = computed(() => page.props.activeBand ?? null);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
});

const firstName = computed(() => user.value?.name?.split(' ')[0] ?? 'there');

const gigStatusClass = {
    confirmed: 'bg-confirmed/15 text-confirmed-text dark:text-soundcheck-teal',
    pending: 'bg-pending/15 text-pending-text dark:text-pending',
    cancelled: 'bg-cancelled/15 text-cancelled-text dark:text-cancelled',
};

// RSVP badge colours keyed by severity from GigResponseStatusEnum.
const rsvpClass = {
    success: 'bg-confirmed/15 text-confirmed-text dark:text-soundcheck-teal',
    danger: 'bg-cancelled/15 text-cancelled-text dark:text-cancelled',
    secondary: 'bg-surface text-muted dark:bg-white/5 dark:text-canvas/50',
};

function gigDate(iso) {
    const [year, month, day] = iso.split('-').map(Number);
    const d = new Date(year, month - 1, day);
    return {
        month: d.toLocaleDateString('en-US', { month: 'short' }).toUpperCase(),
        day: d.toLocaleDateString('en-US', { day: 'numeric' }),
        weekday: d.toLocaleDateString('en-US', { weekday: 'long' }),
    };
}
</script>

<template>
    <Head title="Dashboard" />

    <!-- Header -->
    <div>
        <h2 class="font-display text-3xl font-bold tracking-tight">
            {{ greeting }}, {{ firstName }}
        </h2>
        <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
            Here's what's coming up with
            <span class="font-medium text-ink dark:text-canvas">{{ activeBand?.name ?? 'your band' }}</span>.
        </p>
    </div>

    <!-- Stat card: upcoming gig count -->
    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-surface bg-white p-5 shadow-sm dark:border-white/10 dark:bg-riser">
            <span class="grid size-10 place-items-center rounded-xl bg-soundcheck-teal/15 text-confirmed-text dark:text-soundcheck-teal">
                <i class="pi pi-calendar" />
            </span>
            <p class="mt-4 font-display text-2xl font-bold tracking-tight">{{ stats.upcomingGigs ?? 0 }}</p>
            <p class="text-xs text-ink/55 dark:text-canvas/50">Upcoming gigs</p>
        </div>
    </div>

    <!-- Upcoming gigs -->
    <div class="mt-8 rounded-2xl border border-surface bg-white shadow-sm dark:border-white/10 dark:bg-riser">
        <div class="border-b border-surface px-5 py-4 dark:border-white/10">
            <h3 class="font-display text-lg font-semibold tracking-tight">Upcoming gigs</h3>
        </div>

        <ul v-if="upcomingGigs.length" class="divide-y divide-surface dark:divide-white/10">
            <Link
                v-for="gig in upcomingGigs"
                :key="gig.id"
                :href="`/gigs/${gig.id}`"
                class="flex cursor-pointer items-center gap-4 px-5 py-4 transition-colors hover:bg-canvas dark:hover:bg-white/5"
            >
                <!-- Date block -->
                <div class="flex size-14 shrink-0 flex-col items-center justify-center rounded-xl bg-stage-indigo text-white dark:bg-white/5">
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-canvas/70">
                        {{ gigDate(gig.date).month }}
                    </span>
                    <span class="font-display text-xl font-bold leading-none">
                        {{ gigDate(gig.date).day }}
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ gig.name ?? gig.venue }}</p>
                    <p class="truncate text-sm text-ink/55 dark:text-canvas/50">
                        <i class="pi pi-map-marker text-[10px]" /> {{ gig.venue }}
                        <span class="text-ink/30 dark:text-canvas/30">·</span>
                        {{ gigDate(gig.date).weekday }}
                    </p>
                </div>

                <!-- RSVP badge for poll gigs -->
                <span
                    v-if="gig.myRsvp"
                    class="hidden shrink-0 rounded-full px-3 py-1 text-xs font-semibold sm:block"
                    :class="rsvpClass[gig.myRsvpSeverity] ?? rsvpClass.secondary"
                >
                    {{ gig.myRsvpLabel }}
                </span>
                <!-- Gig status for auto/confirmed gigs with no poll response -->
                <span
                    v-else
                    class="hidden shrink-0 rounded-full px-3 py-1 text-xs font-semibold capitalize sm:block"
                    :class="gigStatusClass[gig.status] ?? gigStatusClass.pending"
                >
                    {{ gig.status }}
                </span>
            </Link>
        </ul>

        <!-- Empty state -->
        <div v-else class="flex flex-col items-center justify-center px-5 py-12 text-center">
            <span class="grid size-12 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
                <i class="pi pi-calendar text-lg" />
            </span>
            <p class="mt-3 text-sm font-medium">No gigs on the calendar yet</p>
            <p class="text-sm text-ink/50 dark:text-canvas/45">Your band hasn't booked anything yet.</p>
        </div>
    </div>
</template>
