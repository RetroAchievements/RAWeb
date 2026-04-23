<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickEditAchievementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|filled|max:64',
            'description' => 'sometimes|string|filled|max:255',
            'points' => 'sometimes|integer|in:0,1,2,3,4,5,10,25,50,100',
            'type' => 'sometimes|nullable|string|in:missable,progression,win_condition',
            'isPromoted' => 'sometimes|boolean',
        ];
    }
}
