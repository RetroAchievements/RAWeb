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

        if ($this->hasMinimumUniqueCharacters($processedValue)) {
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

    /**
     * Count unique characters without splitting the full password into an array.
     *
     * A lengthy input string may contain millions of characters. Don't exhaust
     * memory with mb_str_split() or array_unique() on that. Store only the unique
     * characters seen so far and stop as soon as that minimum is met.
     */
    private function hasMinimumUniqueCharacters(string $value): bool
    {
        $uniqueCharacters = [];
        $offset = 0;
        $byteLength = strlen($value);

        while ($offset < $byteLength) {
            if (preg_match('/\G./us', $value, $matches, 0, $offset) === 1) {
                $character = $matches[0];
                $offset += strlen($character);
            } else {
                $character = $value[$offset];
                $offset++;
            }

            $uniqueCharacters[$character] = true;

            if (count($uniqueCharacters) >= $this->minimum) {
                return true;
            }
        }

        return false;
    }
}
