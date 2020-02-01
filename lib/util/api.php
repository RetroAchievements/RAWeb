<?php

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
