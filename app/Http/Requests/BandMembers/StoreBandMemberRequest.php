<?php

namespace App\Http\Requests\BandMembers;

use App\Enums\BandUserRoleEnum;
use App\Facades\ActiveBand;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreBandMemberRequest extends FormRequest
{
    /**
     * Only owners and admins manage the roster. Membership of the active band is
     * already guaranteed by HasActiveBand; here we narrow that to the two roles
     * allowed to add people.
     */
    public function authorize(): bool
    {
        $band = ActiveBand::band();

        if ($band === null || $this->user() === null) {
            return false;
        }

        return in_array(
            $band->getUserRole($this->user()),
            [BandUserRoleEnum::Owner, BandUserRoleEnum::Admin],
            true,
        );
    }

    /**
     * Normalise the email up front so the duplicate-membership check and the
     * eventual user lookup compare against the stored, lower-cased form.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'email' => is_string($this->email) ? Str::of($this->email)->trim()->lower()->value() : $this->email,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Used only when the email is unknown and a new account is created;
            // for an existing user their own account name wins, but we still
            // require it so a fresh account always has a name.
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required', 'email', 'max:255',
                // Reject anyone already on this band's roster — the pivot has a
                // (band_id, user_id) unique key, and this is a friendlier error.
                function (string $attribute, mixed $value, callable $fail): void {
                    $band = ActiveBand::band();
                    $user = User::where('email', $value)->first();

                    if ($band && $user && $band->users()->whereKey($user->getKey())->exists()) {
                        $fail('That person is already in your band.');
                    }
                },
            ],

            'role' => ['required', Rule::enum(BandUserRoleEnum::class)],
            'critical' => ['boolean'],
        ];
    }
}
