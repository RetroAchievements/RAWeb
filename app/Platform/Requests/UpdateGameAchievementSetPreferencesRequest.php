<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameAchievementSetPreferencesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'preferences' => 'required|array',
            'preferences.*.gameAchievementSetId' => 'required|integer|exists:game_achievement_sets,id',
            'preferences.*.optedIn' => 'required|boolean',
        ];
    }
}
