<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

// Persistent layout with a per-page title for the topbar.
export default {
    layout: (_h, page) => h(AppLayout, { title: 'New band' }, () => page),
};
</script>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    // Existing genres, offered as type-ahead suggestions.
    genreSuggestions: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    genres: [],
    hometown: '',
    founded_year: null,
    website: '',
    email: '',
    description: '',
});

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
        .post('/bands');
}
</script>

<template>
    <Head title="Start a band" />

    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h2 class="font-display text-3xl font-bold tracking-tight">Start a band</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Name it and you're in — everything else you can flesh out later. You'll be
                set as the owner and switched into it automatically.
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
                    autofocus
                    fluid
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
                        placeholder="hello@band.com"
                        :invalid="!!form.errors.email"
                    />
                    <small v-if="form.errors.email" class="text-cancelled">{{ form.errors.email }}</small>
                </div>
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
                    rows="3"
                    auto-resize
                    placeholder="A sentence or two about the band."
                    :invalid="!!form.errors.description"
                />
                <small v-if="form.errors.description" class="text-cancelled">{{ form.errors.description }}</small>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 border-t border-surface pt-6 dark:border-white/10">
                <Link
                    href="/dashboard"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-ink/70 transition-colors hover:bg-surface dark:text-canvas/70 dark:hover:bg-white/5"
                >
                    Cancel
                </Link>
                <Button type="submit" label="Create band" :loading="form.processing" />
            </div>
        </form>
    </div>
</template>
