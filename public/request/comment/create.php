<?php

use RA\ArticleType;
use RA\Permissions;
use RA\TicketState;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

// Sanitise!
if (!ValidatePOSTChars("act")) {
    echo "FAILED";
    exit;
}

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    echo "FAILED!";
    exit;
}

$articleID = requestInputPost('a', null, 'integer');
$articleType = requestInputPost('t', null, 'integer');

$commentPayload = requestInputPost('c');

if (addArticleComment($user, $articleType, $articleID, $commentPayload)) {
    if ($articleType == ArticleType::AchievementTicket) {
        // if a user is responding to a ticket in the Request state,
        // automatically change the state back to Open
        $ticketData = getTicket($articleID);
        if ($ticketData['ReportState'] == TicketState::Request && $ticketData['ReportedBy'] == $user) {
            updateTicket($user, $articleID, TicketState::Open);
        }
    }

    echo $articleID;
} else {
    echo "FAILED!";
}
