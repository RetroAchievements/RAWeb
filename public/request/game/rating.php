<?php

use App\Community\Enums\RatingType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
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
