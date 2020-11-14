<?php

use RA\ActivityType;

function SubmitLeaderboardEntryJSON($user, $lbID, $newEntry, $validation)
{
    global $db;
    sanitize_sql_inputs($user, $lbID, $newEntry);

    $retVal = [];
    $retVal['Success'] = true;

    //    Fetch some always-needed data
    $query = "SELECT Format, ID AS LeaderboardID, GameID, Title, LowerIsBetter
              FROM LeaderboardDef AS ld
              WHERE ld.ID = $lbID";
    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);
    if ($dbResult !== false) {
        $lbData = mysqli_fetch_assoc($dbResult);

        $lbID = $lbData['LeaderboardID'];
        $gameID = $lbData['GameID'];
        $lbTitle = $lbData['Title'];
        $lowerIsBetter = $lbData['LowerIsBetter'];
        settype($lowerIsBetter, 'integer');

        $scoreFormatted = GetFormattedLeaderboardEntry($lbData['Format'], $newEntry);

        $retVal['LBData'] = $lbData;
        settype($retVal['LBData']['LeaderboardID'], 'integer');
        settype($retVal['LBData']['GameID'], 'integer');
        settype($retVal['LBData']['LowerIsBetter'], 'integer');

        $retVal['Score'] = $newEntry;
        settype($retVal['Score'], 'integer');
        $retVal['ScoreFormatted'] = $scoreFormatted;

        $scoreData = [];
        $scoreData['Score'] = $newEntry;
        settype($scoreData['Score'], 'integer');
        $scoreData['GameID'] = $gameID;
        settype($scoreData['GameID'], 'integer');
        $scoreData['ScoreFormatted'] = $scoreFormatted;
        $scoreData['LBTitle'] = $lbTitle;
        $scoreData['LBID'] = $lbID;
        settype($scoreData['LBID'], 'integer');

        $comparisonOp = ($lowerIsBetter == 1) ? '<' : '>';

        //    Read: IF the score VALUE provided $compares as "betterthan" the existing score, use the VALUE given, otherwise the existing Score.
        //    Also, if the score VALUE provided $compares as "betterthan" the existing score, use NOW(), otherwise the existing DateSubmitted.
        $query = "
        INSERT INTO LeaderboardEntry (LeaderboardID, UserID, Score, DateSubmitted)
                VALUES('$lbID', (SELECT ID FROM UserAccounts WHERE User='$user' ), '$newEntry', NOW())
        ON DUPLICATE KEY
            UPDATE
                LeaderboardID=LeaderboardID, UserID=UserID,
                DateSubmitted=IF(( VALUES(Score) $comparisonOp Score), VALUES(DateSubmitted), DateSubmitted),
                Score=IF((VALUES(Score) $comparisonOp Score), VALUES(Score), Score)";

        // log_sql($query);
        $dbResult = s_mysql_query($query);

        if ($dbResult !== false) {
            $numRowsAffected = mysqli_affected_rows($db);
            if ($numRowsAffected == 0) {
                //    No change made!
                //    Worst case: go fetch my existing score, it was better
                $query = "SELECT Score FROM LeaderboardEntry WHERE LeaderboardID=$lbID AND UserID=(SELECT ID FROM UserAccounts WHERE User='$user')";
                $dbResult = s_mysql_query($query);
                SQL_ASSERT($dbResult);
                $data = mysqli_fetch_assoc($dbResult);
                $retVal['BestScore'] = $data['Score'];
            } elseif ($numRowsAffected == 1) {
                //    (New) Entry added!
                $retVal['BestScore'] = $newEntry;
                postActivity($user, ActivityType::NewLeaderboardEntry, $scoreData);
            } else { //if( $numRowsAffected == 2 )
                //    Improved Entry added!
                $retVal['BestScore'] = $newEntry;
                postActivity($user, ActivityType::ImprovedLeaderboardEntry, $scoreData);
            }

            settype($retVal['BestScore'], 'integer');

            //    If you fall through to here, populate $dataOut with some juicy info :)
            $retVal['TopEntries'] = GetLeaderboardEntriesDataJSON($lbID, $user, 10, 0, false);
            $retVal['TopEntriesFriends'] = GetLeaderboardEntriesDataJSON($lbID, $user, 10, 0, true);
            $retVal['RankInfo'] = GetLeaderboardRankingJSON($user, $lbID);
        } else {
            // error_log(__FUNCTION__ . " broken: " . mysqli_error($db));
            $retVal['Success'] = false;
            $retVal['Error'] = "Cannot insert the value $newEntry into leaderboard with ID: $lbID (unknown issue)";
        }
    } else {
        // error_log(__FUNCTION__ . " broken2: " . mysqli_error($db));
        $retVal['Success'] = false;
        $retVal['Error'] = "Cannot find the leaderboard with ID: $lbID";
    }
    return $retVal;
}

