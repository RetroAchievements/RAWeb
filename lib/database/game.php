<?php

use RA\ActivityType;
use RA\Permissions;

function getGameFromHash($md5Hash, &$gameIDOut, &$gameTitleOut)
{
    $query = "SELECT ID, GameName FROM GameData WHERE GameMD5='$md5Hash'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== null) {
        $data = mysqli_fetch_assoc($dbResult);
        if ($data !== null) {
            $gameIDOut = $data['ID'];
            $gameTitleOut = $data['GameName'];
            return true;
        } else {
            // error_log(__FUNCTION__ . " cannot find game with md5 ($md5Hash) in DB!");
            return false;
        }
    } else {
        // error_log(__FUNCTION__ . " issues getting game with md5 ($md5Hash) from DB!");
        return false;
    }
}

function getGameData($gameID)
{
    $query = "SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, IFNULL( gd.Flags, 0 ) AS Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, c.Name AS ConsoleName, c.ID AS ConsoleID, gd.RichPresencePatch
              FROM GameData AS gd
              LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
              WHERE gd.ID = $gameID";

    $dbResult = s_mysql_query($query);
    if ($retVal = mysqli_fetch_assoc($dbResult)) {
        settype($retVal['ID'], 'integer');
        settype($retVal['ConsoleID'], 'integer');
        settype($retVal['Flags'], 'integer');
        settype($retVal['ForumTopicID'], 'integer');
        settype($retVal['IsFinal'], 'boolean');
        return $retVal;
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " cannot find game with ID ($gameID) in DB!");
        return null;
    }
}

function getGameTitleFromID($gameID, &$gameTitle, &$consoleID, &$consoleName, &$forumTopicID, &$allData)
{
    $gameTitle = "UNRECOGNISED";
    settype($gameID, "integer");

    if ($gameID !== 0) {
        $query = "SELECT gd.Title, gd.ForumTopicID, c.ID AS ConsoleID, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon, gd.ImageIcon AS GameIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released
                  FROM GameData AS gd
                  LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
                  WHERE gd.ID=$gameID";
        $dbResult = s_mysql_query($query);

        if ($dbResult !== false) {
            $data = mysqli_fetch_assoc($dbResult);
            if ($data !== false) {
                $gameTitle = $data['Title'];
                $consoleName = $data['ConsoleName'];
                $consoleID = $data['ConsoleID'];
                $forumTopicID = $data['ForumTopicID'];
                $allData = $data;
            } else {
                // error_log(__FUNCTION__ . " cannot find game with ID ($gameID) in DB!");
            }
        } else {
            log_sql_fail();
            // error_log(__FUNCTION__ . " issues getting game with ID ($gameID) from DB!");
        }
    }

    return $gameTitle;
}

function getGameMetadata($gameID, $user, &$achievementDataOut, &$gameDataOut, $sortBy = 0, $user2 = null, $flag = null)
{
    return getGameMetadataByFlags($gameID, $user, $achievementDataOut, $gameDataOut, $sortBy, $user2, $flag);
}

