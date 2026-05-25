<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Gigs' }, () => page),
};
</script>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    gigs: { type: Array, default: () => [] },
});

// The gig queued for deletion — drives the confirmation dialog. Null when
// nothing is pending.
const pendingDeletion = ref(null);
const deleting = ref(false);

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
                Every show, rehearsal, and date on the books — Roadie keeps the schedule
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

    <!-- Gig list -->
    <ul v-if="gigs.length" class="mt-8 space-y-3">
        <li
            v-for="gig in gigs"
            :key="gig.id"
            class="flex items-center gap-4 rounded-2xl border border-surface bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-white/10 dark:bg-riser sm:gap-5 sm:p-5"
        >
            <!-- Date chip -->
            <div
                class="flex w-14 shrink-0 flex-col items-center rounded-xl bg-amp-violet/10 py-2 text-amp-violet dark:bg-primary-500/15 dark:text-primary-300"
            >
                <span class="text-[10px] font-semibold uppercase tracking-wide">{{ dateParts(gig.date).weekday }}</span>
                <span class="font-display text-xl font-bold leading-none">{{ dateParts(gig.date).day }}</span>
                <span class="text-[10px] font-medium uppercase">{{ dateParts(gig.date).month }}</span>
            </div>

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

            <!-- Status + fee -->
            <div class="flex shrink-0 flex-col items-end gap-1.5">
                <Tag :value="gig.statusLabel" :severity="gig.statusSeverity" />
                <span v-if="formatFee(gig)" class="text-sm font-medium text-ink/70 dark:text-canvas/65">
                    {{ formatFee(gig) }}
                </span>
            </div>

            <!-- Edit -->
            <Link
                :href="`/gigs/${gig.id}/edit`"
                :aria-label="`Edit ${gigLabel(gig)}`"
                class="grid size-8 shrink-0 place-items-center rounded-lg text-ink/40 transition-colors hover:bg-amp-violet/10 hover:text-amp-violet dark:text-canvas/40 dark:hover:text-primary-300"
            >
                <i class="pi pi-pencil text-sm" />
            </Link>

            <!-- Remove -->
            <button
                type="button"
                :aria-label="`Delete ${gigLabel(gig)}`"
                class="grid size-8 shrink-0 place-items-center rounded-lg text-ink/40 transition-colors hover:bg-cancelled/10 hover:text-cancelled dark:text-canvas/40"
                @click="pendingDeletion = gig"
            >
                <i class="pi pi-trash text-sm" />
            </button>
        </li>
    </ul>

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
