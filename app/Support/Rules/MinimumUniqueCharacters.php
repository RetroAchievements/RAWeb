<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinimumUniqueCharacters implements ValidationRule
{
    /**
     * @param array<int, string> $stripWords words to remove before counting unique characters
     */
    public function __construct(
        private int $minimum = 5,
        private array $stripWords = [],
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $stringValue = (string) $value;

        // Strip specified words (case-insensitive) before counting.
        $processedValue = str_ireplace($this->stripWords, '', $stringValue);
        $uniqueCount = count(array_unique(mb_str_split($processedValue)));

        if ($uniqueCount >= $this->minimum) {
            return;
        }

        // Use a more specific message when strip words are detected.
        $hasStrippedWords = strlen($processedValue) < strlen($stringValue);
        $messageKey = $hasStrippedWords
            ? 'validation.minimum_unique_characters_with_strip'
            : 'validation.minimum_unique_characters';

        $fail($messageKey)->translate([
            'minimum' => $this->minimum,
        ]);
    }
}
