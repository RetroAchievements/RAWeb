<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    public static function get(bool $requireConfirmation = false): array
    {
        $rules = [
            'required',
            'different:username',
            'different:email',
            'not_regex:/retroachievements\.org/i',
            Password::min(10)->uncompromised(5),
            new MinimumUniqueCharacters(minimum: 5, stripWords: ['retroachievements']),
        ];

        if ($requireConfirmation) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }
}
