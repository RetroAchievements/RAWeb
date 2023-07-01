<?php

/*
 *  API_GetTicketData - returns details for a specific ticket
 *    i : ticket id
 *
 *  int        ID                      unique identifier of the ticket
 *  int        AchievementID           unique identifier of the achievement associated to the ticket
 *  string     AchievementTitle        title of the achievement
 *  string     AchievementDesc         description of the achievement
 *  int        Points                  number of points the achievement
 *  string     BadgeName               unique identifier of the badge image for the achievement
 *  string     AchievementAuthor       user who originally created the achievement
 *  int        GameID                  unique identifier of the game associated to the achievement
 *  string     GameTitle               title of the game
 *  string     GameIcon                site-relative path to the game's icon image
 *  string     ConsoleName             name of the console associated to the game
 *  datetime   ReportedAt              when the ticket was created
 *  int        ReportType              unique identifier of the ticket type
 *  string     ReportTypeDescription   text description of the ticket type
 *  int        ReportState             unique identifier of the ticket state
 *  string     ReportStateDescription  text description of the ticket state
 *  int?       Hardcore                1=Hardcore, 0=not Hardcore, null=unknown
 *  string     ReportNotes             summary of the problem as reported by the user
 *  string     ReportedBy              user that created the ticket
 *  datetime   ResolvedAt              when the ticket was closed
 *  string     ResolvedBy              user that closed the ticket
 *  string     URL                     URL to the editor for the ticket
 */

/*
 *  API_GetTicketData - returns open tickets, starting at the most recent
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 10, max: 100)
 *
 *  array      RecentTickets
 *   int        ID                     unique identifier of the ticket
 *   int        AchievementID          unique identifier of the achievement associated to the ticket
 *   string     AchievementTitle       title of the achievement
 *   string     AchievementDesc        description of the achievement
 *   int        Points                 number of points the achievement
 *   string     BadgeName              unique identifier of the badge image for the achievement
 *   string     AchievementAuthor      user who originally created the achievement
 *   int        GameID                 unique identifier of the game associated to the achievement
 *   string     GameTitle              title of the game
 *   string     GameIcon               site-relative path to the game's icon image
 *   string     ConsoleName            name of the console associated to the game
 *   datetime   ReportedAt             when the ticket was created
 *   int        ReportType             unique identifier of the ticket type
 *   string     ReportTypeDescription  text description of the ticket type
 *   int        ReportState            unique identifier of the ticket state
 *   string     ReportStateDescription text description of the ticket state
 *   int?       Hardcore               1=Hardcore, 0=not Hardcore, null=unknown
 *   string     ReportNotes            summary of the problem as reported by the user
 *   string     ReportedBy             user that created the ticket
 *   datetime   ResolvedAt             when the ticket was closed
 *   string     ResolvedBy             user that closed the ticket
 *  int        OpenTickets             number of open tickets
 *  string     URL                     URL to the list of open tickets
 */

/*
 *  API_GetTicketData - returns games with the most open tickets
 *    f=1 : get most ticketed games
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 10, max: 100)
 *
 *  array      MostReportedGames
 *   int        GameID                 unique identifier of the game
 *   string     GameTitle              title of the game
 *   string     GameIcon               site-relative path to the game's icon image
 *   string     Console                name of the console associated to the game
 *   int        OpenTickets            number of open tickets associated to the game
 *  string     URL                     URL to the list of games with the most tickets
 */

/*
 *  API_GetTicketData - returns ticket statistics for the specified user
 *    u : username
 *
 *  string     User                    unique identifier of the user
 *  int        Open                    number of open tickets
 *  int        Closed                  number of closed tickets
 *  int        Resolved                number of resolved tickets
 *  int        Total                   total number of tickets
 *  string     URL                     URL to the list of tickets associated to achievements created by the user
 */

/*
 *  API_GetTicketData - returns ticket statistics for the specified game
 *    g : game id
 *    f=5 : query for tickets against unofficial achievements (default: core achievements)
 *    d=1 : specifies that the Tickets field should be populated
 *
 *  int        GameID                  unique identifier of the game
 *  string     GameTitle               title of the game
 *  string     ConsoleName             name of the console associated to the game
 *  int        OpenTickets             number of open tickets
 *  string     URL                     URL to the list of tickets associated to the game
 *  array      Tickets                 more details on open tickets (only present if requested)
 *   int        ID                     unique identifier of the ticket
 *   int        AchievementID          unique identifier of the achievement associated to the ticket
 *   string     AchievementTitle       title of the achievement
 *   string     AchievementDesc        description of the achievement
 *   int        Points                 number of points the achievement
 *   string     BadgeName              unique identifier of the badge image for the achievement
 *   string     AchievementAuthor      user who originally created the achievement
 *   int        GameID                 unique identifier of the game associated to the achievement
 *   string     GameTitle              title of the game
 *   string     GameIcon               site-relative path to the game's icon image
 *   string     ConsoleName            name of the console associated to the game
 *   datetime   ReportedAt             when the ticket was created
 *   int        ReportType             unique identifier of the ticket type
 *   string     ReportTypeDescription  text description of the ticket type
 *   int        ReportState            unique identifier of the ticket state
 *   string     ReportStateDescription text description of the ticket state
 *   int?       Hardcore               1=Hardcore, 0=not Hardcore, null=unknown
 *   string     ReportNotes            summary of the problem as reported by the user
 *   string     ReportedBy             user that created the ticket
 *   datetime   ResolvedAt             when the ticket was closed
 *   string     ResolvedBy             user that closed the ticket
 */

