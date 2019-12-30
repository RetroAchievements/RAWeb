<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidatePOSTChars("utn")) {
    echo "FAILED";
    return;
}

$user = $_POST["u"];
$token = $_POST["t"];

$newFriend = $_POST["n"];

if (validateUser_app($user, $token, $fbUser, 0) == true) {
    if (addFriend($user, $newFriend)) {
        echo "OK:Sent Friend Request!";
    }
} else {
    echo "INVALID USER/PASS!";
}
