<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Platform\Actions\UpdateGameWeightedPoints as UpdateGameWeightedPointsAction;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
]);

/** @var UpdateGameWeightedPointsAction $updateGameWeightedPointsAction */
$updateGameWeightedPointsAction = app()->make(UpdateGameWeightedPointsAction::class);
if ($updateGameWeightedPointsAction->run((int) $input['game'])) {
    return back()->with('success', __('legacy.success.points_recalculate'));
}

return back()->withErrors(__('legacy.error.error'));
