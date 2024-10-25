<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LocaleExists implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $doesExist = file_exists(lang_path("{$value}.json"));

        if (!$doesExist) {
            $fail('validation.locale')->translate();
        }
    }
}
