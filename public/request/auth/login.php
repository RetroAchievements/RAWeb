<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$user = $_POST["u"];
$pass = $_POST["p"];
$redir = $_POST["r"];
$fbUser = "";
$cookie = "";

if (validateUser($user, $pass, $fbUser, 0)) {
    generateCookie($user, $cookie);

    //	TBD: Check for messages, updates? etc
    //	Post activity of login:
    postActivity($user, \RA\ActivityType::Login, null);

    //	Remove 'incorrect password' from redir url:
    $redir = str_replace("e=incorrectpassword", "", $redir);
    //	Remove 'notloggedin'
    $redir = str_replace("e=notloggedin", "", $redir);

    header("Location: " . getenv('APP_URL') . "$redir");
} else {
    if (isset($redir) && mb_stristr($redir, "?")) {
        header("Location: " . getenv('APP_URL') . "$redir&e=incorrectpassword"); //	if redir has a query string, append errorcode!
    } else {
        header("Location: " . getenv('APP_URL') . "$redir?e=incorrectpassword");
    }
}
