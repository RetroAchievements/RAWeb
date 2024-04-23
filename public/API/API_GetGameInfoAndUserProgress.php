<?php

/*
 *  API_GetGameInfoAndUserProgress
 *    g : game id
 *    u : username
 *    a : if 1, include highest award metadata (default: 0)
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
 *    string     TrueRatio                number of RetroPoints ("white points") the achievement is worth
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
 *  ?string    HighestAwardKind           "mastered", "completed", "beaten-hardcore", "beaten-softcore", or null. requires the 'a' query param to be 1.
 *  ?datetime  HighestAwardDate           an ISO8601 timestamp string, or null, for when the HighestAwardKind was granted. requires the 'a' query param to be 1.
 */

 use App\Models\PlayerBadge;
 use App\Models\User;

$gameID = (int) request()->query('g');
$targetUser = User::firstWhere('User', request()->query('u'));
if (!$targetUser) {
    return response()->json([]);
}

getGameMetadata($gameID, $targetUser, $achData, $gameData, metrics: true);

if ($gameData === null) {
    return response()->json([]);
}

if (empty($achData)) {
    $gameData['Achievements'] = new ArrayObject(); // issue #484 - force serialization to {}
} else {
    foreach ($achData as &$achievement) {
        $achievement['MemAddr'] = md5($achievement['MemAddr'] ?? null);
    }
    $gameData['Achievements'] = $achData;
}

$gameData['RichPresencePatch'] = md5($gameData['RichPresencePatch'] ?? null);

$gameData['NumAwardedToUser'] = 0;
$gameData['NumAwardedToUserHardcore'] = 0;

$gameData['NumDistinctPlayersCasual'] = $gameData['NumDistinctPlayers'];
$gameData['NumDistinctPlayersHardcore'] = $gameData['NumDistinctPlayers'];

if (!empty($achData)) {
    foreach ($achData as $nextAch) {
        if (isset($nextAch['DateEarned'])) {
            $gameData['NumAwardedToUser']++;
        }
        if (isset($nextAch['DateEarnedHardcore'])) {
            $gameData['NumAwardedToUserHardcore']++;
        }
    }
}

unset($gameData['system']);
unset($gameData['achievement_set_version_hash']);
unset($gameData['Updated']);

$gameData['UserCompletion'] = '0.00%';
$gameData['UserCompletionHardcore'] = '0.00%';
if ($gameData['NumAchievements'] ?? false) {
    $gameData['UserCompletion'] = sprintf("%01.2f%%", ($gameData['NumAwardedToUser'] / $gameData['NumAchievements']) * 100.0);
    $gameData['UserCompletionHardcore'] = sprintf("%01.2f%%", ($gameData['NumAwardedToUserHardcore'] / $gameData['NumAchievements']) * 100.0);
}

$includeAwardMetadata = request()->query('a', 0);
if ($includeAwardMetadata == 1) {
    $highestAwardMetadata = PlayerBadge::getHighestUserAwardForGameId($targetUser, $gameID);

    if ($highestAwardMetadata) {
        $gameData['HighestAwardKind'] = $highestAwardMetadata['highestAwardKind'];
        $gameData['HighestAwardDate'] = $highestAwardMetadata['highestAward']->AwardDate->toIso8601String();
    } else {
        $gameData['HighestAwardKind'] = null;
        $gameData['HighestAwardDate'] = null;
    }
}

return response()->json($gameData);
