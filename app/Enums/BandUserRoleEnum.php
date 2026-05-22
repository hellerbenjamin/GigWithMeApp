<?php

namespace App\Enums;

/**
 * A user's role within a band, stored in the `band_user.role` pivot column.
 *
 * String-backed with lowercase values so the database column, this enum, and
 * any `wherePivot('role', …)` filters all share one representation.
 */
enum BandUserRoleEnum: string
{
    case Member = 'member';
    case Admin = 'admin';
    case Owner = 'owner';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::Admin => 'Admin',
            self::Owner => 'Owner',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
