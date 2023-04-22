<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use LegacyApp\Community\Enums\ActivityType;
use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Enums\UnlockMode;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;

function testFullyCompletedGame(int $gameID, string $user, bool $isHardcore, bool $postMastery): array
{
    sanitize_sql_inputs($user);

    $query = "SELECT COUNT(DISTINCT ach.ID) AS NumAch,
                     COUNT(IF(aw.HardcoreMode=1,1,NULL)) AS NumAwardedHC,
                     COUNT(IF(aw.HardcoreMode=0,1,NULL)) AS NumAwardedSC
              FROM Achievements AS ach
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID AND aw.User = '$user'
              WHERE ach.GameID = $gameID AND ach.Flags = " . AchievementType::OfficialCore;

    $dbResult = s_mysql_query($query);
    if ($dbResult === false) {
        return [];
    }

    $data = mysqli_fetch_assoc($dbResult);

    $minToCompleteGame = 6;
    if ($postMastery && $data['NumAch'] >= $minToCompleteGame) {
        $awardBadge = null;
        if ($isHardcore && $data['NumAwardedHC'] === $data['NumAch']) {
            // all hardcore achievements unlocked, award mastery
            $awardBadge = UnlockMode::Hardcore;
        } elseif ($data['NumAwardedSC'] === $data['NumAch']) {
            if ($isHardcore && HasSiteAward($user, AwardType::Mastery, $gameID, UnlockMode::Softcore)) {
                // when unlocking a hardcore achievement, don't update the completion
                // date if the user already has a completion badge
            } else {
                $awardBadge = UnlockMode::Softcore;
            }
        }

        if ($awardBadge !== null) {
            if (!HasSiteAward($user, AwardType::Mastery, $gameID, $awardBadge)) {
                AddSiteAward($user, AwardType::Mastery, $gameID, $awardBadge);
            }

            if (!RecentlyPostedCompletionActivity($user, $gameID, $awardBadge)) {
                postActivity($user, ActivityType::CompleteGame, $gameID, $awardBadge);
            }

            expireGameTopAchievers($gameID);
        }
    }

    return [
        'NumAch' => $data['NumAch'],
        'NumAwarded' => $isHardcore ? $data['NumAwardedHC'] : $data['NumAwardedSC'],
    ];
}

function getGameRankAndScore(int $gameID, string $username): array
{
    $user = User::firstWhere('User', $username);
    if (!$user || empty($gameID)) {
        return [];
    }

    $rankClause = "ROW_NUMBER() OVER (ORDER BY SUM(ach.points) DESC, MAX(aw.Date) ASC) UserRank";
    $untrackedClause = "AND NOT ua.Untracked";
    if ($user->Untracked) {
        $rankClause = "NULL AS UserRank";
        $untrackedClause = "";
    }

    $query = "WITH data
    AS (SELECT aw.User, SUM(ach.points) AS TotalScore, MAX(aw.Date) AS LastAward,
        $rankClause
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
        WHERE ach.Flags = " . AchievementType::OfficialCore . "
          AND gd.ID = $gameID $untrackedClause
        GROUP BY aw.User
        ORDER BY TotalScore DESC, LastAward ASC
   ) SELECT * FROM data WHERE User = :username";

    return legacyDbFetchAll($query, ['username' => $username])->toArray();
}

