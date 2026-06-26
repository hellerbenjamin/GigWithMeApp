<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

export default {
    layout: (_h, page) =>
        h(
            GuestLayout,
            { title: 'Sign in', subtitle: "We'll email you a link, no password needed." },
            () => page,
        ),
};
</script>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const status = computed(() => usePage().props.flash?.status ?? null);

const form = useForm({ email: '' });

function submit() {
    form.post('/login/link');
}
</script>

<template>
    <Head title="Sign in · GigWithMe" />

    <Message v-if="status" severity="success" :closable="false" class="mb-5">
        {{ status }}
    </Message>

    <form v-else class="space-y-5" @submit.prevent="submit">
        <div class="space-y-1.5">
            <label for="email" class="block text-sm font-medium">Email address</label>
            <InputText
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                autofocus
                fluid
                :invalid="!!form.errors.email"
            />
            <small v-if="form.errors.email" class="text-encore-coral">{{ form.errors.email }}</small>
        </div>

        <Button
            type="submit"
            label="Send sign-in link"
            fluid
            :loading="form.processing"
        />

        <p class="text-center text-sm text-ink/50 dark:text-canvas/45">
            <Link href="/login" class="font-medium text-amp-violet hover:underline">
                Sign in with password instead
            </Link>
        </p>
    </form>
</template>
