<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    activeBand: { type: Object, default: null }, // { id, name, genre, role }
    bands: { type: Array, default: () => [] }, // [{ id, name, genre, role }]
});

const op = ref(null);
const toggle = (event) => op.value.toggle(event);

function setActive(bandId) {
    op.value.hide();
    // Thin Inertia POST — the BandSessionService will own this server-side once
    // the active-band backend lands (docs/legacy-app-features.md §2).
    router.post(`/bands/${bandId}/set-active`);
}

// Role → swatch. Owner leans on the brand violet, admin on teal, member stays
// quiet so the role reads at a glance without shouting.
const roleClass = {
    owner: 'bg-amp-violet/15 text-amp-violet dark:text-primary-300',
    admin: 'bg-soundcheck-teal/15 text-confirmed-text dark:text-soundcheck-teal',
    member: 'bg-ink/8 text-muted dark:bg-white/10 dark:text-canvas/70',
};
</script>

<template>
    <div>
        <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-left transition-colors hover:bg-white/10"
            @click="toggle"
        >
            <span
                class="grid size-9 shrink-0 place-items-center rounded-lg bg-amp-violet font-display text-base font-semibold text-white"
            >
                {{ activeBand?.name?.charAt(0) ?? '?' }}
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-white">
                    {{ activeBand?.name ?? 'No band selected' }}
                </span>
                <span class="block truncate text-xs text-canvas/55">
                    {{ activeBand?.genre ?? 'Pick a band to get going' }}
                </span>
            </span>
            <i class="pi pi-angle-down text-xs text-canvas/55" />
        </button>

        <Popover ref="op" :pt="{ root: 'w-72' }">
            <div class="flex flex-col">
                <p
                    class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-muted dark:text-canvas/50"
                >
                    Your bands
                </p>

                <button
                    v-for="band in bands"
                    :key="band.id"
                    type="button"
                    class="flex items-center gap-3 rounded-lg px-2 py-2 text-left transition-colors hover:bg-surface dark:hover:bg-white/5"
                    @click="setActive(band.id)"
                >
                    <span
                        class="grid size-8 shrink-0 place-items-center rounded-md bg-amp-violet/10 font-display text-sm font-semibold text-amp-violet dark:bg-amp-violet/20 dark:text-primary-300"
                    >
                        {{ band.name.charAt(0) }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium">{{ band.name }}</span>
                        <span class="block truncate text-xs text-muted dark:text-canvas/50">{{ band.genre }}</span>
                    </span>
                    <span
                        v-if="band.id === activeBand?.id"
                        class="pi pi-check text-sm text-soundcheck-teal"
                    />
                    <span
                        v-else
                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                        :class="roleClass[band.role] ?? roleClass.member"
                    >
                        {{ band.role }}
                    </span>
                </button>

                <hr class="my-2 border-surface dark:border-white/10" />

                <Link
                    href="/bands/create"
                    class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-amp-violet transition-colors hover:bg-surface dark:text-primary-300 dark:hover:bg-white/5"
                    @click="op.hide()"
                >
                    <i class="pi pi-plus text-xs" />
                    Create a new band
                </Link>
            </div>
        </Popover>
    </div>
</template>
