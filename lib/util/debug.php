<?php

if (!function_exists('dump')) {
    function dump(...$args): void
    {
        if (php_sapi_name() !== 'cli') {
            echo '<pre>';
        }
        foreach ($args as $arg) {
            print_r($arg);
        }
        if (php_sapi_name() !== 'cli') {
            echo '</pre>';
        }
    }
}

if (!function_exists('dd')) {
    function dd(...$args): void
    {
        dump(...$args);
        exit;
    }
}
