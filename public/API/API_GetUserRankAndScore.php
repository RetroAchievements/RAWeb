<?php

/*
 *  API_GetUserRankAndScore
 *    u : username
 *    i : user ULID
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
    'u' => ['required_without:i', 'min:2', 'max:20', new CtypeAlnum()],
    'i' => ['required_without:u', 'string', 'size:26'],
]);

$user = isset($input['i'])
    ? User::whereUlid($input['i'])->first()
    : User::whereName($input['u'])->first();

$points = 0;
$softcorePoints = 0;

if ($user) {
    $points = $user?->points ?? 0;
    $softcorePoints = $user?->points_softcore ?? 0;
}

return response()->json([
    'Score' => $points,
    'SoftcoreScore' => $softcorePoints,
    'Rank' => $user ? getUserRank($user->display_name) : null,
    'TotalRanked' => countRankedUsers(),
]);
