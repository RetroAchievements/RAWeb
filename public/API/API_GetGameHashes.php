<?php

/*
 *  API_GetGameHashes - returns information about supported game files for a specific game
 *    i : game id
 *
 *  array
 *   object     [value]
 *    string     Name        name given to the hash, usually a filename
 *    string     MD5         unique hash
 *    array      Labels
 *     string     [value]    labels associated to the hash such as "nointro" or "redump"
 *    string     PatchUrl    link to RAPatches URL for the hash
 */

use App\Models\Game;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'i' => ['required', 'min:1'],
]);

$game = Game::find($input['i']);

if (!$game) {
    return response()->json(['Results' => []], 404);
}

$mappedHashes = $game->compatibleHashes->map(function ($hash) {
    return [
        'Name' => $hash->name,
        'MD5' => $hash->md5,
        'Labels' => array_map(fn ($label) => $label, array_filter(explode(',', $hash->labels))),
        'PatchUrl' => $hash->patch_url,
    ];
})->toArray();

return [
    'Results' => $mappedHashes,
];