function getGameMetadataByFlags(
    $gameID,
    $user,
    &$achievementDataOut,
    &$gameDataOut,
    $sortBy = 0,
    $user2 = null,
    $flags = 0
) {
    settype($gameID, 'integer');
    settype($sortBy, 'integer');
    settype($flags, 'integer');

    // flag = 5 -> Unofficial / flag = 3 -> Core
    $flags = $flags != 5 ? 3 : 5;

    switch ($sortBy) {
        case 1: // display order defined by the developer
            $orderBy = "ORDER BY ach.DisplayOrder, ach.ID ASC ";
            break;
        case 11:
            $orderBy = "ORDER BY ach.DisplayOrder DESC, ach.ID DESC ";
            break;

        case 2: // won by X users
            $orderBy = "ORDER BY NumAwarded, ach.ID ASC ";
            break;
        case 12:
            $orderBy = "ORDER BY NumAwarded DESC, ach.ID DESC ";
            break;

        // meleu: 3 and 13 should sort by the date the user won the cheevo
        //        but it's not trivial to implement (requires tweaks on SQL query).
        //case 3: // date the user won
        //$orderBy = " ";
        //break;
        //case 13:
        //$orderBy = " ";
        //break;

        case 4: // points
            $orderBy = "ORDER BY ach.Points, ach.ID ASC ";
            break;
        case 14:
            $orderBy = "ORDER BY ach.Points DESC, ach.ID DESC ";
            break;

        case 5: // Title
            $orderBy = "ORDER BY ach.Title, ach.ID ASC ";
            break;
        case 15:
            $orderBy = "ORDER BY ach.Title DESC, ach.ID DESC ";
            break;

        default:
            $orderBy = "ORDER BY ach.DisplayOrder, ach.ID ASC ";
    }

    $gameDataOut = getGameData($gameID);

    $achievementDataOut = [];

    if ($gameDataOut == null) {
        return 0;
    }

    //    Get all achievements data
    //  WHERE reads: If never won, or won by a tracked gamer, or won by me
    //$query = "SELECT ach.ID, ( COUNT( aw.AchievementID ) - SUM( IFNULL( aw.HardcoreMode, 0 ) ) ) AS NumAwarded, SUM( IFNULL( aw.HardcoreMode, 0 ) ) AS NumAwardedHardcore, ach.Title, ach.Description, ach.Points, ach.TrueRatio, ach.Author, ach.DateModified, ach.DateCreated, ach.BadgeName, ach.DisplayOrder, ach.MemAddr
    //          FROM Achievements AS ach
    //          LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
    //          LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
    //          WHERE ( !IFNULL( ua.Untracked, FALSE ) || ua.User = \"$user\" ) AND ach.GameID = $gameID AND ach.Flags = $flags
    //          GROUP BY ach.ID
    //          $orderBy";

    $query = "
    SELECT
        ach.ID, 
        IFNULL(tracked_aw.NumAwarded, 0) AS NumAwarded,
        IFNULL(tracked_aw.NumAwardedHardcore, 0) AS NumAwardedHardcore,
        ach.Title,
        ach.Description,
        ach.Points,
        ach.TrueRatio,
        ach.Author,
        ach.DateModified,
        ach.DateCreated,
        ach.BadgeName,
        ach.DisplayOrder,
        ach.MemAddr
    FROM Achievements AS ach
    LEFT JOIN (
        SELECT
            ach.ID AS AchievementID,
            (COUNT(aw.AchievementID) - SUM(IFNULL(aw.HardcoreMode, 0))) AS NumAwarded, 
            (SUM(IFNULL(aw.HardcoreMode, 0))) AS NumAwardedHardcore
        FROM Achievements AS ach
        INNER JOIN Awarded AS aw ON aw.AchievementID = ach.ID
        INNER JOIN UserAccounts AS ua ON ua.User = aw.User
        WHERE ach.GameID = $gameID AND ach.Flags = $flags
          AND (NOT ua.Untracked" . (isset($user) ? " OR ua.User = '$user'" : "") . ")
        GROUP BY ach.ID
    ) AS tracked_aw ON tracked_aw.AchievementID = ach.ID
    WHERE ach.GameID = $gameID AND ach.Flags = $flags
    $orderBy";

    //echo $query;

    $numAchievements = 0;
    $numDistinctPlayersCasual = 0;
    $numDistinctPlayersHardcore = 0;

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $nextID = $data['ID'];
            settype($nextID, 'integer');
            $achievementDataOut[$nextID] = $data;

            $numHC = $data['NumAwardedHardcore'];
            $numCas = $data['NumAwarded'];

            if ($numCas > $numDistinctPlayersCasual) {
                $numDistinctPlayersCasual = $numCas;
            }
            if ($numHC > $numDistinctPlayersHardcore) {
                $numDistinctPlayersHardcore = $numHC;
            }

            $numAchievements++;
        }
    } else {
        log_sql_fail();
        return 0;
    }

    //    Now find local information:
    if (isset($user)) {
        $query = "SELECT ach.ID, aw.Date, aw.HardcoreMode
                  FROM Awarded AS aw
                  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                  WHERE ach.GameID = $gameID AND ach.Flags = $flags AND aw.User = '$user'";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($data = mysqli_fetch_assoc($dbResult)) {
                $nextID = $data['ID'];
                settype($nextID, 'integer');
                if (isset($data['HardcoreMode']) && $data['HardcoreMode'] == 1) {
                    $achievementDataOut[$nextID]['DateEarnedHardcore'] = $data['Date'];
                } else {
                    $achievementDataOut[$nextID]['DateEarned'] = $data['Date'];
                }
            }
        }
    }

    if (isset($user2)) {
        $query = "SELECT ach.ID, aw.Date, aw.HardcoreMode
                  FROM Awarded AS aw
                  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                  WHERE ach.GameID = $gameID AND aw.User = '$user2'";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($data = mysqli_fetch_assoc($dbResult)) {
                $nextID = $data['ID'];
                settype($nextID, 'integer');
                if ($data['HardcoreMode'] == 1) {
                    $achievementDataOut[$nextID]['DateEarnedFriendHardcore'] = $data['Date'];
                } else {
                    $achievementDataOut[$nextID]['DateEarnedFriend'] = $data['Date'];
                }
            }
        }
    }

    $gameDataOut['NumAchievements'] = $numAchievements;
    $gameDataOut['NumDistinctPlayersCasual'] = $numDistinctPlayersCasual;
    $gameDataOut['NumDistinctPlayersHardcore'] = $numDistinctPlayersHardcore;

    return $numAchievements;
}

