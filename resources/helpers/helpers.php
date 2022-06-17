<?php

require_once __DIR__ . '/formatters.php';
require_once __DIR__ . '/resources.php';
require_once __DIR__ . '/shortcode.php';
require_once __DIR__ . '/translation.php';

if (!function_exists('bit_value')) {
    function bit_value(int $value, int $flagBit): bool
    {
        return ($value & (1 << $flagBit)) !== 0;
    }
}

if (!function_exists('percentage')) {
    function percentage(float|int $number, int $precision = 1): float
    {
        return (float) ((int) ($number * 10 ** $precision) / 10 ** $precision);
    }
}

if (!function_exists('media_asset')) {
    function media_asset(string $path): string
    {
        return app('filesystem')->disk('media')->url($path);
    }
}
