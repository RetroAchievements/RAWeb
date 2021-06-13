<?php

use RA\ActivityType;
use RA\Permissions;

function getAchievementFeedData(
    $id,
    &$titleOut,
    &$descOut,
    &$pointsOut,
    &$badgeFilenameOut,
    &$gameTitleOut,
    &$gameIDOut,
    &$consoleNameOut
) {
    sanitize_sql_inputs($id);
    settype($id, "integer");

    //    Updated: embed gametitle, console
    $query = "SELECT a.Title, a.Description, a.Points, a.BadgeName, g.Title AS GameTitle, g.ID AS GameID, c.Name AS ConsoleName FROM Achievements AS a ";
    $query .= "LEFT JOIN GameData AS g ON g.ID = a.GameID ";
    $query .= "LEFT JOIN Console AS c ON c.ID = g.ConsoleID ";
    $query .= "WHERE a.ID='$id'";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed! $id");
        return false;
    }

    $data = mysqli_fetch_assoc($dbResult);
    if ($data == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed! $id");
        return false;
    }

    $titleOut = $data['Title'];
    $descOut = $data['Description'];
    $pointsOut = $data['Points'];
    $badgeFilenameOut = $data['BadgeName'];
    $gameTitleOut = $data['GameTitle'];
    $gameIDOut = $data['GameID'];
    $consoleNameOut = $data['ConsoleName'];

    return true;
}

function getAchievementTitle($id, &$gameTitleOut, &$gameIDOut)
{
    sanitize_sql_inputs($id);
    settype($id, "integer");

    //    Updated: embed gametitle
    $query = "SELECT a.Title, g.Title AS GameTitle, g.ID as GameID FROM Achievements AS a 
                LEFT JOIN GameData AS g ON g.ID = a.GameID 
                WHERE a.ID = '$id'";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " fail on query (id:$id)");
        return "";
    }

    $data = mysqli_fetch_assoc($dbResult);
    if ($data == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " no results (id:$id)");
        return "";
    }

    $gameTitleOut = $data['GameTitle'];
    $gameIDOut = $data['GameID'];

    return $data['Title'];
}

function GetAchievementData($id)
{
    sanitize_sql_inputs($id);
    settype($id, "integer");
    $query = "SELECT * FROM Achievements WHERE ID=$id";
    $dbResult = s_mysql_query($query);

    if ($dbResult == false || mysqli_num_rows($dbResult) != 1) {
        // log_sql_fail();
        error_log(__FUNCTION__ . " failed: Achievement $id doesn't exist!");

        return null;
    } else {
        return mysqli_fetch_assoc($dbResult);
    }
}

function getAchievementsList($consoleIDInput, $user, $sortBy, $params, $count, $offset, &$dataOut, $achFlags = 3)
{
    return getAchievementsListByDev($consoleIDInput, $user, $sortBy, $params, $count, $offset, $dataOut, $achFlags);
}

function getAchievementsListByDev(
    $consoleIDInput,
    $user,
    $sortBy,
    $params,
    $count,
    $offset,
    &$dataOut,
    $achFlags = 3,
    $dev = null
) {
    sanitize_sql_inputs(
        $consoleIDInput,
        $user,
        $sortBy,
        $params,
        $count,
        $offset,
        $achFlags,
        $dev
    );
    settype($sortBy, 'integer');

    $achCount = 0;

    $innerJoin = "";
    if ($params > 0 && $user !== null) {
        $innerJoin = "LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID AND aw.User = '$user'";
    }

    $query = "SELECT 
                    ach.ID, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.TrueRatio, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.GameID, 
                    gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, gd.ConsoleID, c.Name AS ConsoleName
                FROM Achievements AS ach
                $innerJoin
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID ";

    if (isset($achFlags)) {
        $query .= "WHERE ach.Flags=$achFlags ";
        if ($params == 1) {
            $query .= "AND ( !ISNULL( aw.User ) ) AND aw.HardcoreMode = 0 ";
        }
        if ($params == 2) {
            $query .= "AND ( ISNULL( aw.User ) )  ";
        }
        if (isset($dev)) {
            $query .= "AND ach.Author = '$dev' ";
        }
    } elseif (isset($dev)) {
        $query .= "WHERE ach.Author = '$dev' ";
    }

    if ($params > 0 && $user !== null) {
        $query .= "GROUP BY ach.ID ";
    }

    switch ($sortBy) {
        case 0:
        case 1:
            $query .= "ORDER BY ach.Title ";
            break;
        case 2:
            $query .= "ORDER BY ach.Description ";
            break;
        case 3:
            $query .= "ORDER BY ach.Points, GameTitle ";
            break;
        case 4:
            $query .= "ORDER BY ach.TrueRatio, GameTitle ";
            break;
        case 5:
            $query .= "ORDER BY ach.Author ";
            break;
        case 6:
            $query .= "ORDER BY GameTitle ";
            break;
        case 7:
            $query .= "ORDER BY ach.DateCreated ";
            break;
        case 8:
            $query .= "ORDER BY ach.DateModified ";
            break;
        case 11:
            $query .= "ORDER BY ach.Title DESC ";
            break;
        case 12:
            $query .= "ORDER BY ach.Description DESC ";
            break;
        case 13:
            $query .= "ORDER BY ach.Points DESC, GameTitle ";
            break;
        case 14:
            $query .= "ORDER BY ach.TrueRatio DESC, GameTitle ";
            break;
        case 15:
            $query .= "ORDER BY ach.Author DESC ";
            break;
        case 16:
            $query .= "ORDER BY GameTitle DESC ";
            break;
        case 17:
            $query .= "ORDER BY ach.DateCreated DESC ";
            break;
        case 18:
            $query .= "ORDER BY ach.DateModified DESC ";
            break;
    }

    $query .= "LIMIT $offset, $count ";

    //error_log( $query );
    $dataOut = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$achCount] = $db_entry;
            $achCount++;
        }
    } else {
        // error_log(__FUNCTION__);
        log_sql_fail();
    }

    return $achCount;
}

