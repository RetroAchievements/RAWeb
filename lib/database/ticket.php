<?php

use RA\AchievementType;
use RA\ActivityType;
use RA\ArticleType;
use RA\Models\TicketModel;
use RA\SubscriptionSubjectType;
use RA\TicketFilters;
use RA\TicketState;

function isAllowedToSubmitTickets($user): bool
{
    return isValidUsername($user)
        && getUserActivityRange($user, $firstLogin, $lastLogin)
        && time() - strtotime($firstLogin) > 86400 // 86400 seconds = 1 day
        && getRecentlyPlayedGames($user, 0, 1, $userInfo)
        && $userInfo[0]['GameID'];
}

function submitNewTicketsJSON($userSubmitter, $idsCSV, $reportType, $noteIn, $RAHash): array
{
    sanitize_sql_inputs($userSubmitter, $reportType, $noteIn, $RAHash);

    $returnMsg = [];

    if (!isAllowedToSubmitTickets($userSubmitter)) {
        $returnMsg['Success'] = false;
        return $returnMsg;
    }

    global $db;

    $note = $noteIn;
    $note .= "\nRetroAchievements Hash: $RAHash";

    $submitterUserID = getUserIDFromUser($userSubmitter);
    settype($reportType, 'integer');

    $achievementIDs = explode(',', $idsCSV);

    $errorsEncountered = false;

    $idsFound = 0;
    $idsAdded = 0;

    foreach ($achievementIDs as $achID) {
        settype($achID, 'integer');
        if ($achID == 0) {
            continue;
        }

        $idsFound++;

        $query = "INSERT INTO Ticket (AchievementID, ReportedByUserID, ReportType, ReportNotes, ReportedAt, ResolvedAt, ResolvedByUserID ) 
                                VALUES ($achID, $submitterUserID, $reportType, '$note', NOW(), NULL, NULL )";

        $dbResult = mysqli_query($db, $query); // Unescaped?
        $ticketID = mysqli_insert_id($db);

        if (!$dbResult) {
            $errorsEncountered = true;
            log_sql_fail();
        } else {
            // Success
            if (GetAchievementMetadata($achID, $achData)) {
                $achAuthor = $achData['Author'];
                $achTitle = $achData['AchievementTitle'];
                $gameID = $achData['GameID'];
                $gameTitle = $achData['GameTitle'];

                $problemTypeStr = ($reportType == 1) ? "Triggers at wrong time" : "Doesn't trigger";

                $bugReportDetails = "Achievement: [ach=$achID] ($achTitle)
Game: [game=$gameID] ($gameTitle)
Problem: $problemTypeStr
Comment: $note

This ticket will be raised and will be available for all developers to inspect and manage at the following URL:
" . getenv('APP_URL') . "/ticketmanager.php?i=$ticketID"
. " Thanks!";
                $bugReportMessage = "Hi, $achAuthor!
[user=$userSubmitter] would like to report a bug with an achievement you've created:
$bugReportDetails";

                CreateNewMessage($userSubmitter, $achData['Author'], "Bug Report ($gameTitle)", $bugReportMessage);
                postActivity($userSubmitter, ActivityType::OpenedTicket, $achID);

                // notify subscribers other than the achievement's author
                $subscribers = getSubscribersOf(SubscriptionSubjectType::GameTickets, $gameID, (1 << 1));
                $emailHeader = "Bug Report ($gameTitle)";
                foreach ($subscribers as $sub) {
                    if ($sub['User'] != $achAuthor && $sub['User'] != $userSubmitter) {
                        $emailBody = "Hi, " . $sub['User'] . "!
[user=$userSubmitter] would like to report a bug with an achievement you're subscribed to:
$bugReportDetails";
                        sendRAEmail($sub['EmailAddress'], $emailHeader, $emailBody);
                    }
                }
            }

            $idsAdded++;
        }
    }

    $returnMsg['Detected'] = $idsFound;
    $returnMsg['Added'] = $idsAdded;
    $returnMsg['Success'] = ($errorsEncountered == false);

    return $returnMsg;
}

