<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import GigWithMeLogo from '../components/GigWithMeLogo.vue';
import { useDarkMode } from '../composables/useDarkMode';

// Public, login-free homepage. Besides introducing the product, it's the
// website we give Vonage / the carriers for A2P 10DLC campaign registration,
// so it must spell out the SMS program: who gets texts, how they opt in (with
// the exact wording), sample messages, frequency, rates, STOP/HELP, and links
// to the Privacy Policy and Terms. Keep the opt-in text in sync with
// App\Support\SmsConsent::OPT_IN_TEXT and the samples with the notifications'
// toVonage() methods.
const { isDark } = useDarkMode();

const signedIn = computed(() => !!usePage().props.auth?.user);

const optInText =
    "Text me about my band's gigs. I agree to receive recurring SMS from GigWithMe at this number (gig polls, confirmations, and reminders). Msg & data rates may apply. Reply STOP to unsubscribe, HELP for help.";

const features = [
    {
        icon: 'pi pi-users',
        title: 'Rally the band',
        body: 'Invite your members by email. Each person sets up their own notifications, their own way.',
    },
    {
        icon: 'pi pi-check-square',
        title: 'Poll for availability',
        body: 'Got an offer from a venue? Ask the whole band if they can make it and see the replies roll in.',
    },
    {
        icon: 'pi pi-calendar',
        title: 'Keep dates on the books',
        body: 'Confirm the gig and everyone hears about it. Subscribe to your gigs in any calendar app.',
    },
];

const sampleMessages = [
    'The Tidewater Band: can you make The Rusty Anchor on Fri, Oct 9? Tap to reply: https://gigwithme.app/rsvp/…',
    'The Tidewater Band: your gig at The Rusty Anchor on Fri, Oct 9 at 8:00 PM is confirmed.',
    'The Tidewater Band: the band replied about The Rusty Anchor on Fri, Oct 9, but not everyone can make it. Your call: https://gigwithme.app/gigs/…',
];
</script>

