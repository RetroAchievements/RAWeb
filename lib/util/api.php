<?php

if (!function_exists('runPublicApiMiddleware')) {
    function runPublicApiMiddleware(): void
    {
        /**
         * allow access from browsers' script context
         */
        header("Access-Control-Allow-Origin: *");

        /**
         * public api authorization
         */
        if (!ValidateAPIKey(requestInputQuery('z'), requestInputQuery('y'))) {
            echo "Invalid API Key";
            exit;
        }
    }
}
