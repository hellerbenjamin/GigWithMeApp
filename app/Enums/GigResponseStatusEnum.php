<?php

namespace App\Enums;

/**
 * A band member's reply to a gig poll. Lives on gig_member_responses.status.
 * See docs/gig-booking-flow.md.
 */
enum GigResponseStatusEnum: string
{
    case Pending = 'pending';
    case Available = 'available';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting reply',
            self::Available => 'Available',
            self::Unavailable => "Can't make it",
        };
    }

    /**
     * PrimeVue severity for Tag/Badge components.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Available => 'success',
            self::Unavailable => 'danger',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
