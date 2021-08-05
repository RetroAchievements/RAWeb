<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$baseUrl = getenv('APP_URL') . '/ticketmanager.php';
$defaultTicketFilter = 2041; //2041 sets all filters active except for Closed and Resolved
$count = 10;
$offset = 0;

$ticketID = requestInput('i', 0, 'integer');

// if ticket ID given...
if ($ticketID > 0) {
    $ticketData = getTicket($ticketID);
    if ($ticketData == false) {
        http_response_code(404);
        echo json_encode(['error' => "Ticket ID $ticketID not found"]);
        return;
    }

    $reportTypes = ['', "Triggered at a wrong time", "Doesn't trigger"];
    $reportStates = ["Closed", "Open", "Resolved"];

    $ticketData['ReportStateDescription'] = $reportStates[$ticketData['ReportState']];
    $ticketData['ReportTypeDescription'] = $reportTypes[$ticketData['ReportType']];

    $ticketData['URL'] = $baseUrl . "?i=$ticketID";
    echo json_encode($ticketData);
    return;
}

// same logic used in ticketmanager.php
// f=1 - get info for the most reported games
// f=5 - get info for unofficial
$gamesTableFlag = requestInputQuery('f', 0, 'integer');

// get the most reported games...
if ($gamesTableFlag == 1) {
    $ticketData['MostReportedGames'] = gamesSortedByOpenTickets($count);
    $ticketData['URL'] = $baseUrl . "?f=$gamesTableFlag";
    echo json_encode($ticketData);
    return;
}

// getting ticket info for a specific user
$assignedToUser = requestInputQuery('u', null);
if (isValidUsername($assignedToUser)) {
    $ticketData['User'] = $assignedToUser;
    $ticketData['Open'] = 0;
    $ticketData['Closed'] = 0;
    $ticketData['Resolved'] = 0;
    $ticketData['Total'] = 0;
    $prevID = 0;

    $userTicketInfo = getTicketsForUser($assignedToUser);
    foreach ($userTicketInfo as $ticket) {
        switch ($ticket['ReportState']) {
            case 0:
                $ticketData['Closed'] += $ticket['TicketCount'];
                $ticketData['Total'] += $ticket['TicketCount'];
                break;
            case 1:
                $ticketData['Open'] += $ticket['TicketCount'];
                $ticketData['Total'] += $ticket['TicketCount'];
                break;
            case 2:
                $ticketData['Resolved'] += $ticket['TicketCount'];
                $ticketData['Total'] += $ticket['TicketCount'];
                break;
        }
        if ($prevID != $ticket['AchievementID']) {
            $prevID = $ticket['AchievementID'];
        }
    }
    $ticketData['URL'] = $baseUrl . "?u=$assignedToUser";
    echo json_encode($ticketData);
    return;
}
$assignedToUser = null;

// getting data for a specific game
$gameIDGiven = requestInputQuery('g', null, 'integer');
if ($gameIDGiven > 0) {
    if (getGameTitleFromID($gameIDGiven, $gameTitle, $consoleID, $consoleName, $forumTopicID, $allData)) {
        $ticketData['GameID'] = $gameIDGiven;
        $ticketData['GameTitle'] = $gameTitle;
        $ticketData['ConsoleName'] = $consoleName;
        $ticketData['OpenTickets'] = countOpenTickets(
            $gamesTableFlag == 5, // 5 is the magic number for Unofficial
            $defaultTicketFilter,
            $assignedToUser,
            $gameIDGiven
        );
        $ticketData['URL'] = $baseUrl . "?g=$gameIDGiven";

        echo json_encode($ticketData);
        return;
    }
    http_response_code(404);
    echo json_encode(['error' => "Game ID $gameIDGiven not found"]);
    return;
}

// getting data for a specific achievement
$achievementIDGiven = requestInputQuery('a', null, 'integer');
if ($achievementIDGiven > 0) {
    $achievementData = GetAchievementData($achievementIDGiven);
    if (!$achievementData) {
        http_response_code(404);
        echo json_encode(['error' => "Achievement ID $achievementIDGiven not found"]);
        return;
    }
    $ticketData['AchievementID'] = $achievementIDGiven;
    $ticketData['AchievementTitle'] = $achievementData['Title'];
    $ticketData['AchievementDescription'] = $achievementData['Description'];
    $ticketData['URL'] = $baseUrl . "?a=$achievementIDGiven";
    $ticketData['OpenTickets'] = countOpenTicketsByAchievement($achievementIDGiven);
    echo json_encode($ticketData);
    return;
}

// getting the 10 most recent tickets
$ticketData['RecentTickets'] = getAllTickets($offset, $count, null, null, null, $defaultTicketFilter);
$ticketData['OpenTickets'] = countOpenTickets(false, $defaultTicketFilter, null, null);
$ticketData['URL'] = $baseUrl;
echo json_encode($ticketData);
