<?php

use App\Legacy\Models\DeletedModels;
use App\Legacy\Models\User;
use RA\ActivityType;
use RA\ArticleType;
use RA\Permissions;

function SubmitLeaderboardEntryJSON($user, $lbID, $newEntry, $validation): array
{
    $db = getMysqliConnection();
    sanitize_sql_inputs($user, $lbID, $newEntry);

    $retVal = [];
    $retVal['Success'] = true;

    // Fetch some always-needed data
    $query = "SELECT Format, ID AS LeaderboardID, GameID, Title, LowerIsBetter
              FROM LeaderboardDef AS ld
              WHERE ld.ID = $lbID";
    $dbResult = s_mysql_query($query);
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

        // Read: IF the score VALUE provided $compares as "betterthan" the existing score, use the VALUE given, otherwise the existing Score.
        // Also, if the score VALUE provided $compares as "betterthan" the existing score, use NOW(), otherwise the existing DateSubmitted.
        $query = "
        INSERT INTO LeaderboardEntry (LeaderboardID, UserID, Score, DateSubmitted)
                VALUES('$lbID', (SELECT ID FROM UserAccounts WHERE User='$user' ), '$newEntry', NOW())
        ON DUPLICATE KEY
            UPDATE
                LeaderboardID=LeaderboardID, UserID=UserID,
                DateSubmitted=IF(( VALUES(Score) $comparisonOp Score), VALUES(DateSubmitted), DateSubmitted),
                Score=IF((VALUES(Score) $comparisonOp Score), VALUES(Score), Score)";

        $dbResult = s_mysql_query($query);

        if ($dbResult !== false) {
            $numRowsAffected = mysqli_affected_rows($db);
            if ($numRowsAffected == 0) {
                // No change made!
                // Worst case: go fetch my existing score, it was better
                $query = "SELECT Score FROM LeaderboardEntry WHERE LeaderboardID=$lbID AND UserID=(SELECT ID FROM UserAccounts WHERE User='$user')";
                $dbResult = s_mysql_query($query);
                $data = mysqli_fetch_assoc($dbResult);
                $retVal['BestScore'] = $data['Score'];
            } elseif ($numRowsAffected == 1) {
                // (New) Entry added!
                $retVal['BestScore'] = $newEntry;
                postActivity($user, ActivityType::NewLeaderboardEntry, $scoreData);
            } else { // if ( $numRowsAffected == 2 )
                // Improved Entry added!
                $retVal['BestScore'] = $newEntry;
                postActivity($user, ActivityType::ImprovedLeaderboardEntry, $scoreData);
            }

            settype($retVal['BestScore'], 'integer');

            // If you fall through to here, populate $dataOut with some juicy info :)
            $retVal['TopEntries'] = GetLeaderboardEntriesDataJSON($lbID, $user, 10, 0, false);
            $retVal['TopEntriesFriends'] = GetLeaderboardEntriesDataJSON($lbID, $user, 10, 0, true);
            $retVal['RankInfo'] = GetLeaderboardRankingJSON($user, $lbID, $lowerIsBetter);
        } else {
            $retVal['Success'] = false;
            $retVal['Error'] = "Cannot insert the value $newEntry into leaderboard with ID: $lbID (unknown issue)";
        }
    } else {
        $retVal['Success'] = false;
        $retVal['Error'] = "Cannot find the leaderboard with ID: $lbID";
    }

    return $retVal;
}

