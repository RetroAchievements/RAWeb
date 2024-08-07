<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPlayerProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The caller is always the target user.
        return true;
    }

    public function rules(): array
    {
        return [
            'gameId' => 'required_without:achievementId|integer|exists:GameData,ID',
            'achievementId' => 'required_without:gameId|integer|exists:Achievements,ID',
        ];
    }
}
