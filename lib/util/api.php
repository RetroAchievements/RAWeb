<?php

if (!function_exists('runIntegrationApiMiddleware')) {
    function runIntegrationApiMiddleware()
    {
        //
    }
}

if (!function_exists('runIntegrationApiMiddleware')) {
    function apiErrorResponse($errorMsg)
    {
        echo json_encode([
            'Success' => false,
            'Error' => $errorMsg,
        ]);
        exit;
    }
}

if (!function_exists('runPublicApiMiddleware')) {
    function runPublicApiMiddleware()
    {
        /**
         * allow access from browsers' script context
         */
        header("Access-Control-Allow-Origin: *");

        /**
         * public api authorization
         */
        if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
            echo "Invalid API Key";
            exit;
        }
    }
}