function submitLeaderboardEntry($user, $lbID, $newEntry, $validation, &$dataOut): bool
{
    sanitize_sql_inputs($user, $lbID, $newEntry);

    // Fetch some always-needed data
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
    if (!$dbResult) {
        log_sql_fail();

        return false;
    } else {
        if (mysqli_num_rows($dbResult) == 0) {
            // No data found; add new element!
            $query = "INSERT INTO LeaderboardEntry (LeaderboardID, UserID, Score, DateSubmitted)
                VALUES ( $lbID, (SELECT ID FROM UserAccounts WHERE User='$user'), $newEntry, NOW() )";

            $dbResult = s_mysql_query($query);
            if (!$dbResult) {
                log_sql_fail();

                return false;
            } else {
                postActivity($user, ActivityType::NewLeaderboardEntry, $scoreData);
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

                // worse record found: update with new data!
                $query = "UPDATE LeaderboardEntry AS le ";
                $query .= "LEFT JOIN UserAccounts AS ua ON ua.User = '$user' ";
                $query .= "SET le.Score=$newEntry, le.DateSubmitted = NOW() ";
                $query .= "WHERE le.LeaderboardID = $lbID AND le.UserID = ua.ID ";

                $dbResult = s_mysql_query($query);
                if (!$dbResult) {
                    log_sql_fail();

                    return false;
                } else {
                    postActivity($user, ActivityType::ImprovedLeaderboardEntry, $scoreData);
                }
            } else {
                $bestScore = $oldEntry;
                // old score for $user ($oldEntry) is already better than new value ($newEntry), keeping old value.
            }
        }
    }

    // If you fall through to here, populate $dataOut with some juicy info :)
    $dataOut = GetLeaderboardData($lbID, $user, 5, 0, 0);

    getLeaderboardRanking($user, $lbID, $ranking, $totalEntries);
    $dataOut['Rank'] = $ranking;
    $dataOut['NumEntries'] = $totalEntries;
    $dataOut['Score'] = $bestScore;

    return true;
}

function removeLeaderboardEntry($user, $lbID, &$score): bool
{
    sanitize_sql_inputs($user, $lbID);

    $query = "SELECT le.Score, ld.Format FROM LeaderboardEntry AS le
              LEFT JOIN LeaderboardDef AS ld ON ld.ID = le.LeaderboardID
              LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
              WHERE ua.User = '$user' AND ld.ID = $lbID ";

    $dbResult = s_mysql_query($query);
    $data = mysqli_fetch_assoc($dbResult);

    if (!$data) {
        return false;
    }

    $score = GetFormattedLeaderboardEntry($data['Format'], $data['Score']);

    $userID = getUserIDFromUser($user);
    if (!$userID) {
        return false;
    }

    $query = "DELETE FROM LeaderboardEntry
              WHERE ( LeaderboardID = $lbID AND UserID = $userID )";

    s_mysql_query($query);

    $db = getMysqliConnection();
    if (mysqli_affected_rows($db) == 0) {
        return false;
    }

    return true;
}

function GetLeaderboardRankingJSON($user, $lbID, $lowerIsBetter): array
{
    sanitize_sql_inputs($user, $lbID);

    $retVal = [];

    $query = "SELECT COUNT(*) AS UserRank,
                (SELECT COUNT(*) AS NumEntries FROM LeaderboardEntry AS le
                 LEFT JOIN UserAccounts AS ua ON ua.ID=le.UserID
                 WHERE le.LeaderboardID=$lbID AND NOT ua.Untracked) AS NumEntries
              FROM LeaderboardEntry AS lbe
              INNER JOIN LeaderboardEntry AS lbe2 ON lbe.LeaderboardID = lbe2.LeaderboardID AND lbe.Score " . ($lowerIsBetter ? '<=' : '<') . " lbe2.Score
              LEFT JOIN UserAccounts AS ua ON ua.ID = lbe.UserID
              LEFT JOIN UserAccounts AS ua2 ON ua2.ID = lbe2.UserID
              WHERE ua.User = '$user' AND lbe.LeaderboardID = $lbID
              AND NOT ua.Untracked AND NOT ua2.Untracked";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $retVal = mysqli_fetch_assoc($dbResult);

        if ($lowerIsBetter) {
            // Query fetches number of users with scores higher or equal to the player
            // Subtract that number from the total number of players to get the actual rank
            // Top position yields '0', which we should change to '1' for '1st'
            // NOTE: have to use <= for reverse sort so number of users being subtracted
            //       includes all users with the same score (see issue #1201)
            $retVal['Rank'] = (int) $retVal['NumEntries'] - (int) $retVal['UserRank'] + 1;
        } else {
            // Query fetches number of users with scores higher than the player
            // Top position yields '0', which we need to add one.
            $retVal['Rank'] = (int) $retVal['UserRank'] + 1;
        }
    }

    return $retVal;
}

