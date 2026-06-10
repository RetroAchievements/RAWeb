<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasteryBadgePreferenceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gameId' => 'required|integer|exists:games,id',

            // empty means "use the canonical badge" (delete the preference)
            'sha1' => 'nullable|string|size:40',
        ];
    }
}
