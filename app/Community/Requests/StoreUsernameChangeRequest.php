<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Support\Rules\ValidNewUsername;
use Illuminate\Foundation\Http\FormRequest;

class StoreUsernameChangeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'newDisplayName' => ValidNewUsername::get($this->user()),
        ];
    }
}
