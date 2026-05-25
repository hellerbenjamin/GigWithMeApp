<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

// Persistent layout with a per-page title for the topbar.
export default {
    layout: (_h, page) => h(AppLayout, { title: 'Settings' }, () => page),
};
</script>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    // The active band's current settings, from BandSettingsController@edit.
    band: { type: Object, required: true },
    // Existing genres, offered as type-ahead suggestions.
    genreSuggestions: { type: Array, default: () => [] },
    // Whether the current user (owner/admin) may edit; members view read-only.
    canManage: { type: Boolean, default: false },
});

const form = useForm({
    name: props.band.name ?? '',
    genres: props.band.genres ?? [],
    hometown: props.band.hometown ?? '',
    founded_year: props.band.foundedYear ?? null,
    website: props.band.website ?? '',
    email: props.band.email ?? '',
    description: props.band.description ?? '',
    default_currency: props.band.defaultCurrency ?? 'USD',
});

// Lead with the band's current currency so it's always selectable.
const currencies = [...new Set([form.default_currency, 'USD', 'EUR', 'GBP', 'CAD', 'AUD'])];

// Filter existing genres for the AutoComplete; users can also type a brand-new
// genre and press Enter to add it as a chip (the server creates it on save).
const genreOptions = ref([]);
function searchGenres({ query }) {
    const q = query.trim().toLowerCase();
    const chosen = new Set(form.genres.map((g) => g.toLowerCase()));
    genreOptions.value = props.genreSuggestions.filter(
        (g) => g.toLowerCase().includes(q) && !chosen.has(g.toLowerCase()),
    );
}

const currentYear = new Date().getFullYear();

// Settings sections shown in the left sub-sidebar. Currently just the band
// profile; switching is client-side for now. Add entries here as the feature
// set grows (e.g. notifications, members, billing) and render the matching
// panel in the content column below.
const sections = [
    { key: 'band', icon: 'pi pi-id-card', label: 'Band profile' },
];
const activeSection = ref('band');

function submit() {
    form
        .transform((data) => ({
            ...data,
            // Drop blank optionals so they store as null rather than "".
            hometown: data.hometown || null,
            website: data.website || null,
            email: data.email || null,
            description: data.description || null,
        }))
        .put('/settings');
}
</script>

