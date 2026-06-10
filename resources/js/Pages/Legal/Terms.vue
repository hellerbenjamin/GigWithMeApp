<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GigWithMeLogo from '../../components/GigWithMeLogo.vue';
import { useDarkMode } from '../../composables/useDarkMode';

// Public, login-free page — companion to the Privacy Policy, linked from the SMS
// opt-in screen and surfaced for Vonage / carrier A2P registration, so it must not depend
// on AppLayout/auth.
const { isDark } = useDarkMode();

// Keep in sync with the date in docs/terms-and-conditions.md when the Terms change.
const lastUpdated = 'June 9, 2026';
</script>

<template>
    <Head title="Terms & Conditions · GigWithMe" />

    <div class="mx-auto max-w-2xl px-4 py-12 sm:py-16">
        <Link href="/login" class="inline-flex">
            <GigWithMeLogo
                class="h-8 text-stage-indigo dark:text-canvas"
                :variant="isDark ? 'dark' : 'light'"
            />
        </Link>

        <h1 class="mt-8 font-display text-3xl font-semibold tracking-tight">
            Terms &amp; Conditions
        </h1>
        <p class="mt-2 text-sm text-muted dark:text-canvas/60">
            Last updated {{ lastUpdated }}
        </p>

        <div
            class="mt-8 space-y-6 text-sm leading-relaxed text-ink/80 dark:text-canvas/75 [&_a]:text-amp-violet [&_a]:underline [&_h2]:mt-10 [&_h2]:font-display [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-ink [&_h2]:dark:text-canvas [&_li]:ml-1 [&_strong]:text-ink [&_strong]:dark:text-canvas [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5"
        >
            <p>
                These Terms &amp; Conditions ("Terms") govern your access to and use
                of GigWithMe (the "Service"), operated by
                <strong>Benjamin Heller LLC</strong>, an Alaska limited liability
                company ("GigWithMe," "we," "us," or "our"). The marketing site is
                <strong>gigwith.me</strong> and the application is hosted at
                <strong>gigwithme.app</strong>. By creating an account, accessing, or
                using the Service, you agree to be bound by these Terms. If you do not
                agree, do not use the Service.
            </p>

            <h2>1. The Service</h2>
            <p>
                GigWithMe is a band-booking and gig-management application that helps a
                band organize its members, manage venues, schedule and confirm gigs,
                run availability polls, and send gig-related notifications to band
                members by SMS text message, email, and web push.
            </p>

            <h2>2. Eligibility</h2>
            <p>
                You must be at least 18 years old (or the age of majority in your
                jurisdiction) and able to form a binding contract to use the Service.
                By using the Service you represent that you meet these requirements and
                that the information you provide is accurate.
            </p>

            <h2>3. Accounts</h2>
            <ul>
                <li>
                    You are responsible for safeguarding your account credentials and
                    for all activity that occurs under your account.
                </li>
                <li>
                    You must provide accurate contact information, including a valid
                    mobile phone number if you choose to receive SMS notifications.
                </li>
                <li>
                    Notify us promptly at
                    <a href="mailto:support@gigwithme.app">support@gigwithme.app</a>
                    of any unauthorized use of your account.
                </li>
            </ul>

            <h2>4. Acceptable Use</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Use the Service for any unlawful, harmful, or fraudulent purpose;</li>
                <li>
                    Add a phone number to a band or send notifications to anyone who has
                    not consented to receive them;
                </li>
                <li>Send spam, harassing, or unsolicited messages through the Service;</li>
                <li>
                    Attempt to gain unauthorized access to the Service, other accounts,
                    or our systems;
                </li>
                <li>Interfere with or disrupt the integrity or performance of the Service;</li>
                <li>
                    Reverse engineer, scrape, or resell the Service except as permitted
                    by law.
                </li>
            </ul>

            <h2>5. SMS Messaging Terms</h2>
            <p>
                These messaging terms apply to the SMS/text-message program operated
                through the Service.
            </p>
            <ul>
                <li>
                    <strong>Program description.</strong> GigWithMe sends transactional,
                    gig-related text messages to band members. For example, gig
                    confirmations, availability-poll invitations, reminders when a poll
                    needs attention, and account or test messages you trigger. We do
                    <strong>not</strong> send marketing or promotional text messages
                    through this program.
                </li>
                <li>
                    <strong>Consent / opt-in.</strong> By providing your mobile number
                    and opting in within the Service, you consent to receive SMS messages
                    from GigWithMe at that number. Consent to receive text messages is
                    <strong>not</strong> a condition of purchasing any goods or services.
                    You may use the Service's email or web-push channels instead of SMS.
                </li>
                <li>
                    <strong>Message frequency.</strong> Message frequency varies and
                    depends on your band's activity (for example, how often gigs are
                    scheduled or polls are opened).
                </li>
                <li>
                    <strong>Message and data rates.</strong>
                    <strong>Message and data rates may apply.</strong> Check with your
                    mobile carrier for details about your plan.
                </li>
                <li>
                    <strong>Opt-out (STOP).</strong> You can cancel SMS messages at any
                    time by replying <strong>STOP</strong> to any message. After you send
                    <strong>STOP</strong>, we will send a one-time confirmation and you
                    will no longer receive SMS messages. You can also disable SMS
                    notifications in your account settings. To resume, reply
                    <strong>START</strong> or re-enable SMS in your settings.
                </li>
                <li>
                    <strong>Help (HELP).</strong> For help, reply <strong>HELP</strong>
                    to any message or contact us at
                    <a href="mailto:support@gigwithme.app">support@gigwithme.app</a>.
                </li>
                <li>
                    <strong>Supported carriers.</strong> Carriers are not liable for
                    delayed or undelivered messages. Message delivery is subject to
                    effective transmission by your mobile carrier and is not guaranteed.
                </li>
                <li>
                    <strong>Privacy.</strong> Information you provide in connection with
                    SMS messaging is handled in accordance with our
                    <Link href="/privacy">Privacy Policy</Link>. We do not sell or share
                    mobile opt-in data or phone numbers with third parties or affiliates
                    for their marketing purposes.
                </li>
            </ul>

            <h2>6. User Content</h2>
            <p>
                You retain ownership of the content you submit to the Service (band,
                member, venue, and gig information). You grant us a limited,
                non-exclusive license to host, store, and process that content solely to
                operate and provide the Service. You are responsible for ensuring you
                have the rights and consents necessary to submit any content, including
                the personal contact details of band members.
            </p>

            <h2>7. Third-Party Services</h2>
            <p>
                The Service relies on third-party providers to function, including but
                not limited to SMS delivery (<strong>Vonage</strong>), email delivery
                (<strong>Mailgun</strong>), and web-push delivery. Your use of the
                Service may be subject to those providers' terms, and we are not
                responsible for their acts or omissions.
            </p>

            <h2>8. Fees</h2>
            <p>
                The Service is currently provided free of charge. We may introduce paid
                plans or paid features in the future. If we do, the applicable pricing,
                billing cycle, renewal, and refund terms will be presented to you before
                you incur any charge, and your continued use of a paid feature after
                those terms are presented constitutes your agreement to them.
            </p>

            <h2>9. Intellectual Property</h2>
            <p>
                The Service, including its software, design, branding, and the
                "GigWithMe" wordmark, is owned by us or our licensors and is protected by
                intellectual property laws. These Terms do not grant you any right to use
                our trademarks without prior written permission.
            </p>

            <h2>10. Termination</h2>
            <p>
                We may suspend or terminate your access to the Service at any time, with
                or without cause or notice, including for violation of these Terms. You
                may stop using the Service and delete your account at any time.
                Provisions that by their nature should survive termination (e.g.,
                ownership, disclaimers, limitations of liability) will survive.
            </p>

            <h2>11. Disclaimers</h2>
            <p>
                The Service is provided <strong>"as is"</strong> and
                <strong>"as available"</strong> without warranties of any kind, whether
                express or implied, including warranties of merchantability, fitness for
                a particular purpose, and non-infringement. We do not warrant that the
                Service will be uninterrupted, secure, error-free, or that messages will
                be delivered on time or at all.
            </p>

            <h2>12. Limitation of Liability</h2>
            <p>
                To the maximum extent permitted by law, GigWithMe and its officers,
                employees, and suppliers will not be liable for any indirect, incidental,
                special, consequential, or punitive damages, or any loss of profits,
                data, or goodwill, arising out of or related to your use of the Service.
                Our total liability for any claim relating to the Service will not exceed
                the greater of the amount you paid us in the 12 months before the claim,
                or USD $100.
            </p>

            <h2>13. Indemnification</h2>
            <p>
                You agree to indemnify and hold harmless GigWithMe from any claims,
                damages, or expenses (including reasonable attorneys' fees) arising from
                your use of the Service, your content, or your violation of these Terms
                or applicable law, including any claim that you sent messages to a
                recipient who did not consent.
            </p>

            <h2>14. Changes to These Terms</h2>
            <p>
                We may update these Terms from time to time. If we make material changes,
                we will notify you by updating the "Last updated" date above and, where
                appropriate, through the Service. Your continued use of the Service after
                changes take effect constitutes acceptance of the revised Terms.
            </p>

            <h2>15. Governing Law</h2>
            <p>
                These Terms are governed by the laws of the
                <strong>State of Alaska</strong>, without regard to its conflict-of-laws
                rules. Any disputes will be resolved in the state or federal courts
                located in Alaska, unless otherwise required by applicable law.
            </p>

            <h2>16. Contact Us</h2>
            <p>
                If you have questions about these Terms, contact us at
                <a href="mailto:support@gigwithme.app">support@gigwithme.app</a>.
            </p>
        </div>

        <p class="mt-10 border-t border-ink/10 pt-6 text-sm text-muted dark:border-canvas/15 dark:text-canvas/60">
            See also our
            <Link href="/privacy" class="text-amp-violet underline">Privacy Policy</Link>.
        </p>
    </div>
</template>
