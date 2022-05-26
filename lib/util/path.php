<?php

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $docRoot = __DIR__ . '/../../';
        $path = $docRoot . ltrim($path, '/');

        return file_exists($path) ? realpath($path) : $path;
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return base_path('public/' . ltrim($path, '/'));
    }
}
