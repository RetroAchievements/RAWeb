<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!ValidatePOSTChars("atl")) {
    echo "FAILED! (POST)";
    exit;
}

$author = requestInputPost('a');
$title = requestInputPost('t');
$link = requestInputPost('l');
$id = requestInputPost('i', null, 'integer');

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::SuperUser) &&
    ($author == $user)) {
    $link = str_replace("_http_", "http", $link);

    requestModifyVid($author, $id, $title, $link);

    echo "OK";
    //header( "location: " . getenv('APP_URL') . "/largechat.php?e=ok" );
    exit;
} else {
    // error_log("aitl: $author, $id, $title, $link");
    echo "FAILED!";
    //header( "location: " . getenv('APP_URL') . "/largechat.php?n=$id&e=failed" );
    exit;
}
