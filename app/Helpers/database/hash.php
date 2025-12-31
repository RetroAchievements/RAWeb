<?php

use App\Models\GameHash;

/**
 * Gets the list of hashes and hash information from the database using the input offset and count.
 */
function getHashList(int $offset, int $count, ?string $searchedHash): array
{
    $query =
        GameHash::with(['user', 'game' => function ($query) {
            $query->select('id', 'title', 'image_icon_asset_path', 'system_id');
        }, 'game.system' => function ($query) {
            $query->select('id', 'name');
        }])
        ->select('md5', 'game_id', 'user_id', 'created_at');

    if (!empty($searchedHash)) {
        $query->where('md5', $searchedHash);
        $offset = 0;
        $count = 1;
    }

    $hashList = $query->orderBy('created_at', 'desc')
        ->offset($offset)
        ->limit($count)
        ->get();

    return $hashList->map(function ($hash) {
        return [
            'Hash' => $hash->md5,
            'GameID' => $hash->game_id,
            'User' => $hash->user ? $hash->user->username : null,
            'DateAdded' => $hash->created_at,
            'GameTitle' => $hash->game ? $hash->game->title : null,
            'GameIcon' => $hash->game ? $hash->game->image_icon_asset_path : null,
            'ConsoleName' => $hash->game->system ? $hash->game->system->name : null,
        ];
    })
        ->toArray();
}
