<?php

use RA\AchievementAwardType;
use RA\AchievementType;
use RA\ActivityType;
use RA\Permissions;

function testFullyCompletedGame($gameID, $user, $isHardcore, $postMastery): array
{
    sanitize_sql_inputs($gameID, $user, $isHardcore);
    settype($isHardcore, 'integer');

    $query = "SELECT COUNT(ach.ID) AS NumAch, COUNT(aw.AchievementID) AS NumAwarded FROM Achievements AS ach
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID AND aw.User = '$user' AND aw.HardcoreMode = $isHardcore 
              WHERE ach.GameID = $gameID AND ach.Flags = " . AchievementType::OfficialCore;

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $minToCompleteGame = 5;

        $data = mysqli_fetch_assoc($dbResult);
        if ($postMastery && ($data['NumAwarded'] == $data['NumAch']) && ($data['NumAwarded'] > $minToCompleteGame)) {
            // Every achievement earned!
            // Test that this wasn't very recently posted!
            if (!RecentlyPostedCompletionActivity($user, $gameID, $isHardcore)) {
                postActivity($user, ActivityType::CompleteGame, $gameID, $isHardcore);
            }
        }

        return $data;
    }

    return [];
}

function getGameRankAndScore($gameID, $requestedBy): ?array
{
    sanitize_sql_inputs($gameID, $requestedBy);

    if (empty($gameID) || !isValidUsername($requestedBy)) {
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
          AND ach.Flags = " . AchievementType::OfficialCore . " 
          AND gd.ID = $gameID
        GROUP BY aw.User
        ORDER BY TotalScore DESC, LastAward ASC
   ) SELECT * FROM data WHERE User = '$requestedBy'";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

function getUserProgress($user, $gameIDsCSV, &$dataOut): ?int
{
    if (empty($gameIDsCSV) || !isValidUsername($user)) {
        return null;
    }
    sanitize_sql_inputs($user);

    // Create null entries so that we pass 'something' back.
    $gameIDsArray = explode(',', $gameIDsCSV);
    $gameIDs = [];
    foreach ($gameIDsArray as $gameID) {
        settype($gameID, "integer");
        $dataOut[$gameID]['NumPossibleAchievements'] = 0;
        $dataOut[$gameID]['PossibleScore'] = 0;
        $dataOut[$gameID]['NumAchieved'] = 0;
        $dataOut[$gameID]['ScoreAchieved'] = 0;
        $dataOut[$gameID]['NumAchievedHardcore'] = 0;
        $dataOut[$gameID]['ScoreAchievedHardcore'] = 0;
        $gameIDs[] = $gameID;
    }
    $gameIDs = implode(',', $gameIDs);

    // Count num possible achievements
    $query = "SELECT GameID, COUNT(*) AS AchCount, SUM(ach.Points) AS PointCount FROM Achievements AS ach
              WHERE ach.Flags = 3 AND ach.GameID IN ( $gameIDs )
              GROUP BY ach.GameID
              HAVING COUNT(*)>0 ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    while ($data = mysqli_fetch_assoc($dbResult)) {
        $dataOut[$data['GameID']]['NumPossibleAchievements'] = $data['AchCount'];
        $dataOut[$data['GameID']]['PossibleScore'] = $data['PointCount'];
        $dataOut[$data['GameID']]['NumAchieved'] = 0;
        $dataOut[$data['GameID']]['ScoreAchieved'] = 0;
        $dataOut[$data['GameID']]['NumAchievedHardcore'] = 0;
        $dataOut[$data['GameID']]['ScoreAchievedHardcore'] = 0;
    }

    // Foreach return value from this, cross-reference with 'earned' achievements. If not found, assume 0.
    // Count earned achievements
    $query = "SELECT GameID, COUNT(*) AS AchCount, SUM( ach.Points ) AS PointCount, aw.HardcoreMode
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON aw.AchievementID = ach.ID
              WHERE ach.GameID IN ( $gameIDsCSV ) AND ach.Flags = " . AchievementType::OfficialCore . " 
              AND aw.User = '$user'
              GROUP BY aw.HardcoreMode, ach.GameID";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    while ($data = mysqli_fetch_assoc($dbResult)) {
        if ($data['HardcoreMode'] == 0) {
            $dataOut[$data['GameID']]['NumAchieved'] = $data['AchCount'];
            $dataOut[$data['GameID']]['ScoreAchieved'] = $data['PointCount'];
        } else {
            $dataOut[$data['GameID']]['NumAchievedHardcore'] = $data['AchCount'];
            $dataOut[$data['GameID']]['ScoreAchievedHardcore'] = $data['PointCount'];
        }
    }

    return 0;
}

