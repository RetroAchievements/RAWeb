<?php

// TODO replace with framework request input
function requestInputQuery(string $key, $default = null, $type = null): mixed
{
    $input = $_GET[$key] ?? $default;

    if ($type) {
        settype($input, $type);
    }

    return $input;
}

// TODO replace with framework request input
function requestInputPost(string $key, $default = null, $type = null): mixed
{
    $input = $_POST[$key] ?? $default;

    if ($type) {
        settype($input, $type);
    }

    return $input;
}

// TODO replace with framework request input
function requestInput(string $key, $default = null, $type = null): mixed
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
 */
// TODO replace with escaped view variables
function requestInputSanitized(string $key, mixed $default = null, mixed $type = null): mixed
{
    if (!$type || $type === 'string') {
        $input = requestInput($key, $default, $type);
        return !empty($input) ? htmlentities($input) : null;
    }
    return requestInput($key, $default, $type);
}