function submitNewTickets($userSubmitter, $idsCSV, $reportType, $hardcore, $noteIn, &$summaryMsgOut): bool
{
    if (!isAllowedToSubmitTickets($userSubmitter)) {
        $summaryMsgOut = "FAILED!";
        return false;
    }

    $note = $noteIn;
    sanitize_sql_inputs($userSubmitter, $reportType, $hardcore, $note);

    global $db;

    $submitterUserID = getUserIDFromUser($userSubmitter);
    settype($reportType, 'integer');

    $achievementIDs = explode(',', $idsCSV);

    $errorsEncountered = false;

    $idsFound = 0;
    $idsAdded = 0;

    foreach ($achievementIDs as $achID) {
        settype($achID, 'integer');
        if ($achID == 0) {
            continue;
        }

        $idsFound++;

        $query = "INSERT INTO Ticket (AchievementID, ReportedByUserID, ReportType, Hardcore, ReportNotes, ReportedAt, ResolvedAt, ResolvedByUserID ) 
                                VALUES($achID, $submitterUserID, $reportType, $hardcore, \"$note\", NOW(), NULL, NULL )";

        $dbResult = mysqli_query($db, $query); // Unescaped?
        $ticketID = mysqli_insert_id($db);

        if (!$dbResult) {
            $errorsEncountered = true;
            log_sql_fail();
        } else {
            // Success
            if (GetAchievementMetadata($achID, $achData)) {
                $achAuthor = $achData['Author'];
                $gameID = $achData['GameID'];
                $gameTitle = $achData['GameTitle'];

                $problemTypeStr = ($reportType == 1) ? "Triggers at wrong time" : "Doesn't trigger";

                $bugReportDetails = "Achievement: [ach=$achID]
Game: [game=$gameID]
Problem: $problemTypeStr
Comment: $noteIn

This ticket will be raised and will be available for all developers to inspect and manage at the following URL:
" . getenv('APP_URL') . "/ticketmanager.php?i=$ticketID"
. " Thanks!";

                $bugReportMessage = "Hi, $achAuthor!\r\n
[user=$userSubmitter] would like to report a bug with an achievement you've created:
$bugReportDetails";
                CreateNewMessage($userSubmitter, $achData['Author'], "Bug Report ($gameTitle)", $bugReportMessage);
                postActivity($userSubmitter, ActivityType::OpenedTicket, $achID);

                // notify subscribers other than the achievement's author
                $subscribers = getSubscribersOf(SubscriptionSubjectType::GameTickets, $gameID, (1 << 0) /* (1 << 1) */);
                $emailHeader = "Bug Report ($gameTitle)";
                foreach ($subscribers as $sub) {
                    if ($sub['User'] != $achAuthor && $sub['User'] != $userSubmitter) {
                        $emailBody = "Hi, " . $sub['User'] . "!\r\n
[user=$userSubmitter] would like to report a bug with an achievement you're subscribed to':
$bugReportDetails";
                        sendRAEmail($sub['EmailAddress'], $emailHeader, $emailBody);
                    }
                }
            }

            $idsAdded++;
        }
    }

    if ($idsAdded > 0 && $idsFound == $idsAdded) {
        // Normal exit
        $summaryMsgOut = "OK:";
    } elseif ($idsAdded > 0) {
        $summaryMsgOut = "OK:$idsAdded/$idsFound added.";
    } else {
        $summaryMsgOut = "FAILED!";
    }

    return $errorsEncountered == false;
}

function getAllTickets(
    $offset = 0,
    $limit = 50,
    $assignedToUser = null,
    $reportedByUser = null,
    $resolvedByUser = null,
    $givenGameID = null,
    $givenAchievementID = null,
    $ticketFilters = TicketFilters::Default,
    $getUnofficial = false
): array {
    sanitize_sql_inputs($offset, $limit, $assignedToUser, $givenGameID, $givenAchievementID);

    $retVal = [];
    settype($givenGameID, 'integer');
    settype($ticketFilters, 'integer');
    settype($givenAchievementID, 'integer');

    $innerCond = "TRUE";
    if (!empty($assignedToUser) && isValidUsername($assignedToUser)) {
        $innerCond .= " AND ach.Author = '$assignedToUser'";
    }
    if (!empty($reportedByUser) && isValidUsername($reportedByUser)) {
        $innerCond .= " AND ua.User = '$reportedByUser'";
    }
    if (!empty($resolvedByUser) && isValidUsername($resolvedByUser)) {
        $innerCond .= " AND ua2.User = '$resolvedByUser'";
    }
    if ($givenGameID != 0) {
        $innerCond .= " AND gd.ID = $givenGameID";
    }
    if ($givenAchievementID != 0) {
        $innerCond .= " AND tick.AchievementID = $givenAchievementID";
    }

    // State condition
    $stateCond = getStateCondition($ticketFilters);
    if ($stateCond === null) {
        return $retVal;
    }

    // Report Type condition
    $reportTypeCond = getReportTypeCondition($ticketFilters);
    if ($reportTypeCond === null) {
        return $retVal;
    }

    // Hash condition
    $hashCond = getHashCondition($ticketFilters);
    if ($hashCond === null) {
        return $retVal;
    }

    // Mode condition
    $modeCond = getModeCondition($ticketFilters);
    if ($modeCond === null) {
        return $retVal;
    }

    // Emulator condition
    $emulatorCond = getEmulatorCondition($ticketFilters);
    if ($emulatorCond === null) {
        return $retVal;
    }

    // Developer Active condition
    $devJoin = "";
    $devActiveCond = getDevActiveCondition($ticketFilters);
    if ($devActiveCond === null) {
        return $retVal;
    } elseif ($devActiveCond != "") {
        $devJoin = "LEFT JOIN UserAccounts AS ua3 ON ua3.User = ach.Author";
    }

    // Karama condition
    $notAuthorCond = getNotAuthorCondition($ticketFilters);

    // official/unofficial filter (ignore when a specific achievement is requested)
    $achFlagCond = '';
    if (!$givenAchievementID) {
        settype($getUnofficial, 'boolean');
        $achFlagCond = $getUnofficial ? " AND ach.Flags = '5'" : "AND ach.Flags = '3'";
    }

    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.Points, ach.BadgeName,
                ach.Author AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
                tick.ReportedAt, tick.ReportType, tick.Hardcore, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy, tick.ReportState
              FROM Ticket AS tick
              LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = tick.ReportedByUserID
              LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.ResolvedByUserID
              $devJoin
              WHERE $innerCond $achFlagCond $stateCond $modeCond $reportTypeCond $hashCond $emulatorCond $devActiveCond $notAuthorCond
              ORDER BY tick.ID DESC
              LIMIT $offset, $limit";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    }

    return $retVal;
}

function getTicket($ticketID): ?array
{
    sanitize_sql_inputs($ticketID);

    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.Points, ach.BadgeName,
                ach.Author AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
                tick.ReportedAt, tick.ReportType, tick.ReportState, tick.Hardcore, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy
              FROM Ticket AS tick
              LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = tick.ReportedByUserID
              LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.ResolvedByUserID
              WHERE tick.ID = $ticketID
              ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return null;
    }

    return mysqli_fetch_assoc($dbResult);
}

