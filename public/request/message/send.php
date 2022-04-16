<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("ucdtm")) {
    echo "FAILED";
    exit;
}

$user = requestInputPost('u');
$cookie = requestInputPost('c');

$recipient = requestInputPost('d');
$title = requestInputPost('t');
$payload = requestInputPost('m');

if (validateUser_cookie($user, $cookie, 0) == true) {
    if (isUserBlocking($recipient, $user)) {
        // recipient has blocked the user. just pretend the message was sent
        header("Location: " . getenv('APP_URL') . "/inbox.php?e=sentok");
        exit;
    }

    if (CreateNewMessage($user, $recipient, $title, $payload)) {
        header("Location: " . getenv('APP_URL') . "/inbox.php?e=sentok");
        exit;
    } else {
        echo "FAILED:Could not send message!";
    }
} else {
    echo "FAILED:Could not validate cookie - please login again!";
}
