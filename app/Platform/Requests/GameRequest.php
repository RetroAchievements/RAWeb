<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'rich_presence_patch' => 'nullable|string',
            'developer' => 'nullable|string|max:50',
            'publisher' => 'nullable|string|max:50',
            'genre' => 'nullable|string|max:50',
            'release' => 'nullable|string|max:50',
        ];
    }
}
