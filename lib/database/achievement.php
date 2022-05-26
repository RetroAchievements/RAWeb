<?php

use RA\AchievementType;
use RA\ActivityType;
use RA\ArticleType;
use RA\Permissions;

function getAchievementTitle($id, &$gameTitleOut, &$gameIDOut): string
{
    sanitize_sql_inputs($id);
    settype($id, "integer");

    // Updated: embed game title
    $query = "SELECT a.Title, g.Title AS GameTitle, g.ID as GameID FROM Achievements AS a 
                LEFT JOIN GameData AS g ON g.ID = a.GameID 
                WHERE a.ID = '$id'";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
        return "";
    }

    $data = mysqli_fetch_assoc($dbResult);
    if (!$data) {
        log_sql_fail();
        return "";
    }

    $gameTitleOut = $data['GameTitle'];
    $gameIDOut = $data['GameID'];

    return $data['Title'];
}

function GetAchievementData($id): ?array
{
    sanitize_sql_inputs($id);
    settype($id, "integer");
    $query = "SELECT * FROM Achievements WHERE ID=$id";
    $dbResult = s_mysql_query($query);

    if (!$dbResult || mysqli_num_rows($dbResult) != 1) {
        error_log(__FUNCTION__ . " failed: Achievement $id doesn't exist!");

        return null;
    } else {
        return mysqli_fetch_assoc($dbResult);
    }
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
): int {
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
        if ($sortBy == 4) {
            $query .= "AND ach.TrueRatio > 0 ";
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
            $query .= "ORDER BY ach.TrueRatio, ach.Points DESC, GameTitle ";
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
            $query .= "ORDER BY ach.TrueRatio DESC, ach.Points, GameTitle ";
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

    $dataOut = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$achCount] = $db_entry;
            $achCount++;
        }
    } else {
        log_sql_fail();
    }

    return $achCount;
}

function GetAchievementMetadataJSON($achID): ?array
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
        return mysqli_fetch_assoc($dbResult);
    }
    log_sql_fail();

    return null;
}

function GetAchievementMetadata($achievementID, &$dataOut): bool
{
    $dataOut = GetAchievementMetadataJSON($achievementID);

    return !empty($dataOut);
}

function InsertAwardedAchievementDB($user, $achIDToAward, $isHardcore): bool
{
    sanitize_sql_inputs($user, $achIDToAward, $isHardcore);
    settype($isHardcore, 'integer');

    $query = "INSERT INTO Awarded ( User, AchievementID, Date, HardcoreMode )
              VALUES ( '$user', '$achIDToAward', NOW(), '$isHardcore' )
              ON DUPLICATE KEY
              UPDATE User=User, AchievementID=AchievementID, Date=Date, HardcoreMode=HardcoreMode";

    $dbResult = s_mysql_query($query);
    return $dbResult !== false;
}

function HasAward($user, $achIDToAward): array
{
    sanitize_sql_inputs($user, $achIDToAward);

    $retVal = [];
    $retVal['HasRegular'] = false;
    $retVal['HasHardcore'] = false;
    $retVal['RegularDate'] = null;
    $retVal['HardcoreDate'] = null;

    $query = "SELECT HardcoreMode, Date
              FROM Awarded
              WHERE AchievementID = '$achIDToAward' AND User = '$user'";

    $dbResult = s_mysql_query($query);
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        if ($nextData['HardcoreMode'] == 0) {
            $retVal['HasRegular'] = true;
            $retVal['RegularDate'] = $nextData['Date'];
        } elseif ($nextData['HardcoreMode'] == 1) {
            $retVal['HasHardcore'] = true;
            $retVal['HardcoreDate'] = $nextData['Date'];
        }
    }

    return $retVal;
}