// TODO Deprecate: fold into above
function getLeaderboardRanking($user, $lbID, &$rankOut, &$totalEntries): bool
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

        $rankOut = (int) $db_entry['UserRank'];
        $totalEntries = (int) $db_entry['NumEntries'];

        // Query actually gives 'how many players are below me in the list.'
        // Top position yields '0', which we should change to '1' for '1st'
        // Reversing the list means we wouldn't need to do this however: Rank 0 becomes 5-0: 5th of 5.
        // 0=1st place.
        if ($db_entry['LowerIsBetter'] == 1) {
            $rankOut = $totalEntries - $rankOut;
        } else {
            $rankOut++;
        }

        return true;
    } else {
        log_sql_fail();

        return false;
    }
}

function getLeaderboardsForGame($gameID, &$dataOut, $localUser): int
{
    sanitize_sql_inputs($gameID, $localUser);

    $query = "SELECT InnerTable.LeaderboardID, InnerTable.Title, InnerTable.Description, le.DateSubmitted, ua.User, le.Score, InnerTable.Format, InnerTable.DisplayOrder FROM (
                SELECT
                CASE
                    WHEN !lbd.LowerIsBetter THEN MAX(le2.Score)
                                            ELSE MIN(le2.Score)
                END
                AS BestScore, le2.UserID, lbd.ID as LeaderboardID, lbd.Title, lbd.Description, lbd.Format, lbd.DisplayOrder
                FROM LeaderboardDef AS lbd
                LEFT JOIN LeaderboardEntry AS le2 ON lbd.ID = le2.LeaderboardID
                LEFT JOIN UserAccounts AS ua ON ua.ID = le2.UserID
                WHERE ( !ua.Untracked || ua.User = '$localUser' || ua.User is null ) && lbd.GameID = $gameID
                GROUP BY lbd.ID
            ) InnerTable
            LEFT JOIN LeaderboardEntry AS le ON le.LeaderboardID = InnerTable.LeaderboardID AND le.Score = InnerTable.BestScore
            LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
            WHERE ( !ua.Untracked || ua.User = '$localUser' || ua.User is null )
            ORDER BY DisplayOrder ASC, LeaderboardID, DateSubmitted ASC ";

    $dataOut = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $lbID = $data['LeaderboardID'];
            if (!isset($dataOut[$lbID])) {
                $dataOut[$lbID] = $data; // Potentially overwrites existing one
            }
        }
    } else {
        log_sql_fail();
    }

    // Get the number of leaderboards for the game
    $query = "SELECT COUNT(*) AS lbCount FROM LeaderboardDef WHERE GameID = $gameID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return (int) mysqli_fetch_assoc($dbResult)['lbCount'];
    } else {
        return 0;
    }
}