function getGameAlternatives($gameID)
{
    settype($gameID, 'integer');

    $query = "SELECT gameIDAlt, gd.Title, gd.ImageIcon, c.Name AS ConsoleName, 
              (SELECT SUM(ach.Points) FROM Achievements ach WHERE ach.GameID = gd.ID AND ach.Flags = 3) AS Points, 
              gd.TotalTruePoints
              FROM GameAlternatives AS ga
              LEFT JOIN GameData AS gd ON gd.ID = ga.gameIDAlt
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ga.gameID = $gameID
              GROUP BY gd.ID, gd.Title
              ORDER BY gd.Title";

    $dbResult = s_mysql_query($query);

    $results = [];

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $results[] = $data;
        }
    }

    return $results;
}

function getGamesListWithNumAchievements($consoleID, &$dataOut, $sortBy)
{
    return getGamesListByDev(null, $consoleID, $dataOut, $sortBy, false);
}

function getGamesListByDev($dev, $consoleID, &$dataOut, $sortBy, $ticketsFlag = false)
{
    //    Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console

    $whereCond = "WHERE ach.Flags=3 ";
    $moreSelectCond = '';
    $havingCond = '';

    if ($ticketsFlag) {
        $selectTickets = ", ticks.OpenTickets";
        $joinTicketsTable = "
        LEFT JOIN (
            SELECT
                ach.GameID,
                count( DISTINCT tick.ID ) AS OpenTickets
            FROM
                Ticket AS tick
            LEFT JOIN
                Achievements AS ach ON ach.ID = tick.AchievementID
            WHERE
                tick.ReportState = 1
            GROUP BY
                ach.GameID
        ) as ticks ON ticks.GameID = ach.GameID ";
    } else {
        $selectTickets = null;
        $joinTicketsTable = null;
    }

    if ($consoleID != 0) {
        $whereCond .= "AND gd.ConsoleID=$consoleID ";
    }

    if ($dev != null) {
        $moreSelectCond = "SUM(CASE WHEN ach.Author LIKE '$dev' THEN 1 ELSE 0 END) AS MyAchievements,
                           SUM(CASE WHEN ach.Author NOT LIKE '$dev' THEN 1 ELSE 0 END) AS NotMyAchievements,";
        $havingCond = "HAVING MyAchievements > 0 ";
    }

    $query = "SELECT gd.Title, ach.GameID AS ID, gd.ConsoleID, c.Name AS ConsoleName, COUNT( ach.GameID ) AS NumAchievements, SUM(ach.Points) AS MaxPointsAvailable, lbdi.NumLBs, gd.ImageIcon as GameIcon, gd.TotalTruePoints $selectTickets,
                $moreSelectCond
                CASE WHEN LENGTH(gd.RichPresencePatch) > 0 THEN 1 ELSE 0 END AS RichPresence
                FROM Achievements AS ach
                LEFT JOIN ( SELECT lbd.GameID, COUNT( DISTINCT lbd.ID ) AS NumLBs FROM LeaderboardDef AS lbd GROUP BY lbd.GameID ) AS lbdi ON lbdi.GameID = ach.GameID
                $joinTicketsTable
                INNER JOIN GameData AS gd on gd.ID = ach.GameID
                INNER JOIN Console AS c ON c.ID = gd.ConsoleID
                $whereCond
                GROUP BY ach.GameID
                $havingCond";

    //echo $query;

    settype($sortBy, 'integer');

    if ($sortBy < 1 || $sortBy > 13) {
        $sortBy = 1;
    }

    switch ($sortBy) {
        case 1:
        default:
            $query .= "ORDER BY gd.ConsoleID, Title ";
            break;
        case 11:
            $query .= "ORDER BY gd.ConsoleID, Title DESC ";
            break;

        case 2:
            $query .= "ORDER BY gd.ConsoleID, NumAchievements DESC, Title ";
            break;
        case 12:
            $query .= "ORDER BY gd.ConsoleID, NumAchievements ASC, Title ";
            break;

        case 3:
            $query .= "ORDER BY gd.ConsoleID, MaxPointsAvailable DESC, Title ";
            break;
        case 13:
            $query .= "ORDER BY gd.ConsoleID, MaxPointsAvailable, Title ";
            break;

        case 4:
            $query .= "ORDER BY NumLBs DESC, gd.ConsoleID, MaxPointsAvailable, Title ";
            break;
        case 14:
            $query .= "ORDER BY NumLBs, gd.ConsoleID, MaxPointsAvailable, Title ";
            break;

        case 5:
            if ($ticketsFlag) {
                $query .= "ORDER BY OpenTickets DESC, gd.ConsoleID, MaxPointsAvailable, Title ";
            } else {
                $query .= "ORDER BY gd.ConsoleID, Title ";
            }
            break;
        case 15:
            if ($ticketsFlag) {
                $query .= "ORDER BY OpenTickets, gd.ConsoleID, MaxPointsAvailable, Title ";
            } else {
                $query .= "ORDER BY gd.ConsoleID, Title DESC ";
            }
            break;
    }

    $numGamesFound = 0;

    $dataOut = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numGamesFound] = $db_entry;
            $numGamesFound++;
        }
    } else {
        // error_log(__FUNCTION__);
        log_sql_fail();
    }

    return $numGamesFound;
}

