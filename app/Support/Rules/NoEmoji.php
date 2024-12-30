<?php

declare(strict_types=1);

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoEmoji implements ValidationRule
{
    /**
     * @see https://stackoverflow.com/a/68146409
     */
    private const EMOJI_PATTERN = '/\p{RI}\p{RI}|\p{Emoji}(\p{EMod}+|\x{FE0F}\x{20E3}?|[\x{E0020}-\x{E007E}]+\x{E007F})?(\x{200D}\p{Emoji}(\p{EMod}+|\x{FE0F}\x{20E3}?|[\x{E0020}-\x{E007E}]+\x{E007F})?)+|\p{EPres}(\p{EMod}+|\x{FE0F}\x{20E3}?|[\x{E0020}-\x{E007E}]+\x{E007F})?|\p{Emoji}(\p{EMod}+|\x{FE0F}\x{20E3}?|[\x{E0020}-\x{E007E}]+\x{E007F})/u';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match(self::EMOJI_PATTERN, (string) $value)) {
            $fail('The :attribute cannot contain emoji characters.');
        }
    }
}
