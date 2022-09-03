<?php

/**
 * Get request input sanitized for output
 *
 * @deprecated refactor to escaped blade view variables
 */
function requestInputSanitized(string $key, mixed $default = null, mixed $type = null): mixed
{
    $input = request()->input($key, $default);

    if (!$type || $type === 'string') {
        return !empty($input) ? htmlentities($input) : null;
    }

    settype($input, $type);

    return $input;
}
