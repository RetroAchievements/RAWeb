<?php

declare(strict_types=1);

namespace App\Filament\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsAllowedGuideUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $doesMatchRequiredPattern = preg_match('/^https?:\/\/(www\.)?github\.com\/RetroAchievements\/guides\//i', $value);
        if (!$doesMatchRequiredPattern) {
            $fail('The guide URL must be a valid link from https://github.com/RetroAchievements/guides/.');
        }
    }
}
