<?php

use App\Models\GameHash;

/**
 * Gets the list of hashes and hash information from the database using the input offset and count.
 */
function getHashList(int $offset, int $count, ?string $searchedHash): array
{
    $query =
        GameHash::with(['user', 'game' => function ($query) {
            $query->select('ID', 'Title', 'ImageIcon', 'ConsoleID');
        }, 'game.system' => function ($query) {
            $query->select('ID', 'Name');
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
            'User' => $hash->user ? $hash->user->User : null,
            'DateAdded' => $hash->created_at,
            'GameTitle' => $hash->game ? $hash->game->Title : null,
            'GameIcon' => $hash->game ? $hash->game->ImageIcon : null,
            'ConsoleName' => $hash->game->system ? $hash->game->system->Name : null,
        ];
    })
        ->toArray();
}