function GetLeaderboardEntriesDataJSON($lbID, $user, $numToFetch, $offset, $friendsOnly): array
{
    sanitize_sql_inputs($lbID, $user, $numToFetch, $offset);

    $retVal = [];

    // 'Me or my friends'
    $friendQuery = $friendsOnly ? "( ua.User IN ( " . GetFriendsSubquery($user) . " ) )" : "TRUE";

    // Get entries:
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

function getLeaderboardTitle($id, &$gameIDOut): string
{
    sanitize_sql_inputs($id);
    settype($id, "integer");

    $query = "SELECT lb.Title, lb.GameID FROM LeaderboardDef AS lb WHERE lb.ID = $id";
    $dbResult = s_mysql_query($query);
    if ($dbResult) {
        $data = mysqli_fetch_assoc($dbResult);
        if ($data) {
            $gameIDOut = $data['GameID'];

            return $data['Title'];
        }
    }

    log_sql_fail();

    return "";
}

function GetLeaderboardData($lbID, $user, $numToFetch, $offset, $friendsOnly, $nearby = false): array
{
    sanitize_sql_inputs($lbID, $user, $numToFetch, $offset);

    $retVal = [];

    // Get raw LB data
    $query = "
      SELECT
        ld.ID AS LBID,
        gd.ID AS GameID,
        gd.Title AS GameTitle,
        ld.LowerIsBetter,
        ld.Title AS LBTitle,
        ld.Description AS LBDesc,
        ld.Format AS LBFormat,
        ld.Mem AS LBMem,
        ld.Author AS LBAuthor,
        gd.ConsoleID,
        c.Name AS ConsoleName,
        gd.ForumTopicID,
        gd.ImageIcon AS GameIcon,
        ld.Created AS LBCreated,
        ld.Updated AS LBUpdated,
        (
          SELECT COUNT(UserID)
          FROM LeaderboardEntry AS le
          LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
          WHERE !ua.Untracked AND le.LeaderboardID = $lbID
        ) AS TotalEntries
      FROM LeaderboardDef AS ld
      LEFT JOIN GameData AS gd ON gd.ID = ld.GameID
      LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
      WHERE ld.ID = $lbID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $retVal = mysqli_fetch_assoc($dbResult);
        settype($retVal['LBID'], 'integer');
        settype($retVal['GameID'], 'integer');
        settype($retVal['LowerIsBetter'], 'integer');
        settype($retVal['ConsoleID'], 'integer');
        settype($retVal['ForumTopicID'], 'integer');
        settype($retVal['TotalEntries'], 'integer');

        $retVal['Entries'] = [];

        // If a $user is passed in and $nearby is true then change $offset to give
        // entries around the player based on their index and total entries
        if ($nearby && !is_null($user)) {
            $userPosition = 0;
            getLeaderboardUserPosition($lbID, $user, $userPosition);
            if ($userPosition != 0) {
                $offset = $userPosition - intdiv($numToFetch, 2) - 1;
                if ($offset <= 0) {
                    $offset = 0;
                } elseif ($retVal['TotalEntries'] - $userPosition + 1 < $numToFetch) {
                    $offset = max(0, $retVal['TotalEntries'] - $numToFetch);
                }
            }
        }

        // Now get entries:
        $query = "SELECT ua.User, le.Score, le.DateSubmitted,
                  CASE WHEN !lbd.LowerIsBetter
                  THEN RANK() OVER(ORDER BY le.Score DESC)
                  ELSE RANK() OVER(ORDER BY le.Score ASC) END AS UserRank,
                  CASE WHEN !lbd.LowerIsBetter
                  THEN ROW_NUMBER() OVER(ORDER BY le.Score DESC, le.DateSubmitted ASC)
                  ELSE ROW_NUMBER() OVER(ORDER BY le.Score ASC, le.DateSubmitted ASC) END AS UserIndex
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
                $db_entry['DateSubmitted'] = strtotime($db_entry['DateSubmitted']);
                settype($db_entry['Score'], 'integer');
                $db_entry['Rank'] = (int) $db_entry['UserRank'];
                unset($db_entry['UserRank']);
                $db_entry['Index'] = (int) $db_entry['UserIndex'];
                unset($db_entry['UserIndex']);

                if (strcmp($db_entry['User'], $user) == 0) {
                    $userFound = true;
                }

                $entries[] = $db_entry;

                $numResultsFound++;
            }

            // Currently only used for appending player to the end on website leaderboard pages
            if ($userFound == false && !$nearby) {
                // Go find user's score in this table, if it exists!
                $query = "SELECT User, Score, DateSubmitted, UserRank, UserIndex FROM
                         (SELECT ua.User, le.Score, le.DateSubmitted,
                          CASE WHEN !lbd.LowerIsBetter
                          THEN RANK() OVER(ORDER BY le.Score DESC)
                          ELSE RANK() OVER(ORDER BY le.Score ASC) END AS UserRank,
                          CASE WHEN !lbd.LowerIsBetter
                          THEN ROW_NUMBER() OVER(ORDER BY le.Score DESC, le.DateSubmitted ASC)
                          ELSE ROW_NUMBER() OVER(ORDER BY le.Score ASC, le.DateSubmitted ASC) END AS UserIndex
                          FROM LeaderboardEntry AS le
                          LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
                          LEFT JOIN LeaderboardDef AS lbd ON lbd.ID = le.LeaderboardID
                          WHERE !ua.Untracked AND le.LeaderboardID = $lbID) InnerTable
                          WHERE InnerTable.User = '$user'";

                $dbResult = s_mysql_query($query);
                if ($dbResult !== false) {
                    if (mysqli_num_rows($dbResult) > 0) {
                        // should be 1 or 0?.. I hope...
                        $db_entry = mysqli_fetch_assoc($dbResult);
                        $db_entry['DateSubmitted'] = strtotime($db_entry['DateSubmitted']);
                        settype($db_entry['Score'], 'integer');
                        $db_entry['Rank'] = (int) $db_entry['UserRank'];
                        // @phpstan-ignore-next-line
                        unset($db_entry['UserRank']);
                        // @phpstan-ignore-next-line
                        $db_entry['Index'] = (int) $db_entry['UserIndex'];
                        // @phpstan-ignore-next-line
                        unset($db_entry['UserIndex']);
                        $entries[] = $db_entry;
                    }
                } else {
                    log_sql_fail();
                }
            }

            $retVal['Entries'] = $entries;
        } else {
            log_sql_fail();
        }
    }

    return $retVal;
}

