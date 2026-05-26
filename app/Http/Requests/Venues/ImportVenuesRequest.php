<?php

namespace App\Http\Requests\Venues;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The column→field mapping the user confirms on the second import step. The
 * uploaded file is already on the server (referenced by token in the route),
 * so all this carries is which CSV column feeds which venue field.
 */
class ImportVenuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Each mapping value is a zero-based CSV column index. `name` is the one
     * required target — everything else is optional. Out-of-range indices are
     * tolerated and simply read as blank cells when importing.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'mapping' => ['required', 'array'],
            'mapping.name' => ['required', 'integer', 'min:0'],
            'mapping.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'mapping.name.required' => 'Choose which column holds the venue name before importing.',
        ];
    }
}
