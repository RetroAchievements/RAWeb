<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:250',
            'lead' => 'nullable|string|min:3|max:2000',
            'body' => 'nullable|string|min:3|max:10000',
            'image' => 'nullable|image',
        ];
    }
}
