<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Booking Roadmap' }, () => page),
};
</script>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    season:          { type: Object, required: true },
    allSeasons:      { type: Array,  default: () => [] },
    columns:         { type: Array,  default: () => [] },
    availableVenues: { type: Array,  default: () => [] },
});

// --- Add venue form ---
const showAddVenue = ref(false);
const addForm = useForm({ venue_id: '', priority: 'medium' });

function addVenue() {
    addForm.post(`/booking/seasons/${props.season.id}/outreach`, {
        onSuccess: () => { addForm.reset(); showAddVenue.value = false; },
    });
}

// --- Edit season form ---
const showEditSeason = ref(false);
const editForm = useForm({
    name:      props.season.name,
    starts_on: props.season.starts_on ?? '',
    ends_on:   props.season.ends_on ?? '',
    notes:     props.season.notes ?? '',
});

function updateSeason() {
    editForm.put(`/booking/seasons/${props.season.id}`, {
        onSuccess: () => { showEditSeason.value = false; },
    });
}

function deleteSeason() {
    if (!confirm(`Delete season "${props.season.name}"? This will remove all venues and contact history for this season.`)) return;
    router.delete(`/booking/seasons/${props.season.id}`);
}

// --- Carry-forward dialog ---
const showCarryForward = ref(false);
const carryFromSeasonId = ref('');
const carrySelectedIds = ref([]);
const sourceSeasons = computed(() =>
    props.allSeasons.filter(s => s.id !== props.season.id),
);
const sourceSeasonOutreach = ref([]);

