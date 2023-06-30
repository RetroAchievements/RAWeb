<?php

use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'hash' => 'required|string',
    'name' => 'required|string',
    'labels' => 'required|string',
]);

if (!updateHashDetails($user, (int) $input['game'], $input['hash'], $input['name'], $input['labels'])) {
    abort(400);
}

return response()->json(['message' => __('legacy.success.update')]);
