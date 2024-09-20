<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameClaimRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|int|min:0|max:3',
        ];
    }
}