function GetAchievementMetadataJSON($achID)
{
    sanitize_sql_inputs($achID);
    $retVal = [];
    settype($achID, 'integer');

    $query = "SELECT ach.ID AS AchievementID, ach.GameID, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.TrueRatio,
                ach.Flags, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.DisplayOrder, ach.AssocVideo, ach.MemAddr,
                c.ID AS ConsoleID, c.Name AS ConsoleName, g.Title AS GameTitle, g.ImageIcon AS GameIcon
              FROM Achievements AS ach
              LEFT JOIN GameData AS g ON g.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = g.ConsoleID
              WHERE ach.ID = $achID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
        $retVal = mysqli_fetch_assoc($dbResult);
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed1: ID $achID requested");
    }

    return $retVal;
}

function GetAchievementMetadata($achievementID, &$dataOut)
{
    $dataOut = GetAchievementMetadataJSON($achievementID);
    return count($dataOut) > 0;
}

function getAchievementBadgeFilename($id)
{
    sanitize_sql_inputs($id);

    $query = "SELECT BadgeName FROM Achievements WHERE ID = '$id'";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " bad request for id: $id");
        return "00000";
    }

    $data = mysqli_fetch_assoc($dbResult);
    return $data['BadgeName'];
}

function InsertAwardedAchievementDB($user, $achIDToAward, $isHardcore)
{
    sanitize_sql_inputs($user, $achIDToAward, $isHardcore);

    $query = "INSERT INTO Awarded ( User, AchievementID, Date, HardcoreMode )
              VALUES ( '$user', '$achIDToAward', NOW(), '$isHardcore' )
              ON DUPLICATE KEY
              UPDATE User=User, AchievementID=AchievementID, Date=Date, HardcoreMode=HardcoreMode";

    $dbResult = s_mysql_query($query);
    return $dbResult !== false;
}

function HasAward($user, $achIDToAward)
{
    sanitize_sql_inputs($user, $achIDToAward);

    $retVal = [];
    $retVal['HasRegular'] = false;
    $retVal['HasHardcore'] = false;

    $query = "SELECT HardcoreMode
              FROM Awarded
              WHERE AchievementID = '$achIDToAward' AND User = '$user'";

    $dbResult = s_mysql_query($query);
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        if ($nextData['HardcoreMode'] == 0) {
            $retVal['HasRegular'] = true;
        } elseif ($nextData['HardcoreMode'] == 1) {
            $retVal['HasHardcore'] = true;
        }
    }

    return $retVal;
}

function addEarnedAchievementJSON($user, $achIDToAward, $isHardcore)
{
    sanitize_sql_inputs($user, $achIDToAward, $isHardcore);
    settype($achIDToAward, 'integer');
    settype($isHardcore, 'integer');

    $retVal = [];
    $retVal['Success'] = false;

    if ($achIDToAward <= 0) {
        $retVal['Error'] = "Achievement ID <= 0! Cannot award.";
        return $retVal;
    }

    if (!isValidUsername($user)) {
        $retVal['Error'] = "User is '$user', cannot award achievement.";
        return $retVal;
    }

    $userData = GetUserData($user);
    if (!$userData) {
        $retVal['Error'] = "User data cannot be found for $user";
        return $retVal;
    }

    $achData = GetAchievementMetadataJSON($achIDToAward);
    if (!$achData) {
        $retVal['Error'] = "Achievement data cannot be found for $achIDToAward";
        return $retVal;
    }

    if ($achData['Flags'] == 5) { // do not award Unofficial achievements
        $retVal['Error'] = "Unofficial achievements are not awarded";
        return $retVal;
    }

    $hasAwardTypes = HasAward($user, $achIDToAward);
    $hasRegular = $hasAwardTypes['HasRegular'];
    $hasHardcore = $hasAwardTypes['HasHardcore'];

    if (($isHardcore && $hasHardcore) || (!$isHardcore && $hasRegular)) {
        if ($isHardcore) {
            // XXX: do not change the messages here, as it can interfere with RetroArch's behavior.
            // https://github.com/libretro/RetroArch/blob/343a04e2b8e1a334dda3ad947c284bde9d9d2894/cheevos/cheevos.c#L524
            $retVal['Error'] = "User already has hardcore and regular achievements awarded.";
        } else {
            $retVal['Error'] = "User already has this achievement awarded.";
        }
        return $retVal;
    }

    //error_log( "AddEarnedAchievementJSON, Ready to add" );

    $awardedOK = InsertAwardedAchievementDB($user, $achIDToAward, $isHardcore);
    if ($awardedOK && $isHardcore) {
        $awardedOK |= InsertAwardedAchievementDB($user, $achIDToAward, false);
    }

    if ($awardedOK == false) {
        $retVal['Error'] = "Issues allocating awards for user?";
        return $retVal;
    }

    $pointsToGive = $achData['Points'];
    settype($pointsToGive, 'integer');

    if ($isHardcore && !$hasRegular) {
        //    Double points (award base as well!)
        $pointsToGive *= 2;
    }

    $query = "UPDATE UserAccounts SET RAPoints=RAPoints+$pointsToGive, Updated=NOW() WHERE User='$user'";
    //error_log( $query );
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        $retVal['Error'] = "Could not add points for this user?";
        error_log(__FUNCTION__ . " failed: cannot add new achievement to DB! $user, $achIDToAward");
        return $retVal;
    }

    $retVal['Success'] = true;
    //    Achievements all awarded. Now housekeeping (no error handling?)

    static_setlastearnedachievement($achIDToAward, $user, $achData['Points']);

    if ($user != $achData['Author']) {
        attributeDevelopmentAuthor($achData['Author'], $pointsToGive);
    }

    //    Update GameData
    //    Removed: this needs rethinking! //##SD TBD
    //recalculateTrueRatio( $gameID );    //    Heavy!
    //    Add TA to the player for this achievement, NOW that the TA value has been recalculated
    //    Select the NEW TA from this achievement, as it has just been recalc'd
    $query = "SELECT TrueRatio
              FROM Achievements
              WHERE ID='$achIDToAward'";
    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    $data = mysqli_fetch_assoc($dbResult);
    $newTA = $data['TrueRatio'];
    settype($newTA, 'integer');

    //    Pack back into $achData
    $achData['TrueRatio'] = $newTA;

    $query = "UPDATE UserAccounts
              SET TrueRAPoints=TrueRAPoints+$newTA
              WHERE User='$user'";
    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    postActivity($user, ActivityType::EarnedAchivement, $achIDToAward, $isHardcore);

    testFullyCompletedGame($user, $achIDToAward, $isHardcore);

    return $retVal;
}

