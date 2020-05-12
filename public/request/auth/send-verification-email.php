<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

$user = seekGet('u');

getAccountDetails($user, $userDetails);
$emailAddr = $userDetails['EmailAddress'];

if (sendValidationEmail($user, $emailAddr) == false) {
    // error_log(__FILE__ . " cannot send validation email to this user!?");
    header("Location: " . getenv('APP_URL') . "/?e=accountissue");
}

header("Location: " . getenv('APP_URL') . "/?e=validateEmailPlease");
