<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBetaFeedbackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'betaName' => 'required|string|max:100',
            'rating' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'positiveFeedback' => 'sometimes|string',
            'negativeFeedback' => 'sometimes|string',
        ];
    }
}