function getUserProgress(string $user, array $gameIDs, int $numRecentAchievements = -1, bool $withGameInfo = false): array
{
    $libraryOut = [];

    $awardedData = [];
    $gameInfo = [];
    $unlockedAchievements = [];
    $lockedAchievements = [];

    foreach ($gameIDs as $gameID) {
        $numAchievements = getGameMetadata($gameID, $user, $achievementData, $gameData);

        $possibleScore = 0;
        $numAchieved = 0;
        $scoreAchieved = 0;
        $numAchievedHardcore = 0;
        $scoreAchievedHardcore = 0;

        foreach ($achievementData as $achievement) {
            $points = $achievement['Points'];
            $possibleScore += $points;

            $dateEarned = $achievement['DateEarned'] ?? null;
            $dateEarnedHardcore = $achievement['DateEarnedHardcore'] ?? null;

            if ($dateEarned !== null) {
                $numAchieved++;
                $scoreAchieved += $points;
            }

            if ($dateEarnedHardcore !== null) {
                $numAchievedHardcore++;
                $scoreAchievedHardcore += $points;
            }

            if ($numRecentAchievements >= 0) {
                if ($dateEarnedHardcore !== null) {
                    $unlockedAchievements[] = [
                        'Achievement' => $achievement,
                        'When' => $dateEarnedHardcore,
                        'Hardcore' => 1,
                        'Game' => $gameData,
                    ];
                } elseif ($dateEarned !== null) {
                    $unlockedAchievements[] = [
                        'Achievement' => $achievement,
                        'When' => $dateEarned,
                        'Hardcore' => 0,
                        'Game' => $gameData,
                    ];
                } else {
                    $lockedAchievements[] = [
                        'Achievement' => $achievement,
                        'Game' => $gameData,
                    ];
                }
            }
        }

        $awardedData[$gameID] = [
            'NumPossibleAchievements' => $numAchievements,
            'PossibleScore' => $possibleScore,
            'NumAchieved' => $numAchieved,
            'ScoreAchieved' => $scoreAchieved,
            'NumAchievedHardcore' => $numAchievedHardcore,
            'ScoreAchievedHardcore' => $scoreAchievedHardcore,
        ];

        if ($withGameInfo) {
            $gameInfo[$gameID] = [
                'ID' => (int) $gameData['ID'],
                'Title' => $gameData['Title'],
                'ConsoleID' => (int) $gameData['ConsoleID'],
                'ConsoleName' => $gameData['ConsoleName'],
                'ForumTopicID' => (int) $gameData['ForumTopicID'],
                'Flags' => (int) $gameData['Flags'],
                'ImageIcon' => $gameData['ImageIcon'],
                'ImageTitle' => $gameData['ImageTitle'],
                'ImageIngame' => $gameData['ImageIngame'],
                'ImageBoxArt' => $gameData['ImageBoxArt'],
                'Publisher' => $gameData['Publisher'],
                'Developer' => $gameData['Developer'],
                'Genre' => $gameData['Genre'],
                'Released' => $gameData['Released'],
                'IsFinal' => (int) $gameData['IsFinal'],
            ];
        }
    }
    $libraryOut['Awarded'] = $awardedData;

    if ($withGameInfo) {
        $libraryOut['GameInfo'] = $gameInfo;
    }

    if ($numRecentAchievements >= 0) {
        usort($unlockedAchievements, function ($a, $b) {
            if ($a['When'] == $b['When'])
                return $a['Achievement']['ID'] <=> $b['Achievement']['ID'];

            return -($a['When'] <=> $b['When']);
        });

        if ($numRecentAchievements !== 0) {
            $unlockedAchievements = array_slice($unlockedAchievements, 0, $numRecentAchievements);
        }

        $recentAchievements = [];

        foreach ($unlockedAchievements as $unlockedAchievement) {
            $gameData = $unlockedAchievement['Game'];
            $gameID = (int) $gameData['ID'];
            $achievementData = $unlockedAchievement['Achievement'];
            $achievementID = (int) $achievementData['ID'];

            $recentAchievements[$gameID][$achievementID] = [
                'ID' => $achievementID,
                'GameID' => $gameID,
                'GameTitle' => $gameData['Title'],
                'Title' => $achievementData['Title'],
                'Description' => $achievementData['Description'],
                'Points' => (int) $achievementData['Points'],
                'BadgeName' => $achievementData['BadgeName'],
                'IsAwarded' => '1',
                'DateAwarded' => $unlockedAchievement['When'],
                'HardcoreAchieved' => (int) $unlockedAchievement['Hardcore'],
            ];
        }

        foreach ($lockedAchievements as $lockedAchievement) {
            $gameData = $lockedAchievement['Game'];
            $gameID = (int) $gameData['ID'];
            $achievementData = $lockedAchievement['Achievement'];
            $achievementID = (int) $achievementData['ID'];

            $recentAchievements[$gameID][$achievementID] = [
                'ID' => $achievementID,
                'GameID' => $gameID,
                'GameTitle' => $gameData['Title'],
                'Title' => $achievementData['Title'],
                'Description' => $achievementData['Description'],
                'Points' => (int) $achievementData['Points'],
                'BadgeName' => $achievementData['BadgeName'],
                'IsAwarded' => '0',
                'DateAwarded' => null,
                'HardcoreAchieved' => null,
            ];
        }

        $libraryOut['RecentAchievements'] = $recentAchievements;
    }

    return $libraryOut;
}

