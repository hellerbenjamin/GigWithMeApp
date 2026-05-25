# GigWithMe — Data Model

The data layer recycled from the original project (`git@github.com:hellerbenjamin/band.git`). It models a **band-booking / gig-management** app: users belong to bands, bands own venues and book gigs at them.

This is the canonical schema for the restart. Where the original's two branches disagreed, the choices and the bugs to fix on the way in are noted at the bottom.

## Entity relationships

```
users  ──< band_user >──  bands          many-to-many; pivot carries `role`
                            │
                            ├──< venues   one-to-many (venues.band_id)
                            └──< gigs     one-to-many (gigs.band_id)

venues ──────────────────< gigs          one-to-many (gigs.venue_id)
```

- A **user** belongs to many **bands**, and a band has many users, through the **band_user** pivot which stores the user's `role` in that band.
- A **band** owns many **venues** and many **gigs**.
- A **gig** belongs to one **band** and one **venue**.
- All foreign keys are `ON DELETE CASCADE`.

## Tables

### users
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string | unique |
| email_verified_at | timestamp | nullable |
| password | string | cast `hashed` |
| phone_number | string | nullable |
| remember_token | string | |
| timestamps | | |

Standard Laravel auth scaffolding (also creates `password_reset_tokens` and `sessions`).

### bands
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| genre | string | nullable |
| website | string | nullable |
| email | string | nullable |
| facebook | string | nullable |
| instagram | string | nullable |
| twitter | string | nullable |
| youtube | string | nullable |
| spotify | string | nullable |
| apple_music | string | nullable |
| bandcamp | string | nullable |
| soundcloud | string | nullable |
| tiktok | string | nullable |
| twitch | string | nullable |
| patreon | string | nullable |
| timestamps | | |

### band_user (pivot)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| band_id | FK → bands | cascade |
| user_id | FK → users | cascade |
| role | string | default `member`; see `BandUserRoleEnum` |
| timestamps | | |

### venues
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| band_id | FK → bands | cascade |
| name | text | required |
| address | text | nullable |
| city | text | nullable |
| state | text | nullable |
| country | text | nullable |
| postal_code | string | nullable |
| phone | text | nullable |
| email | text | nullable |
| website | text | nullable |
| contact_person | text | nullable |
| contact_email | text | nullable |
| contact_phone | text | nullable |
| notes | text | nullable |
| timestamps | | |

### gigs
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| band_id | FK → bands | cascade |
| venue_id | FK → venues | cascade |
| name | string | nullable |
| date | date | required |
| start_time | time | nullable |
| end_time | time | nullable |
| notes | text | nullable |
| fee | decimal(10,2) | nullable |
| timestamps | | |

## Eloquent relationships

| Model | Relationships |
|-------|---------------|
| `User` | `bands()` belongsToMany(Band, 'band_user') withPivot('role') withTimestamps |
| `Band` | `users()` belongsToMany withPivot('role'); `owners()` / `admins()` = `users()->wherePivot('role', …)`; `gigs()` hasMany; `venues()` hasMany; helper `getUserRole(User): ?string` |
| `Venue` | belongsTo(Band); hasMany(Gig) |
| `Gig` | belongsTo(Band); belongsTo(Venue) |
| `BandUser` | belongsTo(Band); belongsTo(User) |

## Roles — `App\Enums\BandUserRoleEnum`

Three roles: **Member**, **Admin**, **Owner**. Stored in `band_user.role`, defaulting to member.

> Recommended for the restart: define it as a **string-backed enum with lowercase values** (`'member'`, `'admin'`, `'owner'`) so the DB column, the enum, and `wherePivot()` filters all use the same representation.

```php
enum BandUserRoleEnum: string
{
    case Member = 'member';
    case Admin  = 'admin';
    case Owner  = 'owner';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
```

## Carried over from the original — decisions & fixes

This data model merges the two branches of the original repo and corrects latent bugs:

- **`bands` schema:** use the rich version (genre + social links). `master` had regressed `bands` to just `id, name`; `feature/vuetify` kept the full set.
- **`users.phone_number`:** taken from `master` (a later addition).
- **Role casing bug (fixed above):** the original DB/filters used lowercase roles, but the enum was a *pure* (non-backed) enum returning the uppercase case name — they never matched. Use the string-backed enum.
- **`BandUserFactory`:** the original seeded `role => faker->word` (invalid). Seed a real `BandUserRoleEnum` value instead.
- **Thin models:** the original `Gig` declared only `venue()` and `Venue` declared no relationships, with no casts. The relationships and casts above (`date`, `fee`) should be defined explicitly.

## Suggested model casts

```php
// Gig
protected $casts = [
    'date' => 'date',
    'fee'  => 'decimal:2',
];
```

---

See [band-booking-color-palette.md](band-booking-color-palette.md) for the visual design tokens.