function getGamesListData($consoleID, $officialFlag = false)
{
    $leftJoinAch = "";
    $whereClause = "";
    if ($officialFlag) {
        $leftJoinAch = "LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID ";
        $whereClause = "WHERE ach.Flags=3 ";
    }

    //    Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console
    if (isset($consoleID) && $consoleID != 0) {
        $whereClause .= $officialFlag ? "AND " : "WHERE ";
        $whereClause .= "ConsoleID=$consoleID ";
    }

    $query = "SELECT gd.Title, gd.ID, gd.ConsoleID, gd.ImageIcon, c.Name as ConsoleName
              FROM GameData AS gd
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              $leftJoinAch
              $whereClause
              ORDER BY ConsoleName, Title";

    $retVal = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

function getGamesList($consoleID, &$dataOut)
{
    $dataOut = getGamesListData($consoleID);
    return count($dataOut);
}

function getGamesListDataNamesOnly($consoleID, $officialFlag = false)
{
    $retval = [];

    $data = getGamesListData($consoleID, $officialFlag);

    foreach ($data as $element) {
        $retval[$element['ID']] = utf8_encode($element['Title']);
    }

    // error_log("getGamesListDataNamesOnly: " . count($data) . ", " . count($retval));

    return $retval;
}

function getAllocatedForGame($gameID, &$allocatedPoints, &$numAchievements)
{
    $query = "SELECT SUM(ach.Points) AS AllocatedPoints, COUNT(ID) AS NumAchievements FROM Achievements AS ach ";
    $query .= "WHERE ach.Flags = 3 AND ach.GameID = $gameID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $allocatedPoints = $data['AllocatedPoints'];
        $numAchievements = $data['NumAchievements'];
        return true;
    } else {
        // error_log(__FUNCTION__);
        log_sql_fail();
        return false;
    }
}

function getAchievementIDs($gameID)
{
    $retVal = [];
    settype($gameID, 'integer');
    $retVal['GameID'] = $gameID;

    //    Get all achievement IDs
    $query = "SELECT ach.ID AS ID
              FROM Achievements AS ach
              WHERE ach.GameID = $gameID AND ach.Flags = 3
              ORDER BY ach.ID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $achIDs = [];
        while ($data = mysqli_fetch_assoc($dbResult)) {
            settype($data['ID'], 'integer');
            $achIDs[] = $data['ID'];
        }
        $retVal['AchievementIDs'] = $achIDs;
    }

    return $retVal;
}

function getGameIDFromTitle($gameTitleIn, $consoleID)
{
    $gameTitle = str_replace("'", "''", $gameTitleIn);
    settype($consoleID, 'integer');

    $query = "SELECT ID FROM GameData
              WHERE Title='$gameTitle' AND ConsoleID='$consoleID'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['ID'];
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: could not find $gameTitle!");
        return 0;
    }
}

function testFullyCompletedGame($user, $achID, $isHardcore)
{
    $achData = [];
    if (getAchievementMetadata($achID, $achData) == false) {
        // error_log(__FUNCTION__);
        // error_log("cannot get achievement metadata for $achID. This is MEGABAD!");
        return false;
    }

    $gameID = $achData['GameID'];

    $query = "SELECT COUNT(ach.ID) AS NumAwarded, COUNT(aw.AchievementID) AS NumAch FROM Achievements AS ach 
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID AND aw.User = '$user' AND aw.HardcoreMode = $isHardcore 
              WHERE ach.GameID = $gameID AND ach.Flags = 3 ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $minToCompleteGame = 5;

        $data = mysqli_fetch_assoc($dbResult);
        if (($data['NumAwarded'] == $data['NumAch']) && ($data['NumAwarded'] > $minToCompleteGame)) {
            //    Every achievement earned!
            //error_log( __FUNCTION__ );
            //error_log( "$user earned EVERY achievement for game $gameID" );
            //    Test that this wasn't very recently posted!
            if (!RecentlyPostedCompletionActivity($user, $gameID, $isHardcore)) {
                postActivity($user, ActivityType::CompleteGame, $gameID, $isHardcore);
            }
            return true;
        } else {
            return false;
        }
    } else {
        // error_log(__FUNCTION__);
        // error_log("broken1 with $achID, $gameID, $user. This is MEGABAD!");
        return false;
    }
}

function requestModifyGameData($gameID, $developerIn, $publisherIn, $genreIn, $releasedIn)
{
    global $db;
    $developer = mysqli_real_escape_string($db, $developerIn);
    $publisher = mysqli_real_escape_string($db, $publisherIn);
    $genre = mysqli_real_escape_string($db, $genreIn);
    $released = mysqli_real_escape_string($db, $releasedIn);

    $query = "UPDATE GameData AS gd
              SET gd.Developer = '$developer', gd.Publisher = '$publisher', gd.Genre = '$genre', gd.Released = '$released'
              WHERE gd.ID = $gameID";

    $dbResult = mysqli_query($db, $query);

    if ($dbResult == false) {
        // log_email(__FUNCTION__ . " went wrong. GameID: $gameID, text: $developer, $publisher, $genre, $released ");
        // log_email($query);
        log_sql_fail();
    } else {
        // error_log(__FUNCTION__ . " OK! GameID: $gameID, text: $developer, $publisher, $genre, $released");
    }

    return $dbResult != null;
}

