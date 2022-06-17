<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SystemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'name_full' => 'nullable|string',
            'name_short' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'order_column' => 'nullable|integer',
            'active' => 'nullable|boolean',
        ];
    }
}
