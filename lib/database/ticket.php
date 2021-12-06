<?php

use RA\ActivityType;
use RA\Models\TicketModel;

function isAllowedToSubmitTickets($user)
{
    return isValidUsername($user)
        && getUserActivityRange($user, $firstLogin, $lastLogin)
        && time() - strtotime($firstLogin) > 86400 // 86400 seconds = 1 day
        && getRecentlyPlayedGames($user, 0, 1, $userInfo)
        && $userInfo[0]['GameID'];
}

function submitNewTicketsJSON($userSubmitter, $idsCSV, $reportType, $noteIn, $ROMMD5)
{
    sanitize_sql_inputs($userSubmitter, $reportType, $noteIn, $ROMMD5);

    $returnMsg = [];

    if (!isAllowedToSubmitTickets($userSubmitter)) {
        $returnMsg['Success'] = false;
        return $returnMsg;
    }

    global $db;

    $note = $noteIn;
    $note .= "\nMD5: $ROMMD5";

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
        // log_sql($query);

        $dbResult = mysqli_query($db, $query); //    Unescaped?
        $ticketID = mysqli_insert_id($db);
        // error_log(__FUNCTION__ . " produced insert id of $ticketID ");

        if ($dbResult == false) {
            // error_log(__FUNCTION__ . " failed?! $userSubmitter, $achID, $reportType, $note");
            $errorsEncountered = true;
            log_sql_fail();
        } else {
            //    Success
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
                $subscribers = getSubscribersOf(\RA\SubscriptionSubjectType::GameTickets, $gameID, (1 << 1));
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

function submitNewTickets($userSubmitter, $idsCSV, $reportType, $noteIn, &$summaryMsgOut)
{
    if (!isAllowedToSubmitTickets($userSubmitter)) {
        $summaryMsgOut = "FAILED!";
        return false;
    }

    $note = $noteIn;
    sanitize_sql_inputs($userSubmitter, $reportType, $note);

    global $db;

    // error_log("mysqli_real_escape_string turned #$noteIn# into #$note#");

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
                                VALUES($achID, $submitterUserID, $reportType, \"$note\", NOW(), NULL, NULL )";
        // log_sql($query);

        $dbResult = mysqli_query($db, $query); //    Unescaped?
        $ticketID = mysqli_insert_id($db);
        // error_log(__FUNCTION__ . " produced insert id of $ticketID ");

        if ($dbResult == false) {
            // error_log(__FUNCTION__ . " failed?! $userSubmitter, $achID, $reportType, $note");
            $errorsEncountered = true;
            log_sql_fail();
        } else {
            //    Success
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
                $subscribers = getSubscribersOf(\RA\SubscriptionSubjectType::GameTickets, $gameID, (1 << 0) /*(1 << 1)*/);
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
        //    Normal exit
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
    $givenGameID = null,
    $givenAchievementID = null,
    $ticketFilters = 2041, //2041 sets all filters active except for Closed and Resolved
    $getUnofficial = false
) {
    sanitize_sql_inputs($offset, $limit, $assignedToUser, $givenGameID, $givenAchievementID);

    $retVal = [];
    settype($givenGameID, 'integer');
    settype($ticketFilters, 'integer');
    settype($givenAchievementID, 'integer');

    $innerCond = "TRUE";
    if (!empty($assignedToUser) && isValidUsername($assignedToUser)) {
        $innerCond .= " AND ach.Author = '$assignedToUser'";
    }
    if ($givenGameID != 0) {
        $innerCond .= " AND gd.ID = $givenGameID";
    }
    if ($givenAchievementID != 0) {
        $innerCond .= " AND tick.AchievementID = $givenAchievementID";
    }

    //State condition
    $stateCond = getStateCondition($ticketFilters);
    if ($stateCond === null) {
        return $retVal;
    }

    //Report Type condition
    $reportTypeCond = getReportTypeCondition($ticketFilters);
    if ($reportTypeCond === null) {
        return $retVal;
    }

    //MD5 condition
    $md5Cond = getMD5Condition($ticketFilters);
    if ($md5Cond === null) {
        return $retVal;
    }

    //Emulator condition
    $emulatorCond = getEmulatorCondition($ticketFilters);
    if ($emulatorCond === null) {
        return $retVal;
    }

    settype($getUnofficial, 'boolean');
    $achFlagCond = $getUnofficial ? " AND ach.Flags = '5'" : "AND ach.Flags = '3'";

    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.Points, ach.BadgeName,
                ach.Author AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
                tick.ReportedAt, tick.ReportType, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy, tick.ReportState
              FROM Ticket AS tick
              LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = tick.ReportedByUserID
              LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.ResolvedByUserID
              WHERE $innerCond $achFlagCond $stateCond $reportTypeCond $md5Cond $emulatorCond
              ORDER BY tick.ID DESC
              LIMIT $offset, $limit";

    //echo $query;

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    } else {
        // error_log(__FUNCTION__ . " failed?! $offset, $limit");
    }

    return $retVal;
}

function getTicket($ticketID)
{
    sanitize_sql_inputs($ticketID);

    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.Points, ach.BadgeName,
                ach.Author AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
                tick.ReportedAt, tick.ReportType, tick.ReportState, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy
              FROM Ticket AS tick
              LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = tick.ReportedByUserID
              LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.ResolvedByUserID
              WHERE tick.ID = $ticketID
              ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    } else {
        // error_log(__FUNCTION__ . " failed?! $offset, $limit");
        return false;
    }
}

function updateTicket($user, $ticketID, $ticketVal, $reason = null)
{
    sanitize_sql_inputs($ticketI, $ticketVal);

    $userID = getUserIDFromUser($user);

    $resolvedFields = "";
    if ($ticketVal != 1) {
        $resolvedFields = ", ResolvedAt=NOW(), ResolvedByUserID=$userID ";
    }

    $query = "UPDATE Ticket
              SET ReportState=$ticketVal $resolvedFields
              WHERE ID=$ticketID";

    // log_sql($query);

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $ticketData = getTicket($ticketID);
        $userReporter = $ticketData['ReportedBy'];
        $achID = $ticketData['AchievementID'];
        $achTitle = $ticketData['AchievementTitle'];
        $gameTitle = $ticketData['GameTitle'];
        $consoleName = $ticketData['ConsoleName'];

        $status = null;
        $comment = null;

        switch ($ticketVal) {
            case 0:
                $status = "Closed";
                if ($reason == "Demoted") {
                    updateAchievementFlags($achID, 5);
                }
                $comment = "Ticket closed by $user. Reason: \"$reason\".";
                postActivity($user, ActivityType::ClosedTicket, $achID);
                break;

            case 1: // Open
                $status = "Open";
                $comment = "Ticket reopened by $user.";
                postActivity($user, ActivityType::OpenedTicket, $achID);
                break;

            case 2: // Resolved
                $status = "Resolved";
                $comment = "Ticket resolved as fixed by $user.";
                postActivity($user, ActivityType::ClosedTicket, $achID);
                break;
        }

        addArticleComment("Server", \RA\ArticleType::AchievementTicket, $ticketID, $comment, $user);

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

        if (isAtHome()) {
            // error_log(__FUNCTION__ . " dumping mail, not sending... no mailserver!");
            // error_log($email);
            // error_log($emailTitle);
            // error_log($msg);
            $retVal = true;
        } else {
            // error_log(__FUNCTION__ . " sending ticket resolution mail to $user at address $email");
            $retVal = mail_utf8($email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg);
            // error_log(__FUNCTION__ . " return val: $retVal");
        }

        return true;
    } else {
        // error_log(__FUNCTION__ . " failed?! $user, $ticketID, $ticketVal");
        log_sql_fail();
        return false;
    }
}

function countOpenTicketsByDev($dev)
{
    if ($dev == null) {
        return null;
    }

    sanitize_sql_inputs($dev);

    $query = "
        SELECT count(*) as count
        FROM Ticket AS tick
        LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN UserAccounts AS ua ON ua.User = ach.Author
        WHERE ach.Author = '$dev' AND ach.Flags IN (3, 5) AND tick.ReportState = 1";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult)['count'];
    } else {
        return false;
    }
}