function expireUserAchievementUnlocksForGame(string $user, int $gameID): void
{
    Cache::forget("user:$user:gameUnlocks:$gameID:" . AchievementType::OfficialCore);
    Cache::forget("user:$user:gameUnlocks:$gameID:" . AchievementType::Unofficial);
}

function getUserAchievementUnlocksForGame(string $user, int $gameID, int $flags = AchievementType::OfficialCore): array
{
    return Cache::remember("user:$user:gameUnlocks:$gameID:$flags",
        Carbon::now()->addDays(7),
        function () use ($user, $gameID, $flags) {
            $query = "SELECT ach.ID, aw.Date, aw.HardcoreMode
                      FROM Awarded AS aw
                      LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                      WHERE ach.GameID = :gameId AND ach.Flags = :achievementType AND aw.User = :username";

            $userUnlocks = legacyDbFetchAll($query, [
                'gameId' => $gameID,
                'achievementType' => $flags,
                'username' => $user,
            ]);

            $achievementUnlocks = [];
            foreach ($userUnlocks as $userUnlock) {
                if ($userUnlock['HardcoreMode'] == UnlockMode::Hardcore) {
                    $achievementUnlocks[$userUnlock['ID']]['DateEarnedHardcore'] = $userUnlock['Date'];
                } else {
                    $achievementUnlocks[$userUnlock['ID']]['DateEarned'] = $userUnlock['Date'];
                }
            }

            return $achievementUnlocks;
        });
}

function GetAllUserProgress(string $user, int $consoleID): array
{
    $retVal = [];
    sanitize_sql_inputs($user);

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
                WHERE aw.User = '$user' AND aw.HardcoreMode = " . UnlockMode::Softcore . "
                GROUP BY gd.ID ) MyAwards ON MyAwards.GameID = gd.ID

            LEFT JOIN (
                SELECT gd.ID AS GameID, COUNT(*) AS NumIAchieved
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                WHERE aw.User = '$user' AND aw.HardcoreMode = " . UnlockMode::Hardcore . "
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

            $nextData['NumAch'] = (int) $nextData['NumAch'];
            $nextData['Earned'] = (int) $nextData['Earned'];
            $nextData['HCEarned'] = (int) $nextData['HCEarned'];

            $retVal[$nextID] = $nextData;
        }
    }

    return $retVal;
}

