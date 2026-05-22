<?php

namespace App\Enums;

/**
 * Booking status of a gig. Values map to the status colors defined in
 * docs/band-booking-color-palette.md.
 */
enum GigStatusEnum: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * PrimeVue severity for Tag/Badge components.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Confirmed => 'success',
            self::Cancelled => 'danger',
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
