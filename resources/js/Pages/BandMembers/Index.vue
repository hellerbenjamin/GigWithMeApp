<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Band Members' }, () => page),
};
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    members: { type: Array, default: () => [] },
    // Whether the current user (owner/admin) may add members.
    canManage: { type: Boolean, default: false },
});

// First letters of the first two words — "Jordan Reyes" → "JR".
function initials(name) {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');
}

// Tint each role differently so the roster reads at a glance.
const roleStyles = {
    owner: 'bg-amp-violet/15 text-amp-violet',
    admin: 'bg-soundcheck-teal/15 text-soundcheck-teal',
    member: 'bg-surface text-muted dark:bg-white/5 dark:text-canvas/50',
};
</script>

<template>
    <Head title="Band Members" />

    <!-- Header row -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="font-display text-3xl font-bold tracking-tight">Band Members</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Your roster — who's in the band and what they can do. Owners and admins
                manage bookings and settings; members book and view.
            </p>
        </div>

        <Link
            v-if="canManage && members.length"
            href="/band-members/create"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
        >
            <i class="pi pi-user-plus text-xs" />
            Add member
        </Link>
    </div>

    <!-- Roster -->
    <ul v-if="members.length" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <li
            v-for="member in members"
            :key="member.id"
            class="flex items-center gap-4 rounded-2xl border border-surface bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-white/10 dark:bg-riser"
        >
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-amp-violet/15 font-semibold text-amp-violet">
                {{ initials(member.name) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="flex items-center gap-2 truncate font-medium">
                    {{ member.name }}
                    <span v-if="member.isYou" class="text-xs font-normal text-muted dark:text-canvas/45">(you)</span>
                </p>
                <p class="truncate text-sm text-ink/55 dark:text-canvas/50">{{ member.email }}</p>
            </div>
            <span
                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="roleStyles[member.role] ?? roleStyles.member"
            >
                {{ member.roleLabel }}
            </span>
        </li>
    </ul>

    <!-- Empty state -->
    <div
        v-else
        class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-surface bg-white px-6 py-14 text-center shadow-sm dark:border-white/10 dark:bg-riser"
    >
        <span class="grid size-12 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
            <i class="pi pi-users text-lg" />
        </span>
        <p class="mt-4 font-display text-lg font-semibold tracking-tight">No one here yet</p>
        <p class="mt-1 max-w-sm text-sm text-ink/55 dark:text-canvas/50">
            Add your bandmates so they can see the calendar and help run the show.
        </p>
        <Link
            v-if="canManage"
            href="/band-members/create"
            class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-amp-violet px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:brightness-105 hover:shadow-md active:scale-[0.98] dark:bg-primary-500"
        >
            <i class="pi pi-user-plus text-xs" />
            Add member
        </Link>
    </div>
</template>
