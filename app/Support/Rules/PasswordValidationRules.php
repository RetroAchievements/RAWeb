<?php

declare(strict_types=1);

namespace App\Support\Rules;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function passwordRules(): array
    {
        return PasswordRules::get(requireConfirmation: true);
    }
}