function updateTicket($user, $ticketID, $ticketVal, $reason = null): bool
{
    sanitize_sql_inputs($ticketID, $ticketVal);

    $userID = getUserIDFromUser($user);

    // get the ticket data before updating so we know what the previous state was
    $ticketData = getTicket($ticketID);

    $resolvedFields = "";
    if ($ticketVal == TicketState::Resolved || $ticketVal == TicketState::Closed) {
        $resolvedFields = ", ResolvedAt=NOW(), ResolvedByUserID=$userID ";
    }

    $query = "UPDATE Ticket
              SET ReportState=$ticketVal $resolvedFields
              WHERE ID=$ticketID";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
        return false;
    }

    $userReporter = $ticketData['ReportedBy'];
    $achID = $ticketData['AchievementID'];
    $achTitle = $ticketData['AchievementTitle'];
    $gameTitle = $ticketData['GameTitle'];
    $consoleName = $ticketData['ConsoleName'];

    $status = TicketState::toString($ticketVal);
    $comment = null;

    switch ($ticketVal) {
        case TicketState::Closed:
            if ($reason == TicketState::REASON_DEMOTED) {
                updateAchievementFlags($achID, AchievementType::UNOFFICIAL);
            }
            $comment = "Ticket closed by $user. Reason: \"$reason\".";
            postActivity($user, ActivityType::ClosedTicket, $achID);
            break;

        case TicketState::Open:
            if ($ticketData['ReportState'] == TicketState::Request) {
                $comment = "Ticket reassigned to author by $user.";
            } else {
                $comment = "Ticket reopened by $user.";
                postActivity($user, ActivityType::OpenedTicket, $achID);
            }
            break;

        case TicketState::Resolved:
            $comment = "Ticket resolved as fixed by $user.";
            postActivity($user, ActivityType::ClosedTicket, $achID);
            break;

        case TicketState::Request:
            $comment = "Ticket reassigned to reporter by $user.";
            break;
    }

    addArticleComment("Server", ArticleType::AchievementTicket, $ticketID, $comment, $user);

    getAccountDetails($userReporter, $reporterData);
    $email = $reporterData['EmailAddress'];

    $emailTitle = "Ticket status changed";

    $msg = "Hello $userReporter!<br>" .
        "<br>" .
        "$achTitle - $gameTitle ($consoleName)<br>" .
        "<br>" .
        "The ticket you opened for the above achievement had its status changed to \"$status\" by \"$user\".<br>" .
        "<br>Comment: $comment" .
        "<br>" .
        "Click <a href='" . getenv('APP_URL') . "/ticketmanager.php?i=$ticketID'>here</a> to view the ticket" .
        "<br>" .
        "Thank-you again for your help in improving the quality of the achievements on RA!<br>" .
        "<br>" .
        "-- Your friends at RetroAchievements.org<br>";

    return mail_utf8($email, $emailTitle, $msg);
}

