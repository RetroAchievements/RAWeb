<?php

use App\Platform\Actions\UpdateGameWeightedPoints as UpdateGameWeightedPointsAction;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
]);

/** @var UpdateGameWeightedPointsAction $updateGameWeightedPointsAction */
$updateGameWeightedPointsAction = app()->make(UpdateGameWeightedPointsAction::class);
if ($updateGameWeightedPointsAction->run((int) $input['game'])) {
    return back()->with('success', __('legacy.success.points_recalculate'));
}

return back()->withErrors(__('legacy.error.error'));
