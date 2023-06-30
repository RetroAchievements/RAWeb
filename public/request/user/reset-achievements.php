<?php

use App\Platform\Actions\ResetPlayerAchievementAction;
use App\Site\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required_without:achievement|integer|exists:GameData,ID',
    'achievement' => 'required_without:game|integer|exists:Achievements,ID',
]);

$userModel = User::firstWhere('User', $user);
$action = new ResetPlayerAchievementAction();

if (!empty($input['achievement'])) {
    $action->execute($userModel, achievementID: (int) $input['achievement']);

    return response()->json(['message' => __('legacy.success.reset')]);
}

if (!empty($input['game'])) {
    $action->execute($userModel, gameID: (int) $input['game']);

    return response()->json(['message' => __('legacy.success.reset')]);
}

abort(400);