function countRequestTicketsByUser($user): ?int
{
    if ($user == null) {
        return null;
    }

    sanitize_sql_inputs($dev);

    $query = "
        SELECT count(*) as count
        FROM Ticket AS tick
        LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN UserAccounts AS ua ON ua.ID = tick.ReportedByUserID
        WHERE ua.User = '$user' AND ach.Flags IN (3, 5) AND tick.ReportState = " . TicketState::Request;

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        return 0;
    }

    return (int) mysqli_fetch_assoc($dbResult)['count'];
}

function countOpenTicketsByDev($dev): ?array
{
    if ($dev == null) {
        return null;
    }

    sanitize_sql_inputs($dev);

    $retVal = [
        TicketState::Open => 0,
        TicketState::Request => 0,
    ];

    $query = "
        SELECT tick.ReportState, count(*) as Count
        FROM Ticket AS tick
        LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN UserAccounts AS ua ON ua.User = ach.Author
        WHERE ach.Author = '$dev' AND ach.Flags IN (3, 5) 
        AND tick.ReportState IN (" . TicketState::Open . "," . TicketState::Request . ")
        GROUP BY tick.ReportState";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[$nextData['ReportState']] = $nextData['Count'];
        }
    }

    return $retVal;
}

function countOpenTicketsByAchievement($achievementID): int
{
    sanitize_sql_inputs($achievementID);
    settype($achievementID, 'integer');
    if ($achievementID <= 0) {
        return 0;
    }

    $query = "
        SELECT COUNT(*) as count
        FROM Ticket
        WHERE AchievementID = $achievementID AND ReportState IN (" . TicketState::Open . "," . TicketState::Request . ')';

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return (int) mysqli_fetch_assoc($dbResult)['count'];
    }

    return 0;
}

