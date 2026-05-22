<?php

namespace App\Http\Requests\Bands;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBandRequest extends FormRequest
{
    /**
     * Any authenticated user may start a band.
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
            'name' => ['required', 'string', 'max:255'],
            'genres' => ['array', 'max:10'],
            'genres.*' => ['string', 'max:50'],
            'hometown' => ['nullable', 'string', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y'))],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'founded_year' => 'founding year',
        ];
    }
}
