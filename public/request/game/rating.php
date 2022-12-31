<?php

use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Enums\RatingType;

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
]);

$gameID = $input['game'];

$gameRating = getGameRating($gameID);

return response()->json([
   'GameID' => $gameID,
   'Ratings' => [
       'Game' => $gameRating[RatingType::Game]['AverageRating'],
       'Achievements' => $gameRating[RatingType::Achievement]['AverageRating'],
       'GameNumVotes' => $gameRating[RatingType::Game]['RatingCount'],
       'AchievementsNumVotes' => $gameRating[RatingType::Achievement]['RatingCount'],
   ],
]);
