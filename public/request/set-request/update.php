<?php

use App\Community\Actions\AddGameToListAction;
use App\Community\Actions\RemoveGameFromListAction;
use App\Community\Enums\UserGameListType;
use App\Platform\Models\Game;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
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
