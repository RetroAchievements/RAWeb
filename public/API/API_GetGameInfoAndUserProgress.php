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
 *  int        NumDistinctPlayersCasual   number of unique players who have earned achievements for the game
 *  int        NumDistinctPlayersHardcore number of unique players who have earned achievements for the game in hardcore
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

$gameID = (int) request()->query('g');
$targetUser = request()->query('u');
getGameMetadata($gameID, $targetUser, $achData, $gameData, metrics: true);

if ($gameData === null) {
    return response()->json();
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

$gameData['UserCompletion'] = '0.00%';
$gameData['UserCompletionHardcore'] = '0.00%';
if ($gameData['NumAchievements'] ?? false) {
    $gameData['UserCompletion'] = sprintf("%01.2f%%", ($gameData['NumAwardedToUser'] / $gameData['NumAchievements']) * 100.0);
    $gameData['UserCompletionHardcore'] = sprintf("%01.2f%%", ($gameData['NumAwardedToUserHardcore'] / $gameData['NumAchievements']) * 100.0);
}

return response()->json($gameData);