function countOpenTickets(
    $unofficialFlag = false,
    $ticketFilters = TicketFilters::Default,
    $assignedToUser = null,
    $reportedByUser = null,
    $resolvedByUser = null,
    $gameID = null,
    $achievementID = null
): int {
    sanitize_sql_inputs($assignedToUser, $reportedByUser, $resolvedByUser, $gameID);

    // State condition
    $stateCond = getStateCondition($ticketFilters);
    if ($stateCond === null) {
        return 0;
    }

    // Report Type condition
    $reportTypeCond = getReportTypeCondition($ticketFilters);
    if ($reportTypeCond === null) {
        return 0;
    }

    // Hash condition
    $hashCond = getHashCondition($ticketFilters);
    if ($hashCond === null) {
        return 0;
    }

    // Emulator condition
    $emulatorCond = getEmulatorCondition($ticketFilters);
    if ($emulatorCond === null) {
        return 0;
    }

    $modeCond = getModeCondition($ticketFilters);
    if ($modeCond === null) {
        return 0;
    }

    // Developer Active condition
    $devJoin = "";
    $devActiveCond = getDevActiveCondition($ticketFilters);
    if ($devActiveCond === null) {
        return 0;
    } elseif ($devActiveCond != "") {
        $devJoin = "LEFT JOIN UserAccounts AS ua3 ON ua3.User = ach.Author";
    }

    // Not Author condition
    $resolverJoin = "";
    $notAuthorCond = getNotAuthorCondition($ticketFilters);
    if ($notAuthorCond != "") {
        $resolverJoin = "LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.ResolvedByUserID AND tick.ReportState IN (" . TicketState::Closed . "," . TicketState::Resolved . ")";
    }

    // Author condition
    $authorCond = "";
    if ($assignedToUser != null) {
        $authorCond = " AND ach.Author LIKE '$assignedToUser'";
    }

    // Reporter condition
    $reporterCond = "";
    $reporterJoin = "";
    if ($reportedByUser != null) {
        $reporterJoin = "LEFT JOIN UserAccounts AS ua ON ua.ID = tick.ReportedByUserID";
        $reporterCond = " AND ua.User LIKE '$reportedByUser'";
    }

    // Resolver condition
    $resolverCond = "";
    if ($resolvedByUser != null) {
        $resolverCond = " AND ua2.User LIKE '$resolvedByUser'";
        if ($resolverJoin == "") {
            $resolverJoin = "LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.ResolvedByUserID AND tick.ReportState IN (" . TicketState::Closed . "," . TicketState::Resolved . ")";
        }
    }

    // Game condition
    $gameCond = "";
    if ($gameID != null) {
        $gameCond = " AND ach.GameID = $gameID";
    }
    if ($achievementID != null) {
        $gameCond .= " AND ach.ID = $achievementID";
    }

    settype($unofficialFlag, 'boolean');
    $achFlagCond = $unofficialFlag ? "ach.Flags = '5'" : "ach.Flags = '3'";

    $query = "
        SELECT count(*) as count
        FROM Ticket AS tick
        LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        $reporterJoin
        $resolverJoin
        $devJoin
        WHERE $achFlagCond $stateCond $gameCond $modeCond $reportTypeCond $hashCond $emulatorCond $authorCond $devActiveCond $notAuthorCond $reporterCond $resolverCond";

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        return 0;
    }

    return (int) mysqli_fetch_assoc($dbResult)['count'];
}

function gamesSortedByOpenTickets($count): array
{
    sanitize_sql_inputs($count);
    settype($count, 'integer');
    if ($count == 0) {
        $count = 20;
    }

    $query = "
        SELECT
            gd.ID AS GameID,
            gd.Title AS GameTitle,
            gd.ImageIcon AS GameIcon,
            cons.Name AS Console,
            COUNT(*) as OpenTickets
        FROM
            Ticket AS tick
        LEFT JOIN
            Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN
            GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN
            Console AS cons ON cons.ID = gd.ConsoleID
        WHERE
            tick.ReportState IN (" . TicketState::Open . "," . TicketState::Request . ") AND ach.Flags = 3
        GROUP BY
            gd.ID
        ORDER BY
            OpenTickets DESC
        LIMIT 0, $count";

    $retVal = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    }

    return $retVal;
}

/**
 * Gets the ticket state condition to put into the main ticket query.
 */
function getStateCondition(int $ticketFilters): ?string
{
    $states = [];
    if ($ticketFilters & TicketFilters::StateOpen) {
        $states[] = TicketState::Open;
    }
    if ($ticketFilters & TicketFilters::StateClosed) {
        $states[] = TicketState::Closed;
    }
    if ($ticketFilters & TicketFilters::StateResolved) {
        $states[] = TicketState::Resolved;
    }
    if ($ticketFilters & TicketFilters::StateRequest) {
        $states[] = TicketState::Request;
    }

    if (count($states) == 4) {
        // all states selected, no need to filter
        return "";
    } elseif (count($states) == 0) {
        // no states selected, can't matching anything
        return null;
    }

    return " AND tick.ReportState IN (" . implode(',', $states) . ')';
}

/**
 * Gets the ticket report type condition to put into the main ticket query.
 */
