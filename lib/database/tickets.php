<?php

use RA\ActivityType;

require_once(__DIR__ . '/../bootstrap.php');
function SubmitNewTicketsJSON($userSubmitter, $idsCSV, $reportType, $noteIn, $ROMMD5)
{
    global $db;

    $note = mysqli_real_escape_string($db, $noteIn);
    $note .= "<br/>MD5: $ROMMD5";
    //error_log( "mysqli_real_escape_string turned #$noteIn# into #$note#" );

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

        $query = "INSERT INTO Ticket( AchievementID, ReportedByUserID, ReportType, ReportNotes, ReportedAt, ResolvedAt, ResolvedByUserID ) VALUES( $achID, $submitterUserID, $reportType, \"$note\", NOW(), NULL, NULL )";
        log_sql($query);

        $dbResult = mysqli_query($db, $query); //    Unescaped?
        $ticketID = mysqli_insert_id($db);
        error_log(__FUNCTION__ . " produced insert id of $ticketID ");

        if ($dbResult == false) {
            $errorsEncountered = true;
            error_log($query);
            error_log(__FUNCTION__ . " failed?! $userSubmitter, $achID, $reportType, $note");
        } else {
            //    Success
            if (GetAchievementMetadata($achID, $achData)) {
                $achAuthor = $achData['Author'];
                $achTitle = $achData['AchievementTitle'];
                $gameID = $achData['GameID'];
                $gameTitle = $achData['GameTitle'];

                $problemTypeStr = ($reportType == 1) ? "Triggers at wrong time" : "Doesn't trigger";

                $bugReportMessage = "Hi, $achAuthor!
[user=$userSubmitter] would like to report a bug with an achievement you've created:
Achievement: [ach=$achID] ($achTitle)
Game: [game=$gameID] ($gameTitle)
Problem: $problemTypeStr
Comment: $note

This ticket will be raised and will be available for all developers to inspect and manage at the following URL:
" . getenv('APP_URL') . "/ticketmanager.php?i=$ticketID

Thanks!";
                CreateNewMessage($userSubmitter, $achData['Author'], "Bug Report ($gameTitle)", $bugReportMessage);
                postActivity($userSubmitter, ActivityType::OpenedTicket, $achID);
            }

            $idsAdded++;
        }
    }

    $returnMsg = array();
    $returnMsg['Detected'] = $idsFound;
    $returnMsg['Added'] = $idsAdded;
    $returnMsg['Success'] = ($errorsEncountered == false);

    return $returnMsg;
}

function SubmitNewTickets($userSubmitter, $idsCSV, $reportType, $noteIn, &$summaryMsgOut)
{
    global $db;
    $note = mysqli_real_escape_string($db, $noteIn);

    error_log("mysqli_real_escape_string turned #$noteIn# into #$note#");

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

        $query = "INSERT INTO Ticket( AchievementID, ReportedByUserID, ReportType, ReportNotes, ReportedAt, ResolvedAt, ResolvedByUserID ) VALUES( $achID, $submitterUserID, $reportType, \"$note\", NOW(), NULL, NULL )";
        log_sql($query);

        $dbResult = mysqli_query($db, $query); //    Unescaped?
        $ticketID = mysqli_insert_id($db);
        error_log(__FUNCTION__ . " produced insert id of $ticketID ");

        if ($dbResult == false) {
            $errorsEncountered = true;
            error_log($query);
            error_log(__FUNCTION__ . " failed?! $userSubmitter, $achID, $reportType, $note");
        } else {
            //    Success
            if (GetAchievementMetadata($achID, $achData)) {
                $achAuthor = $achData['Author'];
                $gameID = $achData['GameID'];
                $gameTitle = $achData['GameTitle'];

                $problemTypeStr = ($reportType == 1) ? "Triggers at wrong time" : "Doesn't trigger";

                $bugReportMessage = "Hi, $achAuthor!\r\n
[user=$userSubmitter] would like to report a bug with an achievement you've created:
Achievement: [ach=$achID]
Game: [game=$gameID]
Problem: $problemTypeStr
Comment: $note

This ticket will be raised and will be available for all developers to inspect and manage at the following URL:
" . getenv('APP_URL') . "/ticketmanager.php?i=$ticketID

Thanks!";
                CreateNewMessage($userSubmitter, $achData['Author'], "Bug Report ($gameTitle)", $bugReportMessage);
                postActivity($userSubmitter, ActivityType::OpenedTicket, $achID);
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

    return ($errorsEncountered == false);
}

function getAllTickets(
    $offset = 0,
    $limit = 50,
    $assignedToUser = null,
    $givenGameID = null,
    $givenAchievementID = null,
    $ticketState = 1,
    $getUnofficial = false
) {
    global $db;

    $retVal = array();
    settype($givenGameID, 'integer');
    settype($ticketState, 'integer');
    settype($givenAchievementID, 'integer');

    $innerCond = "TRUE";
    if (!empty($assignedToUser) && IsValidUsername($assignedToUser)) {
        $assignedToUser = mysqli_real_escape_string($db, $assignedToUser);
        $innerCond .= " AND ach.Author = '$assignedToUser'";
    }
    if ($givenGameID != 0) {
        $innerCond .= " AND gd.ID = $givenGameID";
    }
    if ($ticketState != 0) {
        $innerCond .= " AND tick.ReportState = $ticketState";
    }
    if ($givenAchievementID != 0) {
        $innerCond .= " AND tick.AchievementID = $givenAchievementID";
    }

    settype($getUnofficial, 'boolean');
    $unofficialCond = $getUnofficial ? " AND ach.Flags <> 3" : "";

    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.Points, ach.BadgeName,
                ach.Author AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
                tick.ReportedAt, tick.ReportType, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy, tick.ReportState
              FROM Ticket AS tick
              LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = tick.ReportedByUserID
              LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.ResolvedByUserID
              WHERE $innerCond $unofficialCond
              ORDER BY tick.ID DESC
              LIMIT $offset, $limit";

    //echo $query;

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    } else {
        error_log(__FUNCTION__ . " failed?! $offset, $limit");
    }

    return $retVal;
}

function getTicket($ticketID)
{
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
        error_log(__FUNCTION__ . " failed?! $offset, $limit");
        return false;
    }
}

