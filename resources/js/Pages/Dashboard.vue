<script>
import { h } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';

// Persistent layout with a per-page title for the topbar.
export default {
    layout: (_h, page) => h(AppLayout, { title: 'Dashboard' }, () => page),
};
</script>

<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    // Mock data from the route until the real services land.
    stats: { type: Object, default: () => ({}) },
    upcomingGigs: { type: Array, default: () => [] },
    followUpsDue: { type: Array, default: () => [] },
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

// Whole-dollar currency, matching the gigs index. `currency` defaults to USD.
function formatMoney(amount, currency) {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency || 'USD',
        maximumFractionDigits: 0,
    }).format(amount ?? 0);
}

function gigFee(gig) {
    if (gig.fee === null || gig.fee === undefined) return null;
    return formatMoney(gig.fee, gig.currency);
}

const statCards = computed(() => [
    { key: 'gigs', icon: 'pi pi-calendar', label: 'Upcoming gigs', value: props.stats.upcomingGigs ?? 0, accent: 'teal' },
    { key: 'fees', icon: 'pi pi-dollar', label: 'Booked this month', value: formatMoney(props.stats.bookedThisMonth, props.stats.currency), accent: 'violet' },
    { key: 'venues', icon: 'pi pi-map-marker', label: 'Venues', value: props.stats.venues ?? 0, accent: 'coral' },
    { key: 'members', icon: 'pi pi-users', label: 'Band members', value: props.stats.members ?? 0, accent: 'indigo' },
]);

// Accent ramp for the stat icons — keeps each card distinct without a rainbow.
const accentClass = {
    teal: 'bg-soundcheck-teal/15 text-confirmed-text dark:text-soundcheck-teal',
    violet: 'bg-amp-violet/15 text-amp-violet dark:text-primary-300',
    coral: 'bg-encore-coral/15 text-encore-coral',
    indigo: 'bg-stage-indigo/10 text-stage-indigo dark:bg-white/10 dark:text-canvas/80',
};

const statusClass = {
    confirmed: 'bg-confirmed/15 text-confirmed-text dark:text-soundcheck-teal',
    pending: 'bg-pending/15 text-pending-text dark:text-pending',
    cancelled: 'bg-cancelled/15 text-cancelled-text dark:text-cancelled',
};

function gigDate(iso) {
    // Parse the Y-M-D as a local date — `new Date('2026-05-29')` would be read
    // as UTC midnight and shift back a day in negative-offset timezones.
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

    <!-- Header row -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="font-display text-3xl font-bold tracking-tight">
                {{ greeting }}, {{ firstName }}
            </h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Here's what's happening with
                <span class="font-medium text-ink dark:text-canvas">{{ activeBand?.name ?? 'your band' }}</span>.
            </p>
        </div>

        <!-- Encore Coral is reserved for the single highest-priority action. -->
        <Link
            href="/gigs/create"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
        >
            <i class="pi pi-plus text-xs" />
            Book a gig
        </Link>
    </div>

    <!-- Stat cards -->
    <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div
            v-for="card in statCards"
            :key="card.key"
            class="rounded-2xl border border-surface bg-white p-5 shadow-sm dark:border-white/10 dark:bg-riser"
        >
            <span
                class="grid size-10 place-items-center rounded-xl"
                :class="accentClass[card.accent]"
            >
                <i :class="card.icon" />
            </span>
            <p class="mt-4 font-display text-2xl font-bold tracking-tight">{{ card.value }}</p>
            <p class="text-xs text-ink/55 dark:text-canvas/50">{{ card.label }}</p>
        </div>
    </div>

    <!-- Upcoming gigs -->
    <div class="mt-8 rounded-2xl border border-surface bg-white shadow-sm dark:border-white/10 dark:bg-riser">
        <div class="flex items-center justify-between border-b border-surface px-5 py-4 dark:border-white/10">
            <h3 class="font-display text-lg font-semibold tracking-tight">Upcoming gigs</h3>
            <Link href="/gigs" class="text-sm font-medium text-amp-violet hover:underline dark:text-primary-300">
                View all
            </Link>
        </div>

        <ul v-if="upcomingGigs.length" class="divide-y divide-surface dark:divide-white/10">
            <Link
                v-for="gig in upcomingGigs"
                :key="gig.id"
                :href="`/gigs/${gig.id}`"
                class="flex cursor-pointer items-center gap-4 px-5 py-4 transition-colors hover:bg-canvas dark:hover:bg-white/5"
            >
                <!-- Date block -->
                <div
                    class="flex size-14 shrink-0 flex-col items-center justify-center rounded-xl bg-stage-indigo text-white dark:bg-white/5"
                >
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

                <span
                    class="hidden shrink-0 rounded-full px-3 py-1 text-xs font-semibold capitalize sm:block"
                    :class="statusClass[gig.status] ?? statusClass.pending"
                >
                    {{ gig.status }}
                </span>
                <span v-if="gigFee(gig)" class="hidden shrink-0 font-display text-sm font-semibold tabular-nums sm:block">
                    {{ gigFee(gig) }}
                </span>
            </Link>
        </ul>

        <!-- Empty state -->
        <div v-else class="flex flex-col items-center justify-center px-5 py-12 text-center">
            <span class="grid size-12 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
                <i class="pi pi-calendar text-lg" />
            </span>
            <p class="mt-3 text-sm font-medium">No gigs on the calendar yet</p>
            <p class="text-sm text-ink/50 dark:text-canvas/45">Book your first gig to see it here.</p>
        </div>
    </div>

    <!-- Booking follow-ups -->
    <div v-if="followUpsDue.length" class="mt-8 rounded-2xl border border-encore-coral/30 bg-white shadow-sm dark:border-encore-coral/20 dark:bg-riser">
        <div class="flex items-center justify-between border-b border-encore-coral/20 px-5 py-4 dark:border-encore-coral/15">
            <div class="flex items-center gap-2">
                <span class="grid size-6 place-items-center rounded-full bg-encore-coral/15">
                    <i class="pi pi-clock text-xs text-encore-coral" />
                </span>
                <h3 class="font-display text-lg font-semibold tracking-tight">Booking Follow-ups Due</h3>
            </div>
            <Link href="/booking/seasons" class="text-sm font-medium text-amp-violet hover:underline dark:text-primary-300">
                View roadmap
            </Link>
        </div>
        <ul class="divide-y divide-surface dark:divide-white/10">
            <Link
                v-for="item in followUpsDue"
                :key="item.id"
                :href="`/booking/outreach/${item.id}`"
                class="flex cursor-pointer items-center gap-4 px-5 py-3.5 transition-colors hover:bg-canvas dark:hover:bg-white/5"
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-sm">{{ item.venueName }}</p>
                    <p class="text-xs text-ink/50 dark:text-canvas/45">{{ item.seasonName }} · {{ item.statusLabel }}</p>
                </div>
                <span class="shrink-0 text-xs font-semibold text-encore-coral">
                    {{ item.daysOverdue === 0 ? 'Due today' : `${item.daysOverdue}d overdue` }}
                </span>
            </Link>
        </ul>
    </div>
</template>
