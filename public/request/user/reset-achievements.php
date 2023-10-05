<?php

use App\Platform\Actions\ResetPlayerProgress;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$user = request()->user();
if ($user === null) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required_without:achievement|integer|exists:GameData,ID',
    'achievement' => 'required_without:game|integer|exists:Achievements,ID',
]);

$action = app()->make(ResetPlayerProgress::class);

if (!empty($input['achievement'])) {
    $action->execute($user, achievementID: (int) $input['achievement']);

    return response()->json(['message' => __('legacy.success.reset')]);
}

if (!empty($input['game'])) {
    $action->execute($user, gameID: (int) $input['game']);

    return response()->json(['message' => __('legacy.success.reset')]);
}

abort(400);
