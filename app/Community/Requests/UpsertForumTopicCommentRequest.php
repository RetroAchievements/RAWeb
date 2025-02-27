<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Support\Rules\ContainsRegularCharacter;
use Illuminate\Foundation\Http\FormRequest;

class UpsertForumTopicCommentRequest extends FormRequest
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
        ];
    }
}
