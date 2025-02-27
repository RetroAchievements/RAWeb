<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Enums\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GateForumTopicRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'permissions' => [
                'required',
                'integer',
                Rule::in(Permissions::assignable()),
            ],
        ];
    }
}
