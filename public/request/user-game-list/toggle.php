<?php

use App\Community\Actions\AddGameToListAction;
use App\Community\Actions\RemoveGameFromListAction;
use App\Community\Enums\UserGameListType;
use App\Enums\Permissions;
use App\Models\Game;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'type' => ['required', 'string', Rule::in(UserGameListType::cases())],
]);

$gameId = (int) $input['game'];
$game = Game::findOrFail($gameId);

$type = (string) $input['type'];
$command = '';

if ($user->gameListEntries($type)->where('GameID', $gameId)->exists()) {
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
