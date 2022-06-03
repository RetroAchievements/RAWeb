<?php

function redirect(string $url): void
{
    header('Location: ' . url($url));
}

function back(): string
{
    return url($_SERVER['HTTP_REFERER'] ?? '');
}

function requestInputQuery(string $key, $default = null, $type = null): mixed
{
    $input = $_GET[$key] ?? $default;

    if ($type) {
        settype($input, $type);
    }

    return $input;
}

function requestInputPost(string $key, $default = null, $type = null): mixed
{
    $input = $_POST[$key] ?? $default;

    if ($type) {
        settype($input, $type);
    }

    return $input;
}

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
function requestInputSanitized(string $key, mixed $default = null, mixed $type = null): mixed
{
    if (!$type || $type === 'string') {
        $input = requestInput($key, $default, $type);
        return !empty($input) ? htmlentities($input) : null;
    }
    return requestInput($key, $default, $type);
}

function ValidatePOSTChars($charsIn): bool
{
    $numChars = mb_strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_POST)) {
            return false;
        }
    }
    return true;
}

function ValidateGETChars($charsIn): bool
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
function ValidatePOSTorGETChars($charsIn): bool
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
