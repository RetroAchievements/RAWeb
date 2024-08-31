<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsitePrefsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The caller is always the target user.
        return true;
    }

    public function rules(): array
    {
        return [
            'websitePrefs' => 'required|integer',
        ];
    }
}
