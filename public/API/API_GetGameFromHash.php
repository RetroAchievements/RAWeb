<?php

/**
 *  API_GetGameFromHash - looks up a game from a given hash string
 *    h : game hash
 *
 *  int        ID                         id of the game
 *  string     Title                      name of the game
 *  int        ConsoleID                  unique identifier of the console associated to the game
 *  string     ConsoleName                name of the console associated to the game
 */

use App\Platform\Models\GameHash;
use App\Platform\Models\System;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'h' => ['required', 'size:32'],
]);

$givenHash = request()->query('h');

$foundHash = GameHash::with('game')->where('MD5', $givenHash)->first();

if (!$foundHash) {
    return response()->json(['message' => 'Could not find a game matching the given hash.'], 400);
}

$foundSystem = System::find($foundHash->game->ConsoleID);

return response()->json([
    'ID' => $foundHash->game->ID,
    'Title' => $foundHash->game->Title,
    'ConsoleID' => $foundHash->game->ConsoleID,
    'ConsoleName' => $foundSystem?->Name ?? null,
]);
