<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'relations' => 'required|array',
]);

modifyGameAlternatives($user->username, (int) $input['game'], toRemove: $input['relations']);

return back()->with('success', __('legacy.success.ok'));
