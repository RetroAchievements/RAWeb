<?php

declare(strict_types=1);

namespace App\Api\V2\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CommaDelimitedIn implements ValidationRule
{
    /**
     * @param string[] $allowedValues
     */
    public function __construct(
        private readonly array $allowedValues,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (explode(',', $value) as $v) {
            if (!in_array($v, $this->allowedValues, true)) {
                $fail("The filter value \"{$v}\" is invalid. Allowed values: " . implode(', ', $this->allowedValues) . ".");
            }
        }
    }
}