function countOpenTicketsByAchievement($achievementID)
{
    sanitize_sql_inputs($achievementID);
    settype($achievementID, 'integer');
    if ($achievementID <= 0) {
        return false;
    }

    $query = "
        SELECT COUNT(*) as count
        FROM Ticket
        WHERE AchievementID = $achievementID AND ReportState = 1";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult)['count'];
    } else {
        return false;
    }
}

function countOpenTickets(
    $unofficialFlag = false,
    $ticketFilters = 2041, //2041 sets all filters active except for Closed and Resolved
    $assignedToUser = null,
    $gameID = null
) {
    sanitize_sql_inputs($assignedToUser, $gameID);

    //State condition
    $stateCond = getStateCondition($ticketFilters);
    if ($stateCond === null) {
        return 0;
    }

    //Report Type condition
    $reportTypeCond = getReportTypeCondition($ticketFilters);
    if ($reportTypeCond === null) {
        return 0;
    }

    //MD5 condition
    $md5Cond = getMD5Condition($ticketFilters);
    if ($md5Cond === null) {
        return 0;
    }

    //Emulator condition
    $emulatorCond = getEmulatorCondition($ticketFilters);
    if ($emulatorCond === null) {
        return 0;
    }

    //Author condition
    $authorCond = "";
    if ($assignedToUser != null) {
        $authorCond = " AND ach.Author LIKE '$assignedToUser'";
    }

    //Game condition
    $gameCond = "";
    if ($gameID != null) {
        $gameCond = " AND ach.GameID LIKE '$gameID'";
    }

    settype($unofficialFlag, 'boolean');
    $achFlagCond = $unofficialFlag ? "ach.Flags = '5'" : "ach.Flags = '3'";

    $query = "
        SELECT count(*) as count
        FROM Ticket AS tick
        LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        WHERE $achFlagCond $stateCond $gameCond $reportTypeCond $md5Cond $emulatorCond $authorCond";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult)['count'];
    } else {
        return false;
    }
}

