<?php

declare(strict_types=1);

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// forward existing php files in the public directory to Laravel
if (str_ends_with($uri, '.php')) {
    // Force the Symfony request builder to generate a valid base URL
    // @see \Symfony\Component\HttpFoundation\Request::prepareBaseUrl()
    unset($_SERVER['SCRIPT_FILENAME']);

    require_once $publicPath . '/index.php';

    return;
}

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists($publicPath . $uri)) {
    return false;
}

require_once $publicPath . '/index.php';
