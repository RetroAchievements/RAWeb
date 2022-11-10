<?php

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Translation\PotentiallyTranslatedString;

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