/*
 *  API_GetTicketData - returns ticket statistics for the specified achievement
 *    a : achievement id
 *
 *  int        AchievementID           unique identifier of the achievement
 *  string     AchievementTitle        title of the achievement
 *  string     AchievementDescription  description of the achievement
 *  int        OpenTickets             number of open tickets
 *  string     URL                     URL to the list of tickets associated to the game
 */

use App\Community\Enums\TicketFilters;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Platform\Enums\AchievementType;
use App\Platform\Models\Achievement;

$baseUrl = config('app.url') . '/ticketmanager.php';
$defaultTicketFilter = TicketFilters::Default;
$count = min((int) request()->query('c', '10'), 100);
$offset = (int) request()->query('o');

// if ticket ID given...
$ticketID = (int) request()->input('i');
if ($ticketID > 0) {
    $ticketData = getTicket($ticketID);
    if (!$ticketData) {
        return response()->json(['error' => "Ticket ID $ticketID not found"], 404);
    }

    $ticketData['ReportStateDescription'] = TicketState::toString($ticketData['ReportState']);
    $ticketData['ReportTypeDescription'] = TicketType::toString($ticketData['ReportType']);

    $ticketData['URL'] = $baseUrl . "?i=$ticketID";

    return response()->json($ticketData);
}

// same logic used in ticketmanager.php
// f=1 - get info for the most reported games
// f=5 - get info for unofficial
$gamesTableFlag = (int) request()->query('f');

// get the most reported games...
if ($gamesTableFlag == 1) {
    $ticketData['MostReportedGames'] = gamesSortedByOpenTickets($count);
    $ticketData['URL'] = $baseUrl . "?f=$gamesTableFlag";

    return response()->json($ticketData);
}

// getting ticket info for a specific user
$assignedToUser = request()->query('u');
if (!empty($assignedToUser)) {
    if (!isValidUsername($assignedToUser)) {
        return response()->json(['error' => "User $assignedToUser not found"], 404);
    }

    $ticketData['User'] = $assignedToUser;
    $ticketData['Open'] = 0;
    $ticketData['Closed'] = 0;
    $ticketData['Resolved'] = 0;
    $ticketData['Total'] = 0;
    $prevID = 0;

    $userTicketInfo = getTicketsForUser($assignedToUser);
    foreach ($userTicketInfo as $ticket) {
        switch ($ticket['ReportState']) {
            case TicketState::Closed:
                $ticketData['Closed'] += $ticket['TicketCount'];
                $ticketData['Total'] += $ticket['TicketCount'];
                break;
            case TicketState::Open:
                $ticketData['Open'] += $ticket['TicketCount'];
                $ticketData['Total'] += $ticket['TicketCount'];
                break;
            case TicketState::Resolved:
                $ticketData['Resolved'] += $ticket['TicketCount'];
                $ticketData['Total'] += $ticket['TicketCount'];
                break;
        }
        if ($prevID != $ticket['AchievementID']) {
            $prevID = $ticket['AchievementID'];
        }
    }
    $ticketData['URL'] = $baseUrl . "?u=$assignedToUser";

    return response()->json($ticketData);
}

// getting data for a specific game
$gameIDGiven = (int) request()->query('g');
if ($gameIDGiven > 0) {
    if ($gameData = getGameData($gameIDGiven)) {
        $ticketData['GameID'] = $gameIDGiven;
        $ticketData['GameTitle'] = $gameData['Title'];
        $ticketData['ConsoleName'] = $gameData['ConsoleName'];
        $ticketData['OpenTickets'] = countOpenTickets(
            $gamesTableFlag == AchievementType::Unofficial,
            $defaultTicketFilter,
            null,
            null,
            null,
            $gameIDGiven
        );
        $ticketData['URL'] = $baseUrl . "?g=$gameIDGiven";

        $details = (int) request()->query('d');
        if ($details == 1) {
            $ticketData['Tickets'] = getAllTickets($offset, $count, givenGameID: $gameIDGiven, ticketFilters: $defaultTicketFilter);

            foreach ($ticketData['Tickets'] as &$ticket) {
                $ticket['ReportStateDescription'] = TicketState::toString($ticket['ReportState']);
                $ticket['ReportTypeDescription'] = TicketType::toString($ticket['ReportType']);
            }
        }

        return response()->json($ticketData);
    }

    return response()->json(['error' => "Game ID $gameIDGiven not found"], 404);
}

// getting data for a specific achievement
$achievementIDGiven = (int) request()->query('a');
if ($achievementIDGiven > 0) {
    $achievementData = Achievement::find($achievementIDGiven);
    if (!$achievementData) {
        return response()->json(['error' => "Achievement ID $achievementIDGiven not found"], 404);
    }
    $ticketData['AchievementID'] = $achievementIDGiven;
    $ticketData['AchievementTitle'] = $achievementData['Title'];
    $ticketData['AchievementDescription'] = $achievementData['Description'];
    $ticketData['URL'] = $baseUrl . "?a=$achievementIDGiven";
    $ticketData['OpenTickets'] = countOpenTicketsByAchievement($achievementIDGiven);

    return response()->json($ticketData);
}

// getting the 10 most recent tickets
$ticketData['RecentTickets'] = getAllTickets($offset, $count, null, null, null, null, null, $defaultTicketFilter);
$ticketData['OpenTickets'] = countOpenTickets(false, $defaultTicketFilter, null, null, null, null);
$ticketData['URL'] = $baseUrl;

foreach ($ticketData['RecentTickets'] as &$ticket) {
    $ticket['ReportStateDescription'] = TicketState::toString($ticket['ReportState']);
    $ticket['ReportTypeDescription'] = TicketType::toString($ticket['ReportType']);
}

return response()->json($ticketData);
