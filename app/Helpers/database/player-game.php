<?php

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Services\GameTopAchieversService;

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
    AS (SELECT ua.User, ua.ulid AS ULID, $rankClause, pg.Points AS TotalScore, $dateClause AS LastAward
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

    $games = Game::with('system')->whereIn('id', $gameIDs)->get()->keyBy('id');
    $playerGames = PlayerGame::where('user_id', '=', $user->id)
        ->whereIn('game_id', $gameIDs)
        ->get()
        ->keyBy('game_id');

    foreach ($gameIDs as $gameID) {
        $game = $games->get($gameID);
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

        $playerGame = $playerGames->get($gameID);

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
                'ID' => $game->id,
                'Title' => $game->title,
                'ConsoleID' => $game->system_id,
                'ConsoleName' => $game->system->Name,
                'ForumTopicID' => (int) $game->forum_topic_id,
                'Flags' => 0,
                'ImageIcon' => $game->image_icon_asset_path,
                'ImageTitle' => $game->image_title_asset_path,
                'ImageIngame' => $game->image_ingame_asset_path,
                'ImageBoxArt' => $game->image_box_art_asset_path,
                'Publisher' => $game->publisher,
                'Developer' => $game->developer,
                'Genre' => $game->genre,
                'Released' => $game->released_at?->format('Y-m-d'),
                'ReleasedAtGranularity' => $game->released_at_granularity,
            ];
        }
    }

    if ($numRecentAchievements >= 0) {
        $achievementsQuery = Achievement::query()
            ->published()
            ->whereIn('GameID', $gameIDs)
            ->with(['game'])
            ->leftJoin('player_achievements', function ($join) use ($user) {
                $join->on('player_achievements.achievement_id', '=', 'Achievements.ID');
                $join->where('player_achievements.user_id', $user->id);
            })
            ->select(
                'Achievements.*',
                'player_achievements.unlocked_at',
                'player_achievements.unlocked_hardcore_at'
            )
            ->orderBy('player_achievements.unlocked_at', 'desc');

        // Only limit if we're not requesting all achievements.
        if ($numRecentAchievements > 0) {
            $achievementsQuery->whereNotNull('player_achievements.unlocked_at')
                ->orderBy('player_achievements.unlocked_at', 'desc');
        }

        $allAchievements = $achievementsQuery->get();

        // Group the results by game ID.
        $gameAchievementsMap = [];
        foreach ($gameIDs as $gameID) {
            $gameAchievements = $allAchievements->where('GameID', $gameID);

            if ($numRecentAchievements > 0) {
                $gameAchievements = $gameAchievements->take($numRecentAchievements);
            }

            $gameAchievementsMap[$gameID] = $gameAchievements;
        }

        foreach ($gameAchievementsMap as $gameID => $achievements) {
            foreach ($achievements as $achievement) {
                $gameData = $games->get($achievement->GameID)->toArray();

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
            $gameID = (int) $gameData['id'];
            $achievementData = $unlockedAchievement['Achievement'];
            $achievementID = (int) $achievementData['ID'];

            $recentAchievements[$gameID][$achievementID] = [
                'ID' => $achievementID,
                'GameID' => $gameID,
                'GameTitle' => $gameData['title'],
                'Title' => $achievementData['Title'],
                'Description' => $achievementData['Description'],
                'Points' => (int) $achievementData['Points'],
                'Type' => $achievementData['type'],
                'BadgeName' => $achievementData['BadgeName'],
                'IsAwarded' => '1',
                'DateAwarded' => $unlockedAchievement['When'],
                'HardcoreAchieved' => (int) $unlockedAchievement['Hardcore'],
            ];
        }

        if ($numRecentAchievements === 0) {
            usort($lockedAchievements, function ($a, $b) {
                if ($a['Achievement']['DisplayOrder'] === $b['Achievement']['DisplayOrder']) {
                    //  DisplayOrders haven't been setup correctly; fallback to IDs for consistency
                    return $a['Achievement']['ID'] <=> $b['Achievement']['ID'];
                }

                return $a['Achievement']['DisplayOrder'] <=> $b['Achievement']['DisplayOrder'];
            });

            foreach ($lockedAchievements as $lockedAchievement) {
                $gameData = $lockedAchievement['Game'];
                $gameID = (int) $gameData['id'];
                $achievementData = $lockedAchievement['Achievement'];
                $achievementID = (int) $achievementData['ID'];

                $recentAchievements[$gameID][$achievementID] = [
                    'ID' => $achievementID,
                    'GameID' => $gameID,
                    'GameTitle' => $gameData['title'],
                    'Title' => $achievementData['Title'],
                    'Description' => $achievementData['Description'],
                    'Points' => (int) $achievementData['Points'],
                    'Type' => $achievementData['type'],
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

function getUserAchievementUnlocksForGame(
    User|string $user,
    int $gameID,
    AchievementFlag $flag = AchievementFlag::OfficialCore,
    ?array $achievementSetIds = null,
): array {
    $user = is_string($user) ? User::whereName($user)->first() : $user;

    $playerAchievements = $user
        ->playerAchievements()
        ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
        ->join('achievement_set_achievements', 'Achievements.ID', '=', 'achievement_set_achievements.achievement_id')
        ->join('achievement_sets', 'achievement_sets.id', '=', 'achievement_set_achievements.achievement_set_id')
        ->join('game_achievement_sets', 'game_achievement_sets.achievement_set_id', '=', 'achievement_sets.id')

        /**
         * When achievement set IDs are provided, filter unlocks for content from those specific sets.
         * Otherwise, fall back to filtering by game ID.
         */
        ->when(
            !empty($achievementSetIds),
            fn ($q) => $q->whereIn('achievement_sets.id', $achievementSetIds),
            fn ($q) => $q->where('game_achievement_sets.game_id', $gameID)
        )

        ->where('Flags', $flag->value)
        ->orderBy('player_achievements.achievement_id')
        ->get([
            'player_achievements.achievement_id',
            'player_achievements.unlocked_at',
            'player_achievements.unlocked_hardcore_at',
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

function reactivateUserEventAchievements(User $user, array $userUnlocks): array
{
    // unranked users can't participate in events
    if (!$user->isRanked()) {
        return $userUnlocks;
    }

    // find any active event achievements for the set of achievements that the user has already unlocked
    $activeEventAchievementMap = EventAchievement::active()
        ->whereIn('source_achievement_id', array_keys($userUnlocks))
        ->whereHas('achievement', function ($query) {
            $query->where('Flags', AchievementFlag::OfficialCore->value);
        })
        ->get(['source_achievement_id', 'achievement_id'])
        ->mapWithKeys(function ($eventAchievement, int $key) {
            return [$eventAchievement->achievement_id => $eventAchievement->source_achievement_id];
        })
        ->toArray();

    // no active event achievements found - we're done
    if (empty($activeEventAchievementMap)) {
        return $userUnlocks;
    }

    // see if the user has unlocked any of the active event achievements
    $playerUnlockedEventAchievementIds = $user->playerAchievements()
        ->whereIn('achievement_id', array_keys($activeEventAchievementMap))
        ->whereNotNull('unlocked_hardcore_at')
        ->pluck('achievement_id')
        ->toArray();

    // clear out the hardcore unlock date for the source achievement of each event
    // achievement the user has not unlocked
    foreach ($activeEventAchievementMap as $eventAchievementId => $achievementId) {
        if (!in_array($eventAchievementId, $playerUnlockedEventAchievementIds)) {
            unset($userUnlocks[$achievementId]['DateEarnedHardcore']);
        }
    }

    return $userUnlocks;
}

function getUsersCompletedGamesAndMax(string $user, ?int $limit = null): array
{
    if (!isValidUsername($user)) {
        return [];
    }

    $minAchievementsForCompletion = 5;
    $limitClause = $limit !== null ? "LIMIT $limit" : "";

    $query = "SELECT gd.id AS GameID, c.Name AS ConsoleName, c.ID AS ConsoleID,
            gd.image_icon_asset_path AS ImageIcon, gd.title AS Title, gd.sort_title as SortTitle, gd.achievements_published as MaxPossible,
            pg.first_unlock_at AS FirstWonDate, pg.last_unlock_at AS MostRecentWonDate,
            pg.achievements_unlocked AS NumAwarded, pg.achievements_unlocked_hardcore AS NumAwardedHC, " .
            floatDivisionStatement('pg.achievements_unlocked', 'gd.achievements_published') . " AS PctWon, " .
            floatDivisionStatement('pg.achievements_unlocked_hardcore', 'gd.achievements_published') . " AS PctWonHC
            FROM player_games AS pg
            LEFT JOIN games AS gd ON gd.id = pg.game_id
            LEFT JOIN Console AS c ON c.ID = gd.system_id
            LEFT JOIN UserAccounts ua ON ua.ID = pg.user_id
            WHERE (ua.User = :user OR ua.display_name = :user2)
            AND gd.achievements_published > $minAchievementsForCompletion
            ORDER BY PctWon DESC, PctWonHC DESC, MaxPossible DESC, gd.title
            $limitClause";

    return legacyDbFetchAll($query, ['user' => $user, 'user2' => $user])->toArray();
}

/**
 * @deprecated use denormalized data from player_games
 */
function expireGameTopAchievers(int $gameID): void
{
    GameTopAchieversService::expireTopAchieversComponentData($gameID);
}
