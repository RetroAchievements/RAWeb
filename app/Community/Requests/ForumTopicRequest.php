<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForumTopicRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:250',
            'body' => 'required|string|max:20000',
        ];
    }
}