function UploadNewAchievement(
    $author,
    $gameID,
    $title,
    $desc,
    $progress,
    $progressMax,
    $progressFmt,
    $points,
    $mem,
    $type,
    &$idInOut,
    $badge,
    &$errorOut
) {
    //    Hack for 'development tutorial game'
    if ($gameID == 10971) {
        $errorOut = "Tutorial: Achievement upload! This reply is happening on the server, to say that we have successfully received your achievement data.";
        return false;
    }

    if ($type == 3 && !isValidConsoleId(getGameData($gameID)['ConsoleID'])) {
        $errorOut = "You cannot promote achievements for a game from an unsupported console (console ID: " . getGameData($gameID)['ConsoleID'] . ").";
        return false;
    }

    $title = str_replace("'", "''", $title);
    $desc = str_replace("'", "''", $desc);
    $title = str_replace("/", "_", $title);
    $desc = str_replace("/", "_", $desc);
    $title = str_replace("\\", "_", $title);
    $desc = str_replace("\\", "_", $desc);
    $title = preg_replace('/[^\x20-\x7e]/', '_', $title);
    $desc = preg_replace('/[^\x20-\x7e]/', '_', $desc);

    //    Assume authorised!
    if (!isset($idInOut) || $idInOut == 0) {
        $query = "
            INSERT INTO Achievements (
                ID, GameID, Title, Description,
                MemAddr, Progress, ProgressMax,
                ProgressFormat, Points, Flags,
                Author, DateCreated, DateModified,
                Updated, VotesPos, VotesNeg,
                BadgeName, DisplayOrder, AssocVideo,
                TrueRatio
            )
            VALUES (
                NULL, '$gameID', '$title', '$desc',
                '$mem', '$progress', '$progressMax',
                '$progressFmt', '$points', '$type',
                '$author', NOW(), NOW(),
                NOW(), 0, 0,
                '$badge', 0, NULL,
                0
            )";
        // log_sql($query);
        if (s_mysql_query($query) !== false) {
            global $db;
            $idInOut = mysqli_insert_id($db);
            postActivity($author, ActivityType::UploadAchievement, $idInOut);

            static_addnewachievement($idInOut);
            addArticleComment(
                "Server",
                \RA\ArticleType::Achievement,
                $idInOut,
                "$author uploaded this achievement.",
                $author
            );

            // error_log(__FUNCTION__ . " $author uploaded new achievement: $idInOut, $title, $desc, $progress, $progressMax, $progressFmt, $points, $mem, $type, $badge");

            return true;
        } else {
            log_sql_fail();
            // error_log(__FUNCTION__ . " failed: gameID:$gameID title:$title desc:$desc points:$points mem:$mem type:$type");

            return false;
        }
    } else {
        $query = "SELECT Flags, Points FROM Achievements WHERE ID='$idInOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);

            $changingAchSet = ($data['Flags'] != $type);
            $changingPoints = ($data['Points'] != $points);

            //if( ( $changingAchSet || $changingPoints ) && $type == 3 )
            if ($type == 3 || $changingAchSet) {
                $userPermissions = getUserPermissions($author);
                // error_log("changing ach set detected; user is $author, permissions is $userPermissions, target set is $type");
                if ($userPermissions < Permissions::Developer) {
                    //  Must be developer to modify core!
                    $errorOut = "You must be a developer to modify values in Core! Please drop a message in the forums to apply.";
                    return false;
                }
            }

            $query = "UPDATE Achievements SET Title='$title', Description='$desc', Progress='$progress', ProgressMax='$progressMax', ProgressFormat='$progressFmt', MemAddr='$mem', Points=$points, Flags=$type, DateModified=NOW(), Updated=NOW(), BadgeName='$badge' WHERE ID=$idInOut";
            // log_sql($query);

            if (s_mysql_query($query) !== false) {
                if ($changingAchSet || $changingPoints) {
                    // When changing achievement set, all existing achievements that rely on this should be purged.
                    //$query = "DELETE FROM Awarded WHERE ID='$idInOut'";
                    //error_log( $query );
                    // nah, that's a bit harsh... esp if you're changing something tiny like the badge!!

                    // if (s_mysql_query($query) !== false) {
                    //     global $db;
                    //     $rowsAffected = mysqli_affected_rows($db);
                    //     error_log( __FUNCTION__ . " removed $rowsAffected rows in Achieved" );
                    //     //great
                    // } else {
                    //     //meh
                    // }
                }

                static_setlastupdatedgame($gameID);
                static_setlastupdatedachievement($idInOut);

                postActivity($author, ActivityType::EditAchievement, $idInOut);

                if ($changingAchSet) {
                    if ($type == 3) {
                        addArticleComment(
                            "Server",
                            \RA\ArticleType::Achievement,
                            $idInOut,
                            "$author promoted this achievement to the Core set.",
                            $author
                        );
                    } elseif ($type == 5) {
                        addArticleComment(
                            "Server",
                            \RA\ArticleType::Achievement,
                            $idInOut,
                            "$author demoted this achievement to Unofficial.",
                            $author
                        );
                    }
                } else {
                    addArticleComment(
                        "Server",
                        \RA\ArticleType::Achievement,
                        $idInOut,
                        "$author edited this achievement.",
                        $author
                    );
                }

                return true;
            } else {
                log_sql_fail();
                // error_log(__FUNCTION__ . " 3failed: gameID:$gameID title:$title desc:$desc points:$points mem:$mem type:$type ID:$idInOut");

                return false;
            }
        } else {
            // error_log(__FUNCTION__ . " 2failed: ach to update doesn't exist? gameID:$gameID title:$title desc:$desc points:$points mem:$mem type:$type ID:$idInOut");

            return false;
        }
    }
}

