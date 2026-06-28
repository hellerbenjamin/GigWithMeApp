<script>
import { h } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

export default {
    layout: (_h, page) => h(AppLayout, { title: 'Venue Outreach' }, () => page),
};
</script>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    outreach:      { type: Object, required: true },
    allStatuses:   { type: Array,  default: () => [] },
    allPriorities: { type: Array,  default: () => [] },
    allMethods:    { type: Array,  default: () => [] },
});

// --- Outreach edit form ---
const outreachForm = useForm({
    status:       props.outreach.status,
    priority:     props.outreach.priority,
    follow_up_on: props.outreach.followUpOn ?? '',
    notes:        props.outreach.notes ?? '',
});

function saveOutreach() {
    outreachForm.put(`/booking/outreach/${props.outreach.id}`, {
        preserveScroll: true,
    });
}

function deleteOutreach() {
    if (!confirm(`Remove ${props.outreach.venueName} from this pipeline? The contact history for this season will be lost.`)) return;
    router.delete(`/booking/outreach/${props.outreach.id}`);
}

// --- Add contact form ---
const showAddContact = ref(false);
const contactForm = useForm({
    occurred_on: new Date().toISOString().slice(0, 10),
    method:      'email',
    summary:     '',
    response:    '',
});

function addContact() {
    contactForm.post(`/booking/outreach/${props.outreach.id}/contacts`, {
        preserveScroll: true,
        onSuccess: () => {
            contactForm.reset();
            contactForm.occurred_on = new Date().toISOString().slice(0, 10);
            contactForm.method = 'email';
            showAddContact.value = false;
        },
    });
}

// --- Edit contact ---
const editingContactId = ref(null);
const editContactForm = useForm({ occurred_on: '', method: '', summary: '', response: '' });

function startEditContact(contact) {
    editingContactId.value = contact.id;
    editContactForm.occurred_on = contact.occurredOn;
    editContactForm.method      = contact.method;
    editContactForm.summary     = contact.summary;
    editContactForm.response    = contact.response ?? '';
}

function saveContact(contactId) {
    editContactForm.put(`/booking/contacts/${contactId}`, {
        preserveScroll: true,
        onSuccess: () => { editingContactId.value = null; },
    });
}

function deleteContact(contactId) {
    if (!confirm('Delete this contact entry?')) return;
    router.delete(`/booking/contacts/${contactId}`, { preserveScroll: true });
}

// --- Helpers ---
const priorityColors = {
    high:   'bg-encore-coral/15 text-encore-coral',
    medium: 'bg-stage-indigo/10 text-stage-indigo dark:text-canvas/70',
    low:    'bg-surface text-ink/40 dark:bg-white/5 dark:text-canvas/40',
};

const statusColors = {
    targeting:     'bg-amp-violet/15 text-amp-violet dark:text-primary-300',
    contacted:     'bg-stage-indigo/15 text-stage-indigo',
    in_discussion: 'bg-pending/15 text-pending-text dark:text-pending',
    booked:        'bg-confirmed/15 text-confirmed-text dark:text-soundcheck-teal',
    declined:      'bg-cancelled/15 text-cancelled-text dark:text-cancelled',
    no_response:   'bg-surface text-ink/50 dark:bg-white/5 dark:text-canvas/50',
};

const methodIcons = {
    email:     'pi pi-envelope',
    phone:     'pi pi-phone',
    in_person: 'pi pi-user',
    other:     'pi pi-comment',
};

