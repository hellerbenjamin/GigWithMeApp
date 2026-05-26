<?php

namespace App\Http\Requests\Venues;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexVenuesRequest extends FormRequest
{
    /**
     * Sort key → [column, direction]. Whitelisted so raw input never reaches
     * orderBy; an unknown key falls back to the first entry (name).
     */
    public const SORTS = [
        'name' => ['name', 'asc'],
        'recent' => ['created_at', 'desc'],
        'city' => ['city', 'asc'],
    ];

    /**
     * Behind auth + HasActiveBand, so any member of the active band may list it.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Loose on purpose: bad filter values should narrow to nothing or be
     * ignored, never 422. `sort` is normalized in {@see self::filters()} rather
     * than rejected.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'has_contact' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string'],
        ];
    }

    /**
     * The normalized filter set — blank strings collapsed to null, sort coerced
     * to a known key. Echoed back to the UI so the bar mirrors the URL.
     *
     * @return array{search: ?string, country: ?string, state: ?string, has_contact: bool, sort: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->cleanString('search'),
            'country' => $this->cleanString('country'),
            'state' => $this->cleanString('state'),
            'has_contact' => $this->boolean('has_contact'),
            'sort' => $this->sortKey(),
        ];
    }

    /**
     * The active sort key, falling back to the default for anything unknown.
     */
    public function sortKey(): string
    {
        $sort = (string) $this->query('sort', '');

        return array_key_exists($sort, self::SORTS) ? $sort : array_key_first(self::SORTS);
    }

    /**
     * Trim a query param, treating an empty result as "not set".
     */
    private function cleanString(string $key): ?string
    {
        $value = trim((string) $this->query($key, ''));

        return $value !== '' ? $value : null;
    }
}