function resetAchievements($user, $gameID)
{
    sanitize_sql_inputs($user, $gameID);
    settype($gameID, 'integer');

    $query = "DELETE FROM Awarded WHERE User='$user' ";

    if (!empty($gameID) && $gameID > 0) {
        $query .= "AND AchievementID IN ( SELECT ID FROM Achievements WHERE Achievements.GameID='$gameID' )";
    }

    $numRowsDeleted = 0;
    // log_sql($query);
    if (s_mysql_query($query) !== false) {
        global $db;
        $numRowsDeleted = mysqli_affected_rows($db);
    } else {
        // error_log(__FUNCTION__ . " Delete op failed (no permissions?)!");
        //echo "Delete op failed (no permissions?)!<br>";
    }

    recalcScore($user);
    return $numRowsDeleted;
}

function resetSingleAchievement($user, $achID)
{
    sanitize_sql_inputs($user, $achID);

    if (empty($achID)) {
        return false;
    }
    settype($achID, 'integer');

    $query = "DELETE FROM Awarded WHERE User='$user' AND AchievementID='$achID'";
    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed?! $user, $achID");
    }

    recalcScore($user);
    return true;
}

function getRecentlyEarnedAchievements($count, $user, &$dataOut)
{
    sanitize_sql_inputs($count, $user);
    settype($count, 'integer');

    $query = "SELECT aw.User, aw.Date AS DateAwarded, aw.AchievementID, ach.Title, ach.Description, ach.BadgeName, ach.Points, ach.GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleTitle
               FROM Awarded AS aw
               LEFT JOIN Achievements ach ON aw.AchievementID = ach.ID
               LEFT JOIN GameData gd ON ach.GameID = gd.ID
               LEFT JOIN Console AS c ON c.ID = gd.ConsoleID ";

    if (isset($user) && $user !== false) {
        $query .= "WHERE User='$user' AND HardcoreMode=0 ";
    } else {
        $query .= "WHERE HardcoreMode=0 ";
    }

    $query .= "ORDER BY Date DESC
                LIMIT 0, $count";

    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: no achievements found: count:$count user:$user query:$query");
        return 0;
    } else {
        $i = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$i] = $db_entry;
            $i++;
        }
        return $i;
    }
}

function GetAchievementsPatch($gameID, $flags)
{
    sanitize_sql_inputs($gameID, $flags);
    settype($gameID, 'integer');
    settype($flags, 'integer');

    $retVal = [];

    $flagsCond = "TRUE";
    if ($flags != 0) {
        $flagsCond = "Flags='$flags'";
    }

    $query = "SELECT ID, MemAddr, Title, Description, Points, Author, UNIX_TIMESTAMP(DateModified) AS Modified, UNIX_TIMESTAMP(DateCreated) AS Created, BadgeName, Flags
              FROM Achievements
              WHERE GameID='$gameID' AND $flagsCond
              ORDER BY DisplayOrder";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            settype($db_entry['ID'], 'integer');
            settype($db_entry['Points'], 'integer');
            settype($db_entry['Modified'], 'integer');
            settype($db_entry['Created'], 'integer');
            settype($db_entry['Flags'], 'integer');

            $retVal[] = $db_entry;
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: gameID:$gameID flags:$flags");
    }

    return $retVal;
}

