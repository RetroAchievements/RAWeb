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
]);

if (!removeHash($user, (int) $input['game'], $input['hash'])) {
    abort(400);
}

return response()->json(['message' => __('legacy.success.delete')]);
