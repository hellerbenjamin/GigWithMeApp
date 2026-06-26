<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    band: { type: Object, required: true },
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
});

const title = computed(() => `${props.band.name} — Upcoming Gigs`);

function formatDate(iso) {
    return new Date(iso + 'T00:00:00').toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function venueLabel(gig) {
    if (!gig.venue) return null;
    const parts = [gig.venue.name, gig.venue.city, gig.venue.state].filter(Boolean);
    return parts.join(', ');
}
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-canvas dark:bg-riser">
        <!-- Header -->
        <header class="border-b border-black/8 bg-canvas dark:border-white/8 dark:bg-riser">
            <div class="mx-auto max-w-2xl px-4 py-6 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-ink dark:text-canvas">
                            {{ band.name }}
                        </h1>
                        <p v-if="band.hometown" class="mt-0.5 text-sm text-ink/50 dark:text-canvas/45">
                            {{ band.hometown }}
                        </p>
                    </div>
                    <a
                        v-if="band.website"
                        :href="band.website"
                        target="_blank"
                        rel="noopener"
                        class="text-sm text-amp-violet hover:underline"
                    >
                        Website
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
            <!-- Upcoming gigs -->
            <section>
                <h2 class="mb-4 text-xs font-semibold uppercase tracking-widest text-ink/40 dark:text-canvas/35">
                    Upcoming
                </h2>

                <div v-if="upcoming.length === 0" class="rounded-xl border border-black/8 p-6 text-center text-sm text-ink/50 dark:border-white/8 dark:text-canvas/45">
                    No upcoming gigs scheduled yet.
                </div>

                <ul v-else class="space-y-2">
                    <li
                        v-for="gig in upcoming"
                        :key="gig.id"
                        class="flex gap-4 rounded-xl border border-black/8 p-4 dark:border-white/8"
                    >
                        <!-- Date block -->
                        <div class="flex w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-amp-violet/10 py-2 text-amp-violet dark:bg-amp-violet/15">
                            <span class="text-xs font-medium uppercase leading-none">
                                {{ new Date(gig.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short' }) }}
                            </span>
                            <span class="mt-0.5 text-2xl font-bold leading-none">
                                {{ new Date(gig.date + 'T00:00:00').getDate() }}
                            </span>
                        </div>

                        <!-- Details -->
                        <div class="min-w-0 flex-1 py-1">
                            <p class="text-sm font-medium text-ink dark:text-canvas">
                                {{ gig.name || formatDate(gig.date) }}
                            </p>
                            <p v-if="gig.name" class="text-xs text-ink/50 dark:text-canvas/45">
                                {{ formatDate(gig.date) }}
                            </p>
                            <p v-if="venueLabel(gig)" class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                                {{ venueLabel(gig) }}
                            </p>
                            <p v-if="gig.start_time" class="mt-0.5 text-xs text-ink/45 dark:text-canvas/40">
                                {{ gig.start_time }}
                            </p>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Past gigs -->
            <section v-if="past.length > 0" class="mt-10">
                <h2 class="mb-4 text-xs font-semibold uppercase tracking-widest text-ink/40 dark:text-canvas/35">
                    Past
                </h2>

                <ul class="space-y-2">
                    <li
                        v-for="gig in past"
                        :key="gig.id"
                        class="flex gap-4 rounded-xl border border-black/8 p-4 opacity-60 dark:border-white/8"
                    >
                        <div class="flex w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-ink/5 py-2 text-ink/50 dark:bg-white/5 dark:text-canvas/45">
                            <span class="text-xs font-medium uppercase leading-none">
                                {{ new Date(gig.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short' }) }}
                            </span>
                            <span class="mt-0.5 text-2xl font-bold leading-none">
                                {{ new Date(gig.date + 'T00:00:00').getDate() }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1 py-1">
                            <p class="text-sm font-medium text-ink dark:text-canvas">
                                {{ gig.name || formatDate(gig.date) }}
                            </p>
                            <p v-if="gig.name" class="text-xs text-ink/50 dark:text-canvas/45">
                                {{ formatDate(gig.date) }}
                            </p>
                            <p v-if="venueLabel(gig)" class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                                {{ venueLabel(gig) }}
                            </p>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Footer branding -->
            <p class="mt-12 text-center text-xs text-ink/30 dark:text-canvas/25">
                Powered by
                <a href="https://gigwith.me" class="hover:text-amp-violet">GigWithMe</a>
            </p>
        </main>
    </div>
</template>
