<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidatePOSTChars("uxy")) {
    error_log(__FILE__);
    error_log("Cannot validate uxy input...");
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=baddata");
}

$user = seekPOST('u');
$pass = seekPOST('p');
$passResetToken = seekPOST('t');
$newpass1 = seekPOST('x');
$newpass2 = seekPOST('y');

if (strlen($newpass1) < 2 ||
    strlen($newpass2) < 2) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=badnewpass");
} else {
    if ($newpass1 !== $newpass2) {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=passinequal");
    } else {
        if (isset($passResetToken) && isValidPasswordResetToken($user, $passResetToken)) {
            RemovePasswordResetToken($user, $passResetToken);

            if (changePassword($user, $newpass1)) {
                //	Perform auto-login:
                generateCookie($user, $newCookie);
                RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

                header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=changepassok");
            } else {
                header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=generalerror");
            }
        } else {
            if (validateUser($user, $pass, $fbUser, 0) == true) {
                if (changePassword($user, $newpass1)) {
                    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=changepassok");
                } else {
                    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=generalerror");
                }
            } else {
                header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=badpass");
            }
        }
    }
}