function GetAllUserProgress($user, $consoleID): array
{
    $retVal = [];
    sanitize_sql_inputs($user, $consoleID);
    settype($consoleID, 'integer');

    // Title,
    $query = "SELECT ID, IFNULL( AchCounts.NumAch, 0 ) AS NumAch, IFNULL( MyAwards.NumIAchieved, 0 ) AS Earned, IFNULL( MyAwardsHC.NumIAchieved, 0 ) AS HCEarned
            FROM GameData AS gd
            LEFT JOIN (
                SELECT COUNT(ach.ID) AS NumAch, GameID
                FROM Achievements AS ach
                GROUP BY ach.GameID ) AchCounts ON AchCounts.GameID = gd.ID

            LEFT JOIN (
                SELECT gd.ID AS GameID, COUNT(*) AS NumIAchieved
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                WHERE aw.User = '$user' AND aw.HardcoreMode = " . AchievementAwardType::Softcore . "
                GROUP BY gd.ID ) MyAwards ON MyAwards.GameID = gd.ID

            LEFT JOIN (
                SELECT gd.ID AS GameID, COUNT(*) AS NumIAchieved
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                WHERE aw.User = '$user' AND aw.HardcoreMode = " . AchievementAwardType::Hardcore . "
                GROUP BY gd.ID ) MyAwardsHC ON MyAwardsHC.GameID = gd.ID

            WHERE NumAch > 0 && gd.ConsoleID = $consoleID
            ORDER BY ID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            // Auto:
            // $retVal[] = $nextData;
            // Manual:
            $nextID = $nextData['ID'];
            unset($nextData['ID']);

            settype($nextData['NumAch'], 'integer');
            settype($nextData['Earned'], 'integer');
            settype($nextData['HCEarned'], 'integer');

            $retVal[$nextID] = $nextData;
        }
    }

    return $retVal;
}

function getUsersGameList($user, &$dataOut): int
{
    sanitize_sql_inputs($user);

    $query = "SELECT gd.Title, c.Name AS ConsoleName, gd.ID, COUNT(AchievementID) AS NumAchieved
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        LEFT JOIN ( SELECT ach1.GameID AS GameIDInner, ach1.ID, COUNT(ach1.ID) AS TotalAch FROM Achievements AS ach1 GROUP BY GameID ) AS gt ON gt.GameIDInner = gd.ID
        WHERE aw.User = '$user' AND aw.HardcoreMode = " . AchievementAwardType::Softcore . "
        GROUP BY gd.ID";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    $gamelistCSV = '0';

    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $dataOut[$nextData['ID']] = $nextData;
        $gamelistCSV .= ', ' . $nextData['ID'];
    }

    // Get totals:
    $query = "SELECT ach.GameID, gd.Title, COUNT(ach.ID) AS NumAchievements
            FROM Achievements AS ach
            LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
            WHERE ach.Flags = " . AchievementType::OfficialCore . " AND ach.GameID IN ( $gamelistCSV )
            GROUP BY ach.GameID ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    $i = 0;
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $dataOut[$nextData['GameID']]['Title'] = $nextData['Title'];
        $dataOut[$nextData['GameID']]['NumAchievements'] = $nextData['NumAchievements'];
        $i++;
    }

    return $i;
}

function getUsersCompletedGamesAndMax($user): array
{
    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

    sanitize_sql_inputs($user);

    $requiredFlags = AchievementType::OfficialCore;
    $minAchievementsForCompletion = 5;

    $query = "SELECT gd.ID AS GameID, c.Name AS ConsoleName, c.ID AS ConsoleID, gd.ImageIcon, gd.Title, COUNT(ach.GameID) AS NumAwarded, inner1.MaxPossible, (COUNT(ach.GameID)/inner1.MaxPossible) AS PctWon, aw.HardcoreMode
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN
            ( SELECT COUNT(*) AS MaxPossible, ach1.GameID FROM Achievements AS ach1 WHERE Flags = $requiredFlags GROUP BY GameID )
            AS inner1 ON inner1.GameID = ach.GameID AND inner1.MaxPossible > $minAchievementsForCompletion
        LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        WHERE aw.User='$user' AND ach.Flags = $requiredFlags
        GROUP BY ach.GameID, aw.HardcoreMode, gd.Title
        ORDER BY PctWon DESC, inner1.MaxPossible DESC, gd.Title ";

    global $db;
    $dbResult = mysqli_query($db, $query);

    $gamesFound = 0;
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[$gamesFound] = $db_entry;
            $gamesFound++;
        }
    }

    return $retVal;
}

