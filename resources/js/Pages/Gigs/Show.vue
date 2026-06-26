<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

// Persistent layout with a per-page title for the topbar.
export default {
    layout: (_h, page) => h(AppLayout, { title: 'Gig' }, () => page),
};
</script>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    // The gig, shaped by GigController@show.
    gig: { type: Object, required: true },
    // Poll progress for poll-mode gigs; null for auto-mode gigs.
    poll: { type: Object, default: null },
    // Whether the current user (owner/admin) may run the booking actions.
    canManage: { type: Boolean, default: false },
});

// Which booking action is awaiting confirmation — drives the dialog. One of
// 'confirm' | 'repoll' | null. Both fan an SMS out to the band, so neither
// fires without a second tap.
const pendingAction = ref(null);
const working = ref(false);

// Everyone replied but the poll couldn't auto-confirm — the admin's decision.
const needsAttention = computed(
    () => props.poll?.closed && props.gig.status !== 'confirmed',
);

// Still collecting: a poll that hasn't closed and isn't confirmed yet.
const collecting = computed(
    () => props.poll && !props.poll.closed && props.gig.status === 'pending',
);

const actionCopy = {
    confirm: {
        header: 'Confirm this gig?',
        body: "We'll mark it confirmed and text the whole band the good news.",
        label: 'Confirm gig',
        severity: undefined,
        run: () => router.post(`/gigs/${props.gig.id}/confirm`, {}, dialogOpts),
    },
    repoll: {
        header: 'Re-poll the band?',
        body: 'Every reply resets to “waiting” and everyone gets asked again — useful when the date or plan changed.',
        label: 'Re-poll',
        severity: undefined,
        run: () => router.post(`/gigs/${props.gig.id}/repoll`, {}, dialogOpts),
    },
};

const dialogOpts = {
    preserveScroll: true,
    onStart: () => (working.value = true),
    onFinish: () => {
        working.value = false;
        pendingAction.value = null;
    },
};

function runPendingAction() {
    if (pendingAction.value) {
        actionCopy[pendingAction.value].run();
    }
}

// --- Display helpers (mirror Gigs/Index.vue) -----------------------------
function gigDate(date) {
    const [y, m, d] = date.split('-').map(Number);
    return new Date(y, m - 1, d);
}

const longDate = computed(() =>
    gigDate(props.gig.date).toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }),
);

const title = computed(
    () => props.gig.name || (props.gig.type === 'gig' ? 'Untitled gig' : props.gig.typeLabel),
);

const timeRange = computed(() => {
    if (!props.gig.startTime) return null;
    return props.gig.endTime ? `${props.gig.startTime}–${props.gig.endTime}` : props.gig.startTime;
});

const formattedFee = computed(() => {
    if (props.gig.fee === null || props.gig.fee === undefined) return null;
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: props.gig.currency || 'USD',
        maximumFractionDigits: 0,
    }).format(props.gig.fee);
});

// Call-sheet rows that actually have a time, for the detail grid.
const callSheet = computed(() =>
    [
        { label: 'Load-in', value: props.gig.loadInTime },
        { label: 'Soundcheck', value: props.gig.soundcheckTime },
        { label: 'Doors', value: props.gig.doorsTime },
        { label: 'Set time', value: timeRange.value },
    ].filter((row) => row.value),
);

// Width of the "replied" progress bar.
const progressPct = computed(() => {
    if (!props.poll || props.poll.total === 0) return 0;
    return Math.round((props.poll.respondedCount / props.poll.total) * 100);
});
</script>

