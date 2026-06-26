<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

export default {
    layout: (_h, page) =>
        h(GuestLayout, { title: 'Accept your invitation' }, () => page),
};
</script>

<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token: { type: String, required: true },
    memberName: { type: String, required: true },
    bandNames: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.memberName,
});

const bandLabel = computed(() => {
    const names = props.bandNames;
    if (names.length === 0) return 'your band';
    if (names.length === 1) return names[0];
    if (names.length === 2) return `${names[0]} and ${names[1]}`;
    return `${names.slice(0, -1).join(', ')}, and ${names[names.length - 1]}`;
});

function submit() {
    form.post(`/invite/${props.token}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Accept your invitation · GigWithMe" />

    <p class="text-sm text-ink/60 dark:text-canvas/55">
        Hi {{ memberName }}, <strong>{{ bandLabel }}</strong> added you on GigWithMe.
    </p>

    <form class="mt-6 space-y-5" @submit.prevent="submit">
        <div class="space-y-1.5">
            <label for="name" class="block text-sm font-medium">Your name</label>
            <InputText id="name" v-model="form.name" fluid />
            <small v-if="form.errors.name" class="text-encore-coral">{{ form.errors.name }}</small>
        </div>

        <Button
            type="submit"
            label="Accept invitation"
            fluid
            :loading="form.processing"
            :disabled="form.processing"
        />
    </form>
</template>
