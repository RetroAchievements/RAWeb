<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventAwardTierPreferenceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'eventId' => 'required|integer|exists:events,id',
            'tierIndex' => 'nullable|integer|min:0',
        ];
    }
}
