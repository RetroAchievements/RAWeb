<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("uxy")) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=baddata");
    exit;
}

$user = requestInputPost('u');
$pass = requestInputPost('p');
$passResetToken = requestInputPost('t');
$newpass1 = requestInputPost('x');
$newpass2 = requestInputPost('y');

if ($newpass1 !== $newpass2) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=passinequal");
    exit;
}

if (mb_strlen($newpass1) < 8) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=badnewpass");
    exit;
}

if ($newpass1 == $user) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=badnewpass");
    exit;
}

if (isset($passResetToken) && isValidPasswordResetToken($user, $passResetToken)) {
    RemovePasswordResetToken($user);
    if (changePassword($user, $newpass1)) {
        //	Perform auto-login:
        generateCookie($user, $newCookie);
        RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);
        generateAppToken($user, $tokenInOut);

        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=changepassok");
    } else {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=generalerror");
    }
    exit;
}
if (validateUser($user, $pass, $fbUser, 0)) {
    RemovePasswordResetToken($user);
    if (changePassword($user, $newpass1)) {
        generateAppToken($user, $tokenInOut);
        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=changepassok");
    } else {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=generalerror");
    }
    exit;
}
header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=badpass");
