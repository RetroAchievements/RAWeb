<?php

use App\Exceptions\ViewRedirect;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Get request input sanitized for output
 *
 * @deprecated refactor to escaped blade view variables
 */
function requestInputSanitized(string $key, mixed $default = null, mixed $type = null): mixed
{
    $input = request()->input($key, $default);

    if (!$type || $type === 'string') {
        return !empty($input) ? htmlentities((string) $input) : $default;
    }

    settype($input, $type);

    return $input;
}

/**
 * @deprecated TODO do not redirect in views, refactor to controller when needed
 *
 * @throws ViewRedirect
 */
function abort_with(RedirectResponse $redirect): void
{
    throw new ViewRedirect($redirect);
}
