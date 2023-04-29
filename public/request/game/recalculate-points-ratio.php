<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Platform\Actions\UpdateGameWeightedPoints as UpdateGameWeightedPointsAction;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'gameId' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'parentGameId' => 'nullable|integer|exists:mysql_legacy.GameData,ID',
]);

/** @var UpdateGameWeightedPointsAction $updateGameWeightedPointsAction */
$updateGameWeightedPointsAction = app()->make(UpdateGameWeightedPointsAction::class);

$gameId = (int) $input['gameId'];
$parentGameId = isset($input['parentGameId']) ? (int) $input['parentGameId'] : null;

if ($updateGameWeightedPointsAction->run($gameId, $parentGameId)) {
    return back()->with('success', __('legacy.success.points_recalculate'));
}

return back()->withErrors(__('legacy.error.error'));