function submitLeaderboardEntry($user, $lbID, $newEntry, $validation, &$dataOut)
{
    sanitize_sql_inputs($user, $lbID, $newEntry);

    //    Fetch some always-needed data
    $query = "SELECT Format, ID, GameID, Title
              FROM LeaderboardDef AS ld
              WHERE ld.ID = $lbID";
    $dbResult = s_mysql_query($query);
    $data = mysqli_fetch_assoc($dbResult);

    $gameID = $data['GameID'];
    $lbTitle = $data['Title'];
    $lbID = $data['ID'];
    $scoreFormatted = GetFormattedLeaderboardEntry($data['Format'], $newEntry);

    $scoreData = [];
    $scoreData['Score'] = $newEntry;
    $scoreData['GameID'] = $gameID;
    $scoreData['ScoreFormatted'] = $scoreFormatted;
    $scoreData['LBTitle'] = $lbTitle;
    $scoreData['LBID'] = $lbID;

    $query = "SELECT le.Score, ld.LowerIsBetter, ld.Format FROM LeaderboardEntry AS le
              LEFT JOIN LeaderboardDef AS ld ON ld.ID = le.LeaderboardID
              LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
              WHERE ua.User = '$user' AND ld.ID = $lbID ";

    $bestScore = $newEntry;

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_sql_fail();
        //log_email(__FUNCTION__ . " failed 1 for $user!");
        return false;
    } else {
        //error_log( $query );

        if (mysqli_num_rows($dbResult) == 0) {
            //    No data found; add new element!
            $query = "INSERT INTO LeaderboardEntry (LeaderboardID, UserID, Score, DateSubmitted) 
                VALUES ( $lbID, (SELECT ID FROM UserAccounts WHERE User='$user'), $newEntry, NOW() )";
            // log_sql($query);

            $dbResult = s_mysql_query($query);
            if ($dbResult == false) {
                log_sql_fail();
                //log_email(__FUNCTION__ . " failed 2 for $user!");
                return false;
            } else {
                postActivity($user, ActivityType::NewLeaderboardEntry, $scoreData);

                //error_log( $query );
                //log_email( __FUNCTION__ . " NEW entry added OK for $user, new record $newEntry for leaderboard $lbID!" );
            }
        } else {
            $dataFound = mysqli_fetch_assoc($dbResult);
            $oldEntry = $dataFound['Score'];
            $lowerIsBetter = $dataFound['LowerIsBetter'];
            $format = $dataFound['Format'];

            settype($lowerIsBetter, 'integer');
            settype($newEntry, 'integer');
            settype($oldEntry, 'integer');

            $newScoreOK = false;
            if ($lowerIsBetter) {
                $newScoreOK = ($newEntry < $oldEntry);
            } else {
                $newScoreOK = ($newEntry > $oldEntry);
            }

            if ($newScoreOK) {
                $scoreFormatted = GetFormattedLeaderboardEntry($format, $newEntry);

                //    worse record found: update with new data!
                $query = "UPDATE LeaderboardEntry AS le ";
                $query .= "LEFT JOIN UserAccounts AS ua ON ua.User = '$user' ";
                $query .= "SET le.Score=$newEntry, le.DateSubmitted = NOW() ";
                $query .= "WHERE le.LeaderboardID = $lbID AND le.UserID = ua.ID ";

                $dbResult = s_mysql_query($query);
                if ($dbResult == false) {
                    log_sql_fail();
                    //log_email(__FUNCTION__ . " failed 3 for $user!");
                    return false;
                } else {
                    postActivity($user, ActivityType::ImprovedLeaderboardEntry, $scoreData);

                    //error_log( $query );
                    //log_email( __FUNCTION__ . " entry added OK for $user, new record $newEntry (was $oldEntry) for leaderboard $lbID!" );
                }
            } else {
                $bestScore = $oldEntry;

                //error_log( $query );
                //error_log( __FUNCTION__ );
                //error_log( "old score for $user ($oldEntry) is already better than new value ($newEntry), keeping old value." );
            }
        }
    }

    //    If you fall through to here, populate $dataOut with some juicy info :)
    $dataOut = GetLeaderboardData($lbID, $user, 5, 0, 0);

    getLeaderboardRanking($user, $lbID, $ranking, $totalEntries);
    $dataOut['Rank'] = $ranking;
    $dataOut['NumEntries'] = $totalEntries;
    $dataOut['Score'] = $bestScore;

    return true;
}

