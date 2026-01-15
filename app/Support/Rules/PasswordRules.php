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
            'not_regex:/retroachievements/i',
            Password::min(10)->uncompromised(),
            new MinimumUniqueCharacters(5),
        ];

        if ($requireConfirmation) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }
}
