<?php

use RA\ArticleType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

// TODO do not allow GET requests, POST only
if (ValidatePOSTorGETChars("tpv")) {
    $targetUser = requestInput('t');
    $propertyType = requestInput('p', null, 'integer');
    $value = requestInput('v', null, 'integer');
} else {
    echo "FAILED";
    exit;
}

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Admin)) {
    echo "FAILED!";
    exit;
}

// Account permissions
if ($propertyType == 0) {
    $response = SetAccountPermissionsJSON($user, $permissions, $targetUser, $value);
    if ($response['Success']) {
        if ($value >= Permissions::JuniorDeveloper) {
            if (!getUserForumPostAuth($targetUser)) {
                if (setAccountForumPostAuth($user, $permissions, $targetUser, authorize: true)) {
                    header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
                } else {
                    echo "FAILED!";
                }
            } else {
                header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
            }
        } else {
            header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
        }
    } else {
        echo "Failed: " . $response['Error'];
    }
    exit;
}

// Forum post permissions
if ($propertyType == 1) {
    if (setAccountForumPostAuth($user, $permissions, $targetUser, authorize: (bool) $value)) {
        header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
    } else {
        echo "FAILED!";
    }
    exit;
}

// Toggle Patreon badge
if ($propertyType == 2) {
    $hasBadge = HasPatreonBadge($targetUser);
    SetPatreonSupporter($targetUser, !$hasBadge);

    if (getAccountDetails($targetUser, $targetUserData)) {
        addArticleComment('Server', ArticleType::UserModeration, $targetUserData['ID'],
            $user . ($hasBadge ? ' revoked' : ' awarded') . ' Patreon badge');
    }

    header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
}

// Toggle 'Untracked' status
if ($propertyType == 3) {
    SetUserUntrackedStatus($targetUser, $value);

    if (getAccountDetails($targetUser, $targetUserData)) {
        addArticleComment('Server', ArticleType::UserModeration, $targetUserData['ID'],
            $user . ' set status to ' . ($value ? 'Untracked' : 'Tracked'));
    }

    header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=OK");
}
