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

function getAchievementsList(
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

    $gameData = getGameData($gameID);
    $consoleID = $gameData['ConsoleID'];
    $consoleName = $gameData['ConsoleName'];
    $isEventGame = $consoleName == 'Events';
    $userPermissions = getUserPermissions($author);

    // Prevent <= registered users from uploading or modifying achievements
    if ($userPermissions < Permissions::JuniorDeveloper) {
        $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";
        return false;
    }

    if (!AchievementType::isValid($type)) {
        $errorOut = "Invalid type flag";
        return false;
    }

    if ($type === AchievementType::OfficialCore && !isValidConsoleId($consoleID)) {
        $errorOut = "You cannot promote achievements for a game from an unsupported console (console ID: " . $consoleID . ").";
        return false;
    }

    $dbAuthor = $author;
    $rawDesc = $desc;
    $rawTitle = $title;
    sanitize_sql_inputs($title, $desc, $mem, $progress, $progressMax, $progressFmt, $dbAuthor);

    // Assume authorised!
    if (!isset($idInOut) || $idInOut == 0) { // New achievement added
        // Prevent users from uploading achievements for games they do not have an active claim on unless it's an event game
        if (!(hasSetClaimed($author, $gameID, false) || $isEventGame)) {
            $errorOut = "You must have an active claim on this game to perform this action.";
            return false;
        }

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
    } else { // Achievement being updated
        $query = "SELECT Flags, MemAddr, Points, Title, Description, BadgeName, Author FROM Achievements WHERE ID='$idInOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);

            $changingAchSet = ($data['Flags'] != $type);
            $changingPoints = ($data['Points'] != $points);
            $changingBadge = ($data['BadgeName'] != $badge);
            $changingWording = ($data['Title'] != $rawTitle || $data['Description'] != $rawDesc);
            $changingLogic = ($data['MemAddr'] != $mem);

            if ($type === AchievementType::OfficialCore || $changingAchSet) { // If modifying core or changing achievement state
                // changing ach set detected; user is $author, permissions is $userPermissions, target set is $type
                if ($userPermissions < Permissions::Developer) {
                    // Must be developer to modify core!
                    $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";
                    return false;
                }
            }

            if ($type === AchievementType::Unofficial) { // If modifying unofficial
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
                    if ($type === AchievementType::OfficialCore) {
                        addArticleComment(
                            "Server",
                            ArticleType::Achievement,
                            $idInOut,
                            "$author promoted this achievement to the Core set.",
                            $author
                        );
                    } elseif ($type === AchievementType::Unofficial) {
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

function getAchievementIDsByGame($gameID): array
{
    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');

    $retVal = [];
    $retVal['GameID'] = $gameID;

    // Get all achievement IDs
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
