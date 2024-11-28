<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivePlayersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'perPage' => 'nullable|integer',
            'page' => 'nullable|integer',
            'gameIds' => 'nullable|array',
            'gameIds.*' => 'integer',
            'search' => 'nullable|string',
        ];
    }
}
