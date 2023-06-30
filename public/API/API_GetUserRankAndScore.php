<?php

/*
 *  API_GetUserRankAndScore
 *    u : username
 *
 *  int        Score           number of hardcore points the user has
 *  int        SoftcoreScore   number of softcore points the user has
 *  int?       Rank            user's site rank
 *  int        TotalRanked     total number of ranked users
 */

use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
]);

$user = request()->query('u');

$points = 0;
$softcorePoints = 0;
if (getPlayerPoints($user, $playerPoints)) {
    $points = $playerPoints['RAPoints'];
    $softcorePoints = $playerPoints['RASoftcorePoints'];
}

return response()->json([
    'Score' => $points,
    'SoftcoreScore' => $softcorePoints,
    'Rank' => getUserRank($user),
    'TotalRanked' => countRankedUsers(),
]);