function GetPatchData($gameID, $flags, $user)
{
    sanitize_sql_inputs($gameID, $flags, $user);
    settype($gameID, 'integer');
    settype($flags, 'integer');

    $retVal = [];

    if ($gameID == 0) {
        // error_log(__FUNCTION__ . " cannot lookup game with gameID $gameID for user $user");
    } else {
        //$retVal['MinVer'] = "0.049";
        $retVal = array_merge(getGameData($gameID));

        $retVal['Achievements'] = GetAchievementsPatch($gameID, $flags);
        $retVal['Leaderboards'] = GetLBPatch($gameID);
    }
    return $retVal;
}

function getPatch($gameID, $flags, $user, $andLeaderboards)
{
    sanitize_sql_inputs($gameID, $flags, $user);
    settype($gameID, 'integer');
    settype($flags, 'integer');

    getGameTitleFromID($gameID, $gameTitle, $consoleID, $consoleName, $forumTopicID, $gameData);

    $minVer = "0.001";
    if ($consoleID == 1) { //    Mega Drive
        $minVer = "0.042";
    } //"0.028";
    elseif ($consoleID == 2) { //    N64
        $minVer = "0.008";
    } //??
    elseif ($consoleID == 3) { //    SNES
        $minVer = "0.008";
    }

    echo $minVer . "\n";
    echo $gameTitle . "\n";

    if ($gameID == 0) {
        // error_log(__FUNCTION__ . " cannot lookup game with gameID $gameID for user $user");
        return false;
    }

    $query = "SELECT ID, MemAddr, Title, Description, Progress, ProgressMax, ProgressFormat, Points, Author, DateModified, DateCreated, VotesPos, VotesNeg, BadgeName FROM Achievements ";
    $query .= "WHERE GameID='$gameID' AND Flags='$flags' ";
    $query .= "ORDER BY DisplayOrder";

    global $db;

    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            //    Unix time
            $dateStr = strtotime($db_entry["DateCreated"]);
            $dateCreatedSecs = date("U", $dateStr);
            $dateStr = strtotime($db_entry["DateModified"]);
            $dateModifiedSecs = date("U", $dateStr);

            $points = $db_entry["Points"];
            settype($points, 'integer');

            if (!isset($db_entry["Author"])) {
                $db_entry["Author"] = "Unknown";
            }

            if (!isset($db_entry["Progress"]) || $db_entry["Progress"] == '') {
                $db_entry["Progress"] = ' ';
            }
            if (!isset($db_entry["ProgressMax"]) || $db_entry["ProgressMax"] == '') {
                $db_entry["ProgressMax"] = ' ';
            }
            if (!isset($db_entry["ProgressFormat"]) || $db_entry["ProgressFormat"] == '') {
                $db_entry["ProgressFormat"] = ' ';
            }

            echo $db_entry["ID"] . ":" .
                $db_entry["MemAddr"] . ":" .
                $db_entry["Title"] . ":" .
                $db_entry["Description"] . ":" .
                $db_entry["Progress"] . ":" .
                $db_entry["ProgressMax"] . ":" .
                $db_entry["ProgressFormat"] . ":" .
                $db_entry["Author"] . ":" .
                $points . ":" .
                $dateCreatedSecs . ":" .
                $dateModifiedSecs . ":" .
                $db_entry["VotesPos"] . ":" .
                $db_entry["VotesNeg"] . ":" .
                $db_entry["BadgeName"];

            echo "\n";
        }

        //if( $flags == 3 ) //core
        //    postActivity( $user, 3, $title );

        if ($andLeaderboards) {
            $query = "SELECT ld.ID, ld.Mem, ld.Format, ld.Title, ld.Description
                      FROM LeaderboardDef AS ld
                      WHERE ld.GameID = $gameID
                      ORDER BY ld.DisplayOrder, ld.ID ";

            global $db;
            $dbResult = mysqli_query($db, $query);
            if ($dbResult !== false) {
                while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                    echo 'L' . $db_entry['ID'] .
                        '::' . $db_entry['Mem'] .
                        '::FOR:' . $db_entry['Format'] .
                        '::TTL:' . $db_entry['Title'] .
                        '::DES:' . $db_entry['Description'];

                    echo "\n";
                }
            } else {
                //    No leaderboards found: this is probably normal.
            }
        }

        return true;
    } else {
        log_sql_fail();
        return false;
    }
}

function updateAchievementDisplayID($achID, $newID)
{
    sanitize_sql_inputs($achID, $newID);

    $query = "UPDATE Achievements SET DisplayOrder = $newID, Updated=NOW() WHERE ID = $achID";
    $dbResult = s_mysql_query($query);

    return $dbResult !== false;
}

function updateAchievementEmbedVideo($achID, $newURL)
{
    $newURL = strip_tags($newURL);
    sanitize_sql_inputs($achID, $newURL);

    $query = "UPDATE Achievements SET AssocVideo = '$newURL', Updated=NOW() WHERE ID = $achID";

    global $db;
    $dbResult = mysqli_query($db, $query);

    return $dbResult !== false;
}