async function loadSourceSeason() {
    carrySelectedIds.value = [];
    if (!carryFromSeasonId.value) { sourceSeasonOutreach.value = []; return; }
    // Fetch the outreach items for the selected season via Inertia visit.
    // We use a simple fetch call here since we just need the list.
    const resp = await fetch(`/booking/seasons/${carryFromSeasonId.value}`, {
        headers: { 'X-Inertia': 'true', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await resp.json();
    const cols = data?.props?.columns ?? [];
    sourceSeasonOutreach.value = cols.flatMap(c => c.items ?? []);
}

const carryForm = useForm({});

function submitCarryForward() {
    if (!carryFromSeasonId.value || !carrySelectedIds.value.length) return;
    router.post(`/booking/seasons/${props.season.id}/carry-forward`, {
        from_season_id:     parseInt(carryFromSeasonId.value),
        venue_outreach_ids: carrySelectedIds.value,
    }, {
        onSuccess: () => {
            showCarryForward.value = false;
            carryFromSeasonId.value = '';
            carrySelectedIds.value = [];
            sourceSeasonOutreach.value = [];
        },
    });
}

function toggleCarryItem(id) {
    const idx = carrySelectedIds.value.indexOf(id);
    if (idx === -1) carrySelectedIds.value.push(id);
    else carrySelectedIds.value.splice(idx, 1);
}

// --- Helpers ---
const priorityColors = {
    high:   'bg-encore-coral/15 text-encore-coral',
    medium: 'bg-stage-indigo/10 text-stage-indigo dark:text-canvas/70',
    low:    'bg-surface text-ink/40 dark:bg-white/5 dark:text-canvas/40',
};

const priorityLabels = { high: 'High', medium: 'Med', low: 'Low' };

function followUpClass(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-').map(Number);
    const due = new Date(y, m - 1, d);
    const today = new Date(); today.setHours(0,0,0,0);
    return due <= today
        ? 'text-encore-coral font-semibold'
        : 'text-ink/45 dark:text-canvas/40';
}

function formatShortDate(iso) {
    if (!iso) return null;
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

const columnHeaderColors = {
    targeting:     'border-amp-violet/40 text-amp-violet dark:text-primary-300',
    contacted:     'border-stage-indigo/40 text-stage-indigo',
    in_discussion: 'border-pending/60 text-pending-text dark:text-pending',
    booked:        'border-confirmed/60 text-confirmed-text dark:text-soundcheck-teal',
    declined:      'border-cancelled/40 text-cancelled-text dark:text-cancelled',
    no_response:   'border-surface text-ink/40 dark:border-white/10 dark:text-canvas/40',
};
</script>

<template>
    <Head :title="season.name" />

    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <Link href="/booking/seasons" class="text-sm text-ink/50 hover:text-ink dark:text-canvas/45 dark:hover:text-canvas">
                    Booking
                </Link>
                <i class="pi pi-angle-right text-xs text-ink/30 dark:text-canvas/30" />
                <span class="text-sm text-ink/70 dark:text-canvas/60">{{ season.name }}</span>
            </div>
            <h2 class="mt-1 font-display text-3xl font-bold tracking-tight truncate">{{ season.name }}</h2>
            <p v-if="season.starts_on || season.ends_on" class="mt-0.5 text-sm text-ink/50 dark:text-canvas/45">
                <span v-if="season.starts_on">{{ formatShortDate(season.starts_on) }}</span>
                <span v-if="season.starts_on && season.ends_on"> – </span>
                <span v-if="season.ends_on">{{ formatShortDate(season.ends_on) }}</span>
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            <!-- Season switcher -->
            <select
                v-if="allSeasons.length > 1"
                class="rounded-xl border border-surface bg-canvas px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                :value="season.id"
                @change="router.visit(`/booking/seasons/${$event.target.value}`)"
            >
                <option v-for="s in allSeasons" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>

            <button
                type="button"
                class="rounded-xl border border-surface px-3 py-2 text-sm font-medium text-ink/70 hover:bg-surface dark:border-white/15 dark:text-canvas/65 dark:hover:bg-white/5"
                @click="showCarryForward = true"
            >
                <i class="pi pi-copy mr-1.5 text-xs" />
                Carry Forward
            </button>
            <button
                type="button"
                class="rounded-xl border border-surface px-3 py-2 text-sm font-medium text-ink/70 hover:bg-surface dark:border-white/15 dark:text-canvas/65 dark:hover:bg-white/5"
                @click="showEditSeason = !showEditSeason"
            >
                <i class="pi pi-pencil text-xs" />
            </button>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-amp-violet px-4 py-2 text-sm font-semibold text-white hover:brightness-105"
                @click="showAddVenue = !showAddVenue"
            >
                <i class="pi pi-plus text-xs" />
                Add Venue
            </button>
        </div>
    </div>

    <!-- Edit season panel -->
    <div
        v-if="showEditSeason"
        class="mt-4 rounded-2xl border border-surface bg-white p-5 shadow-sm dark:border-white/10 dark:bg-riser"
    >
        <h3 class="font-display font-semibold">Edit Season</h3>
        <form class="mt-4 space-y-4" @submit.prevent="updateSeason">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Name</label>
                    <input v-model="editForm.name" type="text" required
                        class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Start date</label>
                    <input v-model="editForm.starts_on" type="date"
                        class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">End date</label>
                    <input v-model="editForm.ends_on" type="date"
                        class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Notes</label>
                <textarea v-model="editForm.notes" rows="2"
                    class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas" />
            </div>
            <div class="flex items-center justify-between">
                <div class="flex gap-3">
                    <button type="submit" :disabled="editForm.processing"
                        class="rounded-xl bg-amp-violet px-4 py-2 text-sm font-semibold text-white hover:brightness-105 disabled:opacity-50">
                        Save
                    </button>
                    <button type="button" @click="showEditSeason = false"
                        class="rounded-xl border border-surface px-4 py-2 text-sm font-medium text-ink/70 hover:bg-surface dark:border-white/15 dark:text-canvas/65 dark:hover:bg-white/5">
                        Cancel
                    </button>
                </div>
                <button type="button" @click="deleteSeason"
                    class="text-sm text-cancelled-text hover:underline dark:text-cancelled">
                    Delete season
                </button>
            </div>
        </form>
    </div>

    <!-- Add venue panel -->
    <div
        v-if="showAddVenue"
        class="mt-4 rounded-2xl border border-amp-violet/30 bg-white p-5 shadow-sm dark:border-primary-500/30 dark:bg-riser"
    >
        <h3 class="font-display font-semibold">Add Venue to Pipeline</h3>
        <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="addVenue">
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Venue</label>
                <select v-model="addForm.venue_id" required
                    class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas">
                    <option value="">Select a venue...</option>
                    <option v-for="v in availableVenues" :key="v.id" :value="v.id">
                        {{ v.name }}{{ v.city ? ` — ${v.city}` : '' }}{{ v.state ? `, ${v.state}` : '' }}
                    </option>
                </select>
                <p v-if="!availableVenues.length" class="mt-1 text-xs text-ink/40 dark:text-canvas/35">
                    All venues are already in this season's pipeline.
                    <Link href="/venues/create" class="text-amp-violet hover:underline">Add a new venue</Link>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Priority</label>
                <select v-model="addForm.priority"
                    class="mt-1 rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas">
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>
            <button type="submit" :disabled="addForm.processing || !addForm.venue_id"
                class="rounded-xl bg-amp-violet px-4 py-2 text-sm font-semibold text-white hover:brightness-105 disabled:opacity-50">
                Add
            </button>
            <button type="button" @click="showAddVenue = false"
                class="rounded-xl border border-surface px-4 py-2 text-sm font-medium text-ink/70 hover:bg-surface dark:border-white/15 dark:text-canvas/65 dark:hover:bg-white/5">
                Cancel
            </button>
        </form>
    </div>

    <!-- Kanban board -->
    <div class="mt-6 overflow-x-auto pb-4">
        <div class="flex gap-4" style="min-width: max-content;">
            <div
                v-for="col in columns"
                :key="col.status"
                class="w-64 shrink-0"
            >
                <!-- Column header -->
                <div
                    class="mb-3 flex items-center justify-between border-b-2 pb-2"
                    :class="columnHeaderColors[col.status]"
                >
                    <span class="text-sm font-semibold uppercase tracking-wide">{{ col.label }}</span>
                    <span class="text-sm font-bold tabular-nums">{{ col.items.length }}</span>
                </div>

                <!-- Cards -->
                <div class="space-y-3">
                    <Link
                        v-for="item in col.items"
                        :key="item.id"
                        :href="`/booking/outreach/${item.id}`"
                        class="block rounded-xl border border-surface bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-white/10 dark:bg-riser"
                    >
                        <p class="truncate font-medium text-sm">{{ item.venueName }}</p>
                        <p v-if="item.venueCity" class="mt-0.5 truncate text-xs text-ink/50 dark:text-canvas/45">
                            {{ item.venueCity }}{{ item.venueState ? `, ${item.venueState}` : '' }}
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                            <!-- Priority badge -->
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                :class="priorityColors[item.priority]"
                            >
                                {{ priorityLabels[item.priority] }}
                            </span>

                            <!-- Contact count -->
                            <span v-if="item.contactCount > 0" class="text-xs text-ink/40 dark:text-canvas/40">
                                <i class="pi pi-comments text-[10px]" />
                                {{ item.contactCount }}
                            </span>
                        </div>

                        <!-- Follow-up date -->
                        <p v-if="item.followUpOn" class="mt-2 text-xs" :class="followUpClass(item.followUpOn)">
                            <i class="pi pi-clock text-[10px]" />
                            Follow up {{ formatShortDate(item.followUpOn) }}
                        </p>

                        <!-- Last contact -->
                        <p v-else-if="item.lastContactOn" class="mt-2 text-xs text-ink/35 dark:text-canvas/35">
                            Last contact {{ formatShortDate(item.lastContactOn) }}
                        </p>
                    </Link>

                    <!-- Empty column placeholder -->
                    <div
                        v-if="!col.items.length"
                        class="rounded-xl border border-dashed border-surface px-4 py-6 text-center text-xs text-ink/30 dark:border-white/10 dark:text-canvas/30"
                    >
                        None yet
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Carry-forward dialog -->
    <div
        v-if="showCarryForward"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="showCarryForward = false"
    >
        <div class="absolute inset-0 bg-ink/40 backdrop-blur-sm" @click="showCarryForward = false" />
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-riser">
            <h3 class="font-display text-lg font-semibold tracking-tight">Carry Forward Venues</h3>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Pick a prior season and select venues to import into <strong>{{ season.name }}</strong>.
                Already-present venues are skipped.
            </p>

            <div class="mt-4">
                <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Copy from season</label>
                <select
                    v-model="carryFromSeasonId"
                    class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                    @change="loadSourceSeason"
                >
                    <option value="">Select a season...</option>
                    <option v-for="s in sourceSeasons" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </div>

            <div v-if="sourceSeasonOutreach.length" class="mt-4 max-h-60 overflow-y-auto space-y-2">
                <label
                    v-for="item in sourceSeasonOutreach"
                    :key="item.id"
                    class="flex cursor-pointer items-center gap-3 rounded-xl border border-surface p-3 hover:bg-canvas dark:border-white/10 dark:hover:bg-white/5"
                    :class="carrySelectedIds.includes(item.id) ? 'border-amp-violet/40 bg-amp-violet/5 dark:border-primary-500/40 dark:bg-primary-500/5' : ''"
                >
                    <input
                        type="checkbox"
                        :checked="carrySelectedIds.includes(item.id)"
                        class="accent-amp-violet"
                        @change="toggleCarryItem(item.id)"
                    />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">{{ item.venueName }}</p>
                        <p class="text-xs text-ink/50 dark:text-canvas/45">
                            {{ item.statusLabel }}
                            <span v-if="item.venueCity"> · {{ item.venueCity }}</span>
                        </p>
                    </div>
                </label>
            </div>

            <p v-else-if="carryFromSeasonId && !sourceSeasonOutreach.length" class="mt-4 text-sm text-ink/50 dark:text-canvas/45">
                That season has no venues in its pipeline.
            </p>

            <div class="mt-5 flex justify-end gap-3">
                <button
                    type="button"
                    class="rounded-xl border border-surface px-4 py-2 text-sm font-medium text-ink/70 hover:bg-surface dark:border-white/15 dark:text-canvas/65 dark:hover:bg-white/5"
                    @click="showCarryForward = false"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="!carrySelectedIds.length"
                    class="rounded-xl bg-amp-violet px-4 py-2 text-sm font-semibold text-white hover:brightness-105 disabled:opacity-50"
                    @click="submitCarryForward"
                >
                    Carry Forward ({{ carrySelectedIds.length }})
                </button>
            </div>
        </div>
    </div>
</template>
