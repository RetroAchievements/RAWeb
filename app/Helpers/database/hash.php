<?php

use App\Models\GameHash;

function getMD5List(int $consoleId): array
{
    $query = GameHash::compatible()
        ->select('game_hashes.md5', 'game_hashes.game_id')
        ->leftJoin('GameData as gd', 'gd.ID', '=', 'game_hashes.game_id')
        ->when($consoleId > 0, function ($q) use ($consoleId) {
            $q->where('gd.ConsoleID', $consoleId);
        })
        ->orderBy('game_hashes.game_id', 'asc');

    return $query->pluck('game_id', 'md5')->toArray();
}

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
