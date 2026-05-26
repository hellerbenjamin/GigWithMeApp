<?php

namespace App\Http\Requests\Venues;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The uploaded CSV itself, before any column mapping. Like the rest of the
 * venue requests, the route already runs behind auth + HasActiveBand, so any
 * band member may import.
 */
class ParseVenueImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'CSV file',
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Upload a .csv file exported from your spreadsheet.',
            'file.max' => 'That file is larger than 5 MB — split it into smaller imports.',
        ];
    }
}
