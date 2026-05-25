<?php

namespace App\Http\Requests\Gigs;

use App\Enums\GigStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Editing a gig shares the same field rules as booking one (see
 * {@see StoreGigRequest::baseRules()}), but where the create form picks a
 * booking mode, the edit form manages the gig's status directly — confirm,
 * cancel, or set it back to pending.
 */
class UpdateGigRequest extends StoreGigRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'status' => ['required', Rule::enum(GigStatusEnum::class)],
        ];
    }
}