function requestModifyGame($author, $gameID, $field, $value)
{
    global $db;

    settype($field, 'integer');
    switch ($field) {
        case 1: // Title
            if (!isset($value) || mb_strlen($value) < 2) {
                return false;
            }

            $newTitle = str_replace("'", "''", $value);
            $newTitle = mysqli_real_escape_string($db, $newTitle);
            //$newTitle = str_replace( "/", "&#47;", $newTitle );
            //$newTitle = str_replace( "\\", "&#92;", $newTitle );

            $query = "UPDATE GameData SET Title='$newTitle' WHERE ID=$gameID";
            // log_sql("$user: $query");

            $dbResult = mysqli_query($db, $query);

            return $dbResult !== false;
            break;

        /**
         * UPDATE: do not allow destructive actions until proper failovers are in place
         */
        // case 2: // GameHashTable
        //     $query = "DELETE FROM GameHashLibrary WHERE GameID=$gameID";
        //     log_sql( "$user: $query" );
        //     $dbResult = s_mysql_query( $query );
        //
        //     return ( $dbResult !== FALSE );
        //     break;

        case 3: // delete a single hash entry
            $query = "DELETE FROM GameHashLibrary WHERE GameID = $gameID AND MD5 = '$value'";
            // log_sql("$user: $query");
            $dbResult = s_mysql_query($query);

            return $dbResult !== false;
            break;
    }

    return false;
}

function requestModifyGameAlt($gameID, $toAdd = null, $toRemove = null)
{
    if (isset($toAdd)) {
        //Replace all non-numberic characters with comma so the string has a common delimiter.
        $toAdd = preg_replace("/[^0-9]+/", ",", $toAdd);
        $tok = strtok($toAdd, ",");
        $valuesArray = [];
        while ($tok !== false && $tok > 0) {
            settype($tok, 'integer');
            $valuesArray[] = "({$gameID}, {$tok}), ({$tok}, {$gameID})";
            $tok = strtok(",");
        }

        $values = implode(", ", $valuesArray);
        if (!empty($values)) {
            $query = "INSERT INTO GameAlternatives (gameID, gameIDAlt) VALUES $values";
            if (s_mysql_query($query)) {
                // error_log("Added game alt(s): $values");
            } else {
                // error_log("FAILED to add game alt(s): $values");
            }
        }
    }

    if (isset($toRemove) && $toRemove > 0) {
        settype($toRemove, 'integer');
        $query = "DELETE FROM GameAlternatives
                  WHERE ( gameID = $gameID AND gameIDAlt = $toRemove ) || ( gameID = $toRemove AND gameIDAlt = $gameID )";
        // error_log("Removed game alt, $gameID -> $toRemove");
        s_mysql_query($query);
    }
}

function requestModifyGameForumTopic($gameID, $newForumTopic)
{
    settype($gameID, 'integer');
    settype($newForumTopic, 'integer');

    if ($gameID == 0 || $newForumTopic == 0) {
        return false;
    }

    if (getTopicDetails($newForumTopic, $topicData)) {
        global $db;
        $query = "
            UPDATE GameData AS gd
            SET gd.ForumTopicID = '$newForumTopic'
            WHERE gd.ID = $gameID";

        if (mysqli_query($db, $query)) {
            // error_log(__FUNCTION__ . " OK! GameID: $gameID, new ForumTopicID: $newForumTopic");
            return true;
        } else {
            //log_email(__FUNCTION__ . " went wrong. GameID: $gameID, new ForumTopicID: $newForumTopic");
            //log_email($query);
            return false;
        }
    }
    return false;
}

function getAchievementDistribution($gameID, $hardcore, $requestedBy)
{
    settype($hardcore, 'integer');
    $retval = [];

    //    Returns an array of the number of players who have achieved each total, up to the max.
    $query = "
        SELECT InnerTable.AwardedCount, COUNT(*) AS NumUniquePlayers
        FROM (
            SELECT COUNT(*) AS AwardedCount
            FROM Awarded AS aw
            LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
            LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
            LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
            WHERE gd.ID = $gameID AND aw.HardcoreMode = $hardcore
              AND (NOT ua.Untracked" . (isset($requestedBy) ? " OR ua.User = '$requestedBy'" : "") . ")
            GROUP BY aw.User
            ORDER BY AwardedCount DESC
        ) AS InnerTable
        GROUP BY InnerTable.AwardedCount";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $awardedCount = $data['AwardedCount'];
            $numUnique = $data['NumUniquePlayers'];
            settype($awardedCount, 'integer');
            settype($numUnique, 'integer');
            $retval[$awardedCount] = $numUnique;
        }
    }

    return $retval;
}

