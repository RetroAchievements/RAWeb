<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\Permissions;
use RA\SubscriptionSubjectType;

// what is being (un-)subscribed? and where should we go back to at the end?

$returnUrl = requestInputPost("return_url");
$subjectType = requestInputPost("subject_type");
$subjectID = requestInputPost("subject_id");

if ($subjectType === null || $subjectID === null || $returnUrl === null) {
    exit;
}

$requiredPermissions = match ($subjectType) {
    SubscriptionSubjectType::GameTickets, SubscriptionSubjectType::GameAchievements => Permissions::JuniorDeveloper,
    default => Permissions::Registered,
};

if (!validateFromCookie($user, $unused, $permissions, $requiredPermissions)) {
    header("Location: " . getenv("APP_URL") . $returnUrl . "&e=badcredentials");
    exit;
}

$userID = getUserIDFromUser($user);
if ($userID == 0) {
    header("Location: " . getenv("APP_URL") . $returnUrl . "&e=badcredentials");
    exit;
}

// subscribing or unsubscribing?
$operation = requestInputPost("operation");
if ($operation !== "subscribe" && $operation !== "unsubscribe") {
    header("Location: " . getenv("APP_URL") . $returnUrl . "&e=invalidparams");
    exit;
}

// update the database
$subscriptionState = ($operation === "subscribe");
if (!updateSubscription($subjectType, $subjectID, $userID, $subscriptionState)) {
    header("Location " . getenv("APP_URL") . $returnUrl . "&e=subscription_update_fail");
    exit;
}

// everything's ok, go back
header("Location: " . getenv("APP_URL") . $returnUrl);
