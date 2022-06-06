<?php

function clearCookie($cookieName): void
{
    applyCookie($cookieName, '', 1);
}

function readCookie($cookieName): ?string
{
    if (cookieExists($cookieName)) {
        return htmlspecialchars($_COOKIE[$cookieName]);
    }

    return null;
}

function applyCookie($cookieName, $cookieValue, $expire = 0, $httponly = false): bool
{
    return setcookie($cookieName, $cookieValue, [
        'expires' => $expire,
        'path' => "/",
        'domain' => getenv('SESSION_DOMAIN'),
        'samesite' => 'lax',
        'secure' => filter_var(getenv('SESSION_SECURE_COOKIE'), FILTER_VALIDATE_BOOLEAN),
        'httponly' => $httponly,
    ]);
}

function cookieExists($cookieName): bool
{
    return array_key_exists($cookieName, $_COOKIE) && $_COOKIE[$cookieName] !== false;
}