function updateAchievementFlags($achID, $newFlags)
{
    $achievementIDs = is_array($achID) ? implode(', ', $achID) : $achID;

    sanitize_sql_inputs($achievementIDs, $newFlags);

    $query = "UPDATE Achievements SET Flags = '$newFlags', Updated=NOW() WHERE ID IN (" . $achievementIDs . ")";

    global $db;
    $dbResult = mysqli_query($db, $query);

    return $dbResult !== false;
}

function getCommonlyEarnedAchievements($consoleID, $offset, $count, &$dataOut)
{
    sanitize_sql_inputs($consoleID, $offset, $count);

    $subquery = "";
    if (isset($consoleID) && $consoleID > 0) {
        $subquery = "WHERE cons.ID = $consoleID ";
    }

    $query = "SELECT COALESCE(aw.cnt,0) AS NumTimesAwarded, ach.Title AS AchievementTitle, ach.ID, ach.Description, ach.Points, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, gd.ID AS GameID, cons.Name AS ConsoleName
            FROM Achievements AS ach
            LEFT OUTER JOIN (SELECT AchievementID, count(*) cnt FROM Awarded GROUP BY AchievementID) aw ON ach.ID = aw.AchievementID
            LEFT JOIN GameData gd ON gd.ID = ach.GameID
            LEFT JOIN Console AS cons ON cons.ID = gd.ConsoleID
            $subquery
            GROUP BY ach.ID
            ORDER BY NumTimesAwarded DESC
            LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = [];
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[] = $db_entry;
        }
        return true;
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: consoleID:$consoleID offset:$offset, count:$count");
        //log_email("$offset... $count - " . $query);
        return true;
    }
}

function getAchievementWonData(
    $achID,
    &$numWinners,
    &$numPossibleWinners,
    &$numRecentWinners,
    &$winnerInfo,
    $user,
    $offset = 0,
    $limit = 50
) {
    sanitize_sql_inputs($achID, $user, $offset, $limit);

    $winnerInfo = [];

    $query = "
        SELECT ach.GameID, COUNT(tracked_aw.AchievementID) AS NumEarned
        FROM Achievements AS ach
        LEFT JOIN (
            SELECT aw.AchievementID
            FROM Awarded AS aw
            INNER JOIN UserAccounts AS ua ON ua.User = aw.User
            WHERE aw.AchievementID = $achID AND aw.HardcoreMode = 0
              AND (NOT ua.Untracked OR ua.User = \"$user\")
        ) AS tracked_aw ON tracked_aw.AchievementID = ach.ID
        WHERE ach.ID = $achID
    ";
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        return false;
    }

    $data = mysqli_fetch_assoc($dbResult);
    $numWinners = $data['NumEarned'];
    $gameID = $data['GameID'];   //    Grab GameID at this point

    $numPossibleWinners = getTotalUniquePlayers($gameID, $user);

    $numRecentWinners = 0;

    // Get recent winners, and their most recent activity:
    $query = "SELECT aw.User, ua.RAPoints, aw.Date AS DateAwarded, aw.HardcoreMode
              FROM Awarded AS aw
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE ( !ua.Untracked || ua.User = \"$user\" ) AND AchievementID=$achID
              ORDER BY aw.Date DESC";

    // double limit amount - still not correct this way
    $query .= " LIMIT $offset, " . ($limit * 2);

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            if (isset($winnerInfo[$db_entry['User']]) && $winnerInfo[$db_entry['User']]['HardcoreMode'] == 1) {
                //    Prefer this value
                continue;
            }

            // This will overwrite hardcore if found, in order; meaning the result will be
            // either hardcore has been earned ever, or not at all by this user
            $winnerInfo[$db_entry['User']] = $db_entry;
            $numRecentWinners++;
        }
    }

    if ($user !== null && !array_key_exists($user, $winnerInfo)) {
        // Do the same again if I wasn't found:
        $query = "SELECT aw.User, aw.Date AS DateAwarded, aw.HardcoreMode
                  FROM Awarded AS aw
                  LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                  WHERE aw.AchievementID=$achID AND aw.User='$user'
                  ORDER BY aw.Date DESC, HardcoreMode ASC";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $winnerInfo[$db_entry['User']] = $db_entry;
                $numRecentWinners++;
            }
        }
    }

    return true;
}

