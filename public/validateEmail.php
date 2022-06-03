<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!validateGetChars("v")) {
    echo "FAILED";
    exit;
}

$eCookie = requestInputSanitized('v');

if (validateEmailVerificationToken($eCookie, $user)) {
    // Valid!
    generateCookie($user);
    header("Location: " . getenv('APP_URL') . "/?e=validatedEmail");
} else {
    // Not valid!
    echo "Could not validate account!<br>";
}
