<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidatePOSTChars("um")) {
    error_log("FAILED access to " . __FILE__);
    return;
}

$user = seekPOST('u');
$messageID = seekPOST('m');
settype($messageID, "integer");

$messageReadStatus = seekPOST('r', 0);    //	normally set as read

if (markMessageAsRead($user, $messageID, $messageReadStatus)) {
    error_log(__FUNCTION__ . " $user $messageID");
    echo "OK:" . $messageID;
} else {
    error_log(__FUNCTION__ . " failed?! 2" . var_dump($_POST));
    echo "FAILED!";
}