<template>
    <Head title="GigWithMe · Gig management for bands" />

    <div class="min-h-screen bg-canvas text-ink dark:bg-backstage dark:text-canvas">
        <header class="mx-auto flex max-w-5xl items-center justify-between px-4 py-6">
            <Link href="/" class="inline-flex" aria-label="GigWithMe home">
                <GigWithMeLogo
                    class="h-8 text-stage-indigo dark:text-canvas"
                    :variant="isDark ? 'dark' : 'light'"
                />
            </Link>
            <Link
                :href="signedIn ? '/dashboard' : '/login'"
                class="text-sm font-medium text-amp-violet hover:underline"
            >
                {{ signedIn ? 'Open the app' : 'Log in' }}
            </Link>
        </header>

        <main>
            <!-- Hero -->
            <section class="mx-auto max-w-5xl px-4 pt-10 pb-16 sm:pt-16 sm:pb-24">
                <h1 class="max-w-3xl font-display text-4xl font-semibold tracking-tight sm:text-6xl">
                    Rally your band. Keep your dates on the books.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink/75 dark:text-canvas/70">
                    GigWithMe is gig management for working bands. Track venues, ask
                    the band who's free, lock in the date, and make sure everyone
                    hears about it by text, email, or push notification.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        :href="signedIn ? '/dashboard' : '/login'"
                        class="rounded-lg bg-amp-violet px-5 py-3 text-sm font-semibold text-white hover:opacity-90"
                    >
                        {{ signedIn ? 'Open GigWithMe' : 'Log in to GigWithMe' }}
                    </Link>
                    <a
                        href="#text-messages"
                        class="rounded-lg border border-ink/15 px-5 py-3 text-sm font-semibold hover:bg-surface dark:border-canvas/20 dark:hover:bg-riser"
                    >
                        About our text messages
                    </a>
                </div>
            </section>

            <!-- Features -->
            <section class="border-y border-ink/10 bg-surface/60 dark:border-canvas/10 dark:bg-riser">
                <div class="mx-auto grid max-w-5xl gap-8 px-4 py-14 sm:grid-cols-3">
                    <div v-for="feature in features" :key="feature.title">
                        <i :class="feature.icon" class="text-2xl text-amp-violet" aria-hidden="true" />
                        <h2 class="mt-3 font-display text-lg font-semibold">{{ feature.title }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-ink/75 dark:text-canvas/70">
                            {{ feature.body }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- SMS program disclosure (A2P 10DLC) -->
            <section id="text-messages" class="mx-auto max-w-3xl scroll-mt-8 px-4 py-16 sm:py-20">
                <h2 class="font-display text-3xl font-semibold tracking-tight">
                    Text messages from GigWithMe
                </h2>
                <p class="mt-4 leading-relaxed text-ink/75 dark:text-canvas/70">
                    GigWithMe sends transactional text messages to band members about
                    their own band's gigs: availability polls, confirmations, and
                    reminders. We never send marketing or promotional texts, and we only
                    text people who have opted in themselves.
                </p>

                <h3 class="mt-10 font-display text-xl font-semibold">How members opt in</h3>
                <ol class="mt-4 list-decimal space-y-3 pl-5 text-sm leading-relaxed text-ink/80 dark:text-canvas/75">
                    <li>
                        A band admin invites a member by <strong>email</strong>. Adding a
                        member does not turn on texting, and no text is sent at this
                        point.
                    </li>
                    <li>
                        The member opens the invitation link on gigwithme.app, enters and
                        confirms <strong>their own</strong> mobile number, and chooses
                        whether to tick an opt-in checkbox. The box is unticked by
                        default and is not required to join the band.
                    </li>
                    <li>
                        Only after the member ticks the box and submits the form do we
                        text that number. We record when consent was given and the exact
                        wording agreed to.
                    </li>
                </ol>

                <p class="mt-6 text-sm font-medium">The opt-in checkbox reads:</p>
                <blockquote
                    class="mt-2 rounded-lg border-l-4 border-amp-violet bg-surface px-4 py-3 text-sm leading-relaxed dark:bg-riser"
                >
                    {{ optInText }}
                </blockquote>

                <h3 class="mt-10 font-display text-xl font-semibold">Sample messages</h3>
                <ul class="mt-4 space-y-3">
                    <li
                        v-for="message in sampleMessages"
                        :key="message"
                        class="max-w-md rounded-2xl rounded-bl-sm bg-surface px-4 py-3 text-sm leading-relaxed dark:bg-riser"
                    >
                        {{ message }}
                    </li>
                </ul>

                <h3 class="mt-10 font-display text-xl font-semibold">The details</h3>
                <dl class="mt-4 space-y-4 text-sm leading-relaxed text-ink/80 dark:text-canvas/75">
                    <div>
                        <dt class="font-semibold text-ink dark:text-canvas">Message frequency</dt>
                        <dd>
                            Varies with your band's activity (for example, when a gig is
                            being booked or confirmed). There is no fixed schedule.
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink dark:text-canvas">Cost</dt>
                        <dd>Message and data rates may apply, depending on your carrier and plan.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink dark:text-canvas">Opting out</dt>
                        <dd>
                            Reply <strong>STOP</strong> to any message to unsubscribe at any
                            time. You'll get one confirmation text and no more after that.
                            Opting out doesn't remove you from your band; you can still hear
                            about gigs by email or push.
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink dark:text-canvas">Help</dt>
                        <dd>
                            Reply <strong>HELP</strong> to any message, or email
                            <a href="mailto:support@gigwithme.app" class="text-amp-violet underline">support@gigwithme.app</a>.
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink dark:text-canvas">Your number stays private</dt>
                        <dd>
                            We do not sell or share mobile numbers or opt-in data with third
                            parties or affiliates for their marketing purposes.
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink dark:text-canvas">Carriers</dt>
                        <dd>Carriers are not liable for delayed or undelivered messages.</dd>
                    </div>
                </dl>

                <p class="mt-8 text-sm leading-relaxed text-ink/80 dark:text-canvas/75">
                    Read the full
                    <Link href="/privacy" class="text-amp-violet underline">Privacy Policy</Link>
                    and
                    <Link href="/terms" class="text-amp-violet underline">Terms &amp; Conditions</Link>.
                </p>
            </section>
        </main>

        <footer class="border-t border-ink/10 dark:border-canvas/10">
            <div
                class="mx-auto flex max-w-5xl flex-col gap-3 px-4 py-8 text-sm text-muted sm:flex-row sm:items-center sm:justify-between dark:text-canvas/60"
            >
                <p>&copy; {{ new Date().getFullYear() }} Benjamin Heller LLC. GigWithMe is operated by Benjamin Heller LLC.</p>
                <nav class="flex flex-wrap gap-4">
                    <Link href="/privacy" class="hover:underline">Privacy</Link>
                    <Link href="/terms" class="hover:underline">Terms</Link>
                    <a href="mailto:support@gigwithme.app" class="hover:underline">support@gigwithme.app</a>
                </nav>
            </div>
        </footer>
    </div>
</template>