function getMostPopularGames($offset, $count, $method)
{
    settype($method, 'integer');

    $retval = [];

    if ($method == 0) {
        //    By num awards given:
        $query = "    SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, gd.Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, gd.TotalTruePoints, c.Name AS ConsoleName,     SUM(NumTimesAwarded) AS NumRecords
                    FROM GameData AS gd
                    LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                    LEFT OUTER JOIN (
                        SELECT
                            COALESCE(aw.cnt, 0) AS NumTimesAwarded,
                            GameID
                        FROM
                            Achievements AS ach
                        LEFT OUTER JOIN (
                            SELECT
                                AchievementID,
                                count(*) cnt
                            FROM
                                Awarded
                            GROUP BY
                                AchievementID) aw ON ach.ID = aw.AchievementID
                        GROUP BY
                            ach.ID) aw ON aw.GameID = gd.ID
                    GROUP BY gd.ID
                    ORDER BY NumRecords DESC
                    LIMIT $offset, $count";
    } else {
        return $retval;
        // $query = "    SELECT COUNT(*) AS NumRecords, Inner1.*
        //         FROM
        //         (
        //             SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, gd.Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, gd.TotalTruePoints, c.Name AS ConsoleName
        //             FROM Activity AS act
        //             LEFT JOIN GameData AS gd ON gd.ID = act.data
        //             LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        //             WHERE act.activitytype = 3 AND !ISNULL( gd.ID )
        //             GROUP BY gd.ID, act.User
        //         ) AS Inner1
        //         GROUP BY Inner1.ID
        //         ORDER BY NumRecords DESC
        //         LIMIT $offset, $count";
    }

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

function getGameListSearch($offset, $count, $method, $consoleID = null)
{
    settype($method, 'integer');

    $retval = [];

    if ($method == 0) {
        $where = '';
        if (isset($consoleID) && $consoleID > 0) {
            $where = "WHERE gd.ConsoleID = $consoleID ";
        }

        //    By TA:
        $query = "    SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, gd.Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.TotalTruePoints, gd.IsFinal, c.Name AS ConsoleName
                    FROM GameData AS gd
                    LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                    $where
                    ORDER BY gd.TotalTruePoints DESC
                    LIMIT $offset, $count";
    } else {
        //?
    }

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

function getTotalUniquePlayers($gameID, $requestedBy)
{
    settype($gameID, 'integer');

    $query = "
        SELECT COUNT(DISTINCT aw.User) As UniquePlayers
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
        WHERE gd.ID = $gameID
          AND (NOT ua.Untracked" . (isset($requestedBy) ? " OR ua.User = '$requestedBy'" : "") . ")
    ";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    $data = mysqli_fetch_assoc($dbResult);
    return $data['UniquePlayers'];
}

function getGameTopAchievers($gameID, $offset, $count, $requestedBy)
{
    $retval = [];

    $query = "SELECT aw.User, SUM(ach.points) AS TotalScore, MAX(aw.Date) AS LastAward
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                WHERE ( !ua.Untracked OR ua.User = '$requestedBy' ) 
                  AND ach.Flags = 3 
                  AND gd.ID = $gameID
                GROUP BY aw.User
                ORDER BY TotalScore DESC, LastAward ASC
                LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

function getGameRankAndScore($gameID, $requestedBy)
{
    if (empty($gameID)) {
        return null;
    }

    if (empty($requestedBy)) {
        return null;
    }
    $retval = [];

    $query = "WITH data
    AS (SELECT aw.User, SUM(ach.points) AS TotalScore, MAX(aw.Date) AS LastAward,
        ROW_NUMBER() OVER (ORDER BY SUM(ach.points) DESC, MAX(aw.Date) ASC) UserRank
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
        WHERE ( !ua.Untracked OR ua.User = '$requestedBy') 
          AND ach.Flags = 3 
          AND gd.ID = $gameID
        GROUP BY aw.User
        ORDER BY TotalScore DESC, LastAward ASC
   ) SELECT * FROM data WHERE User = '$requestedBy'";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

//////////////////////////////////////////////////////////////////////////////////////////
//    Game Title and Alts (Dupe Handling)
//////////////////////////////////////////////////////////////////////////////////////////
function submitAlternativeGameTitle($user, $md5, $gameTitleDest, $consoleID, &$idOut)
{
    if (!isset($md5) || mb_strlen($md5) != 32) {
        //log_email("invalid md5 provided ($md5) by $user, $gameTitleDest");
        return false;
    }

    //    Redirect the given md5 to an existing gameID:
    $idOut = getGameIDFromTitle($gameTitleDest, $consoleID);
    if ($idOut == 0) {
        //log_email("CANNOT find this existing game title! ($user requested $md5 forward to '$gameTitleDest')");
        return false;
    }

    $query = "SELECT COUNT(*) AS NumEntries, GameID FROM GameHashLibrary WHERE MD5='$md5'";
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        if ($data['NumEntries'] == 0) {
            //    Add new name
            $query = "INSERT INTO GameHashLibrary (MD5, GameID) VALUES( '$md5', '$idOut' )";
            // log_sql($query);
            $dbResult = s_mysql_query($query);
            SQL_ASSERT($dbResult);

            if ($dbResult !== false) {
                //error_log( __FUNCTION__ . " success: $user added ($md5, $idOut) to GameHashLibrary" );
                return true;
            } else {
                log_sql_fail();
                // error_log(__FUNCTION__ . " failed INSERT! $user, $md5 and $idOut");
                return false;
            }
        } elseif ($data['NumEntries'] == 1) {
            //    Looks like it's already here?
            $existingRedirTo = $dbResult['GameID'];
            if ($existingRedirTo !== $checksumToRedirTo) {
                //    Update existing redir entry
                $query = "UPDATE GameHashLibrary SET GameID='$idOut' WHERE MD5='$md5'";
                $dbResult = s_mysql_query($query);
                if ($dbResult !== false) {
                    // error_log(__FUNCTION__ . " success: $user updated $md5 from $existingRedirTo to $idOut");
                    return true;
                } else {
                    log_sql_fail();
                    // error_log(__FUNCTION__ . " failed UPDATE! $user, $md5 and $idOut");
                    return false;
                }
            } else {
                //    This exact entry is already here.
                // error_log(__FUNCTION__ . " failed, already exists! $user, $md5 and $idOut");
                return false;
            }
        } else {
            //error_log( __FUNCTION__ . " failed MULTIPLE ENTRIES IN GameHashLibrary! ( " .  $data['NumEntries'] . " ) $user, $md5 and $idOut" );
            //log_email(" failed MULTIPLE ENTRIES IN GameHashLibrary! ( " . $data['NumEntries'] . " ) $user, $md5 and $idOut");
            return false;
        }
    } else {
        log_sql_fail();
        //log_email(__FUNCTION__ . "failed SELECT! $user, $md5 and $idOut");
        return false;
    }
}

function createNewGame($title, $consoleID)
{
    settype($consoleID, 'integer');
    //$title = str_replace( "--", "-", $title );    //    subtle non-comment breaker

    $query = "INSERT INTO GameData (Title, ConsoleID, ForumTopicID, Flags, ImageIcon, ImageTitle, ImageIngame, ImageBoxArt, Publisher, Developer, Genre, Released, IsFinal, RichPresencePatch, TotalTruePoints) 
                            VALUES ('$title', $consoleID, NULL, 0, '/Images/000001.png', '/Images/000002.png', '/Images/000002.png', '/Images/000002.png', NULL, NULL, NULL, NULL, 0, NULL, 0 )";
    // log_sql($query);

    global $db;
    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        $newID = mysqli_insert_id($db);
        static_addnewgame($newID);
        return $newID;
    }

    log_sql_fail();
    // error_log(__FUNCTION__ . "failed ($title)");
    return 0;
}

function submitNewGameTitleJSON($user, $md5, $titleIn, $consoleID)
{
    settype($consoleID, 'integer');

    // error_log(__FUNCTION__ . " called with $user, $md5, $titleIn, $consoleID");

    $retVal = [];
    $retVal['MD5'] = $md5;
    $retVal['ConsoleID'] = $consoleID;
    $retVal['GameID'] = 0;
    $retVal['GameTitle'] = "";
    $retVal['Success'] = true;

    $permissions = getUserPermissions($user);

    if (!isset($user)) {
        $retVal['Error'] = "User doesn't appear to be set or have permissions?";
        $retVal['Success'] = false;
    } elseif (mb_strlen($md5) != 32) {
        // error_log(__FUNCTION__ . " Md5 unready? Ignoring");
        $retVal['Error'] = "MD5 provided ($md5) doesn't appear to be exactly 32 characters, this request is invalid.";
        $retVal['Success'] = false;
    } elseif (mb_strlen($titleIn) < 2) {
        // error_log(__FUNCTION__ . " $user provided a new md5 $md5 for console $consoleID, but provided the title $titleIn. Ignoring");
        $retVal['Error'] = "Cannot submit game title given as '$titleIn'";
        $retVal['Success'] = false;
    } elseif ($consoleID == 0) {
        /**
         * cannot submitGameTitle, $consoleID is 0! What console is this for?
         */
        $retVal['Error'] = "Cannot submit game title, ConsoleID is 0! What console is this for?";
        $retVal['Success'] = false;
    } elseif ($permissions < Permissions::Developer) {
        /**
         * Cannot submit *new* game title, not allowed! User level too low ($user, $permissions)
         */
        $retVal['Error'] = "The ROM you are trying to load is not in the database. Check official forum thread for details about versions of the game which are supported.";
        $retVal['Success'] = false;
    } else {
        $gameID = getGameIDFromTitle($titleIn, $consoleID);
        if ($gameID == 0) {
            //    Remove single quotes, replace with double quotes:
            $title = str_replace("'", "''", $titleIn);
            $title = str_replace("/", "-", $title);
            $title = str_replace("\\", "-", $title);

            /**
             * New Game!
             * The MD5 for this game doesn't yet exist in our DB. Insert a new game:
             */
            $gameID = createNewGame($title, $consoleID);
            if ($gameID !== 0) {
                $query = "INSERT INTO GameHashLibrary (MD5, GameID) VALUES( '$md5', '$gameID' )";
                $dbResult = s_mysql_query($query);
                if ($dbResult !== false) {
                    /**
                     * $user added $md5, $gameID to GameHashLibrary, and $gameID, $title to GameData
                     */
                    $retVal['GameID'] = $gameID;
                    $retVal['GameTitle'] = $title;
                } else {
                    log_sql_fail();
                    $retVal['Error'] = "Failed to add $md5 for '$title'";
                    $retVal['Success'] = false;
                }
            } else {
                /**
                 * cannot create game $title
                 */
                $retVal['Error'] = "Failed to create game title '$title'";
                $retVal['Success'] = false;
            }
        } else {
            /**
             * Adding md5 to an existing title ($gameID)
             */
            $query = "INSERT INTO GameHashLibrary (MD5, GameID) VALUES( '$md5', '$gameID' )";
            $dbResult = s_mysql_query($query);
            if ($dbResult !== false) {
                /**
                 * $user added $md5, $gameID to GameHashLibrary, and $gameID, $titleIn to GameData
                 */
                $retVal['GameID'] = $gameID;
                $retVal['GameTitle'] = $titleIn;
            } else {
                /**
                 * cannot insert duplicate md5 (already present?
                 */
                $retVal['Error'] = "Failed to add duplicate md5 for '$titleIn' (already present?)";
                $retVal['Success'] = false;
            }
        }
    }

    settype($retVal['ConsoleID'], 'integer');
    settype($retVal['GameID'], 'integer');
    return $retVal;
}

function submitGameTitle($user, $md5, $titleIn, $consoleID, &$idOut)
{
    if ($consoleID == 0) {
        // error_log(__FUNCTION__ . " cannot submitGameTitle, $consoleID is 0! What console is this for?");
        return false;
    }

    if (mb_strlen($titleIn) < 2) {
        // error_log(__FUNCTION__ . " $user provided a new md5 $md5 for console $consoleID, but provided the title $titleIn. Ignoring");
        return false;
    }

    $query = "SELECT GameID FROM GameHashLibrary WHERE MD5='$md5'";
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        //    Remove single quotes, replace with double quotes:
        $title = str_replace("'", "''", $titleIn);
        $title = str_replace("/", "-", $title);
        $title = str_replace("\\", "-", $title);
        // error_log(__FUNCTION__ . " about to add $title (was $titleIn)");

        if (mysqli_num_rows($dbResult) == 0) {
            //    The MD5 for this game doesn't yet exist in our DB. Insert a new game:
            $idOut = createNewGame($title, $consoleID);

            if ($idOut !== 0) {
                $query = "INSERT INTO GameHashLibrary (MD5, GameID) VALUES( '$md5', '$idOut' )";
                // log_sql($query);
                $dbResult = s_mysql_query($query);
                if ($dbResult !== false) {
                    // error_log(__FUNCTION__ . " success: $user added $md5, $idOut to GameHashLibrary, and $idOut, $title to GameData");
                    return true;
                } else {
                    log_sql_fail();
                    // error_log(__FUNCTION__ . " failed INSERT! $user, $md5 and $title");
                    return false;
                }
            } else {
                //log_email(__FUNCTION__ . "failed: cannot create game $title.");
            }
        } else {
            // error_log(__FUNCTION__ . " unsupported - submitting a game title for a game that already has an associated title.");
            return false;
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . "failed SELECT! $user, $md5 and $titleIn");
        return false;
    }
}

function requestModifyRichPresence($gameID, $dataIn)
{
    global $db;

    $dataIn = mysqli_real_escape_string($db, $dataIn);

    $query = "UPDATE GameData SET RichPresencePatch='$dataIn' WHERE ID=$gameID";

    global $db;
    $dbResult = mysqli_query($db, $query);
    SQL_ASSERT($dbResult);

    if ($dbResult) {
        // error_log(__FUNCTION__);
        // error_log("$gameID RP is now $dataIn");

        return true;
    } else {
        // error_log(__FUNCTION__);
        // error_log("$gameID - $dataIn");

        return false;
    }
}

function getRichPresencePatch($gameID, &$dataOut)
{
    $query = "SELECT gd.RichPresencePatch FROM GameData AS gd WHERE gd.ID = $gameID ";
    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $dataOut = $data['RichPresencePatch'];
        return true;
    } else {
        return false;
    }
}
