<?php

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContainsRegularCharacter implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $decodedValue = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Check for at least one letter, number, punctuation mark, or symbol.
        $requireOneRegularCharacter = '/[\p{L}\p{N}\p{P}\p{S}]/u';

        if (!preg_match($requireOneRegularCharacter, $decodedValue)) {
            $fail('validation.contains_regular_character')->translate();
        }
    }
}
