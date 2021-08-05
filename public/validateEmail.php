<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!validateGetChars("v")) {
    echo "FAILED";
    return;
}

$eCookie = requestInputSanitized('v');

if (validateEmailValidationString($eCookie, $user)) {
    //	Valid!
    generateCookie($user, $cookieOut);
    header("Location: " . getenv('APP_URL') . "/?e=validatedEmail");
} else {
    //	Not valid!
    echo "Could not validate account!<br>";
}
