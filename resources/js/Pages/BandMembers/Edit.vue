<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

// Persistent layout with a per-page title for the topbar.
export default {
    layout: (_h, page) => h(AppLayout, { title: 'Edit member' }, () => page),
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    // The member being edited: { id, name, email, phoneNumber, role }.
    member: { type: Object, required: true },
    // Role options as { value, label }, from BandUserRoleEnum.
    roles: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.member.name ?? '',
    email: props.member.email ?? '',
    phone_number: props.member.phoneNumber ?? '',
    role: props.member.role,
});

function submit() {
    form
        .transform((data) => ({
            ...data,
            name: data.name.trim(),
            email: data.email.trim(),
            phone_number: data.phone_number.trim(),
        }))
        .put(`/band-members/${props.member.id}`);
}
</script>

<template>
    <Head title="Edit a band member" />

    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h2 class="font-display text-3xl font-bold tracking-tight">Edit band member</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Update {{ member.name }}'s contact details and their role in the band. These
                details are part of their GigWithMe account.
            </p>
        </div>

        <form
            class="space-y-8 rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8"
            @submit.prevent="submit"
        >
            <div class="space-y-1.5">
                <label for="name" class="block text-sm font-medium">Name</label>
                <InputText
                    id="name"
                    v-model="form.name"
                    autofocus
                    fluid
                    placeholder="Jordan Reyes"
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
                    fluid
                    placeholder="jordan@band.com"
                    :invalid="!!form.errors.email"
                />
                <small class="block text-muted dark:text-canvas/45">
                    This is the email they sign in with. Changing it changes their login.
                </small>
                <small v-if="form.errors.email" class="text-cancelled">{{ form.errors.email }}</small>
            </div>

            <div class="space-y-1.5">
                <label for="phone_number" class="block text-sm font-medium">
                    Phone number <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                </label>
                <InputText
                    id="phone_number"
                    v-model="form.phone_number"
                    type="tel"
                    fluid
                    placeholder="(555) 123-4567"
                    :invalid="!!form.errors.phone_number"
                />
                <small v-if="form.errors.phone_number" class="text-cancelled">{{ form.errors.phone_number }}</small>
            </div>

            <div class="space-y-1.5">
                <label for="role" class="block text-sm font-medium">Role</label>
                <Select
                    input-id="role"
                    v-model="form.role"
                    :options="roles"
                    option-label="label"
                    option-value="value"
                    fluid
                    :invalid="!!form.errors.role"
                />
                <small class="block text-muted dark:text-canvas/45">
                    Owners and admins can manage the roster and band settings; members can
                    book and view.
                </small>
                <small v-if="form.errors.role" class="text-cancelled">{{ form.errors.role }}</small>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 border-t border-surface pt-6 dark:border-white/10">
                <Link
                    href="/band-members"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-ink/70 transition-colors hover:bg-surface dark:text-canvas/70 dark:hover:bg-white/5"
                >
                    Cancel
                </Link>
                <Button type="submit" label="Save changes" :loading="form.processing" />
            </div>
        </form>
    </div>
</template>