<template>
    <Head title="Settings" />

    <div class="mb-6">
        <h2 class="font-display text-3xl font-bold tracking-tight">Settings</h2>
    </div>

    <div class="flex flex-col gap-8 lg:flex-row lg:gap-10">
        <!-- Settings sub-navigation -->
        <nav class="lg:w-56 lg:shrink-0">
            <ul class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible">
                <li v-for="section in sections" :key="section.key" class="shrink-0">
                    <button
                        type="button"
                        class="group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                        :class="
                            activeSection === section.key
                                ? 'bg-amp-violet/10 text-amp-violet dark:bg-amp-violet/15'
                                : 'text-ink/70 hover:bg-surface dark:text-canvas/70 dark:hover:bg-white/5'
                        "
                        :aria-current="activeSection === section.key ? 'page' : undefined"
                        @click="activeSection = section.key"
                    >
                        <i :class="section.icon" class="text-base shrink-0" />
                        <span>{{ section.label }}</span>
                    </button>
                </li>
            </ul>
        </nav>

        <!-- Section content -->
        <div class="min-w-0 flex-1">
            <section v-show="activeSection === 'band'" class="mx-auto max-w-2xl">
                <div class="mb-6">
                    <h3 class="font-display text-2xl font-bold tracking-tight">Band profile</h3>
                    <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                        <template v-if="canManage">
                            Update your band's profile, contact details and booking defaults.
                        </template>
                        <template v-else>
                            Your band's details. Only owners and admins can make changes here.
                        </template>
                    </p>
                </div>

                <form
            class="space-y-6 rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8"
            @submit.prevent="submit"
        >
            <!-- Name -->
            <div class="space-y-1.5">
                <label for="name" class="block text-sm font-medium">Band name</label>
                <InputText
                    id="name"
                    v-model="form.name"
                    fluid
                    :disabled="!canManage"
                    placeholder="The Velvet Hours"
                    :invalid="!!form.errors.name"
                />
                <small v-if="form.errors.name" class="text-cancelled">{{ form.errors.name }}</small>
            </div>

            <!-- Genres -->
            <div class="space-y-1.5">
                <label for="genres" class="block text-sm font-medium">
                    Genres <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                </label>
                <AutoComplete
                    input-id="genres"
                    v-model="form.genres"
                    multiple
                    fluid
                    :disabled="!canManage"
                    :suggestions="genreOptions"
                    :typeahead="false"
                    complete-on-focus
                    placeholder="Type a genre and press Enter"
                    :invalid="!!form.errors.genres"
                    @complete="searchGenres"
                />
                <small class="block text-muted dark:text-canvas/45">
                    Pick from the list or add your own.
                </small>
                <small v-if="form.errors.genres" class="text-cancelled">{{ form.errors.genres }}</small>
            </div>

            <!-- Hometown + founding year -->
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="hometown" class="block text-sm font-medium">
                        Hometown <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                    </label>
                    <InputText
                        id="hometown"
                        v-model="form.hometown"
                        fluid
                        :disabled="!canManage"
                        placeholder="Portland, OR"
                        :invalid="!!form.errors.hometown"
                    />
                    <small v-if="form.errors.hometown" class="text-cancelled">{{ form.errors.hometown }}</small>
                </div>

                <div class="space-y-1.5">
                    <label for="founded_year" class="block text-sm font-medium">
                        Founded <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                    </label>
                    <InputNumber
                        input-id="founded_year"
                        v-model="form.founded_year"
                        fluid
                        :disabled="!canManage"
                        :use-grouping="false"
                        :min="1900"
                        :max="currentYear"
                        placeholder="2021"
                        :invalid="!!form.errors.founded_year"
                    />
                    <small v-if="form.errors.founded_year" class="text-cancelled">{{ form.errors.founded_year }}</small>
                </div>
            </div>

            <!-- Website + email -->
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="website" class="block text-sm font-medium">
                        Website <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                    </label>
                    <InputText
                        id="website"
                        v-model="form.website"
                        fluid
                        :disabled="!canManage"
                        placeholder="https://…"
                        :invalid="!!form.errors.website"
                    />
                    <small v-if="form.errors.website" class="text-cancelled">{{ form.errors.website }}</small>
                </div>

                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium">
                        Contact email <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                    </label>
                    <InputText
                        id="email"
                        v-model="form.email"
                        type="email"
                        fluid
                        :disabled="!canManage"
                        placeholder="hello@band.com"
                        :invalid="!!form.errors.email"
                    />
                    <small v-if="form.errors.email" class="text-cancelled">{{ form.errors.email }}</small>
                </div>
            </div>

            <!-- Default currency -->
            <div class="space-y-1.5">
                <label for="default_currency" class="block text-sm font-medium">Default currency</label>
                <Select
                    input-id="default_currency"
                    v-model="form.default_currency"
                    :options="currencies"
                    :disabled="!canManage"
                    fluid
                    :invalid="!!form.errors.default_currency"
                />
                <small class="block text-muted dark:text-canvas/45">
                    Pre-fills the fee currency when you book a new gig.
                </small>
                <small v-if="form.errors.default_currency" class="text-cancelled">{{ form.errors.default_currency }}</small>
            </div>

            <!-- Description -->
            <div class="space-y-1.5">
                <label for="description" class="block text-sm font-medium">
                    Bio <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                </label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    fluid
                    :disabled="!canManage"
                    rows="3"
                    auto-resize
                    placeholder="A sentence or two about the band."
                    :invalid="!!form.errors.description"
                />
                <small v-if="form.errors.description" class="text-cancelled">{{ form.errors.description }}</small>
            </div>

            <!-- Actions -->
            <div
                v-if="canManage"
                class="flex items-center justify-end gap-3 border-t border-surface pt-6 dark:border-white/10"
            >
                <Link
                    href="/dashboard"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-ink/70 transition-colors hover:bg-surface dark:text-canvas/70 dark:hover:bg-white/5"
                >
                    Cancel
                </Link>
                <Button type="submit" label="Save changes" :loading="form.processing" />
            </div>
                </form>
            </section>
        </div>
    </div>
</template>
