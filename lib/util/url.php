<?php

if (!function_exists('url')) {
    function url(string $url): string
    {
        if (parse_url($url, PHP_URL_HOST)) {
            return $url;
        }

        return getenv('APP_URL') . '/' . ltrim($url, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $url): string
    {
        if (parse_url($url, PHP_URL_HOST)) {
            return $url;
        }

        return getenv('ASSET_URL') . '/' . ltrim($url, '/');
    }
}
