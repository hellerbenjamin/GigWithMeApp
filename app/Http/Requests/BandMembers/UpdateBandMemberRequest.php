<?php

namespace App\Http\Requests\BandMembers;

use App\Enums\BandUserRoleEnum;
use App\Facades\ActiveBand;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBandMemberRequest extends FormRequest
{
    /**
     * Only owners and admins manage the roster. Whether a specific role change
     * is allowed (e.g. not demoting the last owner) is a business rule handled
     * in the controller/service, not an authorization concern.
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
     * Normalise the same way the store request does, so edits and adds store
     * the email/name in a consistent shape.
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
        /** @var User $member */
        $member = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required', 'email', 'max:255',
                // Editing a shared account: the email must stay unique across
                // users, but the member's own current email is fine to keep.
                Rule::unique('users', 'email')->ignore($member->getKey()),
            ],

            'role' => ['required', Rule::enum(BandUserRoleEnum::class)],
        ];
    }
}
