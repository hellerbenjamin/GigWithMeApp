<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

// Reuse the centered guest card + GigWithMe wordmark. This page is reached via a
// magic link by someone who may not be logged in, so it must not use AppLayout.
export default {
    layout: (_h, page) =>
        h(GuestLayout, { title: 'Can you make it?' }, () => page),
};
</script>

<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PushOptIn from '../../components/PushOptIn.vue';

const props = defineProps({
    token: { type: String, required: true },
    // Durable per-member token for login-free push opt-in on this page.
    pushToken: { type: String, default: null },
    memberName: { type: String, required: true },
    bandName: { type: String, required: true },
    gig: { type: Object, required: true },
    // The member's current reply: { status: pending|available|unavailable, note }.
    response: { type: Object, required: true },
    // True once the gig is settled (no longer pending) — show it read-only.
    closed: { type: Boolean, default: false },
    gigStatus: { type: String, default: 'pending' },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const answered = computed(() => props.response.status !== 'pending');
const isAvailable = computed(() => props.response.status === 'available');

const form = useForm({
    available: null,
    note: props.response.note ?? '',
});

function reply(available) {
    form.available = available;
    form.post(`/rsvp/${props.token}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`RSVP · ${bandName}`" />

    <p class="text-sm text-ink/60 dark:text-canvas/55">
        Hey {{ memberName }} — <strong>{{ bandName }}</strong> wants to know if you can play this one.
    </p>

    <!-- Gig details -->
    <dl class="mt-5 space-y-3 rounded-xl bg-surface/60 p-4 text-sm dark:bg-white/5">
        <div class="flex justify-between gap-4">
            <dt class="text-ink/50 dark:text-canvas/45">When</dt>
            <dd class="text-right font-medium">{{ gig.date }}</dd>
        </div>
        <div v-if="gig.startTime" class="flex justify-between gap-4">
            <dt class="text-ink/50 dark:text-canvas/45">Set time</dt>
            <dd class="text-right font-medium">{{ gig.startTime }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-ink/50 dark:text-canvas/45">Where</dt>
            <dd class="text-right font-medium">{{ gig.venue ?? 'To be decided' }}</dd>
        </div>
        <div v-if="gig.name && gig.venue" class="flex justify-between gap-4">
            <dt class="text-ink/50 dark:text-canvas/45">Gig</dt>
            <dd class="text-right font-medium">{{ gig.name }}</dd>
        </div>
    </dl>

    <!-- Flash -->
    <Message v-if="flash.success" severity="success" :closable="false" class="mt-5">
        {{ flash.success }}
    </Message>
    <Message v-else-if="flash.info" severity="info" :closable="false" class="mt-5">
        {{ flash.info }}
    </Message>

    <!-- Settled: poll is closed, show read-only outcome -->
    <div v-if="closed" class="mt-6">
        <p class="rounded-lg bg-surface px-3 py-2.5 text-sm text-ink/70 dark:bg-white/5 dark:text-canvas/70">
            <template v-if="gigStatus === 'confirmed'">This gig is confirmed — see you there!</template>
            <template v-else-if="gigStatus === 'cancelled'">This gig was cancelled.</template>
            <template v-else>The band is finalizing this one. Thanks for replying!</template>
        </p>
        <p v-if="answered" class="mt-3 text-center text-sm text-ink/55 dark:text-canvas/50">
            Your reply: <strong>{{ isAvailable ? "you're in" : "you couldn't make it" }}</strong>.
        </p>
    </div>

    <!-- Open: take the reply -->
    <div v-else class="mt-6 space-y-4">
        <p v-if="answered" class="text-center text-sm text-ink/55 dark:text-canvas/50">
            You said <strong>{{ isAvailable ? "you're in" : "you can't make it" }}</strong>.
            Changed your mind? Update below.
        </p>

        <div class="space-y-1.5">
            <label for="note" class="block text-sm font-medium">
                Add a note <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
            </label>
            <Textarea
                id="note"
                v-model="form.note"
                fluid
                rows="2"
                auto-resize
                placeholder="e.g. can do it if we start after 9"
            />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <Button
                label="I'm in"
                icon="pi pi-check"
                severity="success"
                :loading="form.processing && form.available === true"
                :disabled="form.processing"
                @click="reply(true)"
            />
            <Button
                label="Can't make it"
                icon="pi pi-times"
                severity="danger"
                outlined
                :loading="form.processing && form.available === false"
                :disabled="form.processing"
                @click="reply(false)"
            />
        </div>
    </div>

    <!-- Push opt-in: once they've engaged with one gig, offer instant alerts for
         the next ones — no login, tied to their durable token. -->
    <div v-if="pushToken && answered" class="mt-6">
        <PushOptIn :push-token="pushToken" compact />
    </div>
</template>
