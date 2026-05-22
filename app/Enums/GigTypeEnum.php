<?php

namespace App\Enums;

/**
 * What kind of calendar event a "gig" record represents. Lets the calendar
 * hold rehearsals and other events alongside performances.
 */
enum GigTypeEnum: string
{
    case Gig = 'gig';
    case Rehearsal = 'rehearsal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Gig => 'Gig',
            self::Rehearsal => 'Rehearsal',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
