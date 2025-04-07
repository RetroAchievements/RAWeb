<?php

use App\Community\Actions\AddGameToListAction;
use App\Community\Actions\RemoveGameFromListAction;
use App\Community\Enums\UserGameListType;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'type' => ['required', 'string', Rule::in(array_column(UserGameListType::cases(), 'value'))],
]);

$gameId = (int) $input['game'];
$game = Game::findOrFail($gameId);

$typeString = (string) $input['type'];
$typeEnum = UserGameListType::from($typeString);

$command = '';

/** @var User $user */
$user = User::findOrFail($userDetails['ID']);
if ($user->gameListEntries($typeEnum)->where('GameID', $gameId)->exists()) {
    $action = new RemoveGameFromListAction();
    $success = $action->execute($user, $game, $typeEnum);
    $command = 'removed';
} else {
    $action = new AddGameToListAction();
    $success = $action->execute($user, $game, $typeEnum);
    $command = 'added';
}

if ($success) {
    return response()->json(['message' => __("user-game-list.{$typeString}.{$command}")]);
}

abort(400);
