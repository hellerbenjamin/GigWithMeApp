<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

// Persistent layout with a per-page title for the topbar.
export default {
    layout: (_h, page) => h(AppLayout, { title: 'New gig' }, () => page),
};
</script>

<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    // The active band's venues, for the picker. May be empty — venue is optional.
    venues: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    // Booking modes ({ value, label, description }); how the gig gets confirmed.
    bookingModes: { type: Array, default: () => [] },
    defaultBookingMode: { type: String, default: 'auto' },
    defaultCurrency: { type: String, default: 'USD' },
});

// A short, common set; the band's own default is always first/selected.
const currencies = [...new Set([props.defaultCurrency, 'USD', 'EUR', 'GBP', 'CAD', 'AUD'])];

const form = useForm({
    type: 'gig',
    booking_mode: props.defaultBookingMode,
    name: '',
    venue_id: null,
    date: null, // Date object from the picker
    load_in_time: null,
    soundcheck_time: null,
    doors_time: null,
    start_time: null,
    end_time: null,
    fee: null,
    currency: props.defaultCurrency,
    notes: '',
});

// Helper text under the booking-mode picker, from the selected mode.
const bookingModeHint = computed(
    () => props.bookingModes.find((m) => m.value === form.booking_mode)?.description ?? '',
);

const pad = (n) => String(n).padStart(2, '0');

// PrimeVue DatePicker hands back Date objects; the server wants Y-m-d / H:i
// strings. Formatting from the local Date avoids the UTC-midnight day shift a
// naive toISOString() would introduce.
function asDate(d) {
    return d ? `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}` : null;
}
function asTime(d) {
    return d ? `${pad(d.getHours())}:${pad(d.getMinutes())}` : null;
}

function submit() {
    form
        .transform((data) => ({
            ...data,
            name: data.name.trim() || null,
            notes: data.notes.trim() || null,
            date: asDate(data.date),
            load_in_time: asTime(data.load_in_time),
            soundcheck_time: asTime(data.soundcheck_time),
            doors_time: asTime(data.doors_time),
            start_time: asTime(data.start_time),
            end_time: asTime(data.end_time),
        }))
        .post('/gigs');
}
</script>

