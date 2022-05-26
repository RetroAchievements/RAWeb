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
    if (isset($passResetToken) && isValidPasswordResetToken($user, $passResetToken)) {
        header("Location: " . getenv('APP_URL') . "/resetPassword.php?u=$user&t=$passResetToken&e=passinequal");
    } else {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=passinequal");
    }
    exit;
}
if (mb_strlen($newpass1) < 8 || $newpass1 == $user) {
    if (isset($passResetToken) && isValidPasswordResetToken($user, $passResetToken)) {
        header("Location: " . getenv('APP_URL') . "/resetPassword.php?u=$user&t=$passResetToken&e=badnewpass");
    } else {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=badnewpass");
    }
    exit;
}
if (isset($passResetToken) && isValidPasswordResetToken($user, $passResetToken)) {
    RemovePasswordResetToken($user);
    if (changePassword($user, $newpass1)) {
        // Perform auto-login:
        generateCookie($user, $newCookie);
        authenticateFromCookie($user, $permissions, $userDetails);
        generateAppToken($user, $tokenInOut);

        header("Location: " . getenv('APP_URL') . "/resetPassword.php?e=changepassok");
    } else {
        header("Location: " . getenv('APP_URL') . "/resetPassword.php?e=generalerror");
    }
    exit;
}
if (authenticateFromPassword($user, $pass)) {
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
