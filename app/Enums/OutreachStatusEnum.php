<?php

namespace App\Enums;

enum OutreachStatusEnum: string
{
    case Targeting = 'targeting';
    case Contacted = 'contacted';
    case InDiscussion = 'in_discussion';
    case Booked = 'booked';
    case Declined = 'declined';
    case NoResponse = 'no_response';

    public function label(): string
    {
        return match ($this) {
            self::Targeting => 'Targeting',
            self::Contacted => 'Contacted',
            self::InDiscussion => 'In Discussion',
            self::Booked => 'Booked',
            self::Declined => 'Declined',
            self::NoResponse => 'No Response',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Booked, self::Declined, self::NoResponse], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
