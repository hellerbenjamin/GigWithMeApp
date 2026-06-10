<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

// Reuse the centered guest card + GigWithMe wordmark. Reached via a magic link
// by someone who isn't logged in, so it must not use AppLayout.
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
    // The band(s) that have this person on their roster.
    bandNames: { type: Array, default: () => [] },
    phoneNumber: { type: String, default: null },
    // Exact opt-in wording, shown verbatim next to the checkbox.
    optInText: { type: String, required: true },
});

const form = useForm({
    name: props.memberName,
    phone_number: props.phoneNumber ?? '',
    opt_in: false,
});

const bandLabel = computed(() => {
    const names = props.bandNames;
    if (names.length === 0) return 'your band';
    if (names.length === 1) return names[0];
    if (names.length === 2) return `${names[0]} and ${names[1]}`;
    return `${names.slice(0, -1).join(', ')}, and ${names[names.length - 1]}`;
});

// A member already in another band arrives with a number on file. Show it back
// to them, formatted, so they can confirm or update it instead of retyping.
const hasNumberOnFile = computed(() => Boolean(props.phoneNumber));

const formattedPhone = computed(() => {
    const raw = props.phoneNumber;
    if (!raw) return '';
    // Pretty-print a US +1XXXXXXXXXX as (XXX) XXX-XXXX; otherwise show as stored.
    const match = raw.match(/^\+1(\d{3})(\d{3})(\d{4})$/);
    return match ? `(${match[1]}) ${match[2]}-${match[3]}` : raw;
});

function submit() {
    form.post(`/invite/${props.token}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Accept your invitation · GigWithMe" />

    <p class="text-sm text-ink/60 dark:text-canvas/55">
        Hi {{ memberName }}, <strong>{{ bandLabel }}</strong> added you on GigWithMe.
        Set up how you'd like to hear about gigs.
    </p>

    <form class="mt-6 space-y-5" @submit.prevent="submit">
        <div class="space-y-1.5">
            <label for="name" class="block text-sm font-medium">Your name</label>
            <InputText id="name" v-model="form.name" fluid />
            <small v-if="form.errors.name" class="text-encore-coral">{{ form.errors.name }}</small>
        </div>

        <!-- SMS opt-in. Unchecked by default: ticking it is the consent event. -->
        <div class="rounded-xl bg-surface/60 p-4 dark:bg-white/5">
            <label class="flex items-start gap-3">
                <Checkbox v-model="form.opt_in" :binary="true" input-id="opt_in" class="mt-0.5" />
                <span class="text-sm leading-relaxed text-ink/80 dark:text-canvas/75">
                    {{ optInText }}
                </span>
            </label>

            <p
                v-if="hasNumberOnFile && !form.opt_in"
                class="mt-3 text-sm text-ink/70 dark:text-canvas/65"
            >
                We have your mobile number as <strong>{{ formattedPhone }}</strong>.
                Tick the box to confirm or update it.
            </p>

            <div v-if="form.opt_in" class="mt-4 space-y-1.5">
                <label for="phone" class="block text-sm font-medium">
                    {{ hasNumberOnFile ? 'Confirm or update your mobile number' : 'Mobile number' }}
                </label>
                <InputText
                    id="phone"
                    v-model="form.phone_number"
                    fluid
                    inputmode="tel"
                    autocomplete="tel"
                    placeholder="(555) 123-4567"
                />
                <small
                    v-if="hasNumberOnFile && !form.errors.phone_number"
                    class="text-muted dark:text-canvas/45"
                >
                    This is the number we have on file. Edit it if it's changed.
                </small>
                <small v-if="form.errors.phone_number" class="text-encore-coral">
                    {{ form.errors.phone_number }}
                </small>
            </div>
        </div>

        <p class="text-xs text-muted dark:text-canvas/45">
            You don't have to opt in to texts. Either way you'll be on the roster and
            can get gig updates by email. See our
            <a href="/privacy" class="text-amp-violet underline">Privacy Policy</a> and
            <a href="/terms" class="text-amp-violet underline">Terms</a>.
        </p>

        <Button
            type="submit"
            label="Accept invitation"
            fluid
            :loading="form.processing"
            :disabled="form.processing"
        />
    </form>
</template>
