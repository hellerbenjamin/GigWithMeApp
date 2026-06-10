<?php

namespace App\Support;

/**
 * The single source of truth for the SMS opt-in wording. The acceptance page
 * shows this verbatim, and we store the exact string with the consent record
 * (users.sms_consent_text) so we can later prove what each member agreed to.
 * Keep it in sync with the opt-in language quoted in the privacy policy
 * (resources/js/Pages/Legal/Privacy.vue) and docs/sms-consent-invite-flow.md.
 */
final class SmsConsent
{
    public const OPT_IN_TEXT = "Text me about my band's gigs. I agree to receive recurring SMS from GigWithMe at this number (gig polls, confirmations, and reminders). Msg & data rates may apply. Reply STOP to unsubscribe, HELP for help.";
}
