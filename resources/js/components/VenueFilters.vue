<script setup>
import { computed, ref } from 'vue';

// The search / filter / sort bar above the venue grid. Controlled by the page:
// it holds the live input state, debounces typing, and emits the full filter
// set on every change. The page owns turning that into an Inertia visit.
const props = defineProps({
    filters: { type: Object, required: true }, // { search, country, state, has_contact, sort }
    filterOptions: { type: Object, default: () => ({ countries: [], states: [] }) },
});

const emit = defineEmits(['change']);

const search = ref(props.filters.search ?? '');
const country = ref(props.filters.country ?? null);
const state = ref(props.filters.state ?? null);
const hasContact = ref(props.filters.has_contact ?? false);
const sort = ref(props.filters.sort ?? 'name');

const sortOptions = [
    { label: 'Name (A–Z)', value: 'name' },
    { label: 'Recently added', value: 'recent' },
    { label: 'City', value: 'city' },
];

function emitChange() {
    emit('change', {
        search: search.value || null,
        country: country.value || null,
        state: state.value || null,
        has_contact: hasContact.value,
        sort: sort.value,
    });
}

// Typing shouldn't fire a request per keystroke.
let searchTimer;
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(emitChange, 300);
}

// Switching country invalidates a state from the previous country.
function onCountryChange() {
    state.value = null;
    emitChange();
}

const hasActiveFilters = computed(
    () => !!search.value || !!country.value || !!state.value || hasContact.value || sort.value !== 'name'
);

function clearAll() {
    clearTimeout(searchTimer);
    search.value = '';
    country.value = null;
    state.value = null;
    hasContact.value = false;
    sort.value = 'name';
    emitChange();
}
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <!-- Search -->
        <IconField class="w-full sm:max-w-xs sm:flex-1">
            <InputIcon class="pi pi-search" />
            <InputText
                v-model="search"
                placeholder="Search venues…"
                fluid
                aria-label="Search venues"
                @input="onSearchInput"
            />
        </IconField>

        <!-- Country -->
        <Select
            v-model="country"
            :options="filterOptions.countries"
            placeholder="All countries"
            show-clear
            aria-label="Filter by country"
            class="w-full sm:w-44"
            @change="onCountryChange"
        />

        <!-- State (cascades off country) -->
        <Select
            v-model="state"
            :options="filterOptions.states"
            :disabled="!filterOptions.states.length"
            placeholder="All states"
            show-clear
            aria-label="Filter by state"
            class="w-full sm:w-40"
            @change="emitChange"
        />

        <!-- Has contact -->
        <ToggleButton
            v-model="hasContact"
            on-label="Has contact"
            off-label="Has contact"
            on-icon="pi pi-check"
            off-icon="pi pi-user"
            aria-label="Only venues with contact info"
            class="w-full sm:w-auto"
            @change="emitChange"
        />

        <!-- Sort -->
        <Select
            v-model="sort"
            :options="sortOptions"
            option-label="label"
            option-value="value"
            aria-label="Sort venues"
            class="w-full sm:ml-auto sm:w-44"
            @change="emitChange"
        />

        <!-- Clear -->
        <button
            v-if="hasActiveFilters"
            type="button"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-ink/55 transition-colors hover:text-amp-violet dark:text-canvas/50 dark:hover:text-primary-300"
            @click="clearAll"
        >
            <i class="pi pi-times text-xs" />
            Clear filters
        </button>
    </div>
</template>
