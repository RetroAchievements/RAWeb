<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaContributionTierPreferenceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // null means "use the highest earned tier" (clear the preference)
            'tierIndex' => 'nullable|integer|min:0',
        ];
    }
}
