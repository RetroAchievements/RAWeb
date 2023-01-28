<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Stricter version of the alpha_num validation rule
 * Restricts the input to ASCII characters
 */
class CtypeAlnum implements InvokableRule
{
    /**
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function __invoke($attribute, mixed $value, $fail): void
    {
        if (!ctype_alnum($value)) {
            $fail('validation.ctype_alnum')->translate();
        }
    }
}
