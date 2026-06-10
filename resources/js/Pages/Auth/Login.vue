<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

// Persistent guest layout — centered card with the GigWithMe wordmark.
export default {
    layout: (_h, page) =>
        h(
            GuestLayout,
            { title: 'Welcome back', subtitle: "Log in and get the band's dates on the books." },
            () => page,
        ),
};
</script>

<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: '' },
});

// Surfaces post-redirect notices like the invite-accepted confirmation.
const flashSuccess = computed(() => usePage().props.flash?.success ?? null);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Log in" />

    <Message v-if="status" severity="success" :closable="false" class="mb-5">
        {{ status }}
    </Message>
    <Message v-else-if="flashSuccess" severity="success" :closable="false" class="mb-5">
        {{ flashSuccess }}
    </Message>

    <form class="space-y-5" @submit.prevent="submit">
        <div class="space-y-1.5">
            <label for="email" class="block text-sm font-medium">Email</label>
            <InputText
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                autofocus
                fluid
                :invalid="!!form.errors.email"
            />
            <small v-if="form.errors.email" class="text-cancelled">{{ form.errors.email }}</small>
        </div>

        <div class="space-y-1.5">
            <label for="password" class="block text-sm font-medium">Password</label>
            <Password
                input-id="password"
                v-model="form.password"
                :feedback="false"
                toggle-mask
                fluid
                class="w-full"
                autocomplete="current-password"
                :invalid="!!form.errors.password"
            />
            <small v-if="form.errors.password" class="text-cancelled">{{ form.errors.password }}</small>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <Checkbox v-model="form.remember" input-id="remember" binary />
            Remember me
        </label>

        <Button type="submit" label="Log in" fluid :loading="form.processing" />

        <p class="text-center text-sm text-muted dark:text-canvas/60">
            New to GigWithMe?
            <Link href="/register" class="font-medium text-amp-violet hover:underline">
                Create an account
            </Link>
        </p>
    </form>
</template>
