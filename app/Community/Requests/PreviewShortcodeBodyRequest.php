<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewShortcodeBodyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => 'required|string',
        ];
    }
}
