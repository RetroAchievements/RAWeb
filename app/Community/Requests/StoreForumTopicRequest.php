<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Support\Rules\ContainsRegularCharacter;
use Illuminate\Foundation\Http\FormRequest;

class StoreForumTopicRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:2',
                'max:255',
                new ContainsRegularCharacter(),
            ],

            'body' => [
                'required',
                'string',
                'max:60000',
                new ContainsRegularCharacter(),
            ],
        ];
    }
}
