<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Support\Rules\ContainsRegularCharacter;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'min:3',
                'max:2000',
                new ContainsRegularCharacter(),
            ],
            'commentableId' => 'required|integer',
            'commentableType' => 'required|integer',
        ];
    }
}