function removeLeaderboardEntry($user, $lbID)
{
    sanitize_sql_inputs($user, $lbID);

    $userID = getUserIDFromUser($user);
    if ($userID > 0) {
        $query = "DELETE FROM LeaderboardEntry
                  WHERE ( LeaderboardID = $lbID AND UserID = $userID )";

        s_mysql_query($query);

        global $db;
        if (mysqli_affected_rows($db) > 0) {
            // error_log("Dropped $user 's LB entry from Leaderboard ID $lbID");
            return true;
        } else {
            return false;
        }
    } else {
        // error_log("Could not find user ID for $user");
        return false;
    }
}

function GetLeaderboardRankingJSON($user, $lbID)
{
    sanitize_sql_inputs($user, $lbID);

    $retVal = [];

    $query = "SELECT COUNT(*) AS UserRank,
                (SELECT ld.LowerIsBetter FROM LeaderboardDef AS ld WHERE ld.ID=$lbID) AS LowerIsBetter,
                (SELECT COUNT(*) AS NumEntries FROM LeaderboardEntry AS le WHERE le.LeaderboardID=$lbID) AS NumEntries
              FROM LeaderboardEntry AS lbe
              INNER JOIN LeaderboardEntry AS lbe2 ON lbe.LeaderboardID = lbe2.LeaderboardID AND lbe.Score < lbe2.Score
              LEFT JOIN UserAccounts AS ua ON ua.ID = lbe.UserID
              WHERE ua.User = '$user' AND lbe.LeaderboardID = $lbID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $retVal = mysqli_fetch_assoc($dbResult);

        //    Query actually gives 'how many players are below me in the list.'
        //    Top position yields '0', which we should change to '1' for '1st'
        //    Reversing the list means we wouldn't need to do this however: Rank 0 becomes 5-0: 5th of 5.
        if ($retVal['LowerIsBetter'] == 1) {
            $retVal['Rank'] = ($retVal['NumEntries'] - $retVal['UserRank']);
        } else {
            $retVal['Rank'] = $retVal['UserRank'] + 1;
        }      //    0=1st place.
    }

    return $retVal;
}

