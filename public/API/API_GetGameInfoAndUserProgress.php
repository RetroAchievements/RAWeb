<?php

/*
 *  API_GetGameInfoAndUserProgress
 *    g : game id
 *    u : username
 *
 *  int        ID                         unique identifier of the game
 *  string     Title                      name of the game
 *  int        ConsoleID                  unique identifier of the console associated to the game
 *  string     ConsoleName                name of the console associated to the game
 *  int?       ParentGameID               unique identifier of the parent game if this is a subset
 *  int        NumDistinctPlayers         number of unique players who have earned achievements for the game
 *  int        NumDistinctPlayersCasual   [deprecated] equal to NumDistinctPlayers
 *  int        NumDistinctPlayersHardcore [deprecated] equal to NumDistinctPlayers
 *  int        NumAchievements            count of core achievements associated to the game
 *  int        NumAwardedToUser           number of achievements earned by the user
 *  int        NumAwardedToUserHardcore   number of achievements earned by the user in hardcore
 *  string     UserCompletion             percentage of achievements earned by the user
 *  string     UserCompletionHardcore     percentage of achievements earned by the user in hardcore
 *  map        Achievements
 *   string     [key]                     unique identifier of the achievement
 *    int        ID                       unique identifier of the achievement
 *    string     Title                    title of the achievement
 *    string     Description              description of the achievement
 *    string     Points                   number of points the achievement is worth
 *    string     TrueRatio                number of "white" points the achievement is worth
 *    string     BadgeName                unique identifier of the badge image for the achievement
 *    int        NumAwarded               number of times the achievement has been awarded
 *    int        NumAwardedHardcore       number of times the achievement has been awarded in hardcore
 *    int        DisplayOrder             field used for determining which order to display the achievements
 *    string     Author                   user who originally created the achievement
 *    datetime   DateCreated              when the achievement was created
 *    datetime   DateModified             when the achievement was last modified
 *    string     MemAddr                  md5 of the logic for the achievement
 *    datetime   DateEarned               when the achievement was earned by the user
 *    datetime   DateEarnedHardcore       when the achievement was earned by the user in hardcore
 *  map        Leaderboards               collection of leaderboards related to the game
 *    int        LeaderboardID            unique identifier of the leaderboard
 *    string     Title                    title of the leaderboard
 *    string     Description              description of the leaderboard
 *    string     Format                   format of the leaderboard
 *    boolean    RankAsc                  indicates if a lower score is better
 *    string     OrderColumn              column used for ordering the leaderboard
 *    datetime   CreatedAt                creation date of the leaderboard
 *    datetime   UpdatedAt                last update date of the leaderboard
 *    int?       UserScore                user's score on the leaderboard
 *    int?       UserRank                 user's rank on the leaderboard
 *    datetime?  DateAchieved             date when the user achieved the score
 *  int        ForumTopicID               unique identifier of the official forum topic for the game
 *  int        Flags                      always "0"
 *  string     ImageIcon                  site-relative path to the game's icon image
 *  string     ImageTitle                 site-relative path to the game's title image
 *  string     ImageIngame                site-relative path to the game's in-game image
 *  string     ImageBoxArt                site-relative path to the game's box art image
 *  string     Publisher                  publisher information for the game
 *  string     Developer                  developer information for the game
 *  string     Genre                      genre information for the game
 *  string     Released                   release date information for the game
 *  bool       IsFinal
 *  string     RichPresencePatch          md5 of the script for generating the rich presence for the game
 */

use App\Models\Game;
use App\Models\User;
use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use Illuminate\Support\Facades\DB;

$gameId = (int) request()->query('g');
$username = request()->query('u');

$user = User::where('User', $username)->first();

if (!$user) {
    return response()->json([]);
}

$game = Game::with(['system', 'achievements', 'leaderboards'])
             ->find($gameId);

if (!$game) {
    return response()->json([]);
}

