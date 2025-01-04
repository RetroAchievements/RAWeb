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

use App\Models\User;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
]);

$username = request()->query('u');

$points = 0;
$softcorePoints = 0;

$foundUser = User::whereName($username)->first();

if ($foundUser) {
    $points = $foundUser?->points ?? 0;
    $softcorePoints = $foundUser?->points_softcore ?? 0;
}

return response()->json([
    'Score' => $points,
    'SoftcoreScore' => $softcorePoints,
    'Rank' => $foundUser ? getUserRank($foundUser->display_name) : null,
    'TotalRanked' => countRankedUsers(),
]);
