<?php

function requestInputQuery(string $key, $default = null, $type = null)
{
    $input = $_GET[$key] ?? $default;

    if ($type) {
        settype($input, $type);
    }

    return $input;
}

function requestInputPost(string $key, $default = null, $type = null)
{
    $input = $_POST[$key] ?? $default;

    if ($type) {
        settype($input, $type);
    }

    return $input;
}

function requestInput(string $key, $default = null, $type = null)
{
    $input = requestInputPost($key);
    if (!$input) {
        $input = requestInputQuery($key);
    }
    if (!$input) {
        $input = $default;
    }
    if ($type) {
        settype($input, $type);
    }
    return $input;
}

/**
 * Get request input sanitized for output
 *
 * @param mixed|null $default
 * @param mixed|null $type
 * @return mixed|string|null
 */
function requestInputSanitized(string $key, $default = null, $type = null)
{
    if (!$type || $type === 'string') {
        $input = requestInput($key, $default, $type);
        return !empty($input) ? htmlentities($input) : null;
    }
    return requestInput($key, $default, $type);
}

function ValidatePOSTChars($charsIn)
{
    $numChars = mb_strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_POST)) {
            return false;
        }
    }
    return true;
}

function ValidateGETChars($charsIn)
{
    $numChars = mb_strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_GET)) {
            return false;
        }
    }

    return true;
}

// TODO do not allow GET requests, POST only
function ValidatePOSTorGETChars($charsIn)
{
    $numChars = mb_strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_GET)) {
            if (!array_key_exists($charsIn[$i], $_POST)) {
                return false;
            }
        }
    }

    return true;
}

function CurrentPageURL(): string
{
    // $pageURL = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
    $pageURL = 'https://';
    $pageURL .= $_SERVER['SERVER_PORT'] != '80' ? $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"] : $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    return $pageURL;
}
