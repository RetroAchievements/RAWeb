<?php

/*
 *  API_GetUserGameRankAndScore - gets user's High Scores entry for a game
 *    g : game id
 *    u : username or user ULID
 *
 *  array
 *   object     [value]
 *    string     User             name of user
 *    string     ULID             queryable stable unique identifier of the user
 *    string     TotalScore       total number of points earned by the user for the game
 *    datetime   LastAward        when the last achievement was unlocked for the user
 *    string?    UserRank         position of user on the game's High Scores list
 */

use App\Actions\FindUserByIdentifierAction;
use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', new ValidUserIdentifier()],
    'g' => ['required', 'min:1'],
]);

$gameId = (int) $input['g'];

$user = (new FindUserByIdentifierAction())->execute($input['u']);
if (!$user) {
    return response()->json([]);
}

return response()->json(getGameRankAndScore($gameId, $user));
