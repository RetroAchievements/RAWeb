<?php

/*
 *  API_GetAchievementCount - returns the achievements associated to a game
 *    i : game id
 *
 *  int        GameID                     unique identifier of the game
 *  array      AchievementIDs
 *   int        [value]                   unique identifier of an achievement associated to the game
 */

$gameID = requestInputQuery('i');

return response()->json(getAchievementIDsByGame($gameID));