function updateTicket($user, $ticketID, $ticketVal, $reason = null)
{
    $userID = getUserIDFromUser($user);

    $resolvedFields = "";
    if ($ticketVal != 1) {
        $resolvedFields = ", ResolvedAt=NOW(), ResolvedByUserID=$userID ";
    }

    $query = "UPDATE Ticket
              SET ReportState=$ticketVal $resolvedFields
              WHERE ID=$ticketID";

    log_sql($query);

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $ticketData = getTicket($ticketID);
        $userReporter = $ticketData['ReportedBy'];
        $achID = $ticketData['AchievementID'];
        $achTitle = $ticketData['AchievementTitle'];
        $gameTitle = $ticketData['GameTitle'];
        $consoleName = $ticketData['ConsoleName'];

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

        addArticleComment("Server", 7, $ticketID, $comment);

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


        if (IsAtHome()) {
            error_log(__FUNCTION__ . " dumping mail, not sending... no mailserver!");
            error_log($email);
            error_log($emailTitle);
            error_log($msg);
            $retVal = true;
        } else {
            error_log(__FUNCTION__ . " sending ticket resolution mail to $user at address $email");
            $retVal = mail_utf8($email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg);
            error_log(__FUNCTION__ . " return val: $retVal");
        }


        return true;
    } else {
        error_log(__FUNCTION__ . " failed?! $user, $ticketID, $ticketVal");
        return false;
    }
}

function countOpenTicketsByDev($dev)
{
    if ($dev == null) {
        return null;
    }

    global $db;

    $dev = mysqli_real_escape_string($db, $dev);

    $query = "
        SELECT count(*) as count
        FROM Ticket AS tick
        LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN UserAccounts AS ua ON ua.User = ach.Author
        WHERE ach.Author = '$dev' AND tick.ReportState = 1";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult)['count'];
    } else {
        return false;
    }
}

function countOpenTicketsByAchievement($achievementID)
{
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

function countOpenTickets($unofficialFlag = false)
{
    if ($unofficialFlag === true) {
        $query = "
            SELECT count(*) as count
            FROM Ticket AS tick
            LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
            WHERE tick.ReportState = 1 AND ach.Flags <> 3";
    } else {
        $query = "
            SELECT COUNT(*) as count
            FROM Ticket
            WHERE ReportState = 1";
    }

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult)['count'];
    } else {
        return false;
    }
}

function gamesSortedByOpenTickets($count)
{
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
            tick.ReportState = 1
        GROUP BY
            gd.ID
        ORDER BY
            OpenTickets DESC
        LIMIT 0, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    } else {
        error_log(__FUNCTION__ . " failed?!");
    }

    return $retVal;

}