<template>
    <Head :title="title" />

    <div class="mx-auto max-w-2xl">
        <!-- Back -->
        <Link
            href="/gigs"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-ink/60 transition-colors hover:text-amp-violet dark:text-canvas/55 dark:hover:text-primary-300"
        >
            <i class="pi pi-arrow-left text-xs" />
            All gigs
        </Link>

        <!-- Header -->
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <h2 class="font-display text-3xl font-bold tracking-tight">{{ title }}</h2>
                    <Tag :value="gig.statusLabel" :severity="gig.statusSeverity" class="shrink-0" />
                </div>
                <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                    {{ longDate }}
                    <span v-if="gig.type !== 'gig'"> · {{ gig.typeLabel }}</span>
                </p>
            </div>

            <Link
                :href="`/gigs/${gig.id}/edit`"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-surface bg-white px-4 py-2.5 text-sm font-semibold shadow-sm transition-all hover:shadow-md active:scale-[0.98] dark:border-white/10 dark:bg-riser"
            >
                <i class="pi pi-pencil text-xs" />
                Edit
            </Link>
        </div>

        <!-- Detail card -->
        <div class="mt-6 rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8">
            <dl class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">Venue</dt>
                    <dd class="mt-1 text-sm">{{ gig.venue || 'To be decided' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">Availability</dt>
                    <dd class="mt-1 text-sm">{{ gig.bookingModeLabel }}</dd>
                </div>
                <div v-if="formattedFee">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">Fee</dt>
                    <dd class="mt-1 text-sm font-semibold tabular-nums">{{ formattedFee }}</dd>
                </div>

                <!-- Call sheet times, only when at least one is set. -->
                <div v-if="callSheet.length" class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">Call sheet</dt>
                    <dd class="mt-2 flex flex-wrap gap-x-6 gap-y-2">
                        <span v-for="row in callSheet" :key="row.label" class="text-sm">
                            <span class="text-ink/55 dark:text-canvas/50">{{ row.label }}</span>
                            <span class="ml-1.5 font-medium tabular-nums">{{ row.value }}</span>
                        </span>
                    </dd>
                </div>

                <div v-if="gig.notes" class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">Notes</dt>
                    <dd class="mt-1 whitespace-pre-line text-sm text-ink/80 dark:text-canvas/75">{{ gig.notes }}</dd>
                </div>
            </dl>
        </div>

        <!-- Poll progress -->
        <div
            v-if="poll"
            class="mt-6 rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8"
        >
            <div class="flex items-center justify-between gap-4">
                <h3 class="font-display text-lg font-semibold tracking-tight">Poll</h3>
                <span class="text-sm font-medium tabular-nums text-ink/60 dark:text-canvas/55">
                    {{ poll.respondedCount }}/{{ poll.total }} in
                </span>
            </div>

            <!-- Progress bar -->
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface dark:bg-white/10">
                <div
                    class="h-full rounded-full bg-amp-violet transition-all dark:bg-primary-500"
                    :style="{ width: `${progressPct}%` }"
                />
            </div>

            <!-- Tallies -->
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink/60 dark:text-canvas/55">
                <span><span class="font-semibold text-confirmed">{{ poll.availableCount }}</span> available</span>
                <span><span class="font-semibold text-cancelled">{{ poll.unavailableCount }}</span> can't make it</span>
                <span><span class="font-semibold">{{ poll.total - poll.respondedCount }}</span> waiting</span>
            </div>

            <!-- Needs-attention banner -->
            <div
                v-if="needsAttention"
                class="mt-5 flex gap-3 rounded-xl border border-encore-coral/30 bg-encore-coral/10 p-4 text-sm dark:border-encore-coral/25"
            >
                <i class="pi pi-exclamation-triangle mt-0.5 text-encore-coral" />
                <p class="text-ink/80 dark:text-canvas/75">
                    Everyone's replied, but not all of them can make it. Confirm the gig anyway, or
                    re-poll if the plan has changed.
                </p>
            </div>
            <p
                v-else-if="collecting"
                class="mt-5 text-sm text-ink/55 dark:text-canvas/50"
            >
                Still waiting on a few replies — the gig confirms itself the moment everyone's in.
            </p>

            <!-- Per-member rows -->
            <ul class="mt-5 divide-y divide-surface dark:divide-white/10">
                <li
                    v-for="member in poll.members"
                    :key="member.id"
                    class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                >
                    <div class="min-w-0">
                        <p class="flex items-center gap-1.5 truncate text-sm font-medium">
                            {{ member.name }}
                            <span
                                v-if="member.critical"
                                class="rounded-full bg-encore-coral/12 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-encore-coral"
                            >Critical</span>
                        </p>
                        <p v-if="member.note" class="mt-0.5 text-sm italic text-ink/55 dark:text-canvas/50">
                            “{{ member.note }}”
                        </p>
                        <p v-if="member.respondedAt" class="mt-0.5 text-xs text-muted dark:text-canvas/40">
                            {{ member.respondedAt }}
                        </p>
                    </div>
                    <Tag :value="member.statusLabel" :severity="member.severity" class="shrink-0" />
                </li>
            </ul>

            <!-- Admin actions -->
            <div
                v-if="canManage && gig.status !== 'confirmed'"
                class="mt-6 flex flex-col gap-3 border-t border-surface pt-6 dark:border-white/10 sm:flex-row sm:justify-end"
            >
                <Button
                    label="Re-poll"
                    icon="pi pi-replay"
                    outlined
                    severity="secondary"
                    :disabled="working"
                    @click="pendingAction = 'repoll'"
                />
                <Button
                    label="Confirm anyway"
                    icon="pi pi-check"
                    :disabled="working"
                    @click="pendingAction = 'confirm'"
                />
            </div>
            <!-- A confirmed poll gig can still be reopened if plans change. -->
            <div
                v-else-if="canManage"
                class="mt-6 flex justify-end border-t border-surface pt-6 dark:border-white/10"
            >
                <Button
                    label="Re-poll"
                    icon="pi pi-replay"
                    outlined
                    severity="secondary"
                    :disabled="working"
                    @click="pendingAction = 'repoll'"
                />
            </div>
        </div>

        <!-- Action confirmation -->
        <Dialog
            :visible="!!pendingAction"
            modal
            dismissable-mask
            :header="pendingAction ? actionCopy[pendingAction].header : ''"
            :style="{ width: '26rem' }"
            @update:visible="(open) => { if (!open) pendingAction = null; }"
        >
            <p class="text-sm text-ink/70 dark:text-canvas/65">
                {{ pendingAction ? actionCopy[pendingAction].body : '' }}
            </p>
            <template #footer>
                <Button label="Cancel" text severity="secondary" :disabled="working" @click="pendingAction = null" />
                <Button
                    :label="pendingAction ? actionCopy[pendingAction].label : ''"
                    :loading="working"
                    @click="runPendingAction"
                />
            </template>
        </Dialog>
    </div>
</template>