function formatLeaderboardValueSeconds($hours, $mins, $secs): string
{
    if ($hours == 0) {
        return sprintf("%02d:%02d", $mins, $secs);
    }

    return sprintf("%02dh%02d:%02d", $hours, $mins, $secs);
}

function GetFormattedLeaderboardEntry($formatType, $scoreIn): string
{
    settype($scoreIn, 'integer');

    // NOTE: a/b results in a float, a%b results in an integer

    if ($formatType == 'TIME') { // Number of frames
        $hours = $scoreIn / 216000;
        settype($hours, 'integer');
        $mins = ($scoreIn / 3600) - ($hours * 60);
        $secs = ($scoreIn % 3600) / 60;
        $milli = (($scoreIn % 3600) % 60) * (100.0 / 60.0);
        settype($mins, 'integer');
        settype($secs, 'integer');
        settype($milli, 'integer');

        return sprintf("%s.%02d", formatLeaderboardValueSeconds($hours, $mins, $secs), $milli);
    } elseif ($formatType == 'TIMESECS') { // Number of seconds
        $hours = $scoreIn / 3600;
        settype($hours, 'integer');
        $mins = ($scoreIn / 60) - ($hours * 60);
        $secs = $scoreIn % 60;

        return formatLeaderboardValueSeconds($hours, $mins, $secs);
    } elseif ($formatType == 'MILLISECS') { // Hundredths of seconds
        $hours = $scoreIn / 360000;
        settype($hours, 'integer');
        $mins = ($scoreIn / 6000) - ($hours * 60);
        $secs = ($scoreIn % 6000) / 100;
        $milli = ($scoreIn % 100);
        settype($mins, 'integer');
        settype($secs, 'integer');
        settype($milli, 'integer');

        return sprintf("%s.%02d", formatLeaderboardValueSeconds($hours, $mins, $secs), $milli);
    } elseif ($formatType == 'MINUTES') { // Number of minutes
        $hours = $scoreIn / 60;
        settype($hours, 'integer');
        $mins = $scoreIn % 60;

        return sprintf("%01dh%02d", $hours, $mins);
    } elseif ($formatType == 'SCORE') { // Number padded to six digits
        return sprintf("%06d", $scoreIn);
    } else { // Raw number
        return "$scoreIn";
    }
}

function isValidLeaderboardFormat($formatType): bool
{
    return $formatType == 'TIME' ||      // Frames
           $formatType == 'TIMESECS' ||  // Seconds
           $formatType == 'MINUTES' ||   // Minutes
           $formatType == 'MILLISECS' || // Hundredths of seconds
           $formatType == 'VALUE' ||     // Raw number
           $formatType == 'SCORE';       // Number padded to six digits
}

