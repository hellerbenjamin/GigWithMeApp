<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

// Persistent layout with a per-page title for the topbar.
export default {
    layout: (_h, page) => h(AppLayout, { title: 'New venue' }, () => page),
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    address: '',
    city: '',
    state: '',
    country: '',
    postal_code: '',
    phone: '',
    email: '',
    website: '',
    contact_person: '',
    contact_email: '',
    contact_phone: '',
    notes: '',
    default_load_in_time: null,
    default_soundcheck_time: null,
    default_doors_time: null,
    default_start_time: null,
    default_end_time: null,
    default_notes: '',
});

const pad = (n) => String(n).padStart(2, '0');
function asTime(d) {
    return d ? `${pad(d.getHours())}:${pad(d.getMinutes())}` : null;
}

function seedTime(field) {
    if (form[field]) return;
    const d = new Date();
    d.setHours(12, 0, 0, 0);
    form[field] = d;
}

function submit() {
    form
        .transform((data) => ({
            ...Object.fromEntries(
                Object.entries(data).map(([key, value]) => [
                    key,
                    typeof value === 'string' && value.trim() === '' ? null : value,
                ]),
            ),
            default_load_in_time: asTime(data.default_load_in_time),
            default_soundcheck_time: asTime(data.default_soundcheck_time),
            default_doors_time: asTime(data.default_doors_time),
            default_start_time: asTime(data.default_start_time),
            default_end_time: asTime(data.default_end_time),
        }))
        .post('/venues');
}
</script>

