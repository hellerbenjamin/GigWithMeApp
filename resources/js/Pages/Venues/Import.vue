<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

// Persistent layout with a per-page title for the topbar.
export default {
    layout: (_h, page) => h(AppLayout, { title: 'Import venues' }, () => page),
};
</script>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ file: null });

// Highlights the dropzone while a file is dragged over it.
const dragging = ref(false);

function take(fileList) {
    form.file = fileList?.[0] ?? null;
}

function onDrop(event) {
    dragging.value = false;
    take(event.dataTransfer?.files);
}

function submit() {
    // A File in the payload makes Inertia send multipart automatically.
    form.post('/venues/import');
}
</script>

<template>
    <Head title="Import venues" />

    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h2 class="font-display text-3xl font-bold tracking-tight">Import venues</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Bring your whole venue book over at once. Upload a CSV exported from a
                spreadsheet — you'll match its columns to venue fields on the next step,
                so the headings don't have to be perfect.
            </p>
        </div>

        <form
            class="space-y-6 rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8"
            @submit.prevent="submit"
        >
            <!-- Dropzone -->
            <label
                class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-12 text-center transition-colors"
                :class="[
                    dragging
                        ? 'border-amp-violet bg-amp-violet/5 dark:border-primary-400'
                        : 'border-surface hover:border-amp-violet/50 dark:border-white/15 dark:hover:border-primary-400/50',
                    form.errors.file ? 'border-cancelled' : '',
                ]"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
            >
                <input
                    type="file"
                    accept=".csv,text/csv,text/plain"
                    class="sr-only"
                    @change="take($event.target.files)"
                />
                <span class="grid size-12 place-items-center rounded-full bg-encore-coral/15 text-encore-coral">
                    <i class="pi pi-file-import text-lg" />
                </span>

                <template v-if="form.file">
                    <p class="mt-4 text-sm font-medium">{{ form.file.name }}</p>
                    <p class="text-sm text-ink/50 dark:text-canvas/45">Click to choose a different file</p>
                </template>
                <template v-else>
                    <p class="mt-4 text-sm font-medium">Drop a CSV here, or click to browse</p>
                    <p class="text-sm text-ink/50 dark:text-canvas/45">.csv up to 5 MB</p>
                </template>
            </label>

            <p v-if="form.progress" class="text-sm text-ink/55 dark:text-canvas/50">
                Uploading… {{ form.progress.percentage }}%
            </p>
            <small v-if="form.errors.file" class="block text-cancelled">{{ form.errors.file }}</small>

            <!-- What a good file looks like -->
            <div class="rounded-xl bg-surface/60 px-4 py-3 text-sm text-ink/60 dark:bg-white/5 dark:text-canvas/55">
                <p class="font-medium text-ink dark:text-canvas">First row should be column headings.</p>
                <p class="mt-1">
                    A <span class="font-medium">Venue name</span> column is required; address, city,
                    contact details and notes are all optional. Existing venues with a matching name
                    are updated rather than duplicated.
                </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 border-t border-surface pt-6 dark:border-white/10">
                <Link
                    href="/venues"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-ink/70 transition-colors hover:bg-surface dark:text-canvas/70 dark:hover:bg-white/5"
                >
                    Cancel
                </Link>
                <Button
                    type="submit"
                    label="Continue"
                    icon="pi pi-arrow-right"
                    icon-pos="right"
                    :disabled="!form.file"
                    :loading="form.processing"
                />
            </div>
        </form>
    </div>
</template>
