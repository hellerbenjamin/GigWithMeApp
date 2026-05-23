<?php

namespace App\Http\Requests\Gigs;

use App\Enums\GigStatusEnum;
use App\Enums\GigTypeEnum;
use App\Facades\ActiveBand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGigRequest extends FormRequest
{
    /**
     * Membership of the active band is already guaranteed by the HasActiveBand
     * middleware, and the venue rule below scopes the venue to that band — so
     * there's nothing further to authorize beyond being signed in.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Optional: a gig may have a TBD venue. When set, it must be one of
            // the active band's own venues — never another band's.
            'venue_id' => [
                'nullable',
                Rule::exists('venues', 'id')->where('band_id', ActiveBand::id()),
            ],
            'type' => ['required', Rule::enum(GigTypeEnum::class)],
            'status' => ['required', Rule::enum(GigStatusEnum::class)],
            'name' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],

            // Gig-day call sheet — all optional.
            'load_in_time' => ['nullable', 'date_format:H:i'],
            'soundcheck_time' => ['nullable', 'date_format:H:i'],
            'doors_time' => ['nullable', 'date_format:H:i'],
            'start_time' => ['nullable', 'date_format:H:i'],
            // Only enforce ordering when there's a start time to compare against;
            // otherwise `after:` would compare against a null field.
            'end_time' => array_values(array_filter([
                'nullable',
                'date_format:H:i',
                $this->filled('start_time') ? 'after:start_time' : null,
            ])),

            'notes' => ['nullable', 'string', 'max:2000'],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }

    public function attributes(): array
    {
        return [
            'venue_id' => 'venue',
            'load_in_time' => 'load-in time',
            'soundcheck_time' => 'soundcheck time',
            'doors_time' => 'doors time',
            'start_time' => 'start time',
            'end_time' => 'end time',
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'The end time must be later than the start time.',
        ];
    }
}
