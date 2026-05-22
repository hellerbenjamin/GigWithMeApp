<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppSidebar from '../components/AppSidebar.vue';
import AppTopbar from '../components/AppTopbar.vue';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();

// Shared Inertia props. auth.user is wired today; activeBand/bands arrive as
// page props for now and will move to HandleInertiaRequests::share once the
// BandSessionService is ported (docs/legacy-app-features.md §2).
const user = computed(() => page.props.auth?.user ?? null);
const activeBand = computed(() => page.props.activeBand ?? null);
const bands = computed(() => page.props.bands ?? []);

// Flash messages → PrimeVue Message severities.
const flashes = computed(() => {
    const flash = page.props.flash ?? {};
    const map = { success: 'success', error: 'error', warning: 'warn', info: 'info' };
    return Object.entries(map)
        .filter(([key]) => flash[key])
        .map(([key, severity]) => ({ key, severity, text: flash[key] }));
});

const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-full">
        <AppSidebar
            :active-band="activeBand"
            :bands="bands"
            :open="sidebarOpen"
            @close="sidebarOpen = false"
        />

        <!-- Content column sits to the right of the fixed sidebar on desktop. -->
        <div class="lg:pl-72">
            <AppTopbar :title="title" :user="user" @toggle-sidebar="sidebarOpen = true" />

            <main class="mx-auto max-w-6xl px-4 py-6 lg:px-8 lg:py-8">
                <div v-if="flashes.length" class="mb-6 space-y-3">
                    <Message
                        v-for="flash in flashes"
                        :key="flash.key"
                        :severity="flash.severity"
                        closable
                    >
                        {{ flash.text }}
                    </Message>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
