<?php
function RA_ClearCookie($cookieName)
{
    RA_SetCookie($cookieName, '', 1);
}

function RA_ReadCookie($cookieName)
{
    if (RA_CookieExists($cookieName)) {
        return htmlspecialchars($_COOKIE[$cookieName]);
    }

    return null;
}

function RA_SetCookie($cookieName, $cookieValue, $expire = 0, $httponly = false)
{
    return setcookie($cookieName, $cookieValue, $expire, $path = "/", getenv('SESSION_DOMAIN'), false, $httponly);
    // return setcookie($cookieName, $cookieValue, [
    //     'expires' => $expire,
    //     'path' => '/',
    //     'domain' => getenv('SESSION_DOMAIN'),
    //     'samesite' => 'lax',
    //     'secure' => getenv('SESSION_SECURE_COOKIE'),
    //     'httponly' => $httponly,
    // ]);
}

function RA_CookieExists($cookieName)
{
    return isset($_COOKIE) &&
        array_key_exists($cookieName, $_COOKIE) &&
        $_COOKIE[$cookieName] !== false;
}