// TODO Deprecate: fold into above
function getLeaderboardRanking($user, $lbID, &$rankOut, &$totalEntries)
{
    sanitize_sql_inputs($user, $lbID);

    $query = "SELECT
              COUNT(*) AS UserRank,
              (SELECT ld.LowerIsBetter FROM LeaderboardDef AS ld WHERE ld.ID=$lbID) AS LowerIsBetter,
              (SELECT COUNT(*) AS NumEntries FROM LeaderboardEntry AS le WHERE le.LeaderboardID=$lbID) AS NumEntries
              FROM LeaderboardEntry AS lbe
              INNER JOIN LeaderboardEntry AS lbe2 ON lbe.LeaderboardID = lbe2.LeaderboardID AND lbe.Score < lbe2.Score
              LEFT JOIN UserAccounts AS ua ON ua.ID = lbe.UserID
              WHERE ua.User = '$user' AND lbe.LeaderboardID = $lbID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $db_entry = mysqli_fetch_assoc($dbResult);

        $rankOut = $db_entry['UserRank'];
        $totalEntries = $db_entry['NumEntries'];

        //    Query actually gives 'how many players are below me in the list.'
        //    Top position yields '0', which we should change to '1' for '1st'
        //    Reversing the list means we wouldn't need to do this however: Rank 0 becomes 5-0: 5th of 5.
        if ($db_entry['LowerIsBetter'] == 1) {
            $rankOut = ($totalEntries - $rankOut);
        } else {
            $rankOut += 1;
        }      //    0=1st place.

        return true;
    } else {
        // error_log(__FUNCTION__ . " error");
        log_sql_fail();
        return false;
    }
}

function getLeaderboardsForGame($gameID, &$dataOut, $localUser)
{
    sanitize_sql_inputs($gameID, $localUser);

    $query = "SELECT InnerTable.LeaderboardID, InnerTable.Title, InnerTable.Description, le.DateSubmitted, ua.User, le.Score, InnerTable.Format FROM (
                SELECT
                CASE
                    WHEN !lbd.LowerIsBetter THEN MAX(le2.Score)
                                            ELSE MIN(le2.Score)
                END
                AS BestScore, le2.UserID, le2.LeaderboardID, lbd.Title, lbd.Description, lbd.Format, lbd.DisplayOrder
                FROM LeaderboardEntry AS le2
                LEFT JOIN LeaderboardDef AS lbd ON lbd.ID = le2.LeaderboardID
                LEFT JOIN UserAccounts AS ua ON ua.ID = le2.UserID
                WHERE ( !ua.Untracked || ua.User = '$localUser' ) && lbd.GameID = $gameID
                GROUP BY lbd.ID
            ) InnerTable
            LEFT JOIN LeaderboardEntry AS le ON le.LeaderboardID = InnerTable.LeaderboardID AND le.Score = InnerTable.BestScore
            LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
            ORDER BY DisplayOrder ASC, LeaderboardID, DateSubmitted ASC ";

    $dataOut = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $lbID = $data['LeaderboardID'];
            if (!isset($dataOut[$lbID])) {
                $dataOut[$lbID] = $data; //    Potentially overwrites existing one
            }
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " error: " . mysqli_error($db));
        //error_log( $query );
    }

    return count($dataOut);
}

function GetLeaderboardEntriesDataJSON($lbID, $user, $numToFetch, $offset, $friendsOnly)
{
    sanitize_sql_inputs($lbID, $user, $numToFetch, $offset);

    $retVal = [];

    //    'Me or my friends'
    $friendQuery = $friendsOnly ? "( ( ua.User IN ( SELECT Friend FROM Friends WHERE User='$user' ) ) OR ua.User='$user' )" : "TRUE";

    //    Get entries:
    $query = "SELECT ua.User, le.Score, UNIX_TIMESTAMP( le.DateSubmitted ) AS DateSubmitted
              FROM LeaderboardEntry AS le
              LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
              LEFT JOIN LeaderboardDef AS lbd ON lbd.ID = le.LeaderboardID
              WHERE le.LeaderboardID = $lbID AND $friendQuery
              ORDER BY
              CASE WHEN !lbd.LowerIsBetter THEN Score END DESC,
              CASE WHEN lbd.LowerIsBetter THEN Score END ASC, DateSubmitted ASC
              LIMIT $offset, $numToFetch ";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);
    $numFound = 0;
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $nextData['Rank'] = $numFound + $offset + 1;
        settype($nextData['Score'], 'integer');
        settype($nextData['DateSubmitted'], 'integer');
        $retVal[] = $nextData;
        $numFound++;
    }
    return $retVal;
}