function getAchievementRecentWinnersData($achID, $offset, $count, $user = null, $friendsOnly = null)
{
    sanitize_sql_inputs($achID, $offset, $count, $user, $friendsOnly);

    $retVal = [];

    //    Fetch the number of times this has been earned whatsoever (excluding hardcore)
    $query = "SELECT COUNT(*) AS NumEarned, ach.GameID
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              WHERE AchievementID=$achID AND aw.HardcoreMode = 0";

    $dbResult = s_mysql_query($query);
    $data = mysqli_fetch_assoc($dbResult);

    $retVal['NumEarned'] = $data['NumEarned'];
    settype($retVal['NumEarned'], 'integer');
    $retVal['GameID'] = $data['GameID'];
    settype($retVal['GameID'], 'integer');

    //    Fetch the total number of players for this game:
    $retVal['TotalPlayers'] = getGameNumUniquePlayersByAwards($retVal['GameID']);
    settype($retVal['TotalPlayers'], 'integer');

    $extraWhere = "";
    if (isset($friendsOnly) && $friendsOnly && isset($user) && $user) {
        $extraWhere = " AND aw.User IN ( SELECT Friend FROM Friends WHERE User = '$user' ) ";
    }

    //    Get recent winners, and their most recent activity:
    $query = "SELECT aw.User, ua.RAPoints, UNIX_TIMESTAMP(aw.Date) AS DateAwarded
              FROM Awarded AS aw
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE AchievementID=$achID AND aw.HardcoreMode = 0 $extraWhere
              ORDER BY aw.Date DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        //settype( $db_entry['HardcoreMode'], 'integer' );
        settype($db_entry['RAPoints'], 'integer');
        settype($db_entry['DateAwarded'], 'integer');
        $retVal['RecentWinner'][] = $db_entry;
    }

    return $retVal;
}

function getGameNumUniquePlayersByAwards($gameID)
{
    sanitize_sql_inputs($gameID);

    $query = "SELECT MAX( Inner1.MaxAwarded ) AS TotalPlayers FROM
              (
                  SELECT ach.ID, COUNT(*) AS MaxAwarded
                  FROM Awarded AS aw
                  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                  LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                  WHERE gd.ID = $gameID AND aw.HardcoreMode = 0
                  GROUP BY ach.ID
              ) AS Inner1";

    $dbResult = s_mysql_query($query);
    $data = mysqli_fetch_assoc($dbResult);

    return $data['TotalPlayers'];
}

function recalculateTrueRatio($gameID)
{
    sanitize_sql_inputs($gameID);

    $query = "SELECT ach.ID, ach.Points, COUNT(*) AS NumAchieved
              FROM Achievements AS ach
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
              WHERE ach.GameID = $gameID AND ach.Flags = 3 AND aw.HardcoreMode = 0
              GROUP BY ach.ID";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    if ($dbResult !== false) {
        $achData = [];
        $totalEarners = 0;
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $achData[$nextData['ID']] = $nextData;
            if ($nextData['NumAchieved'] > $totalEarners) {
                $totalEarners = $nextData['NumAchieved'];
            }

            //error_log( "Added " . $achData[ $nextData['ID'] ]['ID'] );
        }

        if ($totalEarners == 0) { // force all unachieved to be 1
            $totalEarners = 1;
        }

        $ratioTotal = 0;

        foreach ($achData as $nextAch) {
            $achID = $nextAch['ID'];
            $achPoints = $nextAch['Points'];
            $numAchieved = $nextAch['NumAchieved'];

            if ($numAchieved == 0) { // force all unachieved to be 1
                $numAchieved = 1;
            }

            $ratioFactor = 0.4;
            $newTrueRatio = ($achPoints * (1.0 - $ratioFactor)) + ($achPoints * (($totalEarners / $numAchieved) * $ratioFactor));
            $trueRatio = (int) $newTrueRatio;

            $ratioTotal += $trueRatio;

            $query = "UPDATE Achievements AS ach
                      SET ach.TrueRatio = $trueRatio
                      WHERE ach.ID = $achID";
            s_mysql_query($query);

            //error_log( "TA: $achID -> $trueRatio" );
        }

        $query = "UPDATE GameData AS gd
                  SET gd.TotalTruePoints = $ratioTotal
                  WHERE gd.ID = $gameID";
        s_mysql_query($query);

        //error_log( __FUNCTION__ . " RECALCULATED " . count($achData) . " achievements for game ID $gameID ($ratioTotal)" );

        return true;
    } else {
        return false;
    }
}

/**
 * Gets the number of softcore and hardcore awards for an achieveemnt since a given time.
 *
 * @param int $id achievement to gets awards count for
 * @param string $date the date to get awards count since
 */
function getAwardsSince($id, $date): array
{
    sanitize_sql_inputs($id, $date);
    settype($id, "integer");
    settype($date, "string");

    $query = "
        SELECT
            COALESCE(SUM(CASE WHEN HardcoreMode = 0 THEN 1 ELSE 0 END), 0) AS softcoreCount,
            COALESCE(SUM(CASE WHEN HardcoreMode = 1 THEN 1 ELSE 0 END), 0) AS hardcoreCount
        FROM
            Awarded
        WHERE
            AchievementID = $id
        AND
            Date > '$date'";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    } else {
        return [
            'softcoreCount' => 0,
            'hardcoreCount' => 0,
        ];
    }
}

/**
 * Gets the number of achievements made by the user for each console they have worked on.
 *
 * @param string $user to get achievement data for
 * @return array of achievement count per console
 */
function getUserAchievemetnsPerConsole($user)
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT COUNT(a.GameID) AS AchievementCount, c.Name AS ConsoleName
              FROM Achievements as a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = '$user'
              AND a.Flags = '3'
              AND gd.ConsoleID NOT IN (100, 101)
              GROUP BY ConsoleName
              ORDER BY AchievementCount DESC, ConsoleName";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

/**
 * Gets the number of sets worked on by the user for each console they have worked on.
 *
 * @param string $user to get set data for
 * @return array of set count per console
 */