function addEarnedAchievementJSON($user, $achIDToAward, $isHardcore): array
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

    if ((int) $achData['Flags'] === AchievementType::UNOFFICIAL) { // do not award Unofficial achievements
        $retVal['Error'] = "Unofficial achievements are not awarded";
        return $retVal;
    }

    $hasAwardTypes = HasAward($user, $achIDToAward);
    $hasRegular = $hasAwardTypes['HasRegular'];
    $hasHardcore = $hasAwardTypes['HasHardcore'];
    $alreadyAwarded = $isHardcore ? $hasHardcore : $hasRegular;

    $awardedOK = true;
    if ($isHardcore && !$hasHardcore) {
        $awardedOK &= InsertAwardedAchievementDB($user, $achIDToAward, true);
    }
    if (!$hasRegular && $awardedOK) {
        $awardedOK &= InsertAwardedAchievementDB($user, $achIDToAward, false);
    }

    if (!$awardedOK) {
        $retVal['Error'] = "Issues allocating awards for user";
        return $retVal;
    }

    if (!$alreadyAwarded) {
        // testFullyCompletedGame could post a mastery notification. make sure to post
        // the achievement unlock notification first
        postActivity($user, ActivityType::EarnedAchievement, $achIDToAward, $isHardcore);
    }

    $completion = testFullyCompletedGame($achData['GameID'], $user, $isHardcore, !$alreadyAwarded);
    if (array_key_exists('NumAwarded', $completion)) {
        $retVal['AchievementsRemaining'] = $completion['NumAch'] - $completion['NumAwarded'];
    }

    if ($alreadyAwarded) {
        // XXX: do not change the messages here. the client detects them and does not report
        // them as errors.
        if ($isHardcore) {
            $retVal['Error'] = "User already has hardcore and regular achievements awarded.";
        } else {
            $retVal['Error'] = "User already has this achievement awarded.";
        }

        return $retVal;
    }

    $pointsToGive = $achData['Points'];
    settype($pointsToGive, 'integer');

    if ($isHardcore && !$hasRegular) {
        // Double points (award base as well!)
        $pointsToGive *= 2;
    }

    $query = "UPDATE UserAccounts SET RAPoints=RAPoints+$pointsToGive, Updated=NOW() WHERE User='$user'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        $retVal['Error'] = "Could not add points for this user?";
        error_log(__FUNCTION__ . " failed: cannot add new achievement to DB! $user, $achIDToAward");
        return $retVal;
    }

    $retVal['Success'] = true;
    // Achievements all awarded. Now housekeeping (no error handling?)

    static_setlastearnedachievement($achIDToAward, $user, $achData['Points']);

    if ($user != $achData['Author']) {
        attributeDevelopmentAuthor($achData['Author'], $pointsToGive);
    }

    // Update GameData
    // Removed: this needs rethinking! //##SD TBD
    // recalculateTrueRatio( $gameID );    // Heavy!
    // Add TA to the player for this achievement, NOW that the TA value has been recalculated
    // Select the NEW TA from this achievement, as it has just been recalculated
    $query = "SELECT TrueRatio
              FROM Achievements
              WHERE ID='$achIDToAward'";
    $dbResult = s_mysql_query($query);

    $data = mysqli_fetch_assoc($dbResult);
    $newTA = $data['TrueRatio'];
    settype($newTA, 'integer');

    // Pack back into $achData
    $achData['TrueRatio'] = $newTA;

    $query = "UPDATE UserAccounts
              SET TrueRAPoints=TrueRAPoints+$newTA
              WHERE User='$user'";
    $dbResult = s_mysql_query($query);

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
    int $type,
    &$idInOut,
    $badge,
    &$errorOut
): bool {
    settype($gameID, 'integer');
    settype($type, 'integer');
    settype($points, 'integer');

    // Prevent <= registered users from uploading or modifying achievements
    if (getUserPermissions($author) < Permissions::JuniorDeveloper) {
        $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";
        return false;
    }

    if (!AchievementType::isValid($type)) {
        $errorOut = "Invalid type flag";
        return false;
    }

    if ($type === AchievementType::OFFICIAL_CORE && !isValidConsoleId(getGameData($gameID)['ConsoleID'])) {
        $errorOut = "You cannot promote achievements for a game from an unsupported console (console ID: " . getGameData($gameID)['ConsoleID'] . ").";
        return false;
    }

    $dbAuthor = $author;
    $rawDesc = $desc;
    $rawTitle = $title;
    sanitize_sql_inputs($title, $desc, $mem, $progress, $progressMax, $progressFmt, $dbAuthor);

    // Assume authorised!
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
                '$progressFmt', $points, $type,
                '$dbAuthor', NOW(), NOW(),
                NOW(), 0, 0,
                '$badge', 0, NULL,
                0
            )";
        global $db;
        if (mysqli_query($db, $query) !== false) {
            $idInOut = mysqli_insert_id($db);
            postActivity($author, ActivityType::UploadAchievement, $idInOut);

            static_addnewachievement($idInOut);
            addArticleComment(
                "Server",
                ArticleType::Achievement,
                $idInOut,
                "$author uploaded this achievement.",
                $author
            );

            // uploaded new achievement

            return true;
        } else {
            // failed
            return false;
        }
    } else {
        $query = "SELECT Flags, MemAddr, Points, Title, Description, BadgeName, Author FROM Achievements WHERE ID='$idInOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);

            $changingAchSet = ($data['Flags'] != $type);
            $changingPoints = ($data['Points'] != $points);
            $changingBadge = ($data['BadgeName'] != $badge);
            $changingWording = ($data['Title'] != $rawTitle || $data['Description'] != $rawDesc);
            $changingLogic = ($data['MemAddr'] != $mem);

            $userPermissions = getUserPermissions($author);
            if ($type === AchievementType::OFFICIAL_CORE || $changingAchSet) { // If modifying core or changing achievement state
                // changing ach set detected; user is $author, permissions is $userPermissions, target set is $type
                if ($userPermissions < Permissions::Developer) {
                    // Must be developer to modify core!
                    $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";
                    return false;
                }
            }

            if ($type === AchievementType::UNOFFICIAL) { // If modifying unofficial
                // Only allow jr. devs to modify unofficial if they are the author
                if ($userPermissions == Permissions::JuniorDeveloper && $data['Author'] != $author) {
                    $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";
                    return false;
                }
            }

            $query = "UPDATE Achievements SET Title='$title', Description='$desc', Progress='$progress', ProgressMax='$progressMax', ProgressFormat='$progressFmt', MemAddr='$mem', Points=$points, Flags=$type, DateModified=NOW(), Updated=NOW(), BadgeName='$badge' WHERE ID=$idInOut";

            global $db;
            if (mysqli_query($db, $query) !== false) {
                // if ($changingAchSet || $changingPoints) {
                //     // When changing achievement set, all existing achievements that rely on this should be purged.
                //     // $query = "DELETE FROM Awarded WHERE ID='$idInOut'";
                //     // nah, that's a bit harsh... esp if you're changing something tiny like the badge!!
                //
                //     // if (s_mysql_query($query) !== false) {
                //     // global $db;
                //     // $rowsAffected = mysqli_affected_rows($db);
                //     // // great
                //     // } else {
                //     // //meh
                //     // }
                // }

                static_setlastupdatedgame($gameID);
                static_setlastupdatedachievement($idInOut);

                postActivity($author, ActivityType::EditAchievement, $idInOut);

                if ($changingAchSet) {
                    if ($type === AchievementType::OFFICIAL_CORE) {
                        addArticleComment(
                            "Server",
                            ArticleType::Achievement,
                            $idInOut,
                            "$author promoted this achievement to the Core set.",
                            $author
                        );
                    } elseif ($type === AchievementType::UNOFFICIAL) {
                        addArticleComment(
                            "Server",
                            ArticleType::Achievement,
                            $idInOut,
                            "$author demoted this achievement to Unofficial.",
                            $author
                        );
                    }
                } else {
                    $fields = [];
                    if ($changingPoints) {
                        $fields[] = "points";
                    }
                    if ($changingBadge) {
                        $fields[] = "badge";
                    }
                    if ($changingWording) {
                        $fields[] = "wording";
                    }
                    if ($changingLogic) {
                        $fields[] = "logic";
                    }
                    $editString = implode(', ', $fields);

                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "$author edited this achievement's $editString.",
                        $author
                    );
                }

                return true;
            } else {
                log_sql_fail();

                return false;
            }
        } else {
            return false;
        }
    }
}

