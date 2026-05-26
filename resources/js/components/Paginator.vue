<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

// Renders a Laravel length-aware paginator (passed straight through from the
// controller): a "Showing X–Y of Z" line plus numbered page links that carry
// the active query string. Page controls hide when there's only one page.
const props = defineProps({
    paginator: { type: Object, required: true },
});

const links = computed(() => props.paginator.links ?? []);
const showPages = computed(() => (props.paginator.last_page ?? 1) > 1);

// Laravel labels the first/last links "&laquo; Previous" / "Next &raquo;" and
// uses "..." for gaps; classify so we can swap in arrow icons and dim gaps.
function kind(label) {
    if (/Previous/i.test(label)) return 'prev';
    if (/Next/i.test(label)) return 'next';
    if (label === '...') return 'gap';
    return 'page';
}
</script>

<template>
    <div
        v-if="paginator.total"
        class="mt-8 flex flex-col-reverse items-center gap-4 sm:flex-row sm:justify-between"
    >
        <p class="text-sm text-ink/55 dark:text-canvas/50">
            Showing <span class="font-medium text-ink dark:text-canvas">{{ paginator.from }}</span>–<span
                class="font-medium text-ink dark:text-canvas"
            >{{ paginator.to }}</span>
            of <span class="font-medium text-ink dark:text-canvas">{{ paginator.total }}</span>
        </p>

        <nav v-if="showPages" aria-label="Venue pages" class="flex items-center gap-1">
            <template v-for="(link, i) in links" :key="i">
                <!-- Gap / disabled edge → inert -->
                <span
                    v-if="kind(link.label) === 'gap' || !link.url"
                    class="grid size-9 place-items-center rounded-lg text-sm text-ink/30 dark:text-canvas/30"
                >
                    <i v-if="kind(link.label) === 'prev'" class="pi pi-angle-left text-xs" />
                    <i v-else-if="kind(link.label) === 'next'" class="pi pi-angle-right text-xs" />
                    <span v-else>…</span>
                </span>

                <!-- Active page -->
                <span
                    v-else-if="link.active"
                    aria-current="page"
                    class="grid size-9 place-items-center rounded-lg bg-amp-violet text-sm font-semibold text-white dark:bg-primary-500"
                >
                    {{ link.label }}
                </span>

                <!-- Navigable page / prev / next -->
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    :aria-label="kind(link.label) === 'prev' ? 'Previous page' : kind(link.label) === 'next' ? 'Next page' : `Page ${link.label}`"
                    class="grid size-9 place-items-center rounded-lg text-sm font-medium text-ink/70 transition-colors hover:bg-surface dark:text-canvas/70 dark:hover:bg-white/5"
                >
                    <i v-if="kind(link.label) === 'prev'" class="pi pi-angle-left text-xs" />
                    <i v-else-if="kind(link.label) === 'next'" class="pi pi-angle-right text-xs" />
                    <span v-else>{{ link.label }}</span>
                </Link>
            </template>
        </nav>
    </div>
</template>