<template>
    <Head title="Add a venue" />

    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h2 class="font-display text-3xl font-bold tracking-tight">Add a venue</h2>
            <p class="mt-1 text-sm text-ink/60 dark:text-canvas/55">
                Save a room once and reuse it whenever you book a gig. Only the name is
                required — fill in the rest as you learn it.
            </p>
        </div>

        <form
            class="space-y-8 rounded-2xl border border-surface bg-white p-6 shadow-sm dark:border-white/10 dark:bg-riser sm:p-8"
            @submit.prevent="submit"
        >
            <!-- Name -->
            <div class="space-y-1.5">
                <label for="name" class="block text-sm font-medium">Venue name</label>
                <InputText
                    id="name"
                    v-model="form.name"
                    autofocus
                    fluid
                    placeholder="The Echo Lounge"
                    :invalid="!!form.errors.name"
                />
                <small v-if="form.errors.name" class="text-cancelled">{{ form.errors.name }}</small>
            </div>

            <!-- Location -->
            <fieldset class="space-y-6">
                <legend class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    Location
                </legend>

                <div class="space-y-1.5">
                    <label for="address" class="block text-sm font-medium">
                        Address <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                    </label>
                    <InputText
                        id="address"
                        v-model="form.address"
                        fluid
                        placeholder="123 Main St"
                        :invalid="!!form.errors.address"
                    />
                    <small v-if="form.errors.address" class="text-cancelled">{{ form.errors.address }}</small>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="city" class="block text-sm font-medium">
                            City <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                        </label>
                        <InputText id="city" v-model="form.city" fluid placeholder="Portland" :invalid="!!form.errors.city" />
                        <small v-if="form.errors.city" class="text-cancelled">{{ form.errors.city }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="state" class="block text-sm font-medium">
                            State / region <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                        </label>
                        <InputText id="state" v-model="form.state" fluid placeholder="OR" :invalid="!!form.errors.state" />
                        <small v-if="form.errors.state" class="text-cancelled">{{ form.errors.state }}</small>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="postal_code" class="block text-sm font-medium">
                            Postal code <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                        </label>
                        <InputText
                            id="postal_code"
                            v-model="form.postal_code"
                            fluid
                            placeholder="97201"
                            :invalid="!!form.errors.postal_code"
                        />
                        <small v-if="form.errors.postal_code" class="text-cancelled">{{ form.errors.postal_code }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="country" class="block text-sm font-medium">
                            Country <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                        </label>
                        <InputText
                            id="country"
                            v-model="form.country"
                            fluid
                            placeholder="United States"
                            :invalid="!!form.errors.country"
                        />
                        <small v-if="form.errors.country" class="text-cancelled">{{ form.errors.country }}</small>
                    </div>
                </div>
            </fieldset>

            <!-- Venue contact details -->
            <fieldset class="space-y-6 border-t border-surface pt-6 dark:border-white/10">
                <legend class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    Venue contact
                </legend>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="phone" class="block text-sm font-medium">
                            Phone <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                        </label>
                        <InputText id="phone" v-model="form.phone" fluid placeholder="(503) 555-0142" :invalid="!!form.errors.phone" />
                        <small v-if="form.errors.phone" class="text-cancelled">{{ form.errors.phone }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="email" class="block text-sm font-medium">
                            Email <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                        </label>
                        <InputText
                            id="email"
                            v-model="form.email"
                            type="email"
                            fluid
                            placeholder="booking@venue.com"
                            :invalid="!!form.errors.email"
                        />
                        <small v-if="form.errors.email" class="text-cancelled">{{ form.errors.email }}</small>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="website" class="block text-sm font-medium">
                        Website <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                    </label>
                    <InputText
                        id="website"
                        v-model="form.website"
                        fluid
                        placeholder="https://…"
                        :invalid="!!form.errors.website"
                    />
                    <small v-if="form.errors.website" class="text-cancelled">{{ form.errors.website }}</small>
                </div>
            </fieldset>

            <!-- Booking contact person -->
            <fieldset class="space-y-6 border-t border-surface pt-6 dark:border-white/10">
                <legend class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    Booking contact
                </legend>

                <div class="space-y-1.5">
                    <label for="contact_person" class="block text-sm font-medium">
                        Name <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                    </label>
                    <InputText
                        id="contact_person"
                        v-model="form.contact_person"
                        fluid
                        placeholder="Jordan Reyes"
                        :invalid="!!form.errors.contact_person"
                    />
                    <small v-if="form.errors.contact_person" class="text-cancelled">{{ form.errors.contact_person }}</small>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="contact_email" class="block text-sm font-medium">
                            Email <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                        </label>
                        <InputText
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                            fluid
                            placeholder="jordan@venue.com"
                            :invalid="!!form.errors.contact_email"
                        />
                        <small v-if="form.errors.contact_email" class="text-cancelled">{{ form.errors.contact_email }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="contact_phone" class="block text-sm font-medium">
                            Phone <span class="font-normal text-muted dark:text-canvas/45">(optional)</span>
                        </label>
                        <InputText
                            id="contact_phone"
                            v-model="form.contact_phone"
                            fluid
                            placeholder="(503) 555-0188"
                            :invalid="!!form.errors.contact_phone"
                        />
                        <small v-if="form.errors.contact_phone" class="text-cancelled">{{ form.errors.contact_phone }}</small>
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
                    placeholder="Load-in details, stage size, parking, anything worth remembering."
                    :invalid="!!form.errors.notes"
                />
                <small v-if="form.errors.notes" class="text-cancelled">{{ form.errors.notes }}</small>
            </div>

            <!-- Gig defaults -->
            <fieldset class="space-y-6 border-t border-surface pt-6 dark:border-white/10">
                <legend class="text-xs font-semibold uppercase tracking-wider text-muted dark:text-canvas/45">
                    Gig defaults <span class="font-normal normal-case tracking-normal">(optional)</span>
                </legend>
                <p class="text-sm text-ink/60 dark:text-canvas/55">
                    Set typical times for this room. They pre-fill the call sheet whenever
                    you book a gig here, and you can adjust them per show.
                </p>

                <div class="grid gap-6 sm:grid-cols-3">
                    <div class="space-y-1.5">
                        <label for="default_load_in_time" class="block text-sm font-medium">Load-in</label>
                        <DatePicker
                            input-id="default_load_in_time"
                            v-model="form.default_load_in_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.default_load_in_time"
                            @show="seedTime('default_load_in_time')"
                        />
                        <small v-if="form.errors.default_load_in_time" class="text-cancelled">{{ form.errors.default_load_in_time }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="default_soundcheck_time" class="block text-sm font-medium">Soundcheck</label>
                        <DatePicker
                            input-id="default_soundcheck_time"
                            v-model="form.default_soundcheck_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.default_soundcheck_time"
                            @show="seedTime('default_soundcheck_time')"
                        />
                        <small v-if="form.errors.default_soundcheck_time" class="text-cancelled">{{ form.errors.default_soundcheck_time }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="default_doors_time" class="block text-sm font-medium">Doors</label>
                        <DatePicker
                            input-id="default_doors_time"
                            v-model="form.default_doors_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.default_doors_time"
                            @show="seedTime('default_doors_time')"
                        />
                        <small v-if="form.errors.default_doors_time" class="text-cancelled">{{ form.errors.default_doors_time }}</small>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="default_start_time" class="block text-sm font-medium">Set time</label>
                        <DatePicker
                            input-id="default_start_time"
                            v-model="form.default_start_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.default_start_time"
                            @show="seedTime('default_start_time')"
                        />
                        <small v-if="form.errors.default_start_time" class="text-cancelled">{{ form.errors.default_start_time }}</small>
                    </div>

                    <div class="space-y-1.5">
                        <label for="default_end_time" class="block text-sm font-medium">End time</label>
                        <DatePicker
                            input-id="default_end_time"
                            v-model="form.default_end_time"
                            time-only
                            fluid
                            hour-format="12"
                            placeholder="—"
                            :invalid="!!form.errors.default_end_time"
                            @show="seedTime('default_end_time')"
                        />
                        <small v-if="form.errors.default_end_time" class="text-cancelled">{{ form.errors.default_end_time }}</small>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="default_notes" class="block text-sm font-medium">Default notes</label>
                    <Textarea
                        id="default_notes"
                        v-model="form.default_notes"
                        fluid
                        rows="3"
                        auto-resize
                        placeholder="Anything that applies to every gig here — backline, stage setup, parking."
                        :invalid="!!form.errors.default_notes"
                    />
                    <small v-if="form.errors.default_notes" class="text-cancelled">{{ form.errors.default_notes }}</small>
                </div>
            </fieldset>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 border-t border-surface pt-6 dark:border-white/10">
                <Link
                    href="/venues"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-ink/70 transition-colors hover:bg-surface dark:text-canvas/70 dark:hover:bg-white/5"
                >
                    Cancel
                </Link>
                <Button type="submit" label="Save venue" :loading="form.processing" />
            </div>
        </form>
    </div>
</template>