function resetAchievements($user, $gameID): int
{
    sanitize_sql_inputs($user, $gameID);
    settype($gameID, 'integer');

    if (empty($gameID)) {
        return 0;
    }

    $query = "DELETE FROM Awarded WHERE User='$user' AND AchievementID IN ( SELECT ID FROM Achievements WHERE Achievements.GameID='$gameID')";

    $numRowsDeleted = 0;
    if (s_mysql_query($query) !== false) {
        global $db;
        $numRowsDeleted = (int) mysqli_affected_rows($db);
    }

    recalcScore($user);
    return $numRowsDeleted;
}

function resetSingleAchievement($user, $achID): bool
{
    sanitize_sql_inputs($user, $achID);
    settype($achID, 'integer');

    if (empty($achID)) {
        return false;
    }

    $query = "DELETE FROM Awarded WHERE User='$user' AND AchievementID='$achID'";
    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();
    }

    recalcScore($user);
    return true;
}

function getRecentlyEarnedAchievements($count, $user, &$dataOut): int
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

    if (!$dbResult) {
        log_sql_fail();
        return 0;
    }

    $i = 0;
    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        $dataOut[$i] = $db_entry;
        $i++;
    }
    return $i;
}

function GetAchievementsPatch($gameID, $flags): array
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
    }

    return $retVal;
}

