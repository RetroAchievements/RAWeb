<?php

use App\Site\Models\User;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$dataOut = User::firstWhere('User', $user)
    ->games()
    ->with('system')
    ->where('player_games.achievements_unlocked', '>', 0)
    ->orderBy('Title')
    ->select(['GameData.ID', 'Title', 'ConsoleID', 'achievements_published', 'player_games.achievements_unlocked'])
    ->get()
    ->map(function ($game) {
        return [
            'ID' => $game->ID,
            'GameTitle' => $game->Title,
            'ConsoleName' => $game->system->Name,
            'NumAwarded' => $game->achievements_unlocked,
            'NumPossible' => $game->achievements_published,
        ];
    });

return response()->json($dataOut);
