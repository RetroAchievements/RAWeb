<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidatePOSTChars("atl")) {
    echo "FAILED! (POST)";
    exit;
}

$author = seekPOST('a');
$title = seekPOST('t');
$link = seekPOST('l');
$id = seekPOST('i', null);
settype($id, 'integer');

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
