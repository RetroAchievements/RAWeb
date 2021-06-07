<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("ucdtm")) {
    echo "FAILED";
    return;
}

$user = requestInputPost('u');
$cookie = requestInputPost('c');

$recipient = requestInputPost('d');
$title = requestInputPost('t');
$payload = requestInputPost('m');

if (validateUser_cookie($user, $cookie, 0) == true) {
    if (CreateNewMessage($user, $recipient, $title, $payload)) {
        header("Location: " . getenv('APP_URL') . "/inbox.php?e=sentok");
        exit;
    //echo "OK:Message sent to $recipient!";
    } else {
        echo "FAILED:Could not send message!";
    }
} else {
    echo "FAILED:Could not validate cookie - please login again!";
}