$gameData = [
    'ID' => $game->ID,
    'Title' => $game->Title,
    'ConsoleID' => $game->ConsoleID,
    'ConsoleName' => $game->system->Name,
    'ForumTopicID' => $game->ForumTopicID,
    'Flags' => $game->Flags,
    'ImageIcon' => $game->ImageIcon,
    'ImageTitle' => $game->ImageTitle,
    'ImageIngame' => $game->ImageIngame,
    'ImageBoxArt' => $game->ImageBoxArt,
    'Publisher' => $game->Publisher,
    'Developer' => $game->Developer,
    'Genre' => $game->Genre,
    'Released' => $game->Released,
    'IsFinal' => $game->IsFinal,
    'RichPresencePatch' => md5($game->RichPresencePatch),
    'Updated' => $game->Updated->format('c'),
    'PlayersTotal' => $game->players_total,
    'AchievementsPublished' => $game->achievements_published,
    'PointsTotal' => $game->points_total,
];


$achievementsData = $game->achievements
    ->where('Flags', AchievementFlag::OfficialCore)
    ->get(['ID', 'Title', 'Description', 'Points', 'TrueRatio', 'BadgeName', 'DisplayOrder', 'Author', 'DateModified', 'DateCreated', 'MemAddr'])
    ->map(function ($achievement) use ($user) {
        $userAchievement = $achievement->players()
            ->where('user_id', $user->id)
            ->first();

        return [
            'ID' => $achievement->ID,
            'Title' => $achievement->Title,
            'Description' => $achievement->Description,
            'Points' => $achievement->Points,
            'TrueRatio' => $achievement->TrueRatio,
            'BadgeName' => $achievement->BadgeName,
            'DisplayOrder' => $achievement->DisplayOrder,
            'Author' => $achievement->Author,
            'DateModified' => $achievement->DateModified?->format('c'),
            'DateCreated' => $achievement->DateCreated?->format('c'),
            'MemAddr' => md5($achievement->MemAddr),
            'DateEarned' => $userAchievement?->pivot->unlocked_at?->format('c') ?? null,
            'DateEarnedHardcore' => $userAchievement?->pivot->unlocked_hardcore_at?->format('c') ?? null,
        ];
    });

$gameData['Achievements'] = $achievementsData;

$leaderboardData = $game->leaderboards
    ->map(function ($leaderboard) use ($user) {
        // Define the order direction based on the leaderboard's logic
        $orderDirection = $leaderboard->RankAsc ? 'asc' : 'desc';

        // Retrieve the user's entry with rank
        $entry = LeaderboardEntry::ofLeaderboard($leaderboard->ID)
            ->withRank($orderDirection)
            ->where('user_id', $user->id)
            ->first();

        // Basic structure for each leaderboard
        $leaderboardInfo = [
            'LeaderboardID' => $leaderboard->ID,
            'Title' => $leaderboard->Title,
            'Description' => $leaderboard->Description,
            'Format' => $leaderboard->Format,
            'RankAsc' => $leaderboard->RankAsc,
            'OrderColumn' => $leaderboard->OrderColumn,
            'CreatedAt' => $leaderboard->Created?->format('c'),
            'UpdatedAt' => $leaderboard->Updated?->format('c'),
        ];

        if ($entry) {
            $leaderboardInfo['UserScore'] = $entry->score;
            $leaderboardInfo['UserRank'] = $entry->rank;
            $leaderboardInfo['DateAchieved'] = $entry->created_at?->format('c');
        }

        return $leaderboardInfo;
    });

$gameData['Leaderboards'] = $leaderboardData;

$numAchievements = count($gameData['Achievements']);
$numAwardedToUser = count(array_filter($gameData['Achievements'], function ($ach) {
    return isset($ach['DateEarned']);
}));
$numAwardedToUserHardcore = count(array_filter($gameData['Achievements'], function ($ach) {
    return isset($ach['DateEarnedHardcore']);
}));

$gameData['UserCompletion'] = $numAchievements ? sprintf("%01.2f%%", ($numAwardedToUser / $numAchievements) * 100.0) : '0.00%';
$gameData['UserCompletionHardcore'] = $numAchievements ? sprintf("%01.2f%%", ($numAwardedToUserHardcore / $numAchievements) * 100.0) : '0.00%';

$gameData['NumDistinctPlayers'] = $game->players->count();
$gameData['NumDistinctPlayersCasual'] = $gameData['NumDistinctPlayers'];
$gameData['NumDistinctPlayersHardcore'] = $gameData['NumDistinctPlayers'];

return response()->json($gameData);
