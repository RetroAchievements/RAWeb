<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidUserIdentifier implements ValidationRule
{
    /**
     * Validate the given input is either a valid ULID (26 chars) or username (2-20 chars).
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $length = mb_strlen($value);
        $ulidLength = 26;

        if ($length === $ulidLength) {
            // ULIDs are base32 encoded - verify.
            if (!preg_match('/^[0-9A-Z]{26}$/i', $value)) {
                $fail('The :attribute must be a valid ULID.');
            }

            return;
        }

        // Otherwise, validate as a username (2-20 chars).
        if ($length < 2 || $length > 20) {
            $fail('The :attribute must be between 2 and 20 characters when providing a username.');

            return;
        }

        // A username must also strictly use alphanumeric chars.
        if (!ctype_alnum($value)) {
            $fail('The :attribute may only contain letters and numbers.');

            return;
        }
    }
}