function GetLeaderboardData($lbID, $user, $numToFetch, $offset, $friendsOnly)
{
    sanitize_sql_inputs($lbID, $user, $numToFetch, $offset);

    $retVal = [];

    //    Get raw LB data
    $query = "SELECT ld.ID AS LBID, gd.ID AS GameID, gd.Title AS GameTitle, ld.LowerIsBetter, ld.Title AS LBTitle, ld.Description AS LBDesc, ld.Format AS LBFormat, ld.Mem AS LBMem, gd.ConsoleID, c.Name AS ConsoleName, gd.ForumTopicID, gd.ImageIcon AS GameIcon
              FROM LeaderboardDef AS ld
              LEFT JOIN GameData AS gd ON gd.ID = ld.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ld.ID = $lbID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $retVal = mysqli_fetch_assoc($dbResult);
        settype($retVal['LBID'], 'integer');
        settype($retVal['GameID'], 'integer');
        settype($retVal['LowerIsBetter'], 'integer');
        settype($retVal['ConsoleID'], 'integer');
        settype($retVal['ForumTopicID'], 'integer');

        $retVal['Entries'] = [];

        //    Now get entries:
        $query = "SELECT ua.User, le.Score, le.DateSubmitted
                  FROM LeaderboardEntry AS le
                  LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
                  LEFT JOIN LeaderboardDef AS lbd ON lbd.ID = le.LeaderboardID
                  WHERE (!ua.Untracked || ua.User = '$user' ) AND le.LeaderboardID = $lbID
                  ORDER BY
                  CASE WHEN !lbd.LowerIsBetter THEN Score END DESC,
                  CASE WHEN lbd.LowerIsBetter THEN Score END ASC, DateSubmitted ASC
                  LIMIT $offset, $numToFetch ";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            $numResultsFound = 0;
            $userFound = false;

            $entries = [];

            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $db_entry['Rank'] = $numResultsFound + $offset + 1;
                $db_entry['DateSubmitted'] = strtotime($db_entry['DateSubmitted']);
                settype($db_entry['Score'], 'integer');

                if (strcmp($db_entry['User'], $user) == 0) {
                    $userFound = true;
                }

                $entries[] = $db_entry;

                //$retVal['Entries'][ $db_entry['Rank'] ] = $db_entry;
                //$retVal[] = $db_entry;

                $numResultsFound++;
            }

            if ($userFound == false) {
                //    Go find user's score in this table, if it exists!
                $query = "SELECT ua.User, le.Score, le.DateSubmitted
                          FROM LeaderboardEntry AS le
                          LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
                          WHERE le.LeaderboardID = $lbID AND ua.User='$user' ";
                $dbResult = s_mysql_query($query);
                if ($dbResult !== false) {
                    if (mysqli_num_rows($dbResult) > 0) {
                        //    should be 1 or 0?.. I hope...
                        $db_entry = mysqli_fetch_assoc($dbResult);
                        $db_entry['DateSubmitted'] = strtotime($db_entry['DateSubmitted']);
                        settype($db_entry['Score'], 'integer');

                        //    Also fetch our user rank!
                        getLeaderboardRanking($user, $lbID, $userRank, $totalEntries);
                        $db_entry['Rank'] = $userRank;

                        //$retVal['Entries'][ $db_entry['Rank'] ] = $db_entry;
                        $entries[] = $db_entry;
                    }
                } else {
                    // error_log(__FUNCTION__ . " error: user doesn't have an entry for this leaderboard table?");
                    log_sql_fail();
                }
            }

            $retVal['Entries'] = $entries;
        } else {
            // error_log(__FUNCTION__ . " error");
            log_sql_fail();
        }
    }

    return $retVal;
}

