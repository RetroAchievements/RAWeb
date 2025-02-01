<?php

/*
 *  API_GetUserGameRankAndScore - gets user's High Scores entry for a game
 *    g : game id
 *    u : username
 *    i : user ULID
 *
 *  array
 *   object     [value]
 *    string     User             name of user
 *    string     ULID             queryable stable unique identifier of the user
 *    string     TotalScore       total number of points earned by the user for the game
 *    datetime   LastAward        when the last achievement was unlocked for the user
 *    string?    UserRank         position of user on the game's High Scores list
 */

use App\Models\User;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required_without:i', 'min:2', 'max:20', new CtypeAlnum()],
    'i' => ['required_without:u', 'string', 'size:26'],
    'g' => ['required', 'min:1'],
]);

$gameId = (int) $input['g'];

$user = isset($input['i'])
    ? User::whereUlid($input['i'])->first()
    : User::whereName($input['u'])->first();
if (!$user) {
    return response()->json([]);
}

return response()->json(getGameRankAndScore($gameId, $user));