function getLeaderboardUserPosition($lbID, $user, &$lbPosition): bool
{
    sanitize_sql_inputs($lbID, $user);

    $query = "SELECT UserIndex FROM
                         (SELECT ua.User, le.Score, le.DateSubmitted,
                          CASE WHEN !lbd.LowerIsBetter
                          THEN ROW_NUMBER() OVER(ORDER BY le.Score DESC, le.DateSubmitted ASC)
                          ELSE ROW_NUMBER() OVER(ORDER BY le.Score ASC, le.DateSubmitted ASC) END AS UserIndex
                          FROM LeaderboardEntry AS le
                          LEFT JOIN UserAccounts AS ua ON ua.ID = le.UserID
                          LEFT JOIN LeaderboardDef AS lbd ON lbd.ID = le.LeaderboardID
                          WHERE !ua.Untracked AND le.LeaderboardID = $lbID) InnerTable
                          WHERE InnerTable.User = '$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $db_entry = mysqli_fetch_assoc($dbResult);

        if (is_null($db_entry)) {
            $lbPosition = 0;

            return true;
        }

        $lbPosition = $db_entry['UserIndex'];

        return true;
    } else {
        log_sql_fail();

        return false;
    }
}

function getLeaderboardsList($consoleIDInput, $gameID, $sortBy, $count, $offset, &$lbDataOut): int
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
        default:
            $orderClause = "ORDER BY ld.ID $ifDesc";
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
                     ld.Author,
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
        log_sql_fail();
    }

    return $lbCount;
}

function submitLBData($user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder): bool
{
    sanitize_sql_inputs($user, $lbMem, $lbTitle, $lbDescription, $lbFormat);
    settype($lbID, 'integer');
    settype($lbDisplayOrder, 'integer');
    settype($lbLowerIsBetter, 'integer');

    $query = "UPDATE LeaderboardDef AS ld SET
              ld.Mem = '$lbMem',
              ld.Format = '$lbFormat',
              ld.Title = '$lbTitle',
              ld.Description = '$lbDescription',
              ld.Format = '$lbFormat',
              ld.LowerIsBetter = '$lbLowerIsBetter',
              ld.DisplayOrder = '$lbDisplayOrder'
              WHERE ld.ID = $lbID";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        return true;
    } else {
        return false;
    }
}

function SubmitNewLeaderboard($gameID, &$lbIDOut, $user): bool
{
    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');
    if ($gameID == 0) {
        return false;
    }

    $defaultMem = "STA:0x0000=h0010_0xhf601=h0c::CAN:0xhfe13<d0xhfe13::SUB:0xf7cc!=0_d0xf7cc=0::VAL:0xhfe24*1_0xhfe25*60_0xhfe22*3600";
    $query = "INSERT INTO LeaderboardDef (GameID, Mem, Format, Title, Description, LowerIsBetter, DisplayOrder, Author, Created)
                                VALUES ($gameID, '$defaultMem', 'SCORE', 'My Leaderboard', 'My Leaderboard Description', 0,
                                (SELECT * FROM (SELECT COALESCE(Max(DisplayOrder) + 1, 0) FROM LeaderboardDef WHERE  GameID = $gameID) AS temp), '$user', NOW())";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $db = getMysqliConnection();
        $lbIDOut = mysqli_insert_id($db);

        return true;
    } else {
        return false;
    }
}

function UploadNewLeaderboard(
    $author,
    $gameID,
    $title,
    $desc,
    $format,
    $lowerIsBetter,
    $mem,
    &$idInOut,
    &$errorOut
): bool {
    $displayOrder = 0;
    $originalAuthor = '';

    if ($idInOut > 0) {
        $query = "SELECT DisplayOrder, Author FROM LeaderboardDef WHERE ID='$idInOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);
            $displayOrder = $data['DisplayOrder'];
            $originalAuthor = $data['Author'] ?? "Unknown";
            settype($displayOrder, 'integer');
        } else {
            $errorOut = "Unknown leaderboard";

            return false;
        }
    }

    // Prevent non-developers from uploading or modifying leaderboards
    $userPermissions = getUserPermissions($author);
    if ($userPermissions < Permissions::Developer) {
        if ($userPermissions < Permissions::JuniorDeveloper ||
            (!empty($originalAuthor) && $author != $originalAuthor)) {
            $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

            return false;
        }
    }

    if (!isValidConsoleId(getGameData($gameID)['ConsoleID'])) {
        $errorOut = "You cannot promote leaderboards for a game from an unsupported console (console ID: " . getGameData($gameID)['ConsoleID'] . ").";

        return false;
    }

    if (!isValidLeaderboardFormat($format)) {
        $errorOut = "Unknown format: $format";

        return false;
    }

    if (!isset($idInOut) || $idInOut == 0) {
        if (!SubmitNewLeaderboard($gameID, $idInOut, $author)) {
            $errorOut = "Internal error creating new leaderboard.";

            return false;
        }

        $query = "SELECT DisplayOrder FROM LeaderboardDef WHERE ID='$idInOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);
            $displayOrder = $data['DisplayOrder'];
            settype($displayOrder, 'integer');
        }
    }

    if (!submitLBData($author, $idInOut, $mem, $title, $desc, $format, $lowerIsBetter, $displayOrder)) {
        $errorOut = "Internal error updating leaderboard.";

        return false;
    }

    if ($originalAuthor != '') {
        addArticleComment("Server", ArticleType::Leaderboard, $idInOut,
            "$author edited this leaderboard.", $author
        );
    }

    return true;
}

