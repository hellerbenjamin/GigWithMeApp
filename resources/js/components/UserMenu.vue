<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    user: { type: Object, default: null }, // { name, email }
});

const op = ref(null);
const toggle = (event) => op.value.toggle(event);

const initials = computed(() => {
    if (!props.user?.name) return '?';
    return props.user.name
        .split(' ')
        .map((part) => part.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

function logout() {
    op.value.hide();
    router.post('/logout');
}
</script>

<template>
    <div>
        <button
            type="button"
            class="flex items-center gap-2 rounded-full p-1 pr-2 transition-colors hover:bg-surface dark:hover:bg-white/5"
            @click="toggle"
        >
            <span
                class="grid size-8 shrink-0 place-items-center rounded-full bg-stage-indigo text-xs font-semibold text-white dark:bg-amp-violet"
            >
                {{ initials }}
            </span>
            <span class="hidden text-sm font-medium sm:block">{{ user?.name ?? 'Guest' }}</span>
            <i class="pi pi-angle-down text-xs text-muted dark:text-canvas/50" />
        </button>

        <Popover ref="op" :pt="{ root: 'w-60' }">
            <div class="flex flex-col">
                <div class="px-2 pb-2">
                    <p class="truncate text-sm font-semibold">{{ user?.name ?? 'Guest' }}</p>
                    <p class="truncate text-xs text-muted dark:text-canvas/50">{{ user?.email }}</p>
                </div>

                <hr class="my-1 border-surface dark:border-white/10" />

                <Link
                    href="/settings"
                    class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm transition-colors hover:bg-surface dark:hover:bg-white/5"
                    @click="op.hide()"
                >
                    <i class="pi pi-cog text-xs text-muted dark:text-canvas/50" />
                    Settings
                </Link>

                <button
                    type="button"
                    class="flex items-center gap-2 rounded-lg px-2 py-2 text-left text-sm text-cancelled transition-colors hover:bg-cancelled/10"
                    @click="logout"
                >
                    <i class="pi pi-sign-out text-xs" />
                    Log out
                </button>
            </div>
        </Popover>
    </div>
</template>
