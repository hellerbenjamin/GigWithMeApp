<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Notifications' }, () => page),
};
</script>

<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    channels: { type: Array, default: () => ['email'] },
    days: { type: Array, default: () => [7, 1] },
    availableDays: { type: Array, default: () => [7, 3, 1, 0] },
    hasPush: { type: Boolean, default: false },
    calendarUrl: { type: String, default: '' },
});

const form = useForm({
    channels: [...props.channels],
    days: [...props.days],
});

function toggleChannel(channel) {
    const idx = form.channels.indexOf(channel);
    if (idx === -1) {
        form.channels.push(channel);
    } else {
        form.channels.splice(idx, 1);
    }
}

function toggleDay(day) {
    const idx = form.days.indexOf(day);
    if (idx === -1) {
        form.days.push(day);
    } else {
        form.days.splice(idx, 1);
    }
}

function dayLabel(day) {
    if (day === 0) return 'Day of';
    if (day === 1) return '1 day before';
    return `${day} days before`;
}

function submit() {
    form.put('/notifications');
}

const copied = ref(false);
function copyCalendarUrl() {
    navigator.clipboard.writeText(props.calendarUrl).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    });
}

const resetting = ref(false);
function resetCalendarToken() {
    resetting.value = true;
    router.post('/calendar/reset', {}, {
        preserveScroll: true,
        onFinish: () => (resetting.value = false),
    });
}
</script>

<template>
    <Head title="Notification preferences" />

    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h2 class="font-display text-3xl font-bold tracking-tight">Notifications</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Choose when and how GigWithMe reminds you about upcoming gigs.
            </p>
        </div>

        <form class="space-y-8" @submit.prevent="submit">
            <!-- Channel section -->
            <div class="rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8">
                <h3 class="font-display text-base font-semibold tracking-tight">How to notify you</h3>
                <p class="mt-1 text-sm text-ink/55 dark:text-canvas/50">
                    Pick one or both. At least one must be selected.
                </p>

                <div class="mt-5 space-y-4">
                    <!-- Email -->
                    <label class="flex cursor-pointer items-start gap-4">
                        <Checkbox
                            :model-value="form.channels.includes('email')"
                            :binary="true"
                            class="mt-0.5 shrink-0"
                            @update:model-value="toggleChannel('email')"
                        />
                        <div>
                            <p class="text-sm font-medium">Email</p>
                            <p class="text-sm text-ink/55 dark:text-canvas/50">Sent to the address on your account.</p>
                        </div>
                    </label>

                    <!-- Push -->
                    <label class="flex cursor-pointer items-start gap-4">
                        <Checkbox
                            :model-value="form.channels.includes('push')"
                            :binary="true"
                            :disabled="!hasPush"
                            class="mt-0.5 shrink-0"
                            @update:model-value="toggleChannel('push')"
                        />
                        <div>
                            <p class="text-sm font-medium" :class="!hasPush && 'text-ink/40 dark:text-canvas/30'">
                                Push notifications
                            </p>
                            <p class="text-sm text-ink/55 dark:text-canvas/50">
                                <template v-if="hasPush">Sent to devices where you've installed the app.</template>
                                <template v-else>
                                    Install GigWithMe on your phone to enable push notifications.
                                </template>
                            </p>
                        </div>
                    </label>
                </div>

                <small v-if="form.errors.channels" class="mt-3 block text-cancelled">{{ form.errors.channels }}</small>
            </div>

            <!-- Timing section -->
            <div class="rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8">
                <h3 class="font-display text-base font-semibold tracking-tight">When to remind you</h3>
                <p class="mt-1 text-sm text-ink/55 dark:text-canvas/50">
                    Select as many as you like. Deselect all to opt out of reminders.
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button
                        v-for="day in availableDays"
                        :key="day"
                        type="button"
                        class="rounded-full border px-4 py-2 text-sm font-medium transition-colors"
                        :class="form.days.includes(day)
                            ? 'border-amp-violet bg-amp-violet/10 text-amp-violet dark:border-primary-400 dark:text-primary-300'
                            : 'border-surface bg-white text-ink/60 hover:border-amp-violet/40 dark:border-white/10 dark:bg-white/5 dark:text-canvas/55'"
                        @click="toggleDay(day)"
                    >
                        {{ dayLabel(day) }}
                    </button>
                </div>

                <small v-if="form.errors.days" class="mt-3 block text-cancelled">{{ form.errors.days }}</small>
            </div>

            <!-- Save -->
            <div class="flex justify-end">
                <Button
                    type="submit"
                    label="Save preferences"
                    :loading="form.processing"
                />
            </div>
        </form>

        <!-- Calendar sync -->
        <div class="mt-10">
            <h3 class="font-display text-lg font-semibold tracking-tight">Calendar sync</h3>
            <p class="mt-1 text-sm text-ink/55 dark:text-canvas/50">
                Subscribe to this private URL in any calendar app (Google Calendar, Apple
                Calendar, Outlook) and your gigs will appear automatically. The feed updates
                as gigs are added or changed.
            </p>

            <div class="mt-4 rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    Your calendar feed URL
                </label>
                <div class="flex items-center gap-3">
                    <input
                        :value="calendarUrl"
                        readonly
                        class="min-w-0 flex-1 rounded-xl border border-surface bg-canvas px-3.5 py-2.5 font-mono text-xs text-ink/70 dark:border-white/10 dark:bg-backstage dark:text-canvas/60"
                        @click="($event.target).select()"
                    />
                    <Button
                        type="button"
                        :label="copied ? 'Copied!' : 'Copy'"
                        :icon="copied ? 'pi pi-check' : 'pi pi-copy'"
                        :severity="copied ? 'success' : 'secondary'"
                        outlined
                        @click="copyCalendarUrl"
                    />
                </div>

                <div class="mt-5 border-t border-surface pt-5 dark:border-white/10">
                    <p class="text-sm text-ink/55 dark:text-canvas/50">
                        If you've shared this URL and want to revoke access, reset it. Any calendar
                        apps using the old URL will stop receiving updates.
                    </p>
                    <Button
                        type="button"
                        label="Reset link"
                        icon="pi pi-refresh"
                        severity="secondary"
                        outlined
                        size="small"
                        class="mt-3"
                        :loading="resetting"
                        @click="resetCalendarToken"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
