<?php

namespace App\Http\Requests\Bands;

use App\Enums\BandUserRoleEnum;
use App\Enums\GigBookingModeEnum;
use App\Facades\ActiveBand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBandRequest extends FormRequest
{
    /**
     * Editing band settings reaches the whole band, so it's limited to the
     * active band's owners and admins (members can view but not change).
     */
    public function authorize(): bool
    {
        $band = ActiveBand::band();

        return $band !== null && in_array(
            $band->getUserRole($this->user()),
            [BandUserRoleEnum::Owner, BandUserRoleEnum::Admin],
            true,
        );
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'genres' => ['array', 'max:10'],
            'genres.*' => ['string', 'max:50'],
            'hometown' => ['nullable', 'string', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y'))],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_booking_mode' => ['required', Rule::enum(GigBookingModeEnum::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'founded_year' => 'founding year',
            'default_currency' => 'currency',
        ];
    }
}
