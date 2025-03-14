<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateForumPostPermissionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'isAuthorized' => 'required|boolean',
            'displayName' => 'required|min:2|max:20',
        ];
    }
}