function GetPatchData($gameID, $flags, $user): array
{
    sanitize_sql_inputs($gameID, $flags, $user);
    settype($gameID, 'integer');
    settype($flags, 'integer');

    $retVal = [];

    if (empty($gameID)) {
        // cannot look up game with gameID $gameID for user $user
        return $retVal;
    }
    $retVal = array_merge(getGameData($gameID));

    $retVal['Achievements'] = GetAchievementsPatch($gameID, $flags);
    $retVal['Leaderboards'] = GetLBPatch($gameID);

    return $retVal;
}

function updateAchievementDisplayID($achID, $newID): bool
{
    sanitize_sql_inputs($achID, $newID);

    $query = "UPDATE Achievements SET DisplayOrder = $newID, Updated=NOW() WHERE ID = $achID";
    $dbResult = s_mysql_query($query);

    return $dbResult !== false;
}

function updateAchievementEmbedVideo($achID, $newURL): bool
{
    $newURL = strip_tags($newURL);
    sanitize_sql_inputs($achID, $newURL);

    $query = "UPDATE Achievements SET AssocVideo = '$newURL', Updated=NOW() WHERE ID = $achID";

    global $db;
    $dbResult = mysqli_query($db, $query);

    return $dbResult !== false;
}

function updateAchievementFlags(int|string|array $achID, int $newFlags): bool
{
    $achievementIDs = is_array($achID) ? implode(', ', $achID) : $achID;

    sanitize_sql_inputs($achievementIDs, $newFlags);

    $query = "UPDATE Achievements SET Flags = '$newFlags', Updated=NOW() WHERE ID IN (" . $achievementIDs . ")";

    global $db;
    $dbResult = mysqli_query($db, $query);

    return $dbResult !== false;
}

function getCommonlyEarnedAchievements($consoleID, $offset, $count, &$dataOut): bool
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
    } else {
        log_sql_fail();
        // failed: consoleID:$consoleID offset:$offset, count:$count
    }

    return true;
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
): bool {
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
    if (!$dbResult) {
        return false;
    }

    $data = mysqli_fetch_assoc($dbResult);
    $numWinners = $data['NumEarned'];
    $gameID = $data['GameID'];   // Grab GameID at this point

    $numPossibleWinners = getTotalUniquePlayers($gameID, $user, false, null);

    // Get recent winners, and their most recent activity:
    $query = "SELECT ua.User, ua.RAPoints,
                     IFNULL(aw_hc.Date, aw_sc.Date) AS DateAwarded,
                     CASE WHEN aw_hc.Date IS NOT NULL THEN 1 ELSE 0 END AS HardcoreMode
              FROM UserAccounts ua
              INNER JOIN
                     (SELECT User, Date FROM Awarded WHERE AchievementID = $achID AND HardcoreMode = 0) AS aw_sc
                     ON aw_sc.User = ua.User
              LEFT JOIN
                     (SELECT User, Date FROM Awarded WHERE AchievementID = $achID AND HardcoreMode = 1) AS aw_hc
                     ON aw_hc.User = ua.User
              WHERE (NOT ua.Untracked OR ua.User = \"$user\" )
              ORDER BY DateAwarded DESC
              LIMIT $offset, $limit";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $winnerInfo[] = $db_entry;
        }
    }

    return true;
}

