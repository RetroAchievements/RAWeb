<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmulatorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'link' => 'nullable|string',
            'active' => 'nullable|boolean',
        ];
    }
}