function GetFormattedLeaderboardEntry($formatType, $scoreIn)
{
    settype($scoreIn, 'integer');

    if ($formatType == 'TIME') { // Number of frames
        $hours = $scoreIn / 216000;
        settype($hours, 'integer');
        $mins = ($scoreIn / 3600) - ($hours * 60);
        $secs = ($scoreIn % 3600) / 60;
        $milli = (($scoreIn % 3600) % 60) * (100.0 / 60.0);
        settype($mins, 'integer');
        settype($secs, 'integer');
        settype($milli, 'integer');
        return sprintf("%01d:%02d:%02d.%02d", $hours, $mins, $secs, $milli);
    } elseif ($formatType == 'TIMESECS') { // Number of seconds
        $hours = $scoreIn / 360;
        settype($hours, 'integer');
        $mins = ($scoreIn / 60) - ($hours * 60);
        $secs = $scoreIn % 60;
        return sprintf("%01d:%02d:%02d", $hours, $mins, $secs);
    } elseif ($formatType == 'MILLISECS') { // Number of milliseconds
        $hours = $scoreIn / 360000;
        settype($hours, 'integer');
        $mins = ($scoreIn / 6000) - ($hours * 60);
        $secs = ($scoreIn % 6000) / 100;
        $milli = ($scoreIn % 100);
        settype($mins, 'integer');
        settype($secs, 'integer');
        settype($milli, 'integer');
        return sprintf("%01d:%02d:%02d.%02d", $hours, $mins, $secs, $milli);
    } else {
        return "$scoreIn";
    }
}

function getLeaderboardDataSmall(
    $lbID,
    &$lbTitleOut,
    &$lbDescOut,
    &$lbFormatOut,
    &$lbLowerIsBetterOut,
    &$gameIDOut,
    &$gameTitleOut,
    &$forumTopicIDOut,
    &$consoleIDOut,
    &$consoleTitleOut
) {
    sanitize_sql_inputs($lbID);

    $query = "SELECT ld.Title, Description, GameID, Format, LowerIsBetter, gd.Title AS GameTitle, gd.ConsoleID, gd.ForumTopicID, c.Name AS ConsoleTitle ";
    $query .= "FROM LeaderboardDef AS ld ";
    $query .= "LEFT JOIN GameData AS gd ON gd.ID = ld.GameID ";
    $query .= "LEFT JOIN Console AS c ON c.ID = gd.ConsoleID ";
    $query .= "WHERE ld.ID = $lbID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $db_entry = mysqli_fetch_assoc($dbResult);

        $lbTitleOut = $db_entry['Title'];
        $lbDescOut = $db_entry['Description'];
        $lbFormatOut = $db_entry['Format'];
        $lbLowerIsBetterOut = $db_entry['LowerIsBetter'];
        $gameIDOut = $db_entry['GameID'];
        $gameTitleOut = $db_entry['GameTitle'];
        $forumTopicIDOut = $db_entry['ForumTopicID'];
        $consoleIDOut = $db_entry['ConsoleID'];
        $consoleTitleOut = $db_entry['ConsoleTitle'];

        return true;
    } else {
        // error_log(__FUNCTION__ . " error");
        log_sql_fail();
        return false;
    }
}