function getAchievementRecentWinnersData($achID, $offset, $count, $user = null, $friendsOnly = null): array
{
    sanitize_sql_inputs($achID, $offset, $count, $user, $friendsOnly);

    $retVal = [];

    // Fetch the number of times this has been earned whatsoever (excluding hardcore)
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

    // Fetch the total number of players for this game:
    $retVal['TotalPlayers'] = getGameNumUniquePlayersByAwards($retVal['GameID']);

    $extraWhere = "";
    if (isset($friendsOnly) && $friendsOnly && isset($user) && $user) {
        $extraWhere = " AND aw.User IN ( SELECT Friend FROM Friends WHERE User = '$user' AND Friendship = 1 ) ";
    }

    // Get recent winners, and their most recent activity:
    $query = "SELECT aw.User, ua.RAPoints, UNIX_TIMESTAMP(aw.Date) AS DateAwarded
              FROM Awarded AS aw
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE AchievementID=$achID AND aw.HardcoreMode = 0 $extraWhere
              ORDER BY aw.Date DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        // settype( $db_entry['HardcoreMode'], 'integer' );
        settype($db_entry['RAPoints'], 'integer');
        settype($db_entry['DateAwarded'], 'integer');
        $retVal['RecentWinner'][] = $db_entry;
    }

    return $retVal;
}

function getGameNumUniquePlayersByAwards($gameID): int
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

    return (int) $data['TotalPlayers'];
}

function recalculateTrueRatio($gameID): bool
{
    sanitize_sql_inputs($gameID);

    $query = "SELECT ach.ID, ach.Points, COUNT(*) AS NumAchieved
              FROM Achievements AS ach
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE ach.GameID = $gameID AND ach.Flags = 3 AND (aw.HardcoreMode = 1 OR aw.HardcoreMode IS NULL)
              AND (NOT ua.Untracked OR ua.Untracked IS NULL)
              GROUP BY ach.ID";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $numHardcoreWinners = getTotalUniquePlayers($gameID, null, true, 3);

        if ($numHardcoreWinners == 0) { // force all unachieved to be 1
            $numHardcoreWinners = 1;
        }

        $ratioTotal = 0;
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $achID = $nextData['ID'];
            $achPoints = (int) $nextData['Points'];
            $numAchieved = (int) $nextData['NumAchieved'];

            if ($numAchieved == 0) { // force all unachieved to be 1
                $numAchieved = 1;
            }

            $ratioFactor = 0.4;
            $newTrueRatio = ($achPoints * (1.0 - $ratioFactor)) + ($achPoints * (($numHardcoreWinners / $numAchieved) * $ratioFactor));
            $trueRatio = (int) $newTrueRatio;
            $ratioTotal += $trueRatio;

            $query = "UPDATE Achievements AS ach
                      SET ach.TrueRatio = $trueRatio
                      WHERE ach.ID = $achID";
            s_mysql_query($query);
        }

        $query = "UPDATE GameData AS gd
                  SET gd.TotalTruePoints = $ratioTotal
                  WHERE gd.ID = $gameID";
        s_mysql_query($query);

        // RECALCULATED " . count($achData) . " achievements for game ID $gameID ($ratioTotal)"

        return true;
    } else {
        return false;
    }
}

/**
 * Gets the number of softcore and hardcore awards for an achievement since a given time.
 */
function getAwardsSince(int $id, string $date): array
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
 */
function getUserAchievementsPerConsole(string $user): array
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
 */
function getUserSetsPerConsole(string $user): array
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
 */
function getUserAchievementInformation(string $user): array
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
 */
function getOwnAchievementsObtained(string $user): bool|array|null
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
 * Gets data for other users that have earned achievements for the input user.
 */
function getObtainersOfSpecificUser(string $user): array
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
 */
function getRecentObtainedAchievements(array $achievementIDs, int $offset = 0, int $count = 200): array
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
 * Gets a list of users who have won an achievement or list of achievements within a given time-range.
 */
function getWinnersOfAchievements($achievementIDs, $startTime, $endTime, $hardcoreMode): array
{
    if (empty($achievementIDs)) {
        return [];
    }

    $dateQuery = "";
    if (strtotime($startTime)) {
        if (strtotime($endTime)) {
            // valid start and end
            $dateQuery = "AND aw.Date BETWEEN '$startTime' AND '$endTime'";
        } else {
            // valid start, invalid end
            $dateQuery = "AND aw.Date >= '$startTime'";
        }
    } else {
        if (strtotime($endTime)) {
            // invalid start, valid end
            $dateQuery = "AND aw.Date <= '$endTime'";
        } else {
            // invalid start and end
            // no date query needed
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
