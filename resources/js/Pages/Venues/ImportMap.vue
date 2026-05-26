<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Map columns' }, () => page),
};
</script>

<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token: { type: String, required: true },
    filename: { type: String, default: '' },
    fields: { type: Array, default: () => [] },
    headers: { type: Array, default: () => [] },
    preview: { type: Array, default: () => [] },
    rowCount: { type: Number, default: 0 },
    mapping: { type: Object, default: () => ({}) },
});

// Seed the form with the server's best-guess mapping; the user adjusts from there.
const form = useForm({ mapping: { ...props.mapping } });

// "— Skip —" plus one option per CSV column, labelled by its heading.
const columnOptions = computed(() => [
    { label: '— Skip this field —', value: null },
    ...props.headers.map((header, index) => ({
        label: header || `Column ${index + 1}`,
        value: index,
    })),
]);

// Two fields pointing at the same column is almost always a mistake worth a nudge.
const duplicateColumns = computed(() => {
    const counts = {};
    Object.values(form.mapping).forEach((column) => {
        if (column !== null && column !== undefined) {
            counts[column] = (counts[column] ?? 0) + 1;
        }
    });
    return Object.entries(counts)
        .filter(([, count]) => count > 1)
        .map(([column]) => props.headers[Number(column)] || `Column ${Number(column) + 1}`);
});

function submit() {
    form
        // Send only fields the user actually mapped to a column.
        .transform((data) => ({
            mapping: Object.fromEntries(
                Object.entries(data.mapping).filter(([, column]) => column !== null && column !== undefined),
            ),
        }))
        .post(`/venues/import/${props.token}`);
}
</script>

<template>
    <Head title="Map columns" />

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h2 class="font-display text-3xl font-bold tracking-tight">Map your columns</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Tell us which column in
                <span class="font-medium text-ink dark:text-canvas">{{ filename }}</span>
                feeds each venue field. We've guessed from the headings — adjust anything
                that looks off. <span class="font-medium">{{ rowCount }}</span>
                {{ rowCount === 1 ? 'row' : 'rows' }} ready to import.
            </p>
        </div>

        <form
            class="space-y-8 rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8"
            @submit.prevent="submit"
        >
            <!-- Field → column mapping -->
            <fieldset class="space-y-4">
                <legend class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    Venue field → CSV column
                </legend>

                <div
                    v-for="field in fields"
                    :key="field.key"
                    class="grid grid-cols-1 items-center gap-2 sm:grid-cols-[1fr_1.4fr] sm:gap-4"
                >
                    <label :for="`map-${field.key}`" class="text-sm font-medium">
                        {{ field.label }}
                        <span v-if="field.required" class="text-cancelled">*</span>
                        <span v-else class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                    </label>
                    <Select
                        :input-id="`map-${field.key}`"
                        v-model="form.mapping[field.key]"
                        :options="columnOptions"
                        option-label="label"
                        option-value="value"
                        fluid
                        :invalid="field.required && !!form.errors['mapping.name']"
                    />
                </div>

                <small v-if="form.errors['mapping.name']" class="block text-cancelled">
                    {{ form.errors['mapping.name'] }}
                </small>
            </fieldset>

            <!-- Heads-up if one column is feeding two fields -->
            <Message v-if="duplicateColumns.length" severity="warn" :closable="false">
                {{ duplicateColumns.join(', ') }}
                {{ duplicateColumns.length === 1 ? 'is' : 'are' }} mapped to more than one field.
                That's allowed, but double-check it's what you want.
            </Message>

            <!-- Preview of the source file -->
            <div v-if="preview.length" class="space-y-3 border-t border-surface pt-6 dark:border-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    First {{ preview.length }} {{ preview.length === 1 ? 'row' : 'rows' }}
                </p>
                <div class="overflow-x-auto rounded-xl border border-surface dark:border-white/10">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-surface/60 dark:bg-white/5">
                            <tr>
                                <th
                                    v-for="(header, index) in headers"
                                    :key="index"
                                    class="whitespace-nowrap px-3 py-2 font-medium text-ink/70 dark:text-canvas/65"
                                >
                                    {{ header || `Column ${index + 1}` }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, rowIndex) in preview"
                                :key="rowIndex"
                                class="border-t border-surface dark:border-white/10"
                            >
                                <td
                                    v-for="(header, colIndex) in headers"
                                    :key="colIndex"
                                    class="max-w-[16rem] truncate px-3 py-2 text-ink/60 dark:text-canvas/55"
                                >
                                    {{ row[colIndex] }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 border-t border-surface pt-6 dark:border-white/10">
                <Link
                    href="/venues/import"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-ink/70 transition-colors hover:bg-surface dark:text-canvas/70 dark:hover:bg-white/5"
                >
                    Upload a different file
                </Link>
                <Button
                    type="submit"
                    :label="`Import ${rowCount} ${rowCount === 1 ? 'row' : 'rows'}`"
                    icon="pi pi-check"
                    :loading="form.processing"
                />
            </div>
        </form>
    </div>
</template>
