<?php

/**
 * laravel polyfill
 */
if (!function_exists('preg_replace_array')) {
    /**
     * Replace a given pattern with each value in the array in sequentially.
     */
    function preg_replace_array(string $pattern, array $replacements, string $subject): string
    {
        return preg_replace_callback($pattern, function () use (&$replacements) {
            foreach ($replacements as $value) {
                return array_shift($replacements);
            }
        }, $subject);
    }
}
