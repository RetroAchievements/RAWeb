<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Actions\AddGameToListAction;
use LegacyApp\Community\Actions\RemoveGameFromListAction;
use LegacyApp\Community\Enums\UserGameListType;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
]);

$gameID = (int) $input['game'];
$game = Game::findOrFail($gameID);

/** @var User $user */
$user = User::findOrFail($userDetails['ID']);
if ($user->gameList(UserGameListType::SetRequest)->where('GameID', $gameID)->exists()) {
    $action = new RemoveGameFromListAction();
    $success = $action->execute($user, $game, UserGameListType::SetRequest);
} else {
    $action = new AddGameToListAction();
    $success = $action->execute($user, $game, UserGameListType::SetRequest);
}

if ($success) {
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
