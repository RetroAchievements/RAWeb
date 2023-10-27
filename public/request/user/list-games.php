<?php

use App\Site\Models\User;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$userModel = User::firstWhere('User', $user);
$games = $userModel
    ->games()
    ->with('system')
    ->where('player_games.achievements_unlocked', '>', 0)
    ->orderBy('Title')
    ->select(['GameData.ID', 'Title', 'ConsoleID', 'achievements_published', 'player_games.achievements_unlocked'])
    ->get();

$dataOut = [];
foreach ($games as $game) {
    $dataOut[] = [
        'ID' => $game->ID,
        'GameTitle' => $game->Title,
        'ConsoleName' => $game->system->Name,
        'NumAwarded' => $game->achievements_unlocked,
        'NumPossible' => $game->achievements_published,
    ];
}

return $dataOut;