function getReportTypeCondition(int $ticketFilters): ?string
{
    $triggeredTickets = ($ticketFilters & TicketFilters::TypeTriggeredAtWrongTime);
    $didNotTriggerTickets = ($ticketFilters & TicketFilters::TypeDidNotTrigger);

    if ($triggeredTickets && $didNotTriggerTickets) {
        return "";
    }
    if ($triggeredTickets) {
        return " AND tick.ReportType LIKE 1";
    }
    if ($didNotTriggerTickets) {
        return " AND tick.ReportType NOT LIKE 1";
    }
    return null;
}

/**
 * Gets the ticket hash condition to put into the main ticket query.
 */
function getHashCondition(int $ticketFilters): ?string
{
    $hashKnownTickets = ($ticketFilters & TicketFilters::HashKnown);
    $hashUnknownTickets = ($ticketFilters & TicketFilters::HashUnknown);

    if ($hashKnownTickets && $hashUnknownTickets) {
        return "";
    }
    if ($hashKnownTickets) {
        return " AND (tick.ReportNotes REGEXP '(MD5|RetroAchievements Hash): [a-fA-F0-9]{32}')";
    }
    if ($hashUnknownTickets) {
        return " AND (tick.ReportNotes NOT REGEXP '(MD5|RetroAchievements Hash): [a-fA-F0-9]{32}')";
    }
    return null;
}

function getModeCondition(int $ticketFilters): ?string
{
    $modeUnknown = ($ticketFilters & TicketFilters::HardcoreUnknown);
    $modeHardcore = ($ticketFilters & TicketFilters::HardcoreOn);
    $modeSoftcore = ($ticketFilters & TicketFilters::HardcoreOff);

    if ($modeUnknown && $modeHardcore && $modeSoftcore) {
        return "";
    }

    if (!$modeUnknown && !$modeHardcore && !$modeSoftcore) {
        return null;
    }

    $subquery = "AND (";
    $added = false;
    if ($modeUnknown) {
        $subquery .= "Hardcore IS NULL";
        $added = true;
    }

    if ($modeHardcore) {
        if ($added) {
            $subquery .= " OR ";
        }
        $subquery .= "Hardcore = 1";
        $added = true;
    }
    if ($modeSoftcore) {
        if ($added) {
            $subquery .= " OR ";
        }
        $subquery .= "Hardcore = 0";
        $subquery .= "";
    }
    $subquery .= ")";
    return $subquery;
}

/**
 * Gets the developer active condition to put into the main ticket query.
 */
function getDevActiveCondition(int $ticketFilters): ?string
{
    $devInactive = ($ticketFilters & TicketFilters::DevInactive);
    $devActive = ($ticketFilters & TicketFilters::DevActive);
    $devJunior = ($ticketFilters & TicketFilters::DevJunior);

    if ($devInactive && $devActive && $devJunior) {
        return "";
    } elseif ($devInactive || $devActive || $devJunior) {
        $stateCond = " AND ua3.Permissions IN (";
        if ($devInactive) {
            $stateCond .= "-1,0,1";
        }

        if ($devActive) {
            if ($devInactive) {
                $stateCond .= ",";
            }
            $stateCond .= "3,4";
        }

        if ($devJunior) {
            if ($devInactive || $devActive) {
                $stateCond .= ",";
            }
            $stateCond .= "2";
        }
        $stateCond .= ")";
        return $stateCond;
    } else {
        return null;
    }
}

/**
 * Gets the Not Author condition to put into the main ticket query.
 */
function getNotAuthorCondition(int $ticketFilters): string
{
    $notAuthorTickets = ($ticketFilters & TicketFilters::ResolvedByNonAuthor);

    if ($notAuthorTickets) {
        return "AND ua2.User IS NOT NULL AND ua2.User <> ach.Author";
    } else {
        return "";
    }
}

/**
 * Gets the ticket emulator condition to put into the main ticket query.
 */
