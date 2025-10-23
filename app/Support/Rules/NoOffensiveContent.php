<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Snipe\BanBuilder\CensorWords;

/**
 * Validates that the input does not contain offensive content.
 * Uses banbuilder's en-base, en-us, and en-uk dictionaries to detect profanity and offensive terms.
 */
class NoOffensiveContent implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $censor = new CensorWords();
        $censor->setDictionary(['en-base', 'en-us', 'en-uk']);

        $result = $censor->censorString((string) $value);

        if (!empty($result['matched'])) {
            $fail('validation.no_offensive_content')->translate();
        }
    }
}