function formatDate(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

const isFollowUpOverdue = computed(() => {
    if (!props.outreach.followUpOn) return false;
    const [y, m, d] = props.outreach.followUpOn.split('-').map(Number);
    const due = new Date(y, m - 1, d);
    const today = new Date(); today.setHours(0,0,0,0);
    return due <= today;
});
</script>

<template>
    <Head :title="`${outreach.venueName} — ${outreach.seasonName}`" />

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm">
        <Link href="/booking/seasons" class="text-ink/50 hover:text-ink dark:text-canvas/45 dark:hover:text-canvas">Booking</Link>
        <i class="pi pi-angle-right text-xs text-ink/30 dark:text-canvas/30" />
        <Link :href="`/booking/seasons/${outreach.seasonId}`" class="text-ink/50 hover:text-ink dark:text-canvas/45 dark:hover:text-canvas">
            {{ outreach.seasonName }}
        </Link>
        <i class="pi pi-angle-right text-xs text-ink/30 dark:text-canvas/30" />
        <span class="text-ink/70 dark:text-canvas/60 truncate">{{ outreach.venueName }}</span>
    </div>

    <div class="mt-4 grid gap-6 lg:grid-cols-5">

        <!-- Left: venue info + outreach settings -->
        <div class="space-y-5 lg:col-span-2">

            <!-- Venue card -->
            <div class="rounded-2xl border border-surface bg-white p-5 shadow-sm dark:border-white/10 dark:bg-riser">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-display text-xl font-bold tracking-tight">{{ outreach.venueName }}</h2>
                        <p v-if="outreach.venueCity" class="mt-0.5 text-sm text-ink/55 dark:text-canvas/50">
                            {{ outreach.venueCity }}{{ outreach.venueState ? `, ${outreach.venueState}` : '' }}
                        </p>
                    </div>
                    <Link
                        :href="`/venues/${outreach.venueId}/edit`"
                        class="shrink-0 text-xs text-amp-violet hover:underline dark:text-primary-300"
                    >
                        Edit venue
                    </Link>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    <div v-if="outreach.contactPerson" class="flex items-center gap-2 text-ink/70 dark:text-canvas/60">
                        <i class="pi pi-user w-4 shrink-0 text-ink/35 dark:text-canvas/35" />
                        {{ outreach.contactPerson }}
                    </div>
                    <a v-if="outreach.contactEmail || outreach.venueEmail"
                        :href="`mailto:${outreach.contactEmail || outreach.venueEmail}`"
                        class="flex items-center gap-2 text-amp-violet hover:underline dark:text-primary-300"
                    >
                        <i class="pi pi-envelope w-4 shrink-0" />
                        {{ outreach.contactEmail || outreach.venueEmail }}
                    </a>
                    <a v-if="outreach.contactPhone || outreach.venuePhone"
                        :href="`tel:${outreach.contactPhone || outreach.venuePhone}`"
                        class="flex items-center gap-2 text-ink/70 hover:underline dark:text-canvas/60"
                    >
                        <i class="pi pi-phone w-4 shrink-0 text-ink/35 dark:text-canvas/35" />
                        {{ outreach.contactPhone || outreach.venuePhone }}
                    </a>
                    <a v-if="outreach.venueWebsite"
                        :href="outreach.venueWebsite"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-2 text-amp-violet hover:underline dark:text-primary-300"
                    >
                        <i class="pi pi-external-link w-4 shrink-0" />
                        Website
                    </a>
                </div>
            </div>

            <!-- Outreach settings -->
            <div class="rounded-2xl border border-surface bg-white p-5 shadow-sm dark:border-white/10 dark:bg-riser">
                <h3 class="font-display font-semibold tracking-tight">Outreach Details</h3>
                <form class="mt-4 space-y-4" @submit.prevent="saveOutreach">
                    <div>
                        <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Status</label>
                        <select
                            v-model="outreachForm.status"
                            class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                        >
                            <option v-for="s in allStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Priority</label>
                        <select
                            v-model="outreachForm.priority"
                            class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                        >
                            <option v-for="p in allPriorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">
                            Follow-up date
                            <span v-if="isFollowUpOverdue" class="ml-1.5 text-encore-coral text-xs font-semibold">Overdue</span>
                        </label>
                        <input
                            v-model="outreachForm.follow_up_on"
                            type="date"
                            class="mt-1 w-full rounded-xl border bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:bg-riser dark:text-canvas"
                            :class="isFollowUpOverdue ? 'border-encore-coral/60 dark:border-encore-coral/40' : 'border-surface dark:border-white/15'"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink/70 dark:text-canvas/65">Notes</label>
                        <textarea
                            v-model="outreachForm.notes"
                            rows="4"
                            placeholder="Fee offers, availability windows, anything relevant..."
                            class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                        />
                    </div>

                    <div class="flex items-center justify-between">
                        <button
                            type="submit"
                            :disabled="outreachForm.processing"
                            class="rounded-xl bg-amp-violet px-4 py-2 text-sm font-semibold text-white hover:brightness-105 disabled:opacity-50"
                        >
                            Save
                        </button>
                        <button
                            type="button"
                            class="text-sm text-cancelled-text hover:underline dark:text-cancelled"
                            @click="deleteOutreach"
                        >
                            Remove from pipeline
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right: contact log -->
        <div class="lg:col-span-3">
            <div class="rounded-2xl border border-surface bg-white shadow-sm dark:border-white/10 dark:bg-riser">
                <div class="flex items-center justify-between border-b border-surface px-5 py-4 dark:border-white/10">
                    <h3 class="font-display font-semibold tracking-tight">Contact Log</h3>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-amp-violet px-3 py-1.5 text-xs font-semibold text-white hover:brightness-105"
                        @click="showAddContact = !showAddContact"
                    >
                        <i class="pi pi-plus text-[10px]" />
                        Log Contact
                    </button>
                </div>

                <!-- Add contact form -->
                <div v-if="showAddContact" class="border-b border-surface p-5 dark:border-white/10 bg-canvas dark:bg-black/10">
                    <h4 class="text-sm font-semibold">New Contact Entry</h4>
                    <form class="mt-3 space-y-3" @submit.prevent="addContact">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-ink/65 dark:text-canvas/60">Date</label>
                                <input
                                    v-model="contactForm.occurred_on"
                                    type="date"
                                    required
                                    class="mt-1 w-full rounded-xl border border-surface bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-ink/65 dark:text-canvas/60">Method</label>
                                <select
                                    v-model="contactForm.method"
                                    class="mt-1 w-full rounded-xl border border-surface bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                                >
                                    <option v-for="m in allMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink/65 dark:text-canvas/60">What you said / offered</label>
                            <textarea
                                v-model="contactForm.summary"
                                rows="2"
                                required
                                placeholder="Reached out about booking a Saturday night in July..."
                                class="mt-1 w-full rounded-xl border border-surface bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink/65 dark:text-canvas/60">Their response (optional)</label>
                            <textarea
                                v-model="contactForm.response"
                                rows="2"
                                placeholder="They said..."
                                class="mt-1 w-full rounded-xl border border-surface bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                            />
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="submit"
                                :disabled="contactForm.processing"
                                class="rounded-xl bg-amp-violet px-3 py-1.5 text-xs font-semibold text-white hover:brightness-105 disabled:opacity-50"
                            >
                                Save
                            </button>
                            <button
                                type="button"
                                class="rounded-xl border border-surface px-3 py-1.5 text-xs font-medium text-ink/65 hover:bg-surface dark:border-white/15 dark:text-canvas/60 dark:hover:bg-white/5"
                                @click="showAddContact = false"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Contact timeline -->
                <ul v-if="outreach.contacts.length" class="divide-y divide-surface dark:divide-white/10">
                    <li v-for="contact in outreach.contacts" :key="contact.id" class="p-5">
                        <div v-if="editingContactId !== contact.id">
                            <!-- View mode -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-amp-violet/10 text-amp-violet dark:bg-primary-500/15 dark:text-primary-300">
                                        <i :class="methodIcons[contact.method]" class="text-xs" />
                                    </span>
                                    <div>
                                        <span class="text-sm font-semibold">{{ contact.methodLabel }}</span>
                                        <span class="mx-1.5 text-ink/30 dark:text-canvas/30">·</span>
                                        <span class="text-sm text-ink/55 dark:text-canvas/50">{{ formatDate(contact.occurredOn) }}</span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 gap-3">
                                    <button
                                        type="button"
                                        class="text-xs text-ink/40 hover:text-amp-violet dark:text-canvas/35 dark:hover:text-primary-300"
                                        @click="startEditContact(contact)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs text-ink/40 hover:text-cancelled-text dark:text-canvas/35 dark:hover:text-cancelled"
                                        @click="deleteContact(contact.id)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                            <p class="mt-3 text-sm text-ink/80 dark:text-canvas/75 whitespace-pre-wrap">{{ contact.summary }}</p>
                            <div v-if="contact.response" class="mt-2 rounded-xl border border-surface bg-canvas px-3 py-2 dark:border-white/10 dark:bg-black/10">
                                <p class="text-xs font-medium text-ink/50 dark:text-canvas/45">Their response</p>
                                <p class="mt-1 text-sm text-ink/80 dark:text-canvas/75 whitespace-pre-wrap">{{ contact.response }}</p>
                            </div>
                        </div>

                        <!-- Edit mode -->
                        <form v-else class="space-y-3" @submit.prevent="saveContact(contact.id)">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-ink/65 dark:text-canvas/60">Date</label>
                                    <input
                                        v-model="editContactForm.occurred_on"
                                        type="date"
                                        required
                                        class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-ink/65 dark:text-canvas/60">Method</label>
                                    <select
                                        v-model="editContactForm.method"
                                        class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                                    >
                                        <option v-for="m in allMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-ink/65 dark:text-canvas/60">What you said</label>
                                <textarea
                                    v-model="editContactForm.summary"
                                    rows="2"
                                    required
                                    class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-ink/65 dark:text-canvas/60">Their response</label>
                                <textarea
                                    v-model="editContactForm.response"
                                    rows="2"
                                    class="mt-1 w-full rounded-xl border border-surface bg-canvas px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amp-violet dark:border-white/15 dark:bg-riser dark:text-canvas"
                                />
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" :disabled="editContactForm.processing"
                                    class="rounded-xl bg-amp-violet px-3 py-1.5 text-xs font-semibold text-white hover:brightness-105 disabled:opacity-50">
                                    Save
                                </button>
                                <button type="button" @click="editingContactId = null"
                                    class="rounded-xl border border-surface px-3 py-1.5 text-xs font-medium text-ink/65 hover:bg-surface dark:border-white/15 dark:text-canvas/60 dark:hover:bg-white/5">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </li>
                </ul>

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center px-5 py-12 text-center">
                    <span class="grid size-10 place-items-center rounded-full bg-surface text-muted dark:bg-white/5 dark:text-canvas/40">
                        <i class="pi pi-comments text-base" />
                    </span>
                    <p class="mt-3 text-sm font-medium">No contacts logged yet</p>
                    <p class="text-sm text-ink/50 dark:text-canvas/45">
                        Log your first outreach attempt above.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