function getUserSetsPerConsole($user)
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT COUNT(DISTINCT(a.GameID)) AS SetCount, c.Name AS ConsoleName
              FROM Achievements AS a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = '$user'
              AND a.Flags = '3'
              AND gd.ConsoleID NOT IN (100, 101)
              GROUP BY ConsoleName
              ORDER BY SetCount DESC, ConsoleName";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

/**
 * Gets information for all achievements made by the user.
 *
 * @param string $user to get achievement data for
 * @return array of achievement data
 */
function getUserAchievementInformation($user)
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT c.Name AS ConsoleName, a.ID, a.GameID, a.Title, a.Description, a.BadgeName, a.Points, a.TrueRatio, a.Author, a.DateCreated, gd.Title AS GameTitle, LENGTH(a.MemAddr) AS MemLength, ua.ContribCount, ua.ContribYield
              FROM Achievements AS a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.User = '$user'
              WHERE Author LIKE '$user'
              AND a.Flags = '3'
              AND gd.ConsoleID NOT IN (100, 101)
              ORDER BY a.DateCreated";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

/**
 * Gets the number of time the user has obtained (softcore and hardcore) their own achievements.
 *
 * @param string $user to get obtained achievement data for
 * @return array|null of obtained achievement data
 */
function getOwnAchievementsObtained($user)
{
    sanitize_sql_inputs($user);

    $query = "SELECT 
              SUM(CASE WHEN aw.HardcoreMode = 0 THEN 1 ELSE 0 END) AS SoftcoreCount,
              SUM(CASE WHEN aw.HardcoreMode = 1 THEN 1 ELSE 0 END) AS HardcoreCount
              FROM Achievements AS a
              LEFT JOIN Awarded AS aw ON aw.AchievementID = a.ID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author LIKE '$user'
              AND aw.User LIKE '$user'
              AND a.Flags = '3'
              AND gd.ConsoleID NOT IN (100, 101)";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    } else {
        return null;
    }
}

/**
 * Gets data for other users that have earned achievemetns for the input user.
 *
 * @param string $user to get obtained achievement data for
 * @return array of achievement obtainer data
 */
function getObtainersOfSpecificUser($user)
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT aw.User, COUNT(aw.User) AS ObtainCount,
              SUM(CASE WHEN aw.HardcoreMode = 0 THEN 1 ELSE 0 END) AS SoftcoreCount,
              SUM(CASE WHEN aw.HardcoreMode = 1 THEN 1 ELSE 0 END) AS HardcoreCount
              FROM Achievements AS a
              LEFT JOIN Awarded AS aw ON aw.AchievementID = a.ID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE a.Author LIKE '$user'
              AND aw.User NOT LIKE '$user'
              AND a.Flags = '3'
              AND gd.ConsoleID NOT IN (100, 101)
              AND Untracked = 0
              GROUP BY aw.User
              ORDER BY ObtainCount DESC";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

/**
 * Gets recently obtained achievements created by the user.
 *
 * @param array $achievementIDs array of achievement IDs
 * @param int $offset starting point to return items
 * @param int $count number of items to return
 * @return array of recently obtained achievements
 */
function getRecentObtainedAchievements($achievementIDs, $offset = 0, $count = 200)
{
    $achievementIDs = implode(",", $achievementIDs);
    sanitize_sql_inputs($achievementIDs, $offset, $count);

    $retVal = [];
    $query = "SELECT aw.User, c.Name AS ConsoleName, aw.Date, aw.AchievementID, a.GameID, aw.HardcoreMode, a.Title, a.Description, a.BadgeName, a.Points, a.TrueRatio, gd.Title AS GameTitle, gd.ImageIcon as GameIcon
              FROM Awarded AS aw
              LEFT JOIN Achievements as a ON a.ID = aw.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE aw.AchievementID IN (" . $achievementIDs . ")
              AND gd.ConsoleID NOT IN (100, 101)
              ORDER BY aw.Date DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

/**
 * Gets a list of users who have won a achievmenet or list of achievements within a given timerange.
 *
 * @param array $achievementIDs Achievement ID or array of achievement IDs
 * @param string $startTime starting point to return items
 * @param string $endTime number of items to return
 * @param int $hardcoreMode get hardcore winners
 * @return array of of winners for each input achievement ID
 */
function getWinnersOfAchievements($achievementIDs, $startTime, $endTime, $hardcoreMode): array
{
    if (empty($achievementIDs)) {
        return [];
    }

    $dateQuery = "";
    if (strtotime($startTime)) {
        if (strtotime($endTime)) {
            //valid start and end
            $dateQuery = "AND aw.Date BETWEEN '$startTime' AND '$endTime'";
        } else {
            //valid start, invalid end
            $dateQuery = "AND aw.Date >= '$startTime'";
        }
    } else {
        if (strtotime($endTime)) {
            //invalid start, valid end
            $dateQuery = "AND aw.Date <= '$endTime'";
        } else {
            //invalid start and end
            //no date query needed
        }
    }

    $userArray = [];
    foreach ($achievementIDs as $nextID) {
        $query = "SELECT aw.User
                      FROM Awarded AS aw
                      LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                      WHERE aw.AchievementID = '$nextID'
                      AND aw.HardcoreMode = '$hardcoreMode'
                      AND ua.Untracked = 0
                      $dateQuery";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $userArray[$nextID][] = $db_entry['User'];
            }
        }
    }
    return $userArray;
}
