<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Community\Enums\UserGameListType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UserGameListEntryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'userGameListType' => ['required', new Enum(UserGameListType::class)],
        ];
    }
}