function gamesSortedByOpenTickets($count)
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
            tick.ReportState = 1 AND ach.Flags = 3
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
 *
 * @param int $ticketFilters the current ticket filters in place
 * @return string|null
 */
function getStateCondition($ticketFilters)
{
    $openTickets = ($ticketFilters & (1 << 0));
    $closedTickets = ($ticketFilters & (1 << 1));
    $resolvedTickets = ($ticketFilters & (1 << 2));

    if ($openTickets && $closedTickets && $resolvedTickets) {
        return "";
    } elseif ($openTickets || $closedTickets || $resolvedTickets) {
        $stateCond = " AND tick.ReportState IN (";
        if ($openTickets) {
            $stateCond .= "1";
        }

        if ($closedTickets) {
            if ($openTickets) {
                $stateCond .= ",";
            }
            $stateCond .= "0";
        }

        if ($resolvedTickets) {
            if ($openTickets || $closedTickets) {
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
 * Gets the ticket report type condition to put into the main ticket query.
 *
 * @param int $ticketFilters the current ticket filters in place
 * @return string|null
 */
function getReportTypeCondition($ticketFilters)
{
    $triggeredTickets = ($ticketFilters & (1 << 3));
    $didNotTriggerTickets = ($ticketFilters & (1 << 4));

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
 * Gets the ticket MD5 condition to put into the main ticket query.
 *
 * @param int $ticketFilters the current ticket filters in place
 * @return string|null
 */
function getMD5Condition($ticketFilters)
{
    $md5KnownTickets = ($ticketFilters & (1 << 5));
    $md5UnknownTickets = ($ticketFilters & (1 << 6));

    if ($md5KnownTickets && $md5UnknownTickets) {
        return "";
    }
    if ($md5KnownTickets) {
        return " AND (tick.ReportNotes REGEXP 'MD5: [a-fA-F0-9]{32}')";
    }
    if ($md5UnknownTickets) {
        return " AND (tick.ReportNotes NOT REGEXP 'MD5: [a-fA-F0-9]{32}')";
    }
    return null;
}

/**
 * Gets the ticket emulator condition to put into the main ticket query.
 *
 * @param int $ticketFilters the current ticket filters in place
 * @return string|null
 */
function getEmulatorCondition($ticketFilters)
{
    $raEmulatorTickets = ($ticketFilters & (1 << 7));
    $rarchKnownTickets = ($ticketFilters & (1 << 8));
    $rarchUnknownTickets = ($ticketFilters & (1 << 9));
    $emulatorUnknownTickets = ($ticketFilters & (1 << 10));

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
 *
 * @param string $user to get ticket data for
 * @return array of user ticket data
 */
function getTicketsForUser($user)
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
 *
 * @param string $user to get ticket data for
 * @return array|null of user ticket data
 */
function getUserGameWithMostTickets($user)
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
 *
 * @param string $user to get ticket data for
 * @return array|null of user ticket data
 */
function getUserAchievementWithMostTickets($user)
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
 *
 * @param string $user to get ticket data for
 * @return array|null of user ticket data
 */
function getUserWhoCreatedMostTickets($user)
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
 *
 * @param string $user to get ticket data for
 * @return array of user ticket data
 */
function getNumberOfTicketsClosedForOthers($user)
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT a.Author, COUNT(a.Author) AS TicketCount,
              SUM(CASE WHEN t.ReportState LIKE '0' THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.ReportState LIKE '2' THEN 1 ELSE 0 END) AS ResolvedCount
              FROM Ticket AS t
              LEFT JOIN UserAccounts as ua ON ua.ID = t.ReportedByUserID
              LEFT JOIN UserAccounts as ua2 ON ua2.ID = t.resolvedByUserID
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              WHERE t.ReportState NOT LIKE '1'
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

function GetTicketModel($ticketId)
{
    $ticketDbResult = getTicket($ticketId);

    if ($ticketDbResult == null) {
        return null;
    }

    $ticketModel = new TicketModel($ticketDbResult);

    return $ticketModel;
}
