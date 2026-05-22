<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

export default {
    layout: (_h, page) =>
        h(
            GuestLayout,
            { title: 'Create your account', subtitle: 'Start booking gigs in minutes.' },
            () => page,
        ),
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Register" />

    <form class="space-y-5" @submit.prevent="submit">
        <div class="space-y-1.5">
            <label for="name" class="block text-sm font-medium">Name</label>
            <InputText
                id="name"
                v-model="form.name"
                autocomplete="name"
                autofocus
                fluid
                :invalid="!!form.errors.name"
            />
            <small v-if="form.errors.name" class="text-cancelled">{{ form.errors.name }}</small>
        </div>

        <div class="space-y-1.5">
            <label for="email" class="block text-sm font-medium">Email</label>
            <InputText
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
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
                toggle-mask
                fluid
                class="w-full"
                autocomplete="new-password"
                :invalid="!!form.errors.password"
            />
            <small v-if="form.errors.password" class="text-cancelled">{{ form.errors.password }}</small>
        </div>

        <div class="space-y-1.5">
            <label for="password_confirmation" class="block text-sm font-medium">Confirm password</label>
            <Password
                input-id="password_confirmation"
                v-model="form.password_confirmation"
                :feedback="false"
                toggle-mask
                fluid
                class="w-full"
                autocomplete="new-password"
            />
        </div>

        <Button type="submit" label="Create account" fluid :loading="form.processing" />

        <p class="text-center text-sm text-muted dark:text-canvas/60">
            Already have an account?
            <Link href="/login" class="font-medium text-amp-violet hover:underline">
                Log in
            </Link>
        </p>
    </form>
</template>
