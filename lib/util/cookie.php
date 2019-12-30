<?php
function RA_ClearCookie($cookieName)
{
    return setcookie("$cookieName", "", 1, '/', AT_HOST_DOT);
}

function RA_ReadCookie($cookieName)
{
    if (RA_CookieExists($cookieName)) {
        return htmlspecialchars($_COOKIE[$cookieName]);
    }

    return null;
}

function RA_SetCookie($cookieName, $cookieValue)
{
    return setcookie("$cookieName", "$cookieValue", time() + 60 * 60 * 24 * 30, '/', AT_HOST_DOT);
}

function RA_CookieExists($cookieName)
{
    return (isset($_COOKIE) &&
        array_key_exists($cookieName, $_COOKIE) &&
        $_COOKIE[$cookieName] !== false);
}
