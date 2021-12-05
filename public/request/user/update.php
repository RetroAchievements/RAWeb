<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (ValidatePOSTorGETChars("tpv")) {
    $targetUser = requestInput('t');
    $propertyType = requestInput('p', null, 'integer');
    $value = requestInput('v', null, 'integer');
} else {
    echo "FAILED";
    return;
}

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Admin)) {
    echo "FAILED!";
    return;
}

// Account permissions
if ($propertyType == 0) {
    $response = SetAccountPermissionsJSON($user, $permissions, $targetUser, $value);
    if ($response['Success']) {
        // error_log("$user updated $targetUser to $value OK!!");
        header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
    } else {
        // error_log("requestupdateuser.php failed?! 0" . $response['Error']);
        echo "Failed: " . $response['Error'];
    }
    return;
}

// Forum post permissions
if ($propertyType == 1) {
    if (setAccountForumPostAuth($user, $permissions, $targetUser, $value)) {
        // error_log("$user updated $targetUser to $value OK!!");
        header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
    } else {
        // error_log("requestupdateuser.php failed?! 1");
        echo "FAILED!";
    }
    return;
}

// Toggle Patreon badge
if ($propertyType == 2) {
    $hasBadge = HasPatreonBadge($targetUser);
    SetPatreonSupporter($targetUser, !$hasBadge);
    // error_log("$user updated $targetUser to Patreon Status $hasBadge OK!!");
    header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
}

// Toggle 'Untracked' status
if ($propertyType == 3) {
    SetUserTrackedStatus($targetUser, $value);
    // error_log("SetUserTrackedStatus, $targetUser => $value");
    header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
}
