<?php

namespace App\Http\Requests\BandMembers;

use App\Enums\BandUserRoleEnum;
use App\Facades\ActiveBand;
use Illuminate\Foundation\Http\FormRequest;

class DestroyBandMemberRequest extends FormRequest
{
    /**
     * Only owners and admins manage the roster, same as adding people. Whether
     * the specific removal is allowed (e.g. not the last owner) is a business
     * rule handled in the controller/service, not an authorization concern.
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
