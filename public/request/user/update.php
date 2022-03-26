<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\ArticleType;

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

    if (getAccountDetails($targetUser, $targetUserData)) {
        addArticleComment('Server', ArticleType::UserModeration, $targetUserData['ID'],
            $user . ($hasBadge ? ' revoked' : ' awarded') . ' Patreon badge');
    }

    // error_log("$user updated $targetUser to Patreon Status $hasBadge OK!!");
    header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
}

// Toggle 'Untracked' status
if ($propertyType == 3) {
    SetUserUntrackedStatus($targetUser, $value);

    if (getAccountDetails($targetUser, $targetUserData)) {
        addArticleComment('Server', ArticleType::UserModeration, $targetUserData['ID'],
            $user . ' set status to ' . ($value ? 'Untracked' : 'Tracked'));
    }

    // error_log("SetUserUntrackedStatus, $targetUser => $value");
    header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
}
