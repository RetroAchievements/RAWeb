<?php

declare(strict_types=1);

namespace App\Filament\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DisallowHtml implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // attempt to match any tag.
        // less than, alpha charactrer, anything not a less than, less then
        $doesMatchHtmlPattern = preg_match('/(<[A-Za-z]([^>]+)>)/i', $value);
        if ($doesMatchHtmlPattern) {
            $fail('This field does not allow HTML tags.');
        }
    }
}
