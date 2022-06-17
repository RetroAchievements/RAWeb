<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => 'required|string|min:3|max:250',
        ];
    }
}