function getTotalUniquePlayers($gameID, $requestedBy, $hardcoreOnly = false, $flags = null)
{
    sanitize_sql_inputs($gameID, $requestedBy);
    settype($gameID, 'integer');

    $hardcoreCond = "";
    if ($hardcoreOnly) {
        $hardcoreCond = " AND aw.HardcoreMode =" . AchievementAwardType::Hardcore;
    }

    $achievementStateCond = "";
    if ($flags !== null) {
        $achievementStateCond = "AND ach.Flags = $flags";
    }

    $query = "
        SELECT COUNT(DISTINCT aw.User) As UniquePlayers
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
        WHERE gd.ID = $gameID
        $hardcoreCond $achievementStateCond
        AND (NOT ua.Untracked" . (isset($requestedBy) ? " OR ua.User = '$requestedBy'" : "") . ")
    ";

    $dbResult = s_mysql_query($query);

    $data = mysqli_fetch_assoc($dbResult);
    return $data['UniquePlayers'];
}

function getGameRecentPlayers($gameID, $maximum_results = 0): array
{
    sanitize_sql_inputs($gameID, $maximum_results);
    settype($gameID, 'integer');

    $retval = [];

    $query = "SELECT ua.ID as UserID, ua.User, ua.RichPresenceMsgDate AS Date, ua.RichPresenceMsg AS Activity
              FROM UserAccounts AS ua
              WHERE ua.LastGameID = $gameID AND ua.Permissions >= " . Permissions::Unregistered . "
              ORDER BY ua.RichPresenceMsgDate DESC";

    if ($maximum_results > 0) {
        $query .= " LIMIT $maximum_results";
    }

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

/**
 * Gets a game's high scorers or latest masters.
 */
function getGameTopAchievers(int $gameID, ?string $requestedBy): array
{
    sanitize_sql_inputs($gameID, $offset, $count, $requestedBy);

    $high_scores = [];
    $masters = [];
    $mastery_score = 0;

    $query = "SELECT SUM(Points) AS Points FROM Achievements WHERE GameID = $gameID AND Flags = 3";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        if ($data = mysqli_fetch_assoc($dbResult)) {
            $mastery_score = $data['Points'];
        }
    }

    $query = "SELECT aw.User, SUM(ach.points) AS TotalScore, MAX(aw.Date) AS LastAward
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                WHERE ( !ua.Untracked OR ua.User = '$requestedBy' ) 
                  AND ach.Flags = " . AchievementType::OfficialCore . " 
                  AND gd.ID = $gameID
                  AND aw.HardcoreMode = " . AchievementAwardType::Hardcore . "
                GROUP BY aw.User
                ORDER BY TotalScore DESC, LastAward";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if (count($high_scores) < 10) {
                $high_scores[] = $data;
            }

            if ($data['TotalScore'] == $mastery_score) {
                if (count($masters) == 10) {
                    array_shift($masters);
                }
                $masters[] = $data;
            } elseif (count($high_scores) == 10) {
                break;
            }
        }
    }

    $retval = [];
    $retval['Masters'] = array_reverse($masters);
    $retval['HighScores'] = $high_scores;
    return $retval;
}

function getMostPopularGames($offset, $count, $method): array
{
    sanitize_sql_inputs($offset, $count, $method);
    settype($method, 'integer');

    $retval = [];

    if ($method == 0) {
        // By num awards given:
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
        //             WHERE act.activitytype = " . AchievementType::OFFICIAL_CORE . " AND !ISNULL( gd.ID )
        //             GROUP BY gd.ID, act.User
        //         ) AS Inner1
        //         GROUP BY Inner1.ID
        //         ORDER BY NumRecords DESC
        //         LIMIT $offset, $count";
    }

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}
