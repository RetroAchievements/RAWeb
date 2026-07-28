<?php

/*
 *  API_GetUserRankAndScore
 *    u : username or user ULID
 *
 *  int        Score           number of hardcore points the user has
 *  int        SoftcoreScore   number of casual points the user has
 *  int?       Rank            user's site rank
 *  int        TotalRanked     total number of ranked users
 */

use App\Actions\FindUserByIdentifierAction;
use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', new ValidUserIdentifier()],
]);

$user = (new FindUserByIdentifierAction())->execute($input['u']);

$points = 0;
$casualPoints = 0;

if ($user) {
    $points = $user?->points_hardcore ?? 0;
    $casualPoints = $user?->points ?? 0;
}

return response()->json([
    'Score' => $points,
    'SoftcoreScore' => $casualPoints,
    'Rank' => $user ? getUserRank($user->display_name) : null,
    'TotalRanked' => countRankedUsers(),
]);
