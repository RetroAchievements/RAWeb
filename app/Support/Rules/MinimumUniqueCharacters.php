<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinimumUniqueCharacters implements ValidationRule
{
    public function __construct(
        private int $minimum = 5,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $characters = mb_str_split((string) $value);
        $uniqueCount = count(array_unique($characters));

        if ($uniqueCount < $this->minimum) {
            $fail('validation.minimum_unique_characters')->translate([
                'minimum' => $this->minimum,
            ]);
        }
    }
}
