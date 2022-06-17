<?php

function clearCookie($cookieName): void
{
    applyCookie($cookieName, '');
}

function readCookie($cookieName): ?string
{
    if (cookieExists($cookieName)) {
        return htmlspecialchars($_COOKIE[$cookieName]);
    }

    return null;
}

function applyCookie($cookieName, $cookieValue): bool
{
    return setcookie($cookieName, $cookieValue, [
        'expires' => time() + config('session.lifetime') * 60,
        'path' => config('session.path'),
        'domain' => config('session.domain'),
        'samesite' => config('session.same_site'),
        'secure' => config('session.secure'),
        'httponly' => config('session.http_only'),
    ]);
}

function cookieExists($cookieName): bool
{
    return array_key_exists($cookieName, $_COOKIE) && $_COOKIE[$cookieName] !== false;
}
