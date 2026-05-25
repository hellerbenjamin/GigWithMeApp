<?php

namespace App\Enums;

/**
 * How a gig reaches a confirmed booking. Chosen per gig; a band's
 * default_booking_mode pre-selects it on the create form. See
 * docs/gig-booking-flow.md.
 */
enum GigBookingModeEnum: string
{
    case Auto = 'auto';
    case Poll = 'poll';

    // Availability (flow 3) is deferred: members supply availability ahead of
    // time and the admin is warned of conflicts at creation. The schema leaves
    // room for it — see the plan doc — but it isn't built yet.
    // case Availability = 'availability';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Auto-confirm',
            self::Poll => 'Poll the band',
        };
    }

    /**
     * One-line helper text for the booking-mode picker.
     */
    public function description(): string
    {
        return match ($this) {
            self::Auto => 'Book it as confirmed and text everyone the details.',
            self::Poll => 'Ask each member if they can make it before confirming.',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $m) => $m->value, self::cases());
    }

    /**
     * Modes shaped for a PrimeVue Select, carrying the helper-text description
     * each mode shows beneath the picker.
     *
     * @return array<int, array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $mode) => [
                'value' => $mode->value,
                'label' => $mode->label(),
                'description' => $mode->description(),
            ],
            self::cases(),
        );
    }
}
