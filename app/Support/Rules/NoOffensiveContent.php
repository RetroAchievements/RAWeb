<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoOffensiveContent implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Let's not explicitly write these words in our codebase.
        $base64EncodedSlurs = 'WyJuaWdnZXIiLCJuaWdnYSIsImtrayIsIm5hemkiLCJoaXRsZXIiLCJjaGluayIsImtpa2UiLCJzcGljIiwid2V0YmFjayIsInRvd2VsaGVhZCIsInJhZ2hlYWQiLCJnb29rIiwiamlnYWJvbyIsImNvb24iLCJiZWFuZXIiXQ==';
        $dictionary = json_decode(base64_decode($base64EncodedSlurs), true);

        // Check for the slurs using substring matching.
        // There's no legitimate reason for these to appear anywhere in a username.
        $lowerValue = strtolower((string) $value);
        foreach ($dictionary as $term) {
            if (str_contains($lowerValue, $term)) {
                $fail('validation.no_offensive_content')->translate();

                return;
            }
        }
    }
}
