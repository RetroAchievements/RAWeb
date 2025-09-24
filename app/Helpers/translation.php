<?php

declare(strict_types=1);

if (!function_exists('__choice')) {
    /**
     * Alias
     */
    function __choice(
        string $key,
        Countable|int|array $number = 2,
        array $replace = [],
        ?string $locale = null,
    ): string {
        return trans_choice($key, $number, $replace, $locale);
    }
}