<template>
    <Head title="Book a gig" />

    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h2 class="font-display text-3xl font-bold tracking-tight">Book a gig</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Pin down the date and the rest can stay loose — set a venue later, fill in
                the call sheet as it firms up.
            </p>
        </div>

        <form
            class="space-y-8 rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8"
            @submit.prevent="submit"
        >
            <!-- Basics -->
            <div class="space-y-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="type" class="block text-sm font-medium">Type</label>
                        <Select
                            input-id="type"
                            v-model="form.type"
                            :options="types"
                            option-label="label"
                            option-value="value"
                            fluid
                            :invalid="!!form.errors.type"
                        />
                        <small v-if="form.errors.type" class="text-cancelled">{{ form.errors.type }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="booking_mode" class="block text-sm font-medium">Booking</label>
                        <Select
                            input-id="booking_mode"
                            v-model="form.booking_mode"
                            :options="bookingModes"
                            option-label="label"
                            option-value="value"
                            fluid
                            :invalid="!!form.errors.booking_mode"
                        />
                        <small v-if="bookingModeHint" class="block text-muted dark:text-canvas/45">
                            {{ bookingModeHint }}
                        </small>
                        <small v-if="form.errors.booking_mode" class="text-cancelled">{{ form.errors.booking_mode }}</small>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="name" class="block text-sm font-medium">
                        Title <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                    </label>
                    <InputText
                        id="name"
                        v-model="form.name"
                        fluid
                        placeholder="Friday Night Headline"
                        :invalid="!!form.errors.name"
                    />
                    <small class="block text-muted dark:text-canvas/45">
                        Leave blank and we'll refer to it by date.
                    </small>
                    <small v-if="form.errors.name" class="text-cancelled">{{ form.errors.name }}</small>
                </div>
            </div>

            <!-- When & where -->
            <fieldset class="space-y-6 border-t border-surface pt-6 dark:border-white/10">
                <legend class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    When &amp; where
                </legend>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="date" class="block text-sm font-medium">Date</label>
                        <DatePicker
                            input-id="date"
                            v-model="form.date"
                            fluid
                            show-icon
                            icon-display="input"
                            date-format="D, M d, yy"
                            placeholder="Pick a date"
                            :invalid="!!form.errors.date"
                        />
                        <small v-if="form.errors.date" class="text-cancelled">{{ form.errors.date }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="venue_id" class="block text-sm font-medium">
                            Venue <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                        </label>
                        <Select
                            input-id="venue_id"
                            v-model="form.venue_id"
                            :options="venues"
                            option-label="name"
                            option-value="id"
                            fluid
                            show-clear
                            :placeholder="venues.length ? 'To be decided' : 'No venues yet'"
                            :disabled="!venues.length"
                            :invalid="!!form.errors.venue_id"
                        />
                        <small v-if="!venues.length" class="block text-muted dark:text-canvas/45">
                            Add venues from the Venues page to pick one here.
                        </small>
                        <small v-if="form.errors.venue_id" class="text-cancelled">{{ form.errors.venue_id }}</small>
                    </div>
                </div>
            </fieldset>

            <!-- Call sheet -->
            <fieldset class="space-y-6 border-t border-surface pt-6 dark:border-white/10">
                <legend class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    Call sheet <span class="font-normal normal-case tracking-normal">(optional)</span>
                </legend>

                <div class="grid gap-6 sm:grid-cols-3">
                    <div class="space-y-1.5">
                        <label for="load_in_time" class="block text-sm font-medium">Load-in</label>
                        <DatePicker
                            input-id="load_in_time"
                            v-model="form.load_in_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.load_in_time"
                        />
                        <small v-if="form.errors.load_in_time" class="text-cancelled">{{ form.errors.load_in_time }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="soundcheck_time" class="block text-sm font-medium">Soundcheck</label>
                        <DatePicker
                            input-id="soundcheck_time"
                            v-model="form.soundcheck_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.soundcheck_time"
                        />
                        <small v-if="form.errors.soundcheck_time" class="text-cancelled">{{ form.errors.soundcheck_time }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="doors_time" class="block text-sm font-medium">Doors</label>
                        <DatePicker
                            input-id="doors_time"
                            v-model="form.doors_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.doors_time"
                        />
                        <small v-if="form.errors.doors_time" class="text-cancelled">{{ form.errors.doors_time }}</small>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="start_time" class="block text-sm font-medium">Set time</label>
                        <DatePicker
                            input-id="start_time"
                            v-model="form.start_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.start_time"
                        />
                        <small v-if="form.errors.start_time" class="text-cancelled">{{ form.errors.start_time }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="end_time" class="block text-sm font-medium">End time</label>
                        <DatePicker
                            input-id="end_time"
                            v-model="form.end_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.end_time"
                        />
                        <small v-if="form.errors.end_time" class="text-cancelled">{{ form.errors.end_time }}</small>
                    </div>
                </div>
            </fieldset>

            <!-- Money -->
            <fieldset class="space-y-6 border-t border-surface pt-6 dark:border-white/10">
                <legend class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    Fee <span class="font-normal normal-case tracking-normal">(optional)</span>
                </legend>

                <div class="grid gap-6 sm:grid-cols-[1fr_auto]">
                    <div class="space-y-1.5">
                        <label for="fee" class="block text-sm font-medium">Amount</label>
                        <InputNumber
                            input-id="fee"
                            v-model="form.fee"
                            fluid
                            mode="currency"
                            :currency="form.currency"
                            :min="0"
                            placeholder="0.00"
                            :invalid="!!form.errors.fee"
                        />
                        <small v-if="form.errors.fee" class="text-cancelled">{{ form.errors.fee }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="currency" class="block text-sm font-medium">Currency</label>
                        <Select
                            input-id="currency"
                            v-model="form.currency"
                            :options="currencies"
                            fluid
                            :invalid="!!form.errors.currency"
                        />
                        <small v-if="form.errors.currency" class="text-cancelled">{{ form.errors.currency }}</small>
                    </div>
                </div>
            </fieldset>

            <!-- Notes -->
            <div class="space-y-1.5 border-t border-surface pt-6 dark:border-white/10">
                <label for="notes" class="block text-sm font-medium">
                    Notes <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                </label>
                <Textarea
                    id="notes"
                    v-model="form.notes"
                    fluid
                    rows="3"
                    auto-resize
                    placeholder="Set length, backline, who's on the bill, anything worth remembering."
                    :invalid="!!form.errors.notes"
                />
                <small v-if="form.errors.notes" class="text-cancelled">{{ form.errors.notes }}</small>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 border-t border-surface pt-6 dark:border-white/10">
                <Link
                    href="/gigs"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-ink/70 transition-colors hover:bg-surface dark:text-canvas/70 dark:hover:bg-white/5"
                >
                    Cancel
                </Link>
                <Button type="submit" label="Book gig" :loading="form.processing" />
            </div>
        </form>
    </div>
</template>
