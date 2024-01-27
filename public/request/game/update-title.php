<?php

use App\Platform\Models\Game;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'title' => 'required|string|max:80',
]);

$gameId = (int) $input['game'];

if (modifyGameTitle($user, $gameId, $input['title'])) {
    $foundGame = Game::find($gameId);
    if ($foundGame) {
        Cache::forget('connect:gameslist:0');
        Cache::forget('connect:gameslist:' . $foundGame->ConsoleID);
        Cache::forget('connect:officialgameslist:0');
        Cache::forget('connect:officialgameslist:' . $foundGame->ConsoleID);
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