function getEmulatorCondition(int $ticketFilters): ?string
{
    $raEmulatorTickets = ($ticketFilters & TicketFilters::EmulatorRA);
    $rarchKnownTickets = ($ticketFilters & TicketFilters::EmulatorRetroArchCoreSpecified);
    $rarchUnknownTickets = ($ticketFilters & TicketFilters::EmulatorRetroArchCoreNotSpecified);
    $emulatorUnknownTickets = ($ticketFilters & TicketFilters::EmulatorUnknown);

    if ($raEmulatorTickets && $rarchKnownTickets && $rarchUnknownTickets && $emulatorUnknownTickets) {
        return "";
    } elseif ($raEmulatorTickets || $rarchKnownTickets || $rarchUnknownTickets || $emulatorUnknownTickets) {
        $emulatorCond = " AND (";
        if ($raEmulatorTickets) {
            $emulatorCond .= "tick.ReportNotes Like '%Emulator: RA%' ";
        }

        if ($rarchKnownTickets) {
            if ($raEmulatorTickets) {
                $emulatorCond .= " OR ";
            }
            $emulatorCond .= "tick.ReportNotes LIKE '%Emulator: RetroArch (_%)%' ";
        }

        if ($rarchUnknownTickets) {
            if ($raEmulatorTickets || $rarchKnownTickets) {
                $emulatorCond .= " OR ";
            }
            $emulatorCond .= "tick.ReportNotes LIKE '%Emulator: RetroArch ()%'";
        }

        if ($emulatorUnknownTickets) {
            if ($raEmulatorTickets || $rarchKnownTickets || $rarchUnknownTickets) {
                $emulatorCond .= " OR ";
            }
            $emulatorCond .= "(tick.ReportNotes NOT LIKE '%Emulator: RA%' AND tick.ReportNotes NOT LIKE '%Emulator: RetroArch%')";
        }
        $emulatorCond .= ")";
        return $emulatorCond;
    }
    return null;
}

/**
 * Gets the total number of tickets and ticket states for a specific user.
 */
function getTicketsForUser(string $user): array
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT t.AchievementID, ReportState, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              WHERE a.Author = '$user' AND a.Flags = '3'
              GROUP BY t.AchievementID, ReportState
              ORDER BY t.AchievementID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

/**
 * Gets the user developed game with the most amount of tickets.
 */
function getUserGameWithMostTickets(string $user): ?array
{
    sanitize_sql_inputs($user);

    $query = "SELECT gd.ID as GameID, gd.Title as GameTitle, gd.ImageIcon as GameIcon, c.Name as ConsoleName, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = '$user'
              AND a.Flags = '3'
              GROUP BY gd.Title
              ORDER BY TicketCount DESC
              LIMIT 1";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    } else {
        return null;
    }
}

/**
 * Gets the user developed achievement with the most amount of tickets.
 */
function getUserAchievementWithMostTickets(string $user): ?array
{
    sanitize_sql_inputs($user);

    $query = "SELECT a.ID as AchievementID, a.Title as AchievementTitle, a.Description as AchievementDescription, a.Points as AchievementPoints, a.BadgeName as AchievementBadge, gd.Title AS GameTitle, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = '$user'
              AND a.Flags = '3'
              GROUP BY a.ID
              ORDER BY TicketCount DESC
              LIMIT 1";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    } else {
        return null;
    }
}

/**
 * Gets the user who created the most tickets for another user.
 */
function getUserWhoCreatedMostTickets(string $user): ?array
{
    sanitize_sql_inputs($user);

    $query = "SELECT ua.User as TicketCreator, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN UserAccounts as ua ON ua.ID = t.ReportedByUserID
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              WHERE a.Author = '$user'
              GROUP BY t.ReportedByUserID
              ORDER BY TicketCount DESC
              LIMIT 1";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    } else {
        return null;
    }
}

/**
 * Gets the number of tickets closed/resolved for other users.
 */
function getNumberOfTicketsClosedForOthers(string $user): array
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT a.Author, COUNT(a.Author) AS TicketCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Closed . " THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Resolved . " THEN 1 ELSE 0 END) AS ResolvedCount
              FROM Ticket AS t
              LEFT JOIN UserAccounts as ua ON ua.ID = t.ReportedByUserID
              LEFT JOIN UserAccounts as ua2 ON ua2.ID = t.resolvedByUserID
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              WHERE t.ReportState IN (" . TicketState::Closed . "," . TicketState::Resolved . ")
              AND ua.User NOT LIKE '$user'
              AND a.Author NOT LIKE '$user'
              AND ua2.User LIKE '$user'
              AND a.Flags = '3'
              GROUP BY a.Author
              ORDER BY TicketCount DESC, Author";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

function GetTicketModel(int $ticketId): ?TicketModel
{
    $ticketDbResult = getTicket($ticketId);

    if ($ticketDbResult == null) {
        return null;
    }

    return new TicketModel($ticketDbResult);
}
