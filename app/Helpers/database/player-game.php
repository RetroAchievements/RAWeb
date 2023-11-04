<?php

use App\Platform\Enums\AchievementFlag;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\PlayerSession;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function getGameRankAndScore(int $gameID, User $user): array
{
    if (empty($gameID)) {
        return [];
    }

    $dateClause = greatestStatement(['pg.last_unlock_hardcore_at', 'pg.last_unlock_at']);
    $rankClause = "ROW_NUMBER() OVER (ORDER BY pg.Points DESC, $dateClause ASC) UserRank";
    $untrackedClause = "AND ua.Untracked = 0";
    if ($user->Untracked) {
        $rankClause = "NULL AS UserRank";
        $untrackedClause = "";
    }

    $query = "WITH data
    AS (SELECT ua.User, $rankClause, pg.Points AS TotalScore, $dateClause AS LastAward
        FROM player_games AS pg
        INNER JOIN UserAccounts AS ua ON ua.ID = pg.user_id
        WHERE pg.game_id = $gameID $untrackedClause
        GROUP BY ua.User
        ORDER BY TotalScore DESC, LastAward ASC
   ) SELECT * FROM data WHERE User = :username";

    return legacyDbFetchAll($query, ['username' => $user->User])->toArray();
}

function getUserProgress(User $user, array $gameIDs, int $numRecentAchievements = -1, bool $withGameInfo = false): array
{
    $libraryOut = [];

    $awardedData = [];
    $gameInfo = [];
    $unlockedAchievements = [];
    $lockedAchievements = [];

    foreach ($gameIDs as $gameID) {
        $game = Game::with('system')->find($gameID);
        if (!$game) {
            $awardedData[$gameID] = [
                'NumPossibleAchievements' => 0,
                'PossibleScore' => 0,
                'NumAchieved' => 0,
                'ScoreAchieved' => 0,
                'NumAchievedHardcore' => 0,
                'ScoreAchievedHardcore' => 0,
            ];
            continue;
        }

        $playerGame = PlayerGame::where('user_id', '=', $user->ID)
            ->where('game_id', $gameID)
            ->first();

        $awardedData[$gameID] = [
            'NumPossibleAchievements' => $game->achievements_published ?? 0,
            'PossibleScore' => $game->points_total ?? 0,
            'NumAchieved' => $playerGame ? ($playerGame->achievements_unlocked ?? 0) : 0,
            'ScoreAchieved' => $playerGame ? ($playerGame->points ?? 0) : 0,
            'NumAchievedHardcore' => $playerGame ? ($playerGame->achievements_unlocked_hardcore ?? 0) : 0,
            'ScoreAchievedHardcore' => $playerGame ? ($playerGame->points_hardcore ?? 0) : 0,
        ];

        if ($withGameInfo) {
            $gameInfo[$gameID] = [
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => (int) $game->system->ID,
                'ConsoleName' => $game->system->Name,
                'ForumTopicID' => (int) $game->ForumTopicID,
                'Flags' => (int) $game->Flags,
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $game->Released,
                'IsFinal' => (int) $game->IsFinal,
            ];
        }

        if ($numRecentAchievements >= 0) {
            $gameData = $game->toArray();

            $achievements = $game->achievements()->published()
                ->leftJoin('player_achievements', function ($join) use ($user) {
                    $join->on('player_achievements.achievement_id', '=', 'Achievements.ID');
                    $join->where('player_achievements.user_id', $user->id);
                });
            foreach ($achievements->get() as $achievement) {
                if ($achievement->unlocked_hardcore_at) {
                    $unlockedAchievements[] = [
                        'Achievement' => $achievement->toArray(),
                        'When' => $achievement->unlocked_hardcore_at,
                        'Hardcore' => 1,
                        'Game' => $gameData,
                    ];
                } elseif ($achievement->unlocked_at) {
                    $unlockedAchievements[] = [
                        'Achievement' => $achievement->toArray(),
                        'When' => $achievement->unlocked_at,
                        'Hardcore' => 0,
                        'Game' => $gameData,
                    ];
                } else {
                    $lockedAchievements[] = [
                        'Achievement' => $achievement->toArray(),
                        'Game' => $gameData,
                    ];
                }
            }
        }
    }
    $libraryOut['Awarded'] = $awardedData;

    if ($withGameInfo) {
        $libraryOut['GameInfo'] = $gameInfo;
    }

    // magic numbers!
    // -1 = don't populate RecentAchievements field
    // 0 = return all achievements for each game, with the unlocked achievements first ordered by unlock date
    // >0 = return the N most recent unlocks across all games queried, grouped by game
    if ($numRecentAchievements >= 0) {
        usort($unlockedAchievements, function ($a, $b) {
            if ($a['When'] == $b['When']) {
                return $a['Achievement']['ID'] <=> $b['Achievement']['ID'];
            }

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

        if ($numRecentAchievements === 0) {
            usort($lockedAchievements, function ($a, $b) {
                return $a['Achievement']['DisplayOrder'] <=> $b['Achievement']['DisplayOrder'];
            });

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
        }

        $libraryOut['RecentAchievements'] = $recentAchievements;
    }

    return $libraryOut;
}

function getUserAchievementUnlocksForGame(User|string $user, int $gameID, int $flag = AchievementFlag::OfficialCore): array
{
    $user = is_string($user) ? User::firstWhere('User', $user) : $user;

    $playerAchievements = $user
        ->playerAchievements()
        ->join('Achievements', 'Achievements.ID', '=', 'achievement_id')
        ->where('GameID', $gameID)
        ->where('Flags', $flag)
        ->get([
            'achievement_id',
            'unlocked_at',
            'unlocked_hardcore_at',
        ])
        ->mapWithKeys(function ($unlock, int $key) {
            $result = [];

            // TODO move this transformation to where it's needed (web api) and use models everywhere else
            if ($unlock->unlocked_at) {
                $result['DateEarned'] = $unlock->unlocked_at->__toString();
            }

            if ($unlock->unlocked_hardcore_at) {
                $result['DateEarnedHardcore'] = $unlock->unlocked_hardcore_at->__toString();
            }

            return [$unlock->achievement_id => $result];
        });

    return $playerAchievements->toArray();
}

function GetAllUserProgress(User $user, int $consoleID): array
{
    $retVal = [];

    $query = "SELECT gd.ID, gd.achievements_published AS NumAch,
                     COALESCE(pg.achievements_unlocked, 0) AS Earned,
                     COALESCE(pg.achievements_unlocked_hardcore, 0) AS HCEarned
            FROM GameData AS gd
            LEFT JOIN player_games pg ON pg.game_id = gd.ID AND pg.user_id={$user->id}
            WHERE gd.achievements_published > 0 AND gd.ConsoleID = $consoleID";

    foreach (legacyDbFetchAll($query) as $row) {
        $id = $row['ID'];
        unset($row['ID']);

        $retVal[$id] = $row;
    }

    return $retVal;
}

function getUsersGameList(User $user): array
{
    $dataOut = [];

    $query = "SELECT gd.ID, gd.Title, c.Name AS ConsoleName,
                     gd.achievements_published AS NumAchievements,
                     pg.achievements_unlocked AS NumAchieved
              FROM player_games pg
              INNER JOIN GameData AS gd ON gd.ID = pg.game_id
              INNER JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE pg.user_id = {$user->id}
              AND pg.achievements_unlocked > 0";

    foreach (legacyDbFetchAll($query) as $row) {
        $dataOut[$row['ID']] = $row;
    }

    return $dataOut;
}

function getUsersCompletedGamesAndMax(string $user): array
{
    if (!isValidUsername($user)) {
        return [];
    }

    $minAchievementsForCompletion = 5;

    $query = "SELECT gd.ID AS GameID, c.Name AS ConsoleName, c.ID AS ConsoleID,
            gd.ImageIcon, gd.Title, gd.achievements_published as MaxPossible,
            pg.first_unlock_at AS FirstWonDate, pg.last_unlock_at AS MostRecentWonDate,
            pg.achievements_unlocked AS NumAwarded, pg.achievements_unlocked_hardcore AS NumAwardedHC, " .
            floatDivisionStatement('pg.achievements_unlocked', 'gd.achievements_published') . " AS PctWon, " .
            floatDivisionStatement('pg.achievements_unlocked_hardcore', 'gd.achievements_published') . " AS PctWonHC
        FROM player_games AS pg
        LEFT JOIN GameData AS gd ON gd.ID = pg.game_id
        LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        LEFT JOIN UserAccounts ua ON ua.ID = pg.user_id
        WHERE ua.User = :user
        AND gd.achievements_published > $minAchievementsForCompletion
        ORDER BY PctWon DESC, PctWonHC DESC, MaxPossible DESC, gd.Title";

    return legacyDbFetchAll($query, ['user' => $user])->toArray();
}

function getGameRecentPlayers(int $gameID, int $maximum_results = 10): array
{
    $retval = [];

    $sessions = PlayerSession::where('game_id', $gameID)
        ->join('UserAccounts', 'UserAccounts.ID', '=', 'user_id')
        ->where('UserAccounts.Permissions', '>=', Permissions::Unregistered)
        ->whereNotNull('rich_presence')
        ->orderBy('rich_presence_updated_at', 'DESC')
        ->groupBy('user_id')
        ->select(['user_id', 'User', 'rich_presence', DB::raw('MAX(rich_presence_updated_at) AS rich_presence_updated_at')]);

    if ($maximum_results) {
        $sessions = $sessions->limit($maximum_results);
    }

    foreach ($sessions->get() as $session) {
        $retval[] = [
            'UserID' => $session->user_id,
            'User' => $session->User,
            'Date' => $session->rich_presence_updated_at->__toString(),
            'Activity' => $session->rich_presence,
        ];
    }

    if ($maximum_results) {
        $maximum_results -= count($retval);
        if ($maximum_results == 0) {
            return $retval;
        }
    }

    $userFilter = '';
    if (count($retval)) {
        $userFilter = 'AND ua.ID NOT IN (' . implode(',', array_column($retval, 'UserID')) . ')';
    }

    $query = "SELECT ua.ID as UserID, ua.User, ua.RichPresenceMsgDate AS Date, ua.RichPresenceMsg AS Activity
              FROM UserAccounts AS ua
              WHERE ua.LastGameID = $gameID AND ua.Permissions >= " . Permissions::Unregistered . "
              AND ua.RichPresenceMsgDate > TIMESTAMPADD(MONTH, -6, NOW()) $userFilter
              ORDER BY ua.RichPresenceMsgDate DESC";

    if ($maximum_results > 0) {
        $query .= " LIMIT $maximum_results";
    }

    foreach (legacyDbFetchAll($query) as $data) {
        $retval[] = $data;
    }

    return $retval;
}

/**
 * @deprecated use denormalized data from player_games
 */
function expireGameTopAchievers(int $gameID): void
{
    $cacheKey = "game:$gameID:topachievers";
    Cache::forget($cacheKey);
}

/**
 * Gets a game's high scorers or latest masters.
 *
 * @deprecated use denormalized data from player_games
 */
function getGameTopAchievers(int $gameID): array
{
    $cacheKey = "game:$gameID:topachievers";
    $retval = Cache::get($cacheKey);
    if ($retval !== null) {
        return $retval;
    }

    $high_scores = [];
    $masters = [];
    $numAchievementsInSet = 0;

    $query = "SELECT COUNT(*) AS NumAchievementsInSet
        FROM Achievements
        WHERE GameID = $gameID AND Flags = " . AchievementFlag::OfficialCore;
    $data = legacyDbFetch($query);
    if ($data !== null) {
        $numAchievementsInSet = $data['NumAchievementsInSet'];
    }

    $query = "SELECT ua.User, pg.achievements_unlocked_hardcore AS NumAchievements,
                        pg.points_hardcore AS TotalScore, pg.last_unlock_hardcore_at AS LastAward
                FROM player_games pg
                INNER JOIN UserAccounts ua ON ua.ID = pg.user_id
                WHERE ua.Untracked = 0
                AND pg.game_id = $gameID
                AND pg.achievements_unlocked_hardcore > 0
                ORDER BY TotalScore DESC, NumAchievements DESC, LastAward";

    $mastersCounter = 0;
    foreach (legacyDbFetchAll($query) as $data) {
        settype($data['NumAchievements'], 'integer');
        settype($data['TotalScore'], 'integer');

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
