<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Snipe\BanBuilder\CensorWords;

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
