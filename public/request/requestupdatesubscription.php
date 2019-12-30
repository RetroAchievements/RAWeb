<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

// what is being (un-)subscribed? and where should we go back to at the end?

$returnUrl = seekPOST("return_url");
$subjectType = seekPOST("subject_type");
$subjectID = seekPOST("subject_id");

if (is_null($subjectType) || is_null($subjectID) || is_null($returnUrl)) {
    exit;
}

// can the user perform this operation?

switch ($subjectType) {
    case \RA\SubscriptionSubjectType::GameTickets:
    case \RA\SubscriptionSubjectType::GameAchievements:
        $requiredPermissions = \RA\Permissions::Developer;
        break;
    default:
        $requiredPermissions = \RA\Permissions::Registered;
        break;
}

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
$operation = seekPost("operation");
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
