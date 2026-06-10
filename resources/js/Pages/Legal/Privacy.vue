<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GigWithMeLogo from '../../components/GigWithMeLogo.vue';
import { useDarkMode } from '../../composables/useDarkMode';

// Public, login-free page — linked from the SMS opt-in screen and required for
// Twilio A2P registration, so it must not depend on AppLayout/auth.
const { isDark } = useDarkMode();

// Keep in sync with the date in the heading below when the policy changes.
const lastUpdated = 'June 3, 2026';
</script>

<template>
    <Head title="Privacy Policy · GigWithMe" />

    <div class="mx-auto max-w-2xl px-4 py-12 sm:py-16">
        <Link href="/login" class="inline-flex">
            <GigWithMeLogo
                class="h-8 text-stage-indigo dark:text-canvas"
                :variant="isDark ? 'dark' : 'light'"
            />
        </Link>

        <h1 class="mt-8 font-display text-3xl font-semibold tracking-tight">
            Privacy Policy
        </h1>
        <p class="mt-2 text-sm text-muted dark:text-canvas/60">
            Last updated {{ lastUpdated }}
        </p>

        <div
            class="mt-8 space-y-6 text-sm leading-relaxed text-ink/80 dark:text-canvas/75 [&_a]:text-amp-violet [&_a]:underline [&_h2]:mt-10 [&_h2]:font-display [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-ink [&_h2]:dark:text-canvas [&_li]:ml-1 [&_strong]:text-ink [&_strong]:dark:text-canvas [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5"
        >
            <p>
                GigWithMe helps a band rally its people and keep its dates on the
                books. This policy explains what we collect, how we use it, and the
                choices you have, including how text-message (SMS) consent works.
                It covers the GigWithMe app at <strong>gigwithme.app</strong> and the
                marketing site at <strong>gigwith.me</strong>.
            </p>

            <h2>Who we are</h2>
            <p>
                GigWithMe is the data controller for the information described here.
                If you have questions about this policy or your data, contact us at
                <a href="mailto:support@gigwithme.app">support@gigwithme.app</a>.
            </p>

            <h2>Information we collect</h2>
            <ul>
                <li>
                    <strong>Account details.</strong> When a band owner or admin
                    creates an account, we collect their name, email address, and a
                    hashed password.
                </li>
                <li>
                    <strong>Band member details.</strong> To run a band, an admin
                    adds members by name and email. Each member supplies and confirms
                    their own mobile phone number when they accept their invitation
                    (see <em>Text messages &amp; consent</em> below).
                </li>
                <li>
                    <strong>Band &amp; gig data.</strong> Bands, gigs, dates,
                    venues and their contact details, availability replies, and
                    related notes.
                </li>
                <li>
                    <strong>Notification tokens.</strong> If you enable web push
                    alerts, we store a push subscription token for your device.
                </li>
                <li>
                    <strong>Technical data.</strong> Standard server logs (such as
                    IP address and timestamps) and the consent records described
                    below.
                </li>
            </ul>

            <h2>How we use your information</h2>
            <ul>
                <li>To provide the service: managing bands, gigs, venues, and availability.</li>
                <li>To send you the gig notifications you've asked for, by your chosen channels.</li>
                <li>To secure your account and operate, maintain, and improve the app.</li>
                <li>To comply with our legal obligations.</li>
            </ul>
            <p>
                We do <strong>not</strong> sell your personal information, and we do
                not share your mobile number or messaging consent with third parties
                for their own marketing.
            </p>

            <h2>Text messages &amp; consent</h2>
            <p>
                GigWithMe can text band members about their gigs. We only send SMS to
                people who have opted in themselves:
            </p>
            <ul>
                <li>
                    An admin invites a member by email. <strong>No text is sent at
                    this point.</strong> Adding a member does not turn on texting.
                </li>
                <li>
                    The member opens their invitation and, on the acceptance page,
                    confirms their own mobile number and ticks an opt-in box that
                    reads: <em>"Text me about my band's gigs. I agree to receive
                    recurring SMS from GigWithMe at this number (gig polls,
                    confirmations, and reminders). Msg &amp; data rates may apply.
                    Reply STOP to unsubscribe, HELP for help."</em>
                </li>
                <li>
                    Only after that opt-in will we text that number. We keep a record
                    of when consent was given and the wording agreed to.
                </li>
            </ul>
            <p>
                <strong>Message types &amp; frequency.</strong> Messages are tied to
                your band's activity. For example, an availability poll when a gig is
                being booked, a confirmation when it's locked in, and reminders. There
                is no fixed schedule; volume depends on how often your band books gigs.
            </p>
            <p>
                <strong>Rates.</strong> Message and data rates may apply, depending on
                your mobile carrier and plan.
            </p>
            <p>
                <strong>Opting out.</strong> Reply <strong>STOP</strong> to any message
                to unsubscribe at any time; we'll stop texting that number. Reply
                <strong>HELP</strong> for help. Opting out of SMS doesn't remove you
                from your band; you can still get notified by email or web push.
            </p>

            <h2>Email &amp; push notifications</h2>
            <p>
                We send transactional email (such as invitations and gig
                notifications) to the address on your account. If you turn on web
                push, we send alerts to that device; you can turn push off at any time
                from your device or notification settings.
            </p>

            <h2>Service providers</h2>
            <p>
                We rely on a few trusted providers to run GigWithMe, and share only
                what they need to do their job:
            </p>
            <ul>
                <li><strong>Twilio</strong>, to deliver SMS messages.</li>
                <li><strong>Mailgun</strong>, to deliver email.</li>
            </ul>

            <h2>Data retention</h2>
            <p>
                We keep your information for as long as your account or band is active,
                and as needed to provide the service and meet legal obligations. We
                retain SMS consent and opt-out records as required to demonstrate
                compliance.
            </p>

            <h2>Your choices &amp; rights</h2>
            <p>
                You can review and update your account details in the app, opt out of
                SMS at any time, and request access to or deletion of your personal
                information by contacting
                <a href="mailto:support@gigwithme.app">support@gigwithme.app</a>. Depending
                on where you live, you may have additional rights under applicable
                privacy laws.
            </p>

            <h2>Children's privacy</h2>
            <p>
                GigWithMe is not directed to children under 13, and we do not knowingly
                collect personal information from them.
            </p>

            <h2>Changes to this policy</h2>
            <p>
                We may update this policy from time to time. When we do, we'll revise
                the "Last updated" date above, and material changes will be
                communicated through the app.
            </p>

            <h2>Contact</h2>
            <p>
                Questions? Reach us at
                <a href="mailto:support@gigwithme.app">support@gigwithme.app</a>.
            </p>
        </div>

        <p class="mt-10 border-t border-ink/10 pt-6 text-sm text-muted dark:border-canvas/15 dark:text-canvas/60">
            See also our
            <Link href="/terms" class="text-amp-violet underline">Terms &amp; Conditions</Link>.
        </p>
    </div>
</template>
