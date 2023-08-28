<?php

use App\Community\Actions\AddGameToListAction;
use App\Community\Actions\RemoveGameFromListAction;
use App\Community\Enums\UserGameListType;
use App\Platform\Models\Game;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
   abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'type' => ['required', 'string', Rule::in(UserGameListType::cases())],
]);

$gameID = (int) $input['game'];
$game = Game::findOrFail($gameID);

$type = (string) $input['type'];
$command = '';

/** @var User $user */
$user = User::findOrFail($userDetails['ID']);
if ($user->gameList($type)->where('GameID', $gameID)->exists()) {
    $action = new RemoveGameFromListAction();
    $success = $action->execute($user, $game, $type);
    $command = 'removed';
} else {
    $action = new AddGameToListAction();
    $success = $action->execute($user, $game, $type);
    $command = 'added';
}

if ($success) {
    return response()->json(['message' => __("user-game-list.$type.$command")]);
}

abort(400);
