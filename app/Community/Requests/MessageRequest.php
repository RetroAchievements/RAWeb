<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'recipient' => 'required|string|min:3|max:250',
            'subject' => 'required|string|min:3|max:250',
            'message' => 'required|string|min:3|max:2000',
        ];
    }
}