function getLeaderboardsList($consoleIDInput, $gameID, $sortBy, $count, $offset, &$lbDataOut)
{
    sanitize_sql_inputs($consoleIDInput, $gameID, $count, $offset);
    settype($sortBy, 'integer');
    settype($consoleIDInput, 'integer');
    settype($gameID, 'integer');

    $lbCount = 0;

    $whereClause = "";

    if ($gameID != 0) {
        $whereClause = "WHERE gd.ID = $gameID";
    } elseif ($consoleIDInput != 0) {
        $whereClause = "WHERE gd.ConsoleID = $consoleIDInput";
    }

    $ifDesc = "";
    if ($sortBy >= 10) {
        $ifDesc = " DESC";
    }

    $orderClause = "";
    switch ($sortBy % 10) {
        case 0:
            $orderClause = "ORDER BY ld.DisplayOrder $ifDesc, c.ID, GameTitle";
            break;
        case 1:
        default:
            $orderClause = "ORDER BY ld.ID $ifDesc";
            break;
        case 2:
            $orderClause = "ORDER BY GameTitle $ifDesc";
            break;
        case 3:
            $orderClause = "ORDER BY ConsoleName $ifDesc, c.ID, GameTitle";
            break;
        case 4:
            $orderClause = "ORDER BY ld.Title $ifDesc";
            break;
        case 5:
            $orderClause = "ORDER BY ld.Description $ifDesc";
            break;
        case 6:
            $orderClause = "ORDER BY ld.LowerIsBetter $ifDesc, ld.Format $ifDesc";
            break;
        case 7:
            if ($sortBy == 17) {
                $ifDesc = "ASC";
            } else {
                $ifDesc = "DESC";
            }

            $orderClause = "ORDER BY NumResults $ifDesc";
            break;
    }

    $query = "SELECT ld.ID, 
                     ld.Title, 
                     ld.Description, 
                     ld.Format, 
                     ld.Mem, 
                     ld.DisplayOrder, 
                     leInner.NumResults, 
                     ld.LowerIsBetter, 
                     gd.ID AS GameID, 
                     gd.ImageIcon AS GameIcon, 
                     gd.Title AS GameTitle, 
                     c.Name AS ConsoleName,
                     c.ID AS ConsoleID
                FROM LeaderboardDef AS ld
                LEFT JOIN GameData AS gd ON gd.ID = ld.GameID
                LEFT JOIN
                (
                    SELECT le.LeaderboardID, COUNT(*) AS NumResults FROM LeaderboardEntry AS le
                    GROUP BY le.LeaderboardID
                    ) AS leInner ON leInner.LeaderboardID = ld.ID
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID

                $whereClause

                GROUP BY ld.GameID, ld.ID
                $orderClause
                ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $lbDataOut[$lbCount] = $db_entry;
            $lbCount++;
        }
    } else {
        // error_log(__FUNCTION__);
        log_sql_fail();
    }

    return $lbCount;
}

function submitLBData($user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder)
{
    sanitize_sql_inputs($user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder);
    settype($lbDisplayOrder, 'integer');

    $query = "UPDATE LeaderboardDef AS ld SET
              ld.Mem = '$lbMem',
              ld.Format = '$lbFormat',
              ld.Title = '$lbTitle',
              ld.Description = '$lbDescription',
              ld.Format = '$lbFormat',
              ld.LowerIsBetter = '$lbLowerIsBetter',
              ld.DisplayOrder = '$lbDisplayOrder'
              WHERE ld.ID = $lbID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        // error_log(__FILE__);
        // error_log("$user changed Leaderboard $lbID: $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder");
        return true;
    } else {
        //log_email(__FUNCTION__ . " LB catastrophic: $user _ $lbID: $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder");
        // error_log(__FILE__);
        // error_log("$user broke Leaderboard $lbID: $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder");
        return false;
    }
}

function SubmitNewLeaderboard($gameID, &$lbIDOut)
{
    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');
    if ($gameID == 0) {
        return false;
    }

    $defaultMem = "STA:0x0000=h0010_0xhf601=h0c::CAN:0xhfe13<d0xhfe13::SUB:0xf7cc!=0_d0xf7cc=0::VAL:0xhfe24*1_0xhfe25*60_0xhfe22*3600";
    $query = "INSERT INTO LeaderboardDef (GameID, Mem, Format, Title, Description, LowerIsBetter, DisplayOrder) 
                                VALUES ($gameID, '$defaultMem', 'SCORE', 'My Leaderboard', 'My Leaderboard Description', 0,
                                (SELECT * FROM (SELECT COALESCE(Max(DisplayOrder) + 1, 0) FROM LeaderboardDef WHERE  GameID = $gameID) AS temp))";
    // log_sql($query);
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        global $db;
        $lbIDOut = mysqli_insert_id($db);
        return true;
    } else {
        return false;
    }
}

