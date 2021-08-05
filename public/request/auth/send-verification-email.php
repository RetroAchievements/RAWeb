<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$user = requestInputQuery('u');

getAccountDetails($user, $userDetails);
$emailAddr = $userDetails['EmailAddress'];

if (sendValidationEmail($user, $emailAddr) == false) {
    header("Location: " . getenv('APP_URL') . "/?e=accountissue");
    exit;
}

header("Location: " . getenv('APP_URL') . "/?e=validateEmailPlease");
