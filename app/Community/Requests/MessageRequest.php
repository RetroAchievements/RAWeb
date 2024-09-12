<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Support\Rules\ContainsRegularCharacter;
use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'max:60000',
                new ContainsRegularCharacter(),
            ],
            'recipient' => 'required_without:thread_id|exists:UserAccounts,User',
            'thread_id' => 'nullable|integer',
            'title' => 'required_without:thread_id|string|max:255',
        ];
    }
}