/**
 * Duplicates a leaderboard a specified number of times.
 */
function duplicateLeaderboard(int $gameID, int $leaderboardID, int $duplicateNumber, $user): bool
{
    sanitize_sql_inputs($gameID, $leaderboardID);
    settype($gameID, 'integer');
    settype($leaderboardID, 'integer');
    settype($duplicateNumber, 'integer');

    if ($gameID == 0) {
        return false;
    }

    // Get the leaderboard info to duplicate
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
    if (!$dbResult) {
        return false;
    }

    $db_entry = mysqli_fetch_assoc($dbResult);

    if (empty($db_entry)) {
        return false;
    }

    $lbMem = $db_entry['Mem'];
    $lbFormat = $db_entry['Format'];
    $lbTitle = $db_entry['Title'];
    $lbDescription = $db_entry['Description'];
    $lbScoreType = $db_entry['LowerIsBetter'];
    $lbDisplayOrder = $db_entry['DisplayOrder'];

    // Create the duplicate entries
    for ($i = 1; $i <= $duplicateNumber; $i++) {
        $query = "INSERT INTO LeaderboardDef (GameID, Mem, Format, Title, Description, LowerIsBetter, DisplayOrder, Author, Created)
                                    VALUES ($gameID, '$lbMem', '$lbFormat', '$lbTitle', '$lbDescription', $lbScoreType, ($lbDisplayOrder + $i), '$user', NOW())";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            $db = getMysqliConnection();
            mysqli_insert_id($db);
        } else {
            return false;
        }
    }

    return true;
}

function requestResetLB($lbID): bool
{
    sanitize_sql_inputs($lbID);
    settype($lbID, 'integer');
    if ($lbID == 0) {
        return false;
    }

    $query = "DELETE FROM LeaderboardEntry
              WHERE LeaderboardID = $lbID";
    $dbResult = s_mysql_query($query);

    return $dbResult !== false;
}

function requestDeleteLB($lbID): bool
{
    sanitize_sql_inputs($lbID);
    settype($lbID, 'integer');

    $query = "DELETE FROM LeaderboardDef WHERE ID = $lbID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        /** @var User $user */
        $user = request()->user();
        DeletedModels::create([
            'ModelType' => 'LeaderboardDef',
            'ModelID' => $lbID,
            'DeletedByUserID' => $user->ID,
        ]);
    }

    return $dbResult !== false;
}

function GetLBPatch($gameID): array
{
    sanitize_sql_inputs($gameID);
    $lbData = [];

    // Always append LBs?
    $query = "SELECT ld.ID, ld.Mem, ld.Format, ld.LowerIsBetter, ld.Title, ld.Description,
                  CASE WHEN ld.DisplayOrder < 0 THEN 1 ELSE 0 END AS Hidden
              FROM LeaderboardDef AS ld
              WHERE ld.GameID = $gameID
              ORDER BY ld.DisplayOrder, ld.ID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            settype($db_entry['ID'], 'integer');
            settype($db_entry['LowerIsBetter'], 'boolean');
            settype($db_entry['Hidden'], 'boolean');
            $lbData[] = $db_entry;
        }
    }

    return $lbData;
}

function deleteOrphanedLeaderboardEntries(): void
{
    s_mysql_query("DELETE le FROM LeaderboardEntry le LEFT JOIN UserAccounts ua ON le.UserID = ua.ID WHERE ua.User IS NULL");
}
