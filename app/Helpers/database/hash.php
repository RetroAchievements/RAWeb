<?php

use App\Models\Game;
use App\Models\GameHash;

function getMD5List(int $consoleId): array
{
    $query = GameHash::query()
        ->select('game_hashes.md5', 'game_hashes.game_id')
        ->leftJoin('GameData as gd', 'gd.ID', '=', 'game_hashes.game_id')
        ->when($consoleId > 0, function ($q) use ($consoleId) {
            $q->where('gd.ConsoleID', $consoleId);
        })
        ->orderBy('game_hashes.game_id', 'asc');

    return $query->pluck('game_id', 'md5')->toArray();
}

function getHashListByGameID(int $gameId): array
{
    if ($gameId < 1) {
        return [];
    }

    $game = Game::find($gameId);
    if (!$game) {
        return [];
    }

    $hashes = $game->hashes()
        ->with('user')
        ->select('md5', 'name', 'labels', 'user_id')
        ->orderBy('name')
        ->orderBy('md5')
        ->get()
        ->map(function ($hash) {
            return [
                'Hash' => $hash->md5,
                'Name' => $hash->name,
                'Labels' => $hash->labels,
                'User' => $hash->user ? $hash->user->User : null,
            ];
        });

    return $hashes->toArray();
}

function getGameIDFromMD5(string $md5): int
{
    $gameHash = GameHash::where('md5', $md5)->first(['game_id']);

    return $gameHash ? $gameHash->game_id : 0;
}

/**
 * Gets the list of hashes and hash information from the databased using the input offset and count.
 */
function getHashList(int $offset, int $count, ?string $searchedHash): array
{
    $query = GameHash::with(['user', 'game' => function ($query) {
                $query->select('ID', 'Title', 'ImageIcon', 'ConsoleID');
            },
            'game.system' => function ($query) {
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