/**
 * Duplicates a leaderboard a specified number of times.
 *
 * @param int $gameID the game to add new leaderboards to
 * @param int $leaderboardID the leaderboard to duplicate
 * @param int $duplicateNumber the number of times to duplicate the leaderboard
 * @return bool
 */
function duplicateLeaderboard($gameID, $leaderboardID, $duplicateNumber)
{
    sanitize_sql_inputs($gameID, $leaderboardID);
    settype($gameID, 'integer');
    settype($leaderboardID, 'integer');
    settype($duplicateNumber, 'integer');

    if ($gameID == 0) {
        return false;
    }

    $lbMem = null;
    $lbFormat = null;
    $lbTitle = null;
    $lbDescription = null;
    $lbScoreType = null;
    $lbDisplayOrder = null;

    //Get the leaderboard info to duplicate
    $getQuery = "
            SELECT Mem, 
                   Format, 
                   Title, 
                   Description, 
                   LowerIsBetter, 
                   (SELECT Max(DisplayOrder) FROM LeaderboardDef WHERE GameID = $gameID) AS DisplayOrder 
            FROM   LeaderboardDef 
            WHERE  ID = $leaderboardID";

    $dbResult = s_mysql_query($getQuery);
    if ($dbResult !== false) {
        $db_entry = mysqli_fetch_assoc($dbResult);

        $lbMem = $db_entry['Mem'];
        $lbFormat = $db_entry['Format'];
        $lbTitle = $db_entry['Title'];
        $lbDescription = $db_entry['Description'];
        $lbScoreType = $db_entry['LowerIsBetter'];
        $lbDisplayOrder = $db_entry['DisplayOrder'];
    } else {
        return false;
    }

    //Create the duplicate entries
    for ($i = 1; $i <= $duplicateNumber; $i++) {
        $query = "INSERT INTO LeaderboardDef (GameID, Mem, Format, Title, Description, LowerIsBetter, DisplayOrder) 
                                    VALUES ($gameID, '$lbMem', '$lbFormat', '$lbTitle', '$lbDescription', $lbScoreType, ($lbDisplayOrder + $i))";
        // log_sql($query);
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            global $db;
            mysqli_insert_id($db);
        } else {
            return false;
        }
    }
    return true;
}

function requestResetLB($lbID)
{
    sanitize_sql_inputs($lbID);
    settype($lbID, 'integer');
    if ($lbID == 0) {
        return false;
    }

    // error_log(__FUNCTION__ . " resetting LB $lbID");
    $query = "DELETE FROM LeaderboardEntry
              WHERE LeaderboardID = $lbID";
    $dbResult = s_mysql_query($query);

    return $dbResult !== false;
}

function requestDeleteLB($lbID)
{
    sanitize_sql_inputs($lbID);
    settype($lbID, 'integer');
    //log_email(__FUNCTION__ . " LB $lbID being deleted!");

    $query = "DELETE FROM LeaderboardDef WHERE ID = $lbID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        s_mysql_query("INSERT INTO DeletedModels SET ModelType='LeaderboardDef', ModelID=$lbID");
    }
    return $dbResult !== false;
}

function GetLBPatch($gameID)
{
    sanitize_sql_inputs($gameID);
    $lbData = [];

    //    Always append LBs?
    $query = "SELECT ld.ID, ld.Mem, ld.Format, ld.Title, ld.Description
              FROM LeaderboardDef AS ld
              WHERE ld.GameID = $gameID
              ORDER BY ld.DisplayOrder, ld.ID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            settype($db_entry['ID'], 'integer');
            $lbData[] = $db_entry;
        }
    } else {
        //    No leaderboards found: this is probably normal.
    }

    return $lbData;
}

function deleteOrphanedLeaderboardEntries()
{
    s_mysql_query("DELETE le FROM LeaderboardEntry le LEFT JOIN UserAccounts ua ON le.UserID = ua.ID WHERE ua.User IS NULL");
}
