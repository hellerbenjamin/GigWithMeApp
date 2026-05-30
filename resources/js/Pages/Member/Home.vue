<script>
import { h } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

// Login-free personal home, reached via the member's durable push_token. Uses
// the guest shell (no AppLayout) since the visitor isn't authenticated.
export default {
    layout: (_h, page) => h(GuestLayout, { title: 'Your gigs' }, () => page),
};
</script>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PushOptIn from '../../components/PushOptIn.vue';

const props = defineProps({
    pushToken: { type: String, required: true },
    memberName: { type: String, required: true },
    // Upcoming gigs across every band the member is in.
    gigs: { type: Array, default: () => [] },
});

const firstName = computed(() => props.memberName.split(' ')[0]);

const statusLabel = {
    confirmed: 'Confirmed',
    pending: 'Awaiting replies',
};

function formatDate(ymd) {
    // Parse as local date (no timezone shift) and format compactly.
    const [y, m, d] = ymd.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Your gigs">
        <!-- Personalized manifest so an installed iOS PWA relaunches already
             identified by this token — the only way push opt-in works on iOS
             without a login. Overrides the generic manifest from the Blade head. -->
        <link rel="manifest" :href="`/m/${pushToken}/manifest.webmanifest`" />
    </Head>

    <p class="text-sm text-ink/60 dark:text-canvas/55">
        Hey {{ firstName }} — here's what's coming up.
    </p>

    <!-- Push opt-in up top: the whole point of this page on a phone. -->
    <div class="mt-5">
        <PushOptIn :push-token="pushToken" />
    </div>

    <!-- Upcoming gigs -->
    <div class="mt-6">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-ink/45 dark:text-canvas/40">
            Upcoming
        </h2>

        <ul v-if="gigs.length" class="mt-3 space-y-2">
            <li
                v-for="gig in gigs"
                :key="gig.id"
                class="rounded-xl bg-surface/60 p-4 dark:bg-white/5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium">
                            {{ gig.venue ?? gig.name ?? 'A gig' }}
                        </p>
                        <p class="mt-0.5 text-sm text-ink/55 dark:text-canvas/50">
                            {{ gig.band }} · {{ formatDate(gig.date) }}
                            <span v-if="gig.startTime"> · {{ gig.startTime }}</span>
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="
                            gig.status === 'confirmed'
                                ? 'bg-confirmed/15 text-confirmed-text dark:text-confirmed'
                                : 'bg-pending/15 text-pending-text dark:text-pending'
                        "
                    >
                        {{ statusLabel[gig.status] ?? gig.status }}
                    </span>
                </div>
            </li>
        </ul>

        <p v-else class="mt-3 rounded-xl bg-surface/60 px-4 py-6 text-center text-sm text-ink/55 dark:bg-white/5 dark:text-canvas/50">
            No gigs on the calendar yet. We'll let you know when one's booked.
        </p>
    </div>
</template>
