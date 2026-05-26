<?php

namespace App\Http\Requests\Venues;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVenueRequest extends FormRequest
{
    /**
     * The route already runs behind auth + HasActiveBand, so any member of the
     * active band may add a venue to it.
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
        return self::venueRules();
    }

    /**
     * The per-field venue rules, kept here as the single source of truth so the
     * CSV importer ({@see \App\Services\VenueImportService}) validates each row
     * against exactly the same constraints as the create/edit forms.
     *
     * @return array<string, array<mixed>>
     */
    public static function venueRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'contact_person' => 'contact name',
            'contact_email' => 'contact email',
            'contact_phone' => 'contact phone',
        ];
    }
}