function getUsersGameList(string $user, ?array &$dataOut): int
{
    $dataOut = [];

    sanitize_sql_inputs($user);

    $query = "SELECT gd.Title, c.Name AS ConsoleName, gd.ID, COUNT(AchievementID) AS NumAchieved
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        LEFT JOIN ( SELECT ach1.GameID AS GameIDInner, ach1.ID, COUNT(ach1.ID) AS TotalAch
                    FROM Achievements AS ach1
                    GROUP BY GameID ) AS gt ON gt.GameIDInner = gd.ID
        WHERE aw.User = '$user'
        AND aw.HardcoreMode = " . UnlockMode::Softcore . "
        AND ach.Flags = " . AchievementType::OfficialCore . "
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

function getUsersCompletedGamesAndMax(string $user): array
{
    if (!isValidUsername($user)) {
        return [];
    }

    $requiredFlags = AchievementType::OfficialCore;
    $minAchievementsForCompletion = 5;

    $query = "SELECT gd.ID AS GameID, c.Name AS ConsoleName, c.ID AS ConsoleID, gd.ImageIcon, gd.Title, inner1.MaxPossible,
            MAX(aw.HardcoreMode), SUM(aw.HardcoreMode = 0) AS NumAwarded, SUM(aw.HardcoreMode = 1) AS NumAwardedHC, " .
            floatDivisionStatement('SUM(aw.HardcoreMode = 0)', 'inner1.MaxPossible') . " AS PctWon, " .
            floatDivisionStatement('SUM(aw.HardcoreMode = 1)', 'inner1.MaxPossible') . " AS PctWonHC
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN
            ( SELECT COUNT(*) AS MaxPossible, ach1.GameID FROM Achievements AS ach1 WHERE Flags = $requiredFlags GROUP BY GameID )
            AS inner1 ON inner1.GameID = ach.GameID AND inner1.MaxPossible > $minAchievementsForCompletion
        LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        WHERE aw.User = '$user' AND ach.Flags = $requiredFlags
        GROUP BY ach.GameID, gd.Title
        ORDER BY PctWon DESC, PctWonHC DESC, inner1.MaxPossible DESC, gd.Title";

    return legacyDbFetchAll($query)->toArray();
}

function getTotalUniquePlayers(int $gameID, ?string $requestedBy = null, bool $hardcoreOnly = false, ?int $achievementType = null): int
{
    $bindings = [
        'gameId' => $gameID,
    ];

    $hardcoreStatement = '';
    if ($hardcoreOnly) {
        $bindings['unlockMode'] = UnlockMode::Hardcore;
        $hardcoreStatement = ' AND aw.HardcoreMode = :unlockMode';
    }

    $achievementStatement = '';
    if ($achievementType !== null) {
        $bindings['achievementType'] = $achievementType;
        $achievementStatement = 'AND ach.Flags = :achievementType';
    }

    $requestedByStatement = '';
    if ($requestedBy) {
        $bindings['requestedBy'] = $requestedBy;
        $requestedByStatement = 'OR ua.User = :requestedBy';
    }

    $query = "
        SELECT COUNT(DISTINCT aw.User) As UniquePlayers
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
        WHERE ach.GameID = :gameId
        $hardcoreStatement $achievementStatement
        AND (NOT ua.Untracked $requestedByStatement)
    ";

    return (int) (legacyDbFetch($query, $bindings)['UniquePlayers'] ?? 0);
}

function getGameRecentPlayers(int $gameID, int $maximum_results = 0): array
{
    $retval = [];

    $query = "SELECT ua.ID as UserID, ua.User, ua.RichPresenceMsgDate AS Date, ua.RichPresenceMsg AS Activity
              FROM UserAccounts AS ua
              WHERE ua.LastGameID = $gameID AND ua.Permissions >= " . Permissions::Unregistered . "
              AND ua.RichPresenceMsgDate > TIMESTAMPADD(MONTH, -6, NOW())
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

function expireGameTopAchievers(int $gameID): void
{
    $cacheKey = "game:$gameID:topachievers";
    Cache::forget($cacheKey);
}

/**
 * Gets a game's high scorers or latest masters.
 */
function getGameTopAchievers(int $gameID): array
{
    $cacheKey = "game:$gameID:topachievers";
    $retval = Cache::get($cacheKey);
    if ($retval !== null) {
        return $retval;
    }

    sanitize_sql_inputs($gameID);

    $high_scores = [];
    $masters = [];
    $numAchievementsInSet = 0;

    $query = "SELECT COUNT(*) AS NumAchievementsInSet
        FROM Achievements
        WHERE GameID = $gameID AND Flags = " . AchievementType::OfficialCore;
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false && ($data = mysqli_fetch_assoc($dbResult))) {
        $numAchievementsInSet = $data['NumAchievementsInSet'];
    }

    $query = "SELECT aw.User, COUNT(*) AS NumAchievements, SUM(ach.points) AS TotalScore, MAX(aw.Date) AS LastAward
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                WHERE !ua.Untracked
                  AND ach.Flags = " . AchievementType::OfficialCore . "
                  AND gd.ID = $gameID
                  AND aw.HardcoreMode = " . UnlockMode::Hardcore . "
                GROUP BY aw.User
                ORDER BY TotalScore DESC, NumAchievements DESC, LastAward";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $mastersCounter = 0;
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if (count($high_scores) < 10) {
                $high_scores[] = $data;
            }

            if ($data['NumAchievements'] == $numAchievementsInSet) {
                if (count($masters) == 10) {
                    array_shift($masters);
                }
                $data['Rank'] = ++$mastersCounter;
                $masters[] = $data;
            } elseif (count($high_scores) == 10) {
                break;
            }
        }
    }

    $retval = [];
    $retval['Masters'] = array_reverse($masters);
    $retval['HighScores'] = $high_scores;

    if (count($masters) == 10) {
        // only cache the result if the masters list is full.
        // that way we only have to expire it when there's a new mastery
        // or an achievement gets promoted or demoted
        Cache::put($cacheKey, $retval, Carbon::now()->addDays(30));
    }

    return $retval;
}

function getMostPopularGames(int $offset, int $count, int $method): array
{
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
