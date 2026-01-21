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
        // Let's not explicitly write these words in our codebase.
        $base64EncodedSlurs = 'WyJuaWdnZXIiLCJuaWdnYSJd';
        $dictionary = json_decode(base64_decode($base64EncodedSlurs), true);

        // CensorWords also checks for ways people can obfuscate the slurs. ie: "wow" === "W0W"
        $censor = new CensorWords();
        // Clear the default dictionary - we only want to check for our specific slurs.
        $censor->setDictionary([]);
        $censor->addFromArray($dictionary);

        // Check for the slurs using substring matching.
        // There's no legitimate reason for these to appear anywhere in a username.
        $result = $censor->censorString((string) $value, false);

        if (!empty($result['matched'])) {
            $fail('validation.no_offensive_content')->translate();
        }
    }
}
