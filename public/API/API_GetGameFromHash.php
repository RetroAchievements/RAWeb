<?php

/**
 *  API_GetGameFromHash - looks up a game from a given hash string
 *    h : game hash
 *
 *  int        ID                         id of the game
 *  string     Title                      name of the game
 *  string     Description                description of the associated hash
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
    return response()->json([
        'message' => "Unknown hash: $givenHash",
        'errors' => [
            [
                'status' => 404,
                'code' => 'not_found',
                'title' => "Unknown hash: $givenHash",
            ],
        ],
    ], 404);
}

$foundSystem = System::find($foundHash->game->ConsoleID);

return response()->json([
    'ID' => $foundHash->game->ID,
    'Title' => $foundHash->game->Title,
    'Description' => $foundHash->Name,
    'ConsoleID' => $foundHash->game->ConsoleID,
    'ConsoleName' => $foundSystem?->Name ?? null,
]);
