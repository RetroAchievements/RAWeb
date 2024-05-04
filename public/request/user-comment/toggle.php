<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'active' => 'sometimes|boolean',
]);

$value = (int) ($input['active'] ?? false);

$user->UserWallActive = $value;
$user->save();

return back()->with('success', __('legacy.success.change'));
